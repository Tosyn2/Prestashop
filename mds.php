<?php

// Avoid direct access to the file
if (!defined('_PS_VERSION_')) {
	exit;
}

define('_MDS_DIR_', __DIR__);

spl_autoload_register(function($class) {
	$classParts = explode('\\', $class);
	$vendor = array_shift($classParts);
	if ($vendor === 'Mds') {
		require _MDS_DIR_ .'/'. implode('/', $classParts) .'.php';
	}
}, true);

class Mds extends CarrierModule
{
	public $id_carrier;
	private $_html = '';

	/**
	 * @type array
	 */
	private $_postErrors = array();

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

		if (self::isInstalled($this->name)) {
			// Getting carrier list
			global $cookie;
			$carriers = Carrier::getCarriers(
				$cookie->id_lang,
				true,
				false,
				false,
				null,
				PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE
			);

			// Saving id carrier list
			$id_carrier_list = array();
			foreach ($carriers as $carrier) {
				$id_carrier_list[] .= $carrier['id_carrier'];
			}
		}

		$settings['mds_user'] = Configuration::get('MDS_EMAIL');
		$settings['mds_pass'] = Configuration::get('MDS_PASSWORD');

		$this->mdsService = \Mds\MdsColliveryService::getInstance($settings);
		$this->collivery = $this->mdsService->returnColliveryClass();
		$this->cache = $this->mdsService->returnCacheClass();
	}

	/**
	 * Prestashop Function to Install
	 */
	public function install()
	{
		if (version_compare(PHP_VERSION, '5.3.0') < 0) {
			$warnings[] = '\'Your PHP version is not able to run this plugin, update to the latest version before installing this plugin.\'';
		}

		if (!extension_loaded('soap')) {
			$warnings[] = '\'' . $this->l('Class Soap') . '\', ';
		}

		if (!empty($warnings)) {
			foreach ($warnings as $warning) {
				echo "<p><strong>$warning</strong></p>";
			}
			return false;
		}

		try {
			$installer = new Mds\Prestashop\Installer\Install();
			$installer->install();
		} catch (\Mds\Prestashop\Exceptions\UpdatingConfigurationException $e) {
			return false;
		}

		if (!parent::install()) {
			return false;
		}

		if ($this->registerHooks($installer->getHooks()) === false) {
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
			if (!$this->registerHook($hook)) return false;
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
			$installer = new Mds\Prestashop\Installer\Uninstall();
			$installer->uninstall();
		} catch (\Mds\Prestashop\Exceptions\UpdatingConfigurationException $e) {
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
			if (!$this->registerHook($hook)) return false;
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
		if (!empty($_POST) AND Tools::isSubmit('submitSave')) {

			$this->_postValidation();
			if (!sizeof($this->_postErrors)) {
				$this->_postProcess();
			} else {
				$errors = $this->_postErrors;
			}
		}

		$displayName = $this->displayName;
		$formUrl ='index.php?tab='. Tools::getValue('tab')
			.'&configure='. Tools::getValue('configure')
			.'&token='. Tools::getValue('token')
			.'&tab_module='. Tools::getValue('tab_module')
			.'&module_name='. Tools::getValue('module_name')
			.'&id_tab=1&section=general';

		$inputs = array(
			'MDS_SERVICE_SURCHARGE_1' => array('name' => 'Overnight before 10:00', 'type' => 'text'),
			'MDS_SERVICE_SURCHARGE_2' => array('name' => 'Overnight before 16:00', 'type' => 'text'),
			'MDS_SERVICE_SURCHARGE_5' => array('name' => 'Road Freight Express', 'type' => 'text'),
			'MDS_SERVICE_SURCHARGE_3' => array('name' => 'Road Freight', 'type' => 'text'),
			'MDS_EMAIL'               => array('name' => 'MDS Account Email', 'type' => 'text'),
			'MDS_PASSWORD'            => array('name' => 'Password', 'type' => 'password'),
			'MDS_RISK'                => array('name' => 'Risk Cover', 'type' => 'checkbox'),
		);

		$configured = true;

		return \Mds\Prestashop\Helpers\View::make('settings', compact('inputs', 'displayName', 'formUrl', 'configured', 'errors'));
	}

	private function _postValidation()
	{
		// Check configuration values
		if (Tools::getValue('MDS_SERVICE_SURCHARGE_1') == '' &&
			Tools::getValue('MDS_SERVICE_SURCHARGE_2') == '' &&
			Tools::getValue('MDS_SERVICE_SURCHARGE_3') == '' &&
			Tools::getValue('MDS_SERVICE_SURCHARGE_5') == '' ||
			Tools::getValue('MDS_EMAIL') == '' ||
			Tools::getValue('MDS_PASSWORD') == ''
		) {
			$this->_postErrors[] = $this->l('You have to configure at least one carrier AND input your MDS account login details');
		}
	}

	/*
	** Saving config settings. First checks if login details are changed and are correct then saves, else just saves
	**
	*/
	private function _postProcess()
	{
		if ($this->settings['mds_user'] != Tools::getValue('MDS_EMAIL') || $this->settings['mds_pass'] != Tools::getValue('MDS_PASSWORD')) {

			$settings['mds_user'] = Tools::getValue('MDS_EMAIL');
			$settings['mds_pass'] = Tools::getValue('MDS_PASSWORD');

			$this->mdsService = \Mds\MdsColliveryService::getInstance($settings);
			$this->collivery = $this->mdsService->returnColliveryClass($settings);
			if ($this->collivery->isAuthenticated()) {
				if ($this->updateSettings(Tools::getAllValues())) {
					$this->_html .= $this->displayConfirmation($this->l('Settings updated'));
				} else {
					throw new Exception(sprintf(Tools::displayError('Unable to update Settings')));
				}
			} else {
				$this->_html .= (sprintf(Tools::displayError('MDS Collivery account details incorrect.')));
			}
		} else {
			if ($this->updateSettings(Tools::getAllValues())) {
				$this->_html .= $this->displayConfirmation($this->l('Settings updated'));
			}
		}
	}

	/*
	 * Hook update carrier
	 *
	 */
	private function updateSettings(array $array)
	{
		$success = true;

		if (Configuration::updateValue('MDS_SERVICE_SURCHARGE_1', Tools::getValue('MDS_SERVICE_SURCHARGE_1')) &&
			Configuration::updateValue('MDS_SERVICE_SURCHARGE_1', Tools::getValue('MDS_SERVICE_SURCHARGE_1')) &&
			Configuration::updateValue('MDS_SERVICE_SURCHARGE_2', Tools::getValue('MDS_SERVICE_SURCHARGE_2')) &&
			Configuration::updateValue('MDS_SERVICE_SURCHARGE_3', Tools::getValue('MDS_SERVICE_SURCHARGE_3')) &&
			Configuration::updateValue('MDS_SERVICE_SURCHARGE_5', Tools::getValue('MDS_SERVICE_SURCHARGE_5')) &&
			Configuration::updateValue('MDS_EMAIL', Tools::getValue('MDS_EMAIL')) &&
			Configuration::updateValue('MDS_PASSWORD', Tools::getValue('MDS_PASSWORD')) &&
			Configuration::updateValue('MDS_RISK', Tools::getValue('MDS_RISK'))
		) {
			return $success;
		} else {
			$success = false;
			return $success;
		}

	}

	function addColliveryAddressTo($params)
	{
		$addAddress1 = $params['cart']->id_address_delivery;
		$sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'address
		WHERE id_address = \'' . $addAddress1 . '\' AND deleted = 0';
		$addressRow = Db::getInstance()->getRow($sql);

		$town_id = $addressRow['id_state'];
		$sql = 'SELECT `id_mds` FROM `' . _DB_PREFIX_ . 'state`
		WHERE `id_state` = "' . $town_id . '" ';
		$mds_town_id = Db::getInstance()->getValue($sql);

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
		$colliveryParams['email'] = Db::getInstance()->getValue($sql);

		try {
			return $this->mdsService->addColliveryAddress($colliveryParams);
		} catch (Exception $e) {
			die($e->getMessage());
		}

	}

	function getDefaultColliveryAddressFrom($params)
	{
		$colliveryAddressesFrom = $this->mdsService->returnDefaultAddress();

		foreach ($colliveryAddressesFrom['contacts'] as $colliveryAddressFrom) {
		}
		return $colliveryAddressFrom;
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
					'width' => $colliveryProduct['width'],
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
		$addressRow = Db::getInstance()->getRow($sql);

		$town_id = $addressRow['id_state'];
		$sql = 'SELECT `id_mds` FROM `' . _DB_PREFIX_ . 'state`
		WHERE `id_state` = "' . $town_id . '" ';
		$mds_town_id = Db::getInstance()->getValue($sql);

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
					'width' => $colliveryProduct['width'],
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
			$service = $this->getServiceFromCarrierId($this->id_carrier);
			$orderParams['service'] = $service;

			if (Configuration::get('MDS_RISK') == 1) $orderParams['cover'] = 1;

			$colliveryPriceOptions = $this->collivery->getPrice($orderParams);
			$colliveryPrice = $colliveryPriceOptions['price']['inc_vat'];

			$price = Configuration::get('MDS_SERVICE_SURCHARGE_'. $service) + $colliveryPrice;

			return $shipping_cost + $price;
		} catch (InvalidArgumentException $e) {
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
			Db::getInstance()->execute($sql);

			foreach ($towns as $index => $town) {
				$sql = 'INSERT INTO ' . _DB_PREFIX_ . 'state (id_country,id_zone,name,iso_code,id_mds,tax_behavior,active)
				VALUES
				(30,4,\'' . $town . '\',\'ZA\',' . $index . ',0,1)';
				Db::getInstance()->execute($sql);
			}

		}

	}

	public function hookActionOrderStatusPostUpdate($params)
	{
		if ($params['newOrderStatus']->name == 'Shipped') {
			try {
				$orderParams = $this->buildColliveryDataArray($params);
				if (Configuration::get('MDS_RISK') == 1) $orderParams['cover'] = 1;

				return $this->mdsService->addCollivery($orderParams, true);
			} catch (InvalidArgumentException $e) {
				return false;
			}
		}
	}

	public function hookDisplayFooter($params)
	{
		$idAddress = (int)$this->context->cart->id_address_delivery;

		$sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'address` WHERE `id_address` = ' . $idAddress;
		$address = Db::getInstance()->getRow($sql);

		$suburb = $address['city'];
		$suburbs = $this->collivery->getSuburbs('');

		$locationType = $address['address2'];
		$locationTypes = $this->collivery->getLocationTypes();

		$this->context->controller->addJS(($this->_path) . 'helper.js');

		return \Mds\Prestashop\Helpers\View::make('footer', compact('suburbs', 'suburb', 'locationTypes', 'locationType'));
	}

	protected function getServiceFromCarrierId($carrierId)
	{
		$serviceMappings = [
			Configuration::get('MDS_SERVICE_CARRIER_ID_1') => 1,
			Configuration::get('MDS_SERVICE_CARRIER_ID_2') => 2,
			Configuration::get('MDS_SERVICE_CARRIER_ID_3') => 3,
			Configuration::get('MDS_SERVICE_CARRIER_ID_5') => 5,
		];

		if (!array_key_exists($carrierId, $serviceMappings)) {
			throw new InvalidArgumentException;
		}

		return $serviceMappings[$carrierId];
	}

}






