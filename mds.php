<?php

// Avoid direct access to the file
if ( ! defined('_PS_VERSION_')) {
	exit;
}

define('_MDS_DIR_', __DIR__);

include('autoload.php');

class Mds extends CarrierModule {

	public $id_carrier;
	protected $db;

	protected $hooks = array(
		'displayFooter',
		'actionOrderStatusPostUpdate',
		'displayShoppingCart',
		'displayAdminOrder',
		'orderConfirmation'
	);

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
		try {
			$installer = new Mds_Install($this->db);
			$installer->install();
			if ( ! parent::install()) {
				return false;
			}
			$this->registerHooks();
		} catch (\Mds\Prestashop\Exceptions\UnmetSystemRequirements $e) {
			echo $this->displayError($e->getErrors());

			return false;
		} catch (\Mds\Prestashop\Exceptions\ColliveryException $e) {
			return false;
		}

		return true;
	}

	/**
	 * @throws \PrestaShopException
	 */
	private function registerHooks()
	{
		foreach ($this->hooks as $hook) {
			if ( ! $this->registerHook($hook)) {
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
		if ( ! parent::uninstall()) {
			return false;
		}

		try {
			$installer = new Mds_Uninstall($this->db);
			$installer->uninstall();
		} catch (\Mds\Prestashop\Exceptions\UnableToUpdateConfiguration $e) {
			return false;
		}

		if ($this->unregisterHooks() === false) {
			return false;
		}

		return true;

	}

	/**
	 * @return bool
	 * @throws \PrestaShopException
	 */
	private function unregisterHooks()
	{
		foreach ($this->hooks as $hook) {
			if ( ! $this->registerHook($hook)) {
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

		if ( ! empty($_POST) AND Tools::isSubmit('submitSave')) {
			try {
				$errors = $settingsService->store($_POST);
			} catch (\Mds\Prestashop\Exceptions\InvalidData $e) {
				$errors[] = $e->getMessage();
			}
		}

		$surcharges = $settingsService->getSurchargesInfo();
		$email = $settingsService->getColliveryEmail();
		$riskCover = Mds_RiskCover::hasCover();

		try {
			$settingsService->testCurrentCredentials();
		} catch (\Mds\Prestashop\Collivery\InvalidCredentials $e) {
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

		$addressString = $addressRow['address1'] . $addressRow['city'] . $mds_town_id . $addressRow['postcode'] . $addressRow['firstname'] . " " . $addressRow['lastname'];
		$hash = hash('md5', $addressString);
		$hash = substr($hash, 0, 15);

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
		$colliveryParams['custom_id'] = $addressRow['id_address'] . "|" . $hash;

		$sql = 'SELECT email FROM ' . _DB_PREFIX_ . 'customer
		WHERE id_customer = \'' . $params['cart']->id_customer . '\'';
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

			$surchargePerc = Mds_Surcharge::get($serviceId);
			$price = $colliveryPrice * (1 + ($surchargePerc / 100));

			return $shipping_cost + $price;
		} catch (\Mds\Prestashop\Exceptions\InvalidData $e) {
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
			} catch (\Mds\Prestashop\Exceptions\InvalidData $e) {
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

	public function hookOrderConfirmation($params)
	{
		$orderId = $params[ objOrder ]->id;
		$deliveryAddressId = $params[ objOrder ]->id_address_delivery;

		$carrierId = $params[ objOrder ]->id_carrier;
		$sql = 'SELECT `name` FROM `' . _DB_PREFIX_ . 'carrier` WHERE `id_carrier` = ' . $carrierId;
		$carrierName = $this->db->getValue($sql);
		$serviceId = $this->getServiceFromCarrierId($carrierId);

		$defaultAddressId = $this->collivery->getDefaultAddressId();
		$defaultAddress = $this->collivery->getAddress($defaultAddressId);

		$towns = $this->collivery->getTowns();
		$location_types = $this->collivery->getLocationTypes();

		$client_id = $defaultAddress['client_id'];

		$contacts = $this->collivery->getContacts($defaultAddressId);

		$contact = array_pop($contacts);

		$name = explode(" ", $contact['full_name']);

		$first_name = array_shift($name);
		$last_name = array_pop($name);

		$streetAddress = $defaultAddress['street'];

		$locationType = $location_types[ $defaultAddress['location_type'] ];
// 		echo $locationType;
// 		die( '<pre>'.print_r($location_types, true));

		$postCode = $defaultAddress['zip_code'];

		$city = $defaultAddress['suburb_name'];

		$phone = $contact['phone'];
		$mobile = $contact['phone_mobile'];

		$sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'state where `id_mds` = "' . $defaultAddress['town_id'] . '" AND `active` = 1';
		$state = $this->db->getRow($sql);

		$state_id = $state['id_state'];
		$state_name = $state['name'];

		$sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'address` WHERE `alias` = "Default MDS Collection Address"';
		$defaultMdsAddressPsId = $this->db->getRow($sql);

		$sql = 'SELECT `id_manufacturer` FROM ' . _DB_PREFIX_ . 'manufacturer where `name` = "MDS Collection Adresses" AND `active` = 1';
		$mdsManufacturerId = $this->db->getValue($sql);
		if ( ! $defaultMdsAddressPsId) {

			$sql = 'INSERT INTO ' . _DB_PREFIX_ . 'address (id_country,id_state,id_customer,id_manufacturer,id_supplier,id_warehouse,alias,company,lastname,firstname,address1,address2,postcode,city,other,phone,phone_mobile,active,deleted)
			VALUES
			(30, \'' . $state_id . '\',0,\'' .$mdsManufacturerId. '\',0,0,"Default MDS Collection Address","", \'' . $last_name . '\', \'' . $first_name . '\', \'' . $streetAddress . '\' , \'' . $locationType . '\' , \'' . $postCode . '\' , \'' . $city . '\',other, \'' . $phone . '\', \'' . $mobile . '\',1,0)';
			$this->db->execute($sql);

		} else {

			$addressStringPs = $defaultMdsAddressPsId['address1'] . $defaultMdsAddressPsId['city'] . $state_name . $defaultMdsAddressPsId['postcode'] . $defaultMdsAddressPsId['firstname'] . " " . $defaultMdsAddressPsId['lastname'];
			$hashPs = hash('md5', $addressStringPs);
			$hashPs = substr($hashPs, 0, 15);

			$addressStringMds = $defaultAddress['street'] . $defaultAddress['suburb_name'] . $defaultAddress['town_name'] . $defaultAddress['zip_code'] . $contact['full_name'];
			$hashMds = hash('md5', $addressStringMds);
			$hashMds = substr($hashMds, 0, 15);

			if ($hashMds != $hashPs) {

				$sql = 'UPDATE ' . _DB_PREFIX_ . 'address SET `id_state` = \'' . $state_id . '\', `lastname` = \'' . $last_name . '\' ,`firstname` =  \'' . $first_name . '\'  ,`address1` =  \'' . $defaultAddress['street'] . '\' , `address2` =  \'' . $locationType . '\',`postcode` =  \'' . $defaultAddress['zip_code'] . '\',`city` =  \'' . $defaultAddress['suburb_name'] . '\' ,`phone` =  \'' . $phone . '\',`phone_mobile` = \'' . $mobile . '\' where `id_address` =  \'' . $defaultMdsAddressPsId['id_address'] . '\'';
				$this->db->execute($sql);

			}

		}

		$sql = 'INSERT INTO ' . _DB_PREFIX_ . 'mds_collivery_processed(id_order,id_collection_address,id_service,id_delivery_address,status)
		VALUES
		(\'' . $orderId . '\',\'' . $defaultMdsAddressPsId['id_address'] . '\',\'' . $serviceId . '\', \'' . $deliveryAddressId . '\', "Not yet sent")';
		$this->db->execute($sql);

	}

	public function hookDisplayAdminOrder($params)
	{
			try {
				$this->client = new SoapClient( // Setup the soap client
					'http://www.collivery.co.za/wsdl/v2', // URL to WSDL File
					array('cache_wsdl' => WSDL_CACHE_NONE) // Don't cache the WSDL file
				);
			} catch (SoapFault $e) {
				echo "Unable to connect to the API, plugin not operational";
				return false;
			}

		$sql = 'SELECT `id_address_delivery` FROM `' . _DB_PREFIX_ . 'orders` WHERE `id_order` = ' . $params['id_order'];
		$deliveryAddressId = $this->db->getValue($sql);

		$sql = 'SELECT `id_service` FROM `' . _DB_PREFIX_ . 'mds_collivery_processed` WHERE `id_order` = ' . $params['id_order'];
		$serviceId = $this->db->getValue($sql);
		$services = $this->collivery->getServices();
		$serviceName= $services[$serviceId];

		$sql = 'SELECT * FROM `ps_address` LEFT JOIN (`ps_state`) ON (`ps_address`.`id_state`=`ps_state`.`id_state`) where `id_customer` = 2 AND deleted = 0';
		$deliveryAddresses = $this->db->ExecuteS($sql);

		$sql = 'SELECT `id_manufacturer` FROM `ps_manufacturer` where `name` = "MDS Collection Adresses"';
		$idManufacturer = $this->db->getValue($sql);

		$sql = 'SELECT * FROM `ps_address` LEFT JOIN (`ps_state`) ON (`ps_address`.`id_state`=`ps_state`.`id_state`) where `id_manufacturer` = ' .$idManufacturer . ' AND deleted = 0';
		$collectionAddresses = $this->db->ExecuteS($sql);

		$sql = 'SELECT `id_collection_address` FROM `' . _DB_PREFIX_ . 'mds_collivery_processed` WHERE `id_order` = ' . $params['id_order'];
		$collectionAddressId = $this->db->getValue($sql);

		$orderId = $params['id_order'];

		global $token;

		$sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'address` WHERE `id_address` = ' . $deliveryAddressId;
		$address = $this->db->getRow($sql);

		$suburb = $address['city'];
		$suburbs = $this->collivery->getSuburbs('');

		$locationType = $address['address2'];
		$locationTypes = $this->collivery->getLocationTypes();

		if ( ! $_POST['func_name']) {

			$_GET['func_name'];
			$form_action_func = $_GET['func_name'];

			if ($form_action_func === "getQuote") {
				$price = $this->getQuote($params);
			} elseif ($form_action_func === "addCollivery") {
				$idOrder = $_GET['id_order'];
				$message = $this->despatchDelivery($params,$idOrder);
			} elseif ($form_action_func === "changeCollectionAddress") {

				$idOrder = $_GET['id_order'];
				$value = $_GET['value'];
				$this->changeCollectionAddress($value, $idOrder);
			} elseif ($form_action_func === "changeDeliveryAddress") {
				$idOrder = $_GET['id_order'];
				$value = $_GET['value'];
				$this->changeDeliveryAddress($value, $idOrder);
			} elseif ($form_action_func === "getPod") {
				$waybill = $_GET['waybill'];
				$this->collivery->getPod($waybill);
			}else {
				echo $_SERVER['PHP_SELF'];
			}

		}

		$this->context->controller->addJS(($this->_path) . 'helper.js');


		$sql = 'SELECT `waybill` FROM `' . _DB_PREFIX_ . 'mds_collivery_processed` WHERE `id_order` = ' . $params['id_order'];
		$waybill = $this->db->getValue($sql);



		if(!$waybill) {

			return Mds_View::make(
				'shipping_control',
				compact(
					'idManufacturer',
					'deliveryAddressId',
					'orderId',
					'carrierName',
					'serviceId',
					'deliveryAddresses',
					'suburb',
					'suburbs',
					'locationType',
					'locationTypes',
					'countryName',
					'token',
					'collectionAddresses',
					'collectionAddressId',
					'price',
					'message'
				)
			);


		} else {

$status = $this ->getDeliveryStatus($waybill);

			return Mds_View::make(
				'delivery_details',
				compact(
					'deliveryAddressId',
					'orderId',
					'serviceId',
					'deliveryAddresses',
					'suburb',
					'suburbs',
					'locationType',
					'locationTypes',
					'token',
					'collectionAddresses',
					'collectionAddressId',
					'status',
					'waybill',
					'serviceName'

				)
			);
		}





	}

	protected function getServiceFromCarrierId($carrierId)
	{
		return Mds_Services::getServiceId($carrierId);
	}

	public function getQuote($params)
	{

		try {
			$orderParams = $this->buildColliveryControlDataArray($params);
			if (Mds_RiskCover::hasCover()) {
				$orderParams['cover'] = 1;
			}

			$colliveryPriceOptions = $this->collivery->getPrice($orderParams);
			$colliveryPrice = $colliveryPriceOptions['price']['inc_vat'];
			$price = $colliveryPrice;

			return $price;

		} catch (\Mds\Prestashop\Exceptions\InvalidData $e) {
			return false;
		}

	}

	public function despatchDelivery($params, $idOrder)
	{
		try {
			$orderParams = $this->buildColliveryControlDataArray($params);
			if (Mds_RiskCover::hasCover()) {
				$orderParams['cover'] = 1;
			}

			$waybill = $this->mdsService->addCollivery($orderParams, true);

			$sql = 'UPDATE ' . _DB_PREFIX_ . 'mds_collivery_processed SET `waybill` = \'' . $waybill . '\' where `id_order` =  \'' . $idOrder . '\'';
			$this->db->execute($sql);

			return;

		} catch (\Mds\Prestashop\Exceptions\InvalidData $e) {
			return false;
		}

	}

	public function buildColliveryControlDataArray($params)
	{
		$service = $this->getServiceFromCarrierId($params['cart']->id_carrier);

		$colliveryAddressTo = $this->addControlColliveryAddressTo($params);
		$colliveryAddressFrom = $this->addControlColliveryAddressFrom($params);

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

	public function addControlColliveryAddressTo($params)
	{

		$addAddress1 = $params['cart']->id_address_delivery;
		$sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'address
		WHERE id_address = \'' . $addAddress1 . '\' AND deleted = 0';
		$addressRow = $this->db->getRow($sql);

		$town_id = $addressRow['id_state'];
		$sql = 'SELECT `id_mds` FROM `' . _DB_PREFIX_ . 'state`
		WHERE `id_state` = "' . $town_id . '" ';
		$mds_town_id = $this->db->getValue($sql);

		$addressString = $addressRow['address1'] . $addressRow['city'] . $mds_town_id . $addressRow['postcode'] . $addressRow['firstname'] . " " . $addressRow['lastname'];
		$hash = hash('md5', $addressString);
		$hash = substr($hash, 0, 15);

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
		$colliveryParams['custom_id'] = $addressRow['id_address'] . "|" . $hash;

		$sql = 'SELECT email FROM ' . _DB_PREFIX_ . 'customer
		WHERE id_customer = \'' . $params['cart']->id_customer . '\'';
		$colliveryParams['email'] = $this->db->getValue($sql);

		try {
			return $this->mdsService->addColliveryAddress($colliveryParams);
		} catch (Exception $e) {
			die($e->getMessage());
		}

	}

	public function addControlColliveryAddressFrom($params)
	{
		$sql = 'SELECT `id_collection_address` FROM ' . _DB_PREFIX_ . 'mds_collivery_processed
		WHERE id_order = \'' . $params['id_order'] . '\'';
		$addAddress1 = $this->db->getValue($sql);

		$sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'address
		WHERE id_address = \'' . $addAddress1 . '\' AND deleted = 0';
		$addressRow = $this->db->getRow($sql);

		$town_id = $addressRow['id_state'];
		$sql = 'SELECT `id_mds` FROM `' . _DB_PREFIX_ . 'state`
		WHERE `id_state` = "' . $town_id . '" ';
		$mds_town_id = $this->db->getValue($sql);

		$addressString = $addressRow['address1'] . $addressRow['city'] . $mds_town_id . $addressRow['postcode'] . $addressRow['firstname'] . " " . $addressRow['lastname'];
		$hash = hash('md5', $addressString);
		$hash = substr($hash, 0, 15);

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
		$colliveryParams['custom_id'] = $addressRow['id_address'] . "|" . $hash;

		$sql = 'SELECT email FROM ' . _DB_PREFIX_ . 'customer
		WHERE id_customer = \'' . $params['cart']->id_customer . '\'';
		$colliveryParams['email'] = $this->db->getValue($sql);

		try {
			return $this->mdsService->addColliveryAddress($colliveryParams);
		} catch (Exception $e) {
			die($e->getMessage());
		}

	}

	public function changeDeliveryAddress($value, $idOrder)
	{
		$sql = 'UPDATE ' . _DB_PREFIX_ . 'mds_collivery_processed SET `id_delivery_address` = \'' . $value . '\' where `id_order` =  \'' . $idOrder . '\'';
		$this->db->execute($sql);

		$sql = 'UPDATE ' . _DB_PREFIX_ . 'orders SET `id_address_delivery` = \'' . $value . '\' where `id_order` =  \'' . $idOrder . '\'';
		$this->db->execute($sql);
	}
	public function changeCollectionAddress($value, $idOrder)
	{
		$sql = 'UPDATE ' . _DB_PREFIX_ . 'mds_collivery_processed SET `id_collection_address` = \'' . $value . '\' where `id_order` =  \'' . $idOrder . '\'';
		$this->db->execute($sql);
	/**
	 * @param $params
	 * @param $waybill
	 *
	 * @return array
	 */
	public function getWayBillStatus( $waybill)
	{
		$status = $this->mdsService->collivery->getStatus($waybill);
//		$colliveryPriceOptions = $this->collivery->getPrice($params);
//		array_push($status, $colliveryPriceOptions);


		return $status;
	}

}
