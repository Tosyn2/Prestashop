<?php

// Avoid direct access to the file
if (!defined('_PS_VERSION_')) {
	exit;
}

define('_MDS_DIR_', __DIR__);

include('autoload.php');

class Mds extends CarrierModule {

	public $id_carrier;
	protected $db;

	public function __construct()
	{
		$this->name = 'mds';
		$this->tab = 'shipping_logistics';
		$this->version = '1.0';
		$this->author = 'MDS Tech (Pty) Ltd';
		$this->limited_countries = array();

		parent::__construct();

		$this->displayName = $this->l('MDS Collivery');
		$this->description = $this->l('Offer your customers, different delivery methods that you want');

		$settings = array();

		$this->mdsService = \Mds\MdsColliveryService::getInstance($settings);
		$this->collivery = Mds_ColliveryApi::getInstance();
		$this->db = Db::getInstance();
	}

	/**
	 * Prestashop Function to Install
	 */
	public function install()
	{
		$warnings = $this->checkSystemRequirements();

		if (!empty($warnings)) {
			foreach ($warnings as $warning) {
				echo "<p><strong>$warning</strong></p>";
			}

			return false;
		}

		try {
			$installer = new Mds_Install($this->db);
			$installer->install();
			if (!parent::install()) {
				return false;
			}
			$this->registerHooks($installer->getHooks());
		} catch (Mds_ColliveryException $e) {
			return false;
		}

		return true;
	}

	/**
	 * @param $hooks
	 *
	 * @return bool
	 * @throws \PrestaShopException
	 */
	private function registerHooks($hooks)
	{
		foreach ($hooks as $hook) {
			if (!$this->registerHook($hook)) {
				throw new Mds_UnableToRegisterHook();
			}
		}
	}

	/**
	 * Prestashop Function to Uninstall
	 *
	 * @return bool
	 */
	public function uninstall()
	{
		if (!parent::uninstall()) {
			return false;
		}

		try {
			$installer = new Mds_Uninstall($this->db);
			$installer->uninstall();
		} catch (Mds_UnableToUpdateConfiguration $e) {
			return false;
		}

		if ($this->unregisterHooks($installer->getHooks()) === false) {
			return false;
		}

		return true;

	}

	/**
	 * @param $hooks
	 *
	 * @return bool
	 * @throws \PrestaShopException
	 */
	private function unregisterHooks($hooks)
	{
		foreach ($hooks as $hook) {
			if (!$this->registerHook($hook)) {
				return false;
			}
		}
	}

