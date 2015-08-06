<?php

// Avoid direct access to the file
if (!defined('_PS_VERSION_')) {
	exit;
}

spl_autoload_register(function($class) {
	$classParts = explode('\\', $class);
	$vendor = array_shift($classParts);
	if ($vendor === 'Mds') {
		require dirname(__FILE__) .'/'. implode('/', $classParts) .'.php';
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

		$this->displayName = $this->l('Mds Shipping');
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
	 * Prestashop Installer
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
			$installer = new Mds\Prestashop\Installer();
			$installer->install();
		} catch (\Mds\Prestashop\Exceptions\UpdatingConfigurationException $e) {
			return false;
		}

		if (!parent::install()) {
			return false;
		}

		if ($this->registerHooks() === false) {
			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 * @throws \PrestaShopException
	 */
	private function registerHooks()
	{
		$hooks = array('updateCarrier', 'displayFooter', 'actionOrderStatusPostUpdate', 'displayShoppingCart');
		foreach ($hooks as $hook) {
			if (!$this->registerHook($hook)) return false;
		}
	}

	public function uninstall()
	{
		// Uninstall
		if (!parent::uninstall() ||
			!Configuration::deleteByName('MDS_SERVICE_SURCHARGE_1') ||
			!Configuration::deleteByName('MDS_SERVICE_SURCHARGE_2') ||
			!Configuration::deleteByName('MDS_SERVICE_SURCHARGE_3') ||
			!Configuration::deleteByName('MDS_SERVICE_SURCHARGE_5') ||
			!Configuration::deleteByName('MDS_EMAIL') ||
			!Configuration::deleteByName('MDS_PASSWORD') ||
			!Configuration::deleteByName('MDS_RISK') ||
			!$this->unregisterHook('updateCarrier') ||
			!$this->unregisterhook('displayFooter') ||
			!$this->unregisterHook('actionOrderStatusPostUpdate')
		) {
			return false;
		}

		// Delete External Carrier
		$Carrier1 = new Carrier((int)(Configuration::get('MDS_SERVICE_CARRIER_ID_1')));
		$Carrier2 = new Carrier((int)(Configuration::get('MDS_SERVICE_CARRIER_ID_2')));
		$Carrier3 = new Carrier((int)(Configuration::get('MDS_SERVICE_CARRIER_ID_3')));
		$Carrier5 = new Carrier((int)(Configuration::get('MDS_SERVICE_CARRIER_ID_5')));

		// If external carrier is default set other one as default
		if (Configuration::get('PS_CARRIER_DEFAULT') == (int)($Carrier1->id) || Configuration::get(
				'PS_CARRIER_DEFAULT'
			) == (int)($Carrier2->id) || Configuration::get(
				'PS_CARRIER_DEFAULT'
			) == (int)($Carrier3->id) || Configuration::get(
				'PS_CARRIER_DEFAULT'
			) == (int)($Carrier3->id) || Configuration::get('PS_CARRIER_DEFAULT') == (int)($Carrier5->id)
		) {
			global $cookie;
			$carriersD = Carrier::getCarriers(
				$cookie->id_lang,
				true,
				false,
				false,
				null,
				PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE
			);
			foreach ($carriersD as $carrierD) {
				if ($carrierD['active'] AND !$carrierD['deleted'] AND ($carrierD['name'] != $this->_config['name'])) {
					Configuration::updateValue('PS_CARRIER_DEFAULT', $carrierD['id_carrier']);
				}
			}
		}

		$sql = 'UPDATE ' . _DB_PREFIX_ . 'carrier SET `deleted` = 1 WHERE `external_module_name` = \'mds\'';
		Db::getInstance()->execute($sql);

		return true;

	}

	/*
	** Form Config Methods
	**
	*/
	public function getContent()
	{
		$this->_html .= '<h2>' . $this->l('My Carrier') . '</h2>';
		if (!empty($_POST) AND Tools::isSubmit('submitSave')) {

			$this->_postValidation();
			if (!sizeof($this->_postErrors)) {
				$this->_postProcess();
			} else {
				foreach ($this->_postErrors AS $err) {
					$this->_html .= '<div class="alert error"><img src="' . _PS_IMG_ . 'admin/forbbiden.gif" alt="nok" />&nbsp;' . $err . '</div>';
				}
			}
		}
		$this->_displayForm();

		return $this->_html;
	}

	/*
	** Service and API config settings form
	**
	*/
	private function checked()
	{
		if (Configuration::get('MDS_RISK') == 1) return "checked";
	}

	private function _displayForm()
	{
		$this->_html .= '<fieldset>
		<legend><img src="' . $this->_path . 'logo.gif" alt="" /> ' . $this->l(
				'My Carrier Module Status'
			) . '</legend>';

		$alert = array();
		if (!Configuration::get('MDS_SERVICE_SURCHARGE_1') || Configuration::get('MDS_SERVICE_SURCHARGE_1') == '') {
			$alert['carrier1'] = 1;
		}
		if (!Configuration::get('MDS_SERVICE_SURCHARGE_2') || Configuration::get('MDS_SERVICE_SURCHARGE_2') == '') {
			$alert['carrier2'] = 1;
		}
		if (!Configuration::get('MDS_SERVICE_SURCHARGE_3') || Configuration::get('MDS_SERVICE_SURCHARGE_3') == '') {
			$alert['carrier3'] = 1;
		}
		if (Configuration::get('MDS_EMAIL') != Tools::getValue('MDS_EMAIL')) {
			$alert['account_email'] = 1;
		}
		if (Configuration::get('MDS_PASSWORD') != Tools::getValue('MDS_PASSWORD')) {
			$alert['account_password'] = 1;
		}
		if (!Configuration::get('MDS_SERVICE_SURCHARGE_5') || Configuration::get('MDS_SERVICE_SURCHARGE_5') == '') {
			$alert['carrier5'] = 1;
		}

		if (!count($alert)) {
			$this->_html .= '<img src="' . _PS_IMG_ . 'admin/module_install.png" /><strong>' . $this->l(
					'My Carrier is configured and online!'
				) . '</strong>';
		} else {
			$this->_html .= '<img src="' . _PS_IMG_ . 'admin/warn2.png" /><strong>' . $this->l(
					'My Carrier is not configured yet, please:'
				) . '</strong>';
			$this->_html .= '<br />' . (isset($alert['carrier1']) ? '<img src="' . _PS_IMG_ . 'admin/warn2.png" />' : '<img src="' . _PS_IMG_ . 'admin/module_install.png" />') . ' 1) ' . $this->l(
					'Overnight before 10:00 overcost price is configured'
				);
			$this->_html .= '<br />' . (isset($alert['carrier2']) ? '<img src="' . _PS_IMG_ . 'admin/warn2.png" />' : '<img src="' . _PS_IMG_ . 'admin/module_install.png" />') . ' 2) ' . $this->l(
					'Overnight before 16:00 overcost is configured'
				);
			$this->_html .= '<br />' . (isset($alert['carrier3']) ? '<img src="' . _PS_IMG_ . 'admin/warn2.png" />' : '<img src="' . _PS_IMG_ . 'admin/module_install.png" />') . ' 3) ' . $this->l(
					'Road Freight Express overcost is configured'
				);
			$this->_html .= '<br />' . (isset($alert['carrier5']) ? '<img src="' . _PS_IMG_ . 'admin/warn2.png" />' : '<img src="' . _PS_IMG_ . 'admin/module_install.png" />') . ' 4) ' . $this->l(
					'Road Freight overcost is connfigured'
				);
			$this->_html .= '<br />' . (isset($alert['account_email']) ? '<img src="' . _PS_IMG_ . 'admin/warn2.png" />' : '<img src="' . _PS_IMG_ . 'admin/module_install.png" />') . ' 5) ' . $this->l(
					'Correct MDS account email'
				);
			$this->_html .= '<br />' . (isset($alert['account_password']) ? '<img src="' . _PS_IMG_ . 'admin/warn2.png" />' : '<img src="' . _PS_IMG_ . 'admin/module_install.png" />') . ' 6) ' . $this->l(
					'Correct MDS account password'
				);
		}

		$this->_html .= '</fieldset><div class="clear">&nbsp;</div>
			<style>
				#tabList { clear: left; }
				.tabItem { display: block; background: #FFFFF0; border: 1px solid #CCCCCC; padding: 10px; padding-top: 20px; }
			</style>
			<div id="tabList">
				<div class="tabItem">
					<form action="index.php?tab=' . Tools::getValue('tab') . '&configure=' . Tools::getValue(
				'configure'
			) . '&token=' . Tools::getValue('token') . '&tab_module=' . Tools::getValue(
				'tab_module'
			) . '&module_name=' . Tools::getValue('module_name') . '&id_tab=1&section=general" method="post" class="form" id="configForm">

					<fieldset style="border: 0px;">
						<h4>' . $this->l('General configuration') . ' :</h4>
						<label>' . $this->l('Overnight before 10:00') . ' : </label>
						<div class="margin-form"><input type="text" size="20" name="MDS_SERVICE_SURCHARGE_1" value="' . Tools::getValue(
				'MDS_SERVICE_SURCHARGE_1',
				Configuration::get('MDS_SERVICE_SURCHARGE_1')
			) . '" /></div>
						<label>' . $this->l('Overnight before 16:00') . ' : </label>
						<div class="margin-form"><input type="text" size="20" name="MDS_SERVICE_SURCHARGE_2" value="' . Tools::getValue(
				'MDS_SERVICE_SURCHARGE_2',
				Configuration::get('MDS_SERVICE_SURCHARGE_2')
			) . '" /></div>
						<label>' . $this->l('Road Freight Express') . ' : </label>
						<div class="margin-form"><input type="text" size="20" name="MDS_SERVICE_SURCHARGE_3" value="' . Tools::getValue(
				'MDS_SERVICE_SURCHARGE_3',
				Configuration::get('MDS_SERVICE_SURCHARGE_3')
			) . '" /></div>
						<label>' . $this->l('Road Freight') . ' : </label>
						<div class="margin-form"><input type="text" size="20" name="MDS_SERVICE_SURCHARGE_5" value="' . Tools::getValue(
				'MDS_SERVICE_SURCHARGE_5',
				Configuration::get('MDS_SERVICE_SURCHARGE_5')
			) . '" /></div>
						<label>' . $this->l('MDS account email') . ' : </label>
						<div class="margin-form"><input type="text" name="MDS_EMAIL" value="' . Tools::getValue(
				'MDS_EMAIL',
				Configuration::get('MDS_EMAIL')
			) . '"  /></div>
						<label>' . $this->l('MDS account password') . ' : </label>
						<div class="margin-form"><input type="text" name="MDS_PASSWORD" value="' . Tools::getValue(
				'MDS_PASSWORD',
				Configuration::get('MDS_PASSWORD')
			) . '" /></div>
						<label>' . $this->l('MDS risk cover') . ' : </label>
						<div class="margin-form">
						<input name="MDS_RISK" type="checkbox"  ' . $this->checked() . '  value="1"  />
					</div>
				<br /><br />
				</fieldset>
				<div class="margin-form"><input class="button"  name="submitSave" type="submit"></div>
			</form>
		</div></div>';
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

	public function hookupdateCarrier($params)
	{
		if ((int)($params['id_carrier']) == (int)(Configuration::get('MDS_SERVICE_CARRIER_ID_1'))) {
			Configuration::updateValue('MDS_SERVICE_CARRIER_ID_1', (int)($params['carrier']->id));
		}
		if ((int)($params['id_carrier']) == (int)(Configuration::get('MDS_SERVICE_CARRIER_ID_2'))) {
			Configuration::updateValue('MDS_SERVICE_CARRIER_ID_2', (int)($params['carrier']->id));
		}
		if ((int)($params['id_carrier']) == (int)(Configuration::get('MDS_SERVICE_CARRIER_ID_3'))) {
			Configuration::updateValue('MDS_SERVICE_CARRIER_ID_3', (int)($params['carrier']->id));
		}
		if ((int)($params['id_carrier']) == (int)(Configuration::get('MDS_SERVICE_CARRIER_ID_5'))) {
			Configuration::updateValue('MDS_SERVICE_CARRIER_ID_5', (int)($params['carrier']->id));
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
		$cartProducts = $cart->getProducts();

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
		$location_type = $address['address2'];

		$this->context->controller->addJS(($this->_path) . 'helper.js');

		$suburbs = $this->collivery->getSuburbs('');
		$location_types = $this->collivery->getLocationTypes();

		return '<script type="text/javascript">
					var suburbs= ' . json_encode($suburbs) . ';
					var location_types= ' . json_encode($location_types) . ';
					var suburb= ' . json_encode($suburb) . ';
					var location_type= ' . json_encode($location_type) . ';
					replaceText("State","Town");
					replaceText("City","Suburb");
					replaceText("Address (Line 2)","Location Type");
					addDropDownSuburb(suburbs, suburb);
					addDropDownLocationType(location_types,location_type);
				</script>';
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






