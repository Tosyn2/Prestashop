<?php
/**
 * Copyright 2020 MDS Technologies (Pty) Ltd and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0).
 * It is also available through the world-wide-web at this URL: https://opensource.org/licenses/AFL-3.0
 *
 *  @author MDS Collivery <integration@collivery.co.za>
 *  @copyright  2020 MDS Technologies (Pty) Ltd
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

define('_MDS_DIR_', _PS_MODULE_DIR_.'mds');
include('autoload.php');

class Mds extends CarrierModule
{
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
        $this->version = '1.1.0';
        $this->author = 'MDS Tech (Pty) Ltd';
        $this->bootstrap = true;
        $this->id_carrier = 0;
        $this->limited_countries = array();

        $this->mdsService = \Mds\MdsColliveryService::getInstance(array());
        $this->collivery = \Mds\Prestashop\Collivery\ColliveryApi::getInstance();
        $this->db = Db::getInstance();

        parent::__construct();

        $this->displayName = $this->l('MDS Collivery');
        $this->description = $this->l('Offer your customers, different delivery methods that you want');
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.8');
    }

    /**
     * Prestashop Function to Install
     */
    public function install()
    {
        try {
            $installer = new Mds_Install($this->db);
            $installer->install();
            if (!parent::install()) {
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
        $errors = array();
        $settingsService = new Mds_SettingsService();

        $formUrl = 'index.php?tab=' . Tools::getValue('tab')
            . '&configure=' . Tools::getValue('configure')
            . '&token=' . Tools::getValue('token')
            . '&tab_module=' . Tools::getValue('tab_module')
            . '&module_name=' . Tools::getValue('module_name')
            . '&id_tab=1&section=general';

        if (!empty($_POST) and Tools::isSubmit('submitSave')) {
            try {
                $errors = $settingsService->store($_POST);
            } catch (\Mds\Prestashop\Exceptions\InvalidData $e) {
                $errors[] = $e->getMessage();
            }
        }

        try {
            $settingsService->testCurrentCredentials();
        } catch (\Mds\Prestashop\Collivery\InvalidCredentials $e) {
            $errors[] = 'Current Collivery credentials are invalid, plugin not operational';
        }

        $errors = empty($errors) ? '' : $this->displayError($errors);

        $this->context->smarty->assign('htmldata', array(
            'formUrl' => $formUrl,
            'errors' => $errors,
            'displayName' => $this->displayName,
            'surcharges' => $settingsService->getSurchargesInfo(),
            'email' => $settingsService->getColliveryEmail(),
            'riskCover' => \Mds\Prestashop\Settings\RiskCover::hasCover()
        ));

        $this->html = $this->display(__FILE__, 'views/templates/admin/settings.tpl');

        return $this->html;
    }


    /**
     * Hook update carrier
     *
     * @param $params
     *
     * @return array
     */
    public function addColliveryAddressTo($params)
    {
        $addAddress1 = $params['cart']->id_address_delivery;
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'address
		WHERE id_address = \'' . $addAddress1 . '\' AND deleted = 0';
        $addressRow = $this->db->getRow($sql);

        $town_id = $addressRow['id_state'];
        $sql = 'SELECT `id_mds` FROM `' . _DB_PREFIX_ . 'state`
		WHERE `id_state` = "' . $town_id . '" ';
        $mds_town_id = $this->db->getValue($sql);

        $colliveryParams = array();
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

    public function getDefaultColliveryAddressFrom()
    {
        $colliveryAddressesFrom = $this->mdsService->returnDefaultAddress();

        return array_pop($colliveryAddressesFrom['contacts']);
    }

    public function buildColliveryDataArray($params)
    {
        $service = $this->getServiceFromCarrierId($params['cart']->id_carrier);

        $colliveryAddressTo = $this->addColliveryAddressTo($params);
        $colliveryAddressFrom = $this->getDefaultColliveryAddressFrom();

        $cart = $params['cart'];

        $colliveryParams = array();
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
        $addressRow = $this->db->getRow($sql);

        $town_id = $addressRow['id_state'];
        $sql = 'SELECT `id_mds` FROM `' . _DB_PREFIX_ . 'state`
		WHERE `id_state` = "' . $town_id . '" ';
        $mds_town_id = $this->db->getValue($sql);

        $colliveryAddressFrom = $this->getDefaultColliveryAddressFrom();

        $cartProducts = $params->getProducts();

        $colliveryGetPriceArray = array();
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
        unset($shipping_cost);
        unset($params);
        return false;
    }

    public function getPackageShippingCost($params, $shipping_cost, $products)
    {
        try {
            unset($products);
            $orderParams = $this->buildColliveryGetPriceArray($params);
            $serviceId = $this->getServiceFromCarrierId($this->id_carrier);
            $orderParams['service'] = $serviceId;

            if (\Mds\Prestashop\Settings\RiskCover::hasCover()) {
                $orderParams['cover'] = 1;
            }

            $colliveryPriceOptions = $this->collivery->getPrice($orderParams);
            $colliveryPrice = $colliveryPriceOptions['price']['inc_vat'];

            $surchargePerc = \Mds\Prestashop\Settings\Surcharge::get($serviceId);
            $price = $colliveryPrice * (1 + ($surchargePerc / 100));

            return $shipping_cost + $price;
        } catch (\Mds\Prestashop\Exceptions\InvalidData $e) {
            return false;
        }
    }

    public function getOrderShippingCostExternal($params)
    {
        unset($params);
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
                $this->db->insert(
                    'state',
                    array(
                      'id_country'  => 30,
                      'id_zone'     => 4,
                      'name'        => PSQL($town),
                      'iso_code'    => "ZA",
                      'id_mds'      => $index,
                      'tax_behavior'=> 0,
                      'active'      => 1
                    )
                );
            }
        }
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        if ($params['newOrderStatus']->name == 'Shipped') {
            try {
                $orderParams = $this->buildColliveryDataArray($params);
                if (\Mds\Prestashop\Settings\RiskCover::hasCover()) {
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
        $idAddress = (int)$this->context->cart->id_address_delivery;

        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'address` WHERE `id_address` = ' . $idAddress;
        $address = $this->db->getRow($sql);

        $suburb = $address['city'];
        $suburbs = $this->collivery->getSuburbs('');

        $locationType = $address['address2'];
        $locationTypes = $this->collivery->getLocationTypes();

        $this->context->controller->addJS(($this->_path) . 'helper.js');

        return \Mds\Prestashop\Helpers\View::make(
            'admin/footer',
            compact('suburbs', 'suburb', 'locationTypes', 'locationType')
        );
    }

    public function hookOrderConfirmation($params)
    {
        $orderId = $params['objOrder']->id;

        $carrierId = $params['objOrder']->id_carrier;
        $sql = 'SELECT `name` FROM `' . _DB_PREFIX_ . 'carrier` WHERE `id_carrier` = ' . $carrierId;
        $carrierName = $this->db->getValue($sql);
        $serviceId = $this->getServiceFromCarrierId($carrierId);

        $this->db->insert(
            'mds_collivery_processed',
            array(
                'order_id'      => $orderId,
                'service_id'    => $serviceId,
                'service_name'  => $carrierName
            )
        );

        $defaultAddressId = $this->collivery->getDefaultAddressId();

        $defaultAddress = $this->collivery->getAddress($defaultAddressId);

        $location_types = $this->collivery->getLocationTypes();

        $sql = 'SELECT `id_state` FROM ' . _DB_PREFIX_ . 'state where `id_mds` = "' . $defaultAddress['town_id']
        . '" AND `active` = 1';
        $state_id = $this->db->getValue($sql);

        $contacts = $this->collivery->getContacts($defaultAddressId);

        $contact = array_pop($contacts);

        $name = explode(" ", $contact['full_name']);

        $first_name = array_shift($name);
        $last_name = array_pop($name);

        $streetAddress = $defaultAddress['street'];

        $locationType = $location_types[$defaultAddress['location_type']];
        $postCode = $defaultAddress['zip_code'];

        $city = $defaultAddress['suburb_name'];

        $phone = $contact['phone'];
        $mobile = isset($contact['phone_mobile']) ? $contact['phone_mobile'] : null;

        $this->db->insert(
            'address',
            array(
                'id_country'      => 30,
                'id_state'        => $state_id,
                'id_customer'     => 0,
                'id_manufacturer' => 0,
                'id_supplier'     => 0,
                'id_warehouse'    => 0,
                'alias'           => "Collection Address",
                'company'         => "MDS Address",
                'lastname'        => PSQL($last_name),
                'firstname'       => PSQL($first_name),
                'address1'        => PSQL($streetAddress),
                'address2'        => PSQL($locationType),
                'postcode'        => $postCode,
                'city'            => PSQL($city),
                'other'           => "other",
                'phone'           => $phone,
                'phone_mobile'    => $mobile,
                'active'          => 1,
                'deleted'         => 0
            )
        );
    }


    public function hookDisplayAdminOrder($params)
    {
        $sql = 'SELECT `id_address_delivery` FROM `' . _DB_PREFIX_ . 'orders` WHERE `id_order` = '
        . $params['id_order'];
        $deliveryAddressId = $this->db->getValue($sql);

        $sql = 'SELECT `service_name` FROM `' . _DB_PREFIX_ . 'mds_collivery_processed` WHERE `order_id` = '
        . $params['id_order'];
        $carrierName = $this->db->getValue($sql);

        $sql = 'SELECT `service_id` FROM `' . _DB_PREFIX_ . 'mds_collivery_processed` WHERE `order_id` = '
        . $params['id_order'];
        $serviceId = $this->db->getValue($sql);

        $sql = 'SELECT * FROM '._DB_PREFIX_.'address LEFT JOIN '._DB_PREFIX_.'state ON '
        ._DB_PREFIX_.'address.`id_state`='._DB_PREFIX_
        .'state.`id_state` where `id_customer` = 2 AND deleted = 0';
        $deliveryAddresses = $this->db->ExecuteS($sql);


        $sql = 'SELECT * FROM '._DB_PREFIX_.'address LEFT JOIN '._DB_PREFIX_.'state ON '
        ._DB_PREFIX_.'address.`id_state`='._DB_PREFIX_.'state.`id_state` where `id_customer` = 0 AND deleted = 0';
        $collectionAdresses = $this->db->ExecuteS($sql);


        $orderId = $params['id_order'];

        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'address` WHERE `id_address` = ' . $deliveryAddressId;
        $address = $this->db->getRow($sql);

        $suburb = $address['city'];
        $suburbs = $this->collivery->getSuburbs('');


        $locationType = $address['address2'];
        $locationTypes = $this->collivery->getLocationTypes();

        $countryName = "South Africa";

        $this->context->controller->addJS(($this->_path) . 'helper.js');
        $view_data = array(
            'deliveryAddressId'=> $deliveryAddressId,
            'orderId' => $orderId,
            'carrierName' => $carrierName,
            'serviceId' => $serviceId,
            'deliveryAddresses' => $deliveryAddresses,
            'suburb' => $suburb,
            'suburbs' => $suburbs,
            'locationType' => $locationType,
            'locationTypes' => $locationTypes,
            'countryName' => $countryName,
            'token' => Tools::getValue('token'),
            'collectionAdresses' => $collectionAdresses
        );

        $this->context->smarty->assign('htmldata', $view_data);
        $this->html = $this->display(__FILE__, 'views/templates/admin/shipping_control.tpl');

        return $this->html;
    }

    protected function getServiceFromCarrierId($carrierId)
    {
        return \Mds\Prestashop\Settings\Services::getServiceId($carrierId);
    }
}
