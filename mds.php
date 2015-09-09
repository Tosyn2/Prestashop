<?php

// Avoid direct access to the file
if ( ! defined('_PS_VERSION_')) {
	exit;
}

define('_MDS_DIR_', __DIR__);

include('autoload.php');

/**
 * Class Mds
 */
class Mds extends CarrierModule {

	public $id_carrier;
	protected $db;

	public static $definition;
	public static $currentIndex;
	protected $hooks = array(
		'displayFooter',
		'actionOrderStatusPostUpdate',
		'displayShoppingCart',
		'displayAdminOrder',
		'orderConfirmation',
		'displayBackOfficeHeader'

	);
	protected $cache;

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
	 * @param $params
	 *
	 * @return mixed
	 */
	public function getCartProducts($params)
	{
		return $params->getProducts();
	}

	/**
	 * @param $params
	 * @param $shipping_cost
	 *
	 * @return bool
	 */
	public function getOrderShippingCost($params, $shipping_cost)
	{
		return false;
	}

	/**
	 * @param $params
	 * @param $shipping_cost
	 * @param $products
	 *
	 * @return bool
	 */
	public function getPackageShippingCost($params, $shipping_cost, $products)
	{
		$hash = 'getPackageShippingCost::'. sha1(json_encode($params)) .'-'. $this->id_carrier;

		if (array_key_exists($hash, $this->cache)) {
			return $this->cache[$hash];
		}

		$price = new Mds_TransactionTable($this->db);
		$prices = $price->getShoppingCartQuote($params, $shipping_cost, $this->id_carrier);

		foreach ($prices as $carrierId => $price) {

			if ($carrierId == $this->id_carrier ) {
				$this->cache[$hash] = $price;
				return $price;
			}else {
				return false;
			}
		}
	}

	/**
	 * @param $params
	 *
	 * @return bool
	 */
	public function getOrderShippingCostExternal($params)
	{
		return false;
	}

	/**
	 *
	 */
	public function hookDisplayShoppingCart()
	{
		$add = new Mds_TransactionTable($this->db);
		$add->addTownsToPsDb();
	}

	/**
	 * @param $params
	 *
	 * @return string
	 */
	public function hookDisplayFooter($params)
	{
		$val = 'other';
		AddressCore::$definition['fields'][$val] ;
		AddressCore::$definition['fields'][$val]['type'] = '1';
		AddressCore::$definition['fields'][$val]['validate'] = "isLanguageIsoCode";
		AddressCore::$definition['fields'][$val]['required'] = 1;

		//die(print_r(AddressCore::$definition));
		$view = new Mds_TransactionView($this->db);

		return $view->addFrontEndJs($params);
	}

	/**
	 * @param $params
	 *
	 * @return string
	 */
	public function hookDisplayBackOfficeHeader($params)
	{
//		$val = 'other';
//		AddressFormatCore::$definition['fields'][$val] ;
//		AddressFormatCore::$definition['fields'][$val]['type'] = '1';
//		AddressFormatCore::$definition['fields'][$val]['validate'] = "isPhpDateFormat";
//		AddressFormatCore::$definition['fields'][$val]['required'] = 1;

		//die(print_r(AddressFormat::$definition));
		$view = new Mds_TransactionView($this->db);

		return $view->addAdminJs($params,$token);
	}

	/**
	 * @param $params
	 */
	public function hookOrderConfirmation($params)
	{
		try {
			$createTransaction = new Mds_TransactionTable($this->db);
			(string) $createTransaction->createTransaction($params);
		} catch (PrestaShopExceptionCore $e) {
			return false;
		} catch (SoapFault $e) {
			echo "Unable to connect to the API, plugin not operational";

			return false;
		}

	}

	/**
	 * @param $params
	 *
	 * @return string|void
	 * @throws \PrestaShopDatabaseException
	 */
	public function hookDisplayAdminOrder($params)
	{
		global $token;
		try {
			$this->client = new SoapClient(
				'http://www.collivery.co.za/wsdl/v2',
				array('cache_wsdl' => WSDL_CACHE_NONE)
			);
		} catch (SoapFault $e) {
			echo "Unable to connect to the API, plugin not operational";

			return false;
		}

		$view = new Mds_TransactionView($this->db);
		return $view->generateView($params, $token);

	}



}
