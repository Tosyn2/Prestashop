<?php

// Avoid direct access to the file
if (!defined('_PS_VERSION_')) {
	exit;
}

if (version_compare(PHP_VERSION, '5.3.0') < 0) {
	die('Your PHP version is not able to run this plugin, update to the latest version before installing this plugin.');
}


class Mds extends CarrierModule
{

	public $id_carrier;
	private $_html = '';
	/**
	 * @type array
	 */
	private $_postErrors = array();
	private $_moduleName = 'mds';
	public static $_this = false;
	protected $cache;
	protected $db;
	protected $towns;
	protected $services;
	protected $location_types;
	protected $extension_id;
	protected $app_name;
	protected $app_info;
	protected $collivery;
	protected $password;
	protected $username;
	protected $converter;
	protected $risk_cover;
	protected $email;
	protected $settings;
	protected $mdsService;
	protected $errors;
	public static $orderParams;

	/*
	** Construct Method
	**
	*/


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


		require_once 'helperClasses/MdsColliveryService.php';

		$settings['mds_user'] = Configuration::get('MDS_EMAIL');
		$settings['mds_pass'] = Configuration::get('MDS_PASSWORD');

		$this->mdsService = \helperClasses\MdsColliveryService::getInstance($settings);
		$this->collivery = $this->mdsService->returnColliveryClass();
		$this->cache = $this->mdsService->returnCacheClass();
	}

	/*
	** Install / Uninstall Methods
	**
	*/
	public function install()
	{

		if (!extension_loaded('soap')) {
			$warning[] = "'" . $this->l('Class Soap') . "', ";
		}

		$carrierConfig = array(
			0 => array(
				'name' => 'Overnight before 10:00',
				'id_tax_rules_group' => 0,
				'active' => true,
				'deleted' => 0,
				'shipping_handling' => false,
				'range_behavior' => 0,
				'delay' => array(
					'fr' => 'Overnight before 10:00', 'en' => 'Overnight before 10:00', Language::getIsoById(
						Configuration::get('PS_LANG_DEFAULT')
					) => 'Overnight before 10:00'
				),
				'id_zone' => 4,
				'is_module' => true,
				'shipping_external' => true,
				'external_module_name' => 'mds',
				'need_range' => true
			),
			1 => array(
				'name' => 'Overnight before 16:00',
				'id_tax_rules_group' => 0,
				'active' => true,
				'deleted' => 0,
				'shipping_handling' => false,
				'range_behavior' => 0,
				'delay' => array(
					'fr' => 'Overnight before 16:00', 'en' => 'Overnight before 16:00', Language::getIsoById(
						Configuration::get('PS_LANG_DEFAULT')
					) => 'Overnight before 16:00'
				),
				'id_zone' => 4,
				'is_module' => true,
				'shipping_external' => true,
				'external_module_name' => 'mds',
				'need_range' => true
			),
			2 => array(
				'name' => 'Road Freight',
				'id_tax_rules_group' => 0,
				'active' => true,
				'deleted' => 0,
				'shipping_handling' => false,
				'range_behavior' => 0,
				'delay' => array(
					'fr' => 'Road Freight', 'en' => 'Road Freight', Language::getIsoById(
						Configuration::get('PS_LANG_DEFAULT')
					) => 'Road Freight'
				),
				'id_zone' => 4,
				'is_module' => true,
				'shipping_external' => true,
				'external_module_name' => 'mds',
				'need_range' => true
			),
			3 => array(
				'name' => 'Road Freight Express',
				'id_tax_rules_group' => 0,
				'active' => true,
				'deleted' => 0,
				'shipping_handling' => false,
				'range_behavior' => 0,
				'delay' => array(
					'fr' => 'Road Freight Express', 'en' => 'Road Freight Express', Language::getIsoById(
						Configuration::get('PS_LANG_DEFAULT')
					) => 'Road Freight Express'
				),
				'id_zone' => 4,
				'is_module' => true,
				'shipping_external' => true,
				'external_module_name' => 'mds',
				'need_range' => true
			),
		);

		$id_carrier1 = $this->installExternalCarrier($carrierConfig[0]);
		$id_carrier2 = $this->installExternalCarrier($carrierConfig[1]);
		$id_carrier3 = $this->installExternalCarrier($carrierConfig[2]);
		$id_carrier5 = $this->installExternalCarrier($carrierConfig[3]);

		Configuration::updateValue('MYCARRIER1_CARRIER_ID', (int)$id_carrier1);
		Configuration::updateValue('MYCARRIER2_CARRIER_ID', (int)$id_carrier2);
		Configuration::updateValue('MYCARRIER3_CARRIER_ID', (int)$id_carrier3);
		Configuration::updateValue('MYCARRIER5_CARRIER_ID', (int)$id_carrier5);

		if (!parent::install() ||
			!Configuration::updateValue('MYCARRIER1_OVERCOST', '0') ||
			!Configuration::updateValue('MYCARRIER2_OVERCOST', '0') ||
			!Configuration::updateValue('MYCARRIER3_OVERCOST', '0') ||
			!Configuration::updateValue('MYCARRIER5_OVERCOST', '0') ||
			!Configuration::updateValue('MDS_EMAIL', 'api@collivery.co.za') ||
			!Configuration::updateValue('MDS_PASSWORD', 'api123') ||
			!Configuration::updateValue('MDS_RISK', '0') ||
			!$this->registerHook('updateCarrier') ||
			!$this->registerHook('actionPaymentConfirmation') ||
			!$this->registerHook('leftColumn') ||
			!$this->registerhook('displayFooter') ||
			!$this->registerHook('header') ||
			!$this->registerHook('displayBackOfficeFooter')

		) {
			return false;
		}

		$sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'state WHERE id_mds';
		if (!Db::getInstance()->query($sql)) {
			$sql = 'ALTER TABLE `' . _DB_PREFIX_ . 'state` ADD `id_mds` INT NULL AFTER  `iso_code`';
			Db::getInstance()->execute($sql);
		}

		$sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mds_collivery_processed` (
						`id` int(11) NOT NULL AUTO_INCREMENT,
						`waybill` int(11) NOT NULL,
						`order_id` int(11) NOT NULL,
						`validation_results` TEXT NOT NULL,
						`status` int(1) NOT NULL DEFAULT 1,
						PRIMARY KEY (`id`)
						) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

		Db::getInstance()->execute($sql);


		$sql = 'UPDATE  `' . _DB_PREFIX_ . 'country` SET `contains_states`= 1 WHERE `iso_code`= "ZA"';
		Db::getInstance()->execute($sql);


		return true;
	}


	public function uninstall()
	{
		// Uninstall
		if (!parent::uninstall() ||
			!Configuration::deleteByName('MYCARRIER1_OVERCOST') ||
			!Configuration::deleteByName('MYCARRIER2_OVERCOST') ||
			!Configuration::deleteByName('MYCARRIER3_OVERCOST') ||
			!Configuration::deleteByName('MYCARRIER5_OVERCOST') ||
			!Configuration::deleteByName('MDS_EMAIL') ||
			!Configuration::deleteByName('MDS_PASSWORD') ||
			!Configuration::deleteByName('MDS_RISK') ||
			!$this->unregisterHook('updateCarrier') ||
			!$this->unregisterHook('actionPaymentConfirmation') ||
			!$this->unregisterHook('leftColumn') ||
			!$this->unregisterhook('displayFooter') ||
			!$this->unregisterHook('header') ||
			!$this->unregisterHook('backOfficeHeader')
		) {
			return false;
		}

		// Delete External Carrier
		$Carrier1 = new Carrier((int)(Configuration::get('MYCARRIER1_CARRIER_ID')));
		$Carrier2 = new Carrier((int)(Configuration::get('MYCARRIER2_CARRIER_ID')));
		$Carrier3 = new Carrier((int)(Configuration::get('MYCARRIER3_CARRIER_ID')));
		$Carrier5 = new Carrier((int)(Configuration::get('MYCARRIER5_CARRIER_ID')));

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

	public static function installExternalCarrier($config)
	{
		$carrier = new Carrier();
		$carrier->name = $config['name'];
		$carrier->id_tax_rules_group = $config['id_tax_rules_group'];
		$carrier->id_zone = $config['id_zone'];
		$carrier->active = $config['active'];
		$carrier->deleted = $config['deleted'];
		$carrier->delay = $config['delay'];
		$carrier->shipping_handling = $config['shipping_handling'];
		$carrier->range_behavior = $config['range_behavior'];
		$carrier->is_module = $config['is_module'];
		$carrier->shipping_external = $config['shipping_external'];
		$carrier->external_module_name = $config['external_module_name'];
		$carrier->need_range = $config['need_range'];

		$languages = Language::getLanguages(true);
		foreach ($languages as $language) {
			if ($language['iso_code'] == 'fr') {
				$carrier->delay[(int)$language['id_lang']] = $config['delay'][$language['iso_code']];
			}
			if ($language['iso_code'] == 'en') {
				$carrier->delay[(int)$language['id_lang']] = $config['delay'][$language['iso_code']];
			}
			if ($language['iso_code'] == Language::getIsoById(Configuration::get('PS_LANG_DEFAULT'))) {
				$carrier->delay[(int)$language['id_lang']] = $config['delay'][$language['iso_code']];
			}
		}

		if ($carrier->add()) {
			$groups = Group::getGroups(true);
			foreach ($groups as $group) {
				Db::getInstance()->autoExecute(
					_DB_PREFIX_ . 'carrier_group',
					array('id_carrier' => (int)($carrier->id), 'id_group' => (int)($group['id_group'])),
					'INSERT'
				);
			}

			$rangePrice = new RangePrice();
			$rangePrice->id_carrier = $carrier->id;
			$rangePrice->delimiter1 = '0';
			$rangePrice->delimiter2 = '10000';
			$rangePrice->add();

			$rangeWeight = new RangeWeight();
			$rangeWeight->id_carrier = $carrier->id;
			$rangeWeight->delimiter1 = '0';
			$rangeWeight->delimiter2 = '10000';
			$rangeWeight->add();

			$zones = Zone::getZones(true);
			foreach ($zones as $zone) {
				Db::getInstance()->autoExecute(
					_DB_PREFIX_ . 'carrier_zone',
					array('id_carrier' => (int)($carrier->id), 'id_zone' => (int)($zone['id_zone'])),
					'INSERT'
				);
				Db::getInstance()->autoExecuteWithNullValues(
					_DB_PREFIX_ . 'delivery',
					array('id_carrier' => (int)($carrier->id), 'id_range_price' => (int)($rangePrice->id), 'id_range_weight' => null, 'id_zone' => (int)($zone['id_zone']), 'price' => '0'),
					'INSERT'
				);
				Db::getInstance()->autoExecuteWithNullValues(
					_DB_PREFIX_ . 'delivery',
					array('id_carrier' => (int)($carrier->id), 'id_range_price' => null, 'id_range_weight' => (int)($rangeWeight->id), 'id_zone' => (int)($zone['id_zone']), 'price' => '0'),
					'INSERT'
				);
			}

			// Copy Logo
			if (!copy(dirname(__FILE__) . '/carrier.jpg', _PS_SHIP_IMG_DIR_ . '/' . (int)$carrier->id . '.jpg')) {
				return false;
			}

			// Return ID Carrier
			return (int)($carrier->id);
		}

		return false;
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

	private function _displayForm()
	{
		$this->_html .= '<fieldset>
		<legend><img src="' . $this->_path . 'logo.gif" alt="" /> ' . $this->l(
				'My Carrier Module Status'
			) . '</legend>';

		$alert = array();
		if (!Configuration::get('MYCARRIER1_OVERCOST') || Configuration::get('MYCARRIER1_OVERCOST') == '') {
			$alert['carrier1'] = 1;
		}
		if (!Configuration::get('MYCARRIER2_OVERCOST') || Configuration::get('MYCARRIER2_OVERCOST') == '') {
			$alert['carrier2'] = 1;
		}
		if (!Configuration::get('MYCARRIER3_OVERCOST') || Configuration::get('MYCARRIER3_OVERCOST') == '') {
			$alert['carrier3'] = 1;
		}
		if (Configuration::get('MDS_EMAIL') != Tools::getValue('MDS_EMAIL')) {
			$alert['account_email'] = 1;
		}
		if (Configuration::get('MDS_PASSWORD') != Tools::getValue('MDS_PASSWORD')) {
			$alert['account_password'] = 1;
		}
		if (!Configuration::get('MYCARRIER5_OVERCOST') || Configuration::get('MYCARRIER5_OVERCOST') == '') {
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
						<div class="margin-form"><input type="text" size="20" name="mycarrier1_overcost" value="' . Tools::getValue(
				'mycarrier1_overcost',
				Configuration::get('MYCARRIER1_OVERCOST')
			) . '" /></div>
						<label>' . $this->l('Overnight before 16:00') . ' : </label>
						<div class="margin-form"><input type="text" size="20" name="mycarrier2_overcost" value="' . Tools::getValue(
				'mycarrier2_overcost',
				Configuration::get('MYCARRIER2_OVERCOST')
			) . '" /></div>
						<label>' . $this->l('Road Freight Express') . ' : </label>
						<div class="margin-form"><input type="text" size="20" name="mycarrier3_overcost" value="' . Tools::getValue(
				'mycarrier3_overcost',
				Configuration::get('MYCARRIER3_OVERCOST')
			) . '" /></div>
						<label>' . $this->l('Road Freight') . ' : </label>
						<div class="margin-form"><input type="text" size="20" name="mycarrier5_overcost" value="' . Tools::getValue(
				'mycarrier5_overcost',
				Configuration::get('MYCARRIER5_OVERCOST')
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
						<div class="margin-form"><input type="checkbox" name="MDS_RISK" value="' . Tools::getValue(
				'MDS_RISK',
				Configuration::get('MDS_RISK')
			) . '"  /></div>
					</div>
				<br /><br />
				</fieldset>				
				<div class="margin-form"><input class="button" name="submitSave" type="submit"></div>
			</form>
		</div></div>';
	}


	private function _postValidation()
	{
		// Check configuration values
		if (Tools::getValue('mycarrier1_overcost') == '' &&
			Tools::getValue('mycarrier2_overcost') == '' &&
			Tools::getValue('mycarrier3_overcost') == '' &&
			Tools::getValue('mycarrier5_overcost') == '' ||
			Tools::getValue('MDS_EMAIL') == '' ||
			Tools::getValue('MDS_PASSWORD') == ''
		) {
			$this->_postErrors[] = $this->l('You have to configure at least one carrier AND input your MDS account login details');
		}
	}

	private function _postProcess()
	{
		if ($this->settings['mds_user'] != Tools::getValue('MDS_EMAIL') || $this->settings['mds_pass'] != Tools::getValue('MDS_PASSWORD')) {

			$settings['mds_user'] = Tools::getValue('MDS_EMAIL');
			$settings['mds_pass'] = Tools::getValue('MDS_PASSWORD');

			$this->mdsService = \helperClasses\MdsColliveryService::getInstance($settings);
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
		}
	}


	/*
	 * Hook update carrier
	 *
	 */


	private function updateSettings(array $array)
	{
		$success = true;

		if (Configuration::updateValue('MYCARRIER1_OVERCOST', Tools::getValue('mycarrier1_overcost')) &&
			Configuration::updateValue('MYCARRIER1_OVERCOST', Tools::getValue('mycarrier1_overcost')) &&
			Configuration::updateValue('MYCARRIER2_OVERCOST', Tools::getValue('mycarrier2_overcost')) &&
			Configuration::updateValue('MYCARRIER3_OVERCOST', Tools::getValue('mycarrier3_overcost')) &&
			Configuration::updateValue('MYCARRIER5_OVERCOST', Tools::getValue('mycarrier5_overcost')) &&
			Configuration::updateValue('MDS_EMAIL', Tools::getValue('MDS_EMAIL')) &&
			Configuration::updateValue('MDS_PASSWORD', Tools::getValue('MDS_PASSWORD'))
		) {
			return $success;
		} else {
			$success = false;
			return $success;
		}


	}


	public function hookupdateCarrier($params)
	{
		if ((int)($params['id_carrier']) == (int)(Configuration::get('MYCARRIER1_CARRIER_ID'))) {
			Configuration::updateValue('MYCARRIER1_CARRIER_ID', (int)($params['carrier']->id));
		}
		if ((int)($params['id_carrier']) == (int)(Configuration::get('MYCARRIER2_CARRIER_ID'))) {
			Configuration::updateValue('MYCARRIER2_CARRIER_ID', (int)($params['carrier']->id));
		}
		if ((int)($params['id_carrier']) == (int)(Configuration::get('MYCARRIER3_CARRIER_ID'))) {
			Configuration::updateValue('MYCARRIER3_CARRIER_ID', (int)($params['carrier']->id));
		}
		if ((int)($params['id_carrier']) == (int)(Configuration::get('MYCARRIER5_CARRIER_ID'))) {
			Configuration::updateValue('MYCARRIER5_CARRIER_ID', (int)($params['carrier']->id));
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
		$colliveryAddressTo = $this->addColliveryAddressTo($params);
		$colliveryAddressFrom = $this->getDefaultColliveryAddressFrom($params);

		$cart = $params['cart'];
		$cartProducts = $cart->getProducts();
		
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
		WHERE `id_state` = "' . $town_id  . '" ';
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
		try {
			$orderParams = $this->buildColliveryGetPriceArray($params);

			$sql = 'SELECT name FROM ' . _DB_PREFIX_ . 'carrier WHERE id_carrier =' . $this->id_carrier . ' and deleted = 0';

			$carrierName = Db::getInstance()->getValue($sql);

			$service = $this->getServiceFromCarrierId($carrierName);
			$orderParams['service'] = $carrierName;

			$colliveryPriceOptions = $this->collivery->getPrice($orderParams);
			$colliveryPrice = $colliveryPriceOptions['price']['inc_vat'];

			return Configuration::get('MYCARRIER' . $service . '_OVERCOST') + $colliveryPrice;
		} catch (InvalidArgumentException $e) {
			return false;
		}
	}

	public function getOrderShippingCostExternal($params)
	{
		return false;
	}

	public function hookLeftColumn()
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

	public function hookActionPaymentConfirmation($params)
	{

		try {
			$orderParams = $this->buildColliveryDataArray($params);
			$sql = 'SELECT name FROM ' . _DB_PREFIX_ . 'carrier WHERE id_carrier =' . $params['cart']->id_carrier . ' and deleted = 0';
			$carrierName = Db::getInstance()->getValue($sql);

			$service = $this->getServiceFromCarrierId($carrierName);
			$orderParams['service'] = $service;

			return $this->mdsService->addCollivery($orderParams, true);

		} catch (InvalidArgumentException $e) {
			return false;
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
			'Overnight before 10:00' => 1,
			'Overnight before 16:00' => 2,
			'Road Freight Express' => 3,
			'Road Freight' => 5,
		];

		if (!array_key_exists($carrierId, $serviceMappings)) {
			throw new InvalidArgumentException;
		}

		return $serviceMappings[$carrierId];
	}

}