	/**
	 * Prestashop Function to get Settings Form
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function getContent()
	{
		$displayName = $this->displayName;

		$formUrl = 'index.php?tab=' . Tools::getValue('tab')
			. '&configure=' . Tools::getValue('configure')
			. '&token=' . Tools::getValue('token')
			. '&tab_module=' . Tools::getValue('tab_module')
			. '&module_name=' . Tools::getValue('module_name')
			. '&id_tab=1&section=general';

		$errors = array();

		$settingsService = new Mds_SettingsService();

		if (!empty($_POST) AND Tools::isSubmit('submitSave')) {
			$errors = $settingsService->store($_POST);
		}

		$surcharges = $settingsService->getSurchargesInfo();
		$email = $settingsService->getColliveryEmail();
		$riskCover = $settingsService->hasRiskCover();

		try {
			$settingsService->testCurrentCredentials();
		} catch (Mds_InvalidCredentials $e) {
			$errors[] = 'Current Collivery credentials are invalid, plugin not operational';
		}

		$errors = empty($errors) ? '' : $this->displayError($errors);

		return Mds_View::make(
			'settings',
			compact('displayName', 'formUrl', 'errors', 'surcharges', 'email', 'riskCover')
		);
	}

	/**
	 * Hook update carrier
	 *
	 * @param $params
	 *
	 * @return array
	 */
	function addColliveryAddressTo($params)
	{
		$addAddress1 = $params['cart']->id_address_delivery;
		$sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'address
		WHERE id_address = \'' . $addAddress1 . '\' AND deleted = 0';
		$addressRow = $this->db->getRow($sql);

		$town_id = $addressRow['id_state'];
		$sql = 'SELECT `id_mds` FROM `' . _DB_PREFIX_ . 'state`
		WHERE `id_state` = "' . $town_id . '" ';
		$mds_town_id = $this->db->getValue($sql);

		$colliveryParams['company_name'] = $addressRow['company'];
		$colliveryParams['building'] = '';
		$colliveryParams['street'] = $addressRow['address1'];
		$colliveryParams['location_type'] = $addressRow['address2'];
		$colliveryParams['suburb'] = $addressRow['city'];
		$colliveryParams['town'] = $mds_town_id;
		$colliveryParams['zip_code'] = $addressRow['postcode'];
		$colliveryParams['full_name'] = $addressRow['firstname'] . " " . $addressRow['lastname'];
		$colliveryParams['phone'] = $addressRow['phone'];
		$colliveryParams['cellphone'] = $addressRow['phone_mobile'];
		$colliveryParams['custom_id'] = $addressRow['id_customer'];

		$custId = $colliveryParams['custom_id'];
		$sql = 'SELECT email FROM ' . _DB_PREFIX_ . 'customer
		WHERE id_customer = \'' . $custId . '\'';
		$colliveryParams['email'] = $this->db->getValue($sql);

		try {
			return $this->mdsService->addColliveryAddress($colliveryParams);
		} catch (Exception $e) {
			die($e->getMessage());
		}
	}

	function getDefaultColliveryAddressFrom($params)
	{
		$colliveryAddressesFrom = $this->mdsService->returnDefaultAddress();

		return array_pop($colliveryAddressesFrom['contacts']);
	}

	public function buildColliveryDataArray($params)
	{
		$service = $this->getServiceFromCarrierId($params['cart']->id_carrier);

		$colliveryAddressTo = $this->addColliveryAddressTo($params);
		$colliveryAddressFrom = $this->getDefaultColliveryAddressFrom($params);

		$cart = $params['cart'];

		$colliveryParams['service'] = $service;
		$colliveryParams['collivery_to'] = $colliveryAddressTo['address_id'];
		$colliveryParams['contact_to'] = $colliveryAddressTo['contact_id'];
		$colliveryParams['collivery_from'] = $colliveryAddressFrom['address_id'];
		$colliveryParams['contact_from'] = $colliveryAddressFrom['contact_id'];
		$colliveryParams['collivery_type'] = '2';

		foreach ($cart->getProducts() as $colliveryProduct) {
			for ($i = 0; $i < $colliveryProduct['cart_quantity']; $i++) {
				$colliveryParams['parcels'][] = array(
					'weight' => $colliveryProduct['weight'],
					'height' => $colliveryProduct['height'],
					'width'  => $colliveryProduct['width'],
					'length' => $colliveryProduct['depth']
				);
			}
		}

		return $colliveryParams;
	}

	public function buildColliveryGetPriceArray($params)
	{
		$addAddress1 = $params->id_address_delivery;
		$sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'address
		WHERE id_address = \'' . $addAddress1 . '\' AND deleted = 0';
		$addressRow = $this->db->getRow($sql);

		$town_id = $addressRow['id_state'];
		$sql = 'SELECT `id_mds` FROM `' . _DB_PREFIX_ . 'state`
		WHERE `id_state` = "' . $town_id . '" ';
		$mds_town_id = $this->db->getValue($sql);

		$colliveryAddressFrom = $this->getDefaultColliveryAddressFrom($params);

		$cartProducts = $params->getProducts();

		$colliveryGetPriceArray = Array();
		$colliveryGetPriceArray['to_town_id'] = $mds_town_id;
		$colliveryGetPriceArray['collivery_from'] = $colliveryAddressFrom['address_id'];

		foreach ($cartProducts as $colliveryProduct) {
			for ($i = 0; $i < $colliveryProduct['cart_quantity']; $i++) {
				$colliveryGetPriceArray['parcels'][] = array(
					'weight' => $colliveryProduct['weight'],
					'height' => $colliveryProduct['height'],
					'width'  => $colliveryProduct['width'],
					'length' => $colliveryProduct['depth']
				);
			}
		}

		return $colliveryGetPriceArray;
	}

	public function getCartProducts($params)
	{
		return $params->getProducts();
	}

	public function getOrderShippingCost($params, $shipping_cost)
	{
		return false;
	}

	public function getPackageShippingCost($params, $shipping_cost, $products)
	{
		try {
			$orderParams = $this->buildColliveryGetPriceArray($params);
			$serviceId = $this->getServiceFromCarrierId($this->id_carrier);
			$orderParams['service'] = $serviceId;

			if (Mds_RiskCover::hasCover()) {
				$orderParams['cover'] = 1;
			}

			$colliveryPriceOptions = $this->collivery->getPrice($orderParams);
			$colliveryPrice = $colliveryPriceOptions['price']['inc_vat'];

			$price = Mds_Surcharge::getServiceSurcharge($serviceId) + $colliveryPrice;

			return $shipping_cost + $price;
		} catch (Mds_InvalidData $e) {
			return false;
		}
	}

	public function getOrderShippingCostExternal($params)
	{
		return false;
	}

	public function hookDisplayShoppingCart()
	{
		$towns = $this->collivery->getTowns();

		$sql = 'SELECT count(`id_mds`) FROM  `' . _DB_PREFIX_ . 'state` WHERE  `id_country` = 30 AND `active` = 1';
		$numberOfTowns = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);

		if ($numberOfTowns != count($towns)) {
			$sql = 'UPDATE `' . _DB_PREFIX_ . 'state` SET `active` = 0 where `id_country` = 30';
			$this->db->execute($sql);

			foreach ($towns as $index => $town) {
				$sql = 'INSERT INTO ' . _DB_PREFIX_ . 'state (id_country,id_zone,name,iso_code,id_mds,tax_behavior,active)
				VALUES
				(30,4,\'' . $town . '\',\'ZA\',' . $index . ',0,1)';
				$this->db->execute($sql);
			}
		}
	}

	public function hookActionOrderStatusPostUpdate($params)
	{
		if ($params['newOrderStatus']->name == 'Shipped') {
			try {
				$orderParams = $this->buildColliveryDataArray($params);
				if (Mds_RiskCover::hasCover()) {
					$orderParams['cover'] = 1;
				}

				return $this->mdsService->addCollivery($orderParams, true);
			} catch (Mds_InvalidData $e) {
				return false;
			}
		}
	}

	public function hookDisplayFooter($params)
	{
		$idAddress = (int) $this->context->cart->id_address_delivery;

		$sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'address` WHERE `id_address` = ' . $idAddress;
		$address = $this->db->getRow($sql);

		$suburb = $address['city'];
		$suburbs = $this->collivery->getSuburbs('');

		$locationType = $address['address2'];
		$locationTypes = $this->collivery->getLocationTypes();

		$this->context->controller->addJS(($this->_path) . 'helper.js');

		return Mds_View::make(
			'footer',
			compact('suburbs', 'suburb', 'locationTypes', 'locationType')
		);
	}

	protected function getServiceFromCarrierId($carrierId)
	{
		return Mds_Service::getServiceIdFromCarrierId($carrierId);
	}

	/**
	 * @return array
	 */
	private function checkSystemRequirements()
	{
		$warnings = [];
		if (version_compare(PHP_VERSION, '5.3.0') < 0) {
			$warnings[] = 'Your PHP version is not able to run this plugin, update to the latest version before installing this plugin.';
		}

		if (!extension_loaded('soap')) {
			$warnings[] = 'Could not find PHP SOAP, please make sure its enabled before installing.';
		}

		return $warnings;
	}

}
