<?php namespace Mds\Prestashop\Transaction;

use Mds\Prestashop;
use Mds;
use Mds_Services;
use Mds_ColliveryApi;
use SoapClient;
use Db;
use Mds_RiskCover;
use Mds_Surcharge;
use MdsColliveryService;

class TransactionTable extends Transaction {

	protected $mdsColliveryService;

	/**
	 * @param $params
	 */
	protected $collivery;

	public function __construct(\Db $db)
	{
		parent::__construct($db);
		$this->collivery = Mds_ColliveryApi::getInstance();
		$this->mdsColliveryService = Mds\MdsColliveryService::getInstance();

	}
	public function createTransaction($params)
	{


		new SoapClient(
			'http://www.collivery.co.za/wsdl/v2',
			array('cache_wsdl' => WSDL_CACHE_NONE)
		);

		$orderId = $params['objOrder']->id;
		$deliveryAddressId = $params['objOrder']->id_address_delivery;

		$carrierId = $params['objOrder']->id_carrier;
		$sql = 'SELECT `name` FROM `' . _DB_PREFIX_ . 'carrier` WHERE `id_carrier` = ' . $carrierId;
		$carrierName = $this->db->getValue($sql);
		$serviceId = Mds_Services::getServiceId($carrierId);

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

		$postCode = $defaultAddress['zip_code'];

		$city = $defaultAddress['suburb_name'];

		$phone = $contact['phone'];
		$mobile = $contact['cellphone'];

		$sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'state where `id_mds` = "' . $defaultAddress['town_id'] . '" AND `active` = 1';
		$state = $this->db->getRow($sql);

		$state_id = $state['id_state'];
		$state_name = $state['name'];

		$sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'address` WHERE `alias` = "Default MDS Collection Address"';
		$defaultMdsAddressPsId = $this->db->getRow($sql);

		$sql = 'SELECT `id_manufacturer` FROM ' . _DB_PREFIX_ . 'manufacturer where `name` = "MDS Collection Addresses" AND `active` = 1';
		$mdsManufacturerId = $this->db->getValue($sql);

		if ( ! $defaultMdsAddressPsId) {

			$sql = 'INSERT INTO ' . _DB_PREFIX_ . 'address (id_country,id_state,id_customer,id_manufacturer,id_supplier,id_warehouse,alias,company,lastname,firstname,address1,address2,postcode,city,other,phone,phone_mobile,active,deleted)
			VALUES
			(30, \'' . $state_id . '\',0,\'' . $mdsManufacturerId . '\',0,0,"Default MDS Collection Address","", \'' . $last_name . '\', \'' . $first_name . '\', \'' . $streetAddress . '\' , "", \'' . $postCode . '\' , \'' . $city . '\',\'' . $locationType . '\', \'' . $phone . '\', \'' . $mobile . '\',1,0)';
			$this->db->execute($sql);

		} else {

			$addressStringPs = $defaultMdsAddressPsId['address1'] . $defaultMdsAddressPsId['city'] . $state_name . $defaultMdsAddressPsId['postcode'] . $defaultMdsAddressPsId['firstname'] . " " . $defaultMdsAddressPsId['lastname'];
			$hashPs = hash('md5', $addressStringPs);
			$hashPs = substr($hashPs, 0, 15);

			$addressStringMds = $defaultAddress['street'] . $defaultAddress['suburb_name'] . $defaultAddress['town_name'] . $defaultAddress['zip_code'] . $contact['full_name'];
			$hashMds = hash('md5', $addressStringMds);
			$hashMds = substr($hashMds, 0, 15);

			if ($hashMds != $hashPs) {

				$sql = 'UPDATE ' . _DB_PREFIX_ . 'address SET `id_state` = \'' . $state_id . '\', `lastname` = \'' . $last_name . '\' ,`firstname` =  \'' . $first_name . '\'  ,`address1` =  \'' . $defaultAddress['street'] . '\' , `other` =  \'' . $locationType . '\',`postcode` =  \'' . $defaultAddress['zip_code'] . '\',`city` =  \'' . $defaultAddress['suburb_name'] . '\' ,`phone` =  \'' . $phone . '\',`phone_mobile` = \'' . $mobile . '\' where `id_address` =  \'' . $defaultMdsAddressPsId['id_address'] . '\'';
				$this->db->execute($sql);

			}

		}

		$sql = 'INSERT INTO ' . _DB_PREFIX_ . 'mds_collivery_processed(id_order,id_collection_address,id_service,id_delivery_address)
		VALUES
		(\'' . $orderId . '\',\'' . $defaultMdsAddressPsId['id_address'] . '\',\'' . $serviceId . '\', \'' . $deliveryAddressId . '\')';

		return $this->db->execute($sql);
	}

	/**
	 * @param $params
	 *
	 * @return mixed
	 */
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

	/**
	 * @param $params
	 *
	 * @return array
	 */
	public function addControlColliveryAddressTo($params)
	{

		$sql = 'SELECT `id_delivery_address` FROM ' . _DB_PREFIX_ . 'mds_collivery_processed
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

		$colliveryParams = array();
		$colliveryParams = $this->setColliveryParamsArray($addressRow, $colliveryParams, $mds_town_id, $hash);

		$sql = 'SELECT email FROM ' . _DB_PREFIX_ . 'customer
		WHERE id_customer = \'' . $params['cart']->id_customer . '\'';
		$colliveryParams['email'] = $this->db->getValue($sql);

		try {
			return $this->mdsColliveryService->addColliveryAddress($colliveryParams);
		} catch (Exception $e) {
			die($e->getMessage());
		}

	}

	/**
	 * @param $params
	 *
	 * @return array
	 */
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

		$colliveryParams = array();
		$colliveryParams = $this->setColliveryParamsArray($addressRow, $colliveryParams, $mds_town_id, $hash);

		$sql = 'SELECT email FROM ' . _DB_PREFIX_ . 'customer
		WHERE id_customer = \'' . $params['cart']->id_customer . '\'';
		$colliveryParams['email'] = $this->db->getValue($sql);

		try {
			return $this->mdsColliveryService->addColliveryAddress($colliveryParams);
		} catch (Exception $e) {
			die($e->getMessage());
		}

	}

	/**
	 * @param $value
	 * @param $idOrder
	 */
	public function changeDeliveryAddress($value, $idOrder)
	{

		if ($result = $this->db->update(
			'mds_collivery_processed',
			array('id_delivery_address' => trim($value)),
			'`id_order` = ' . trim($idOrder)
		)
		) {
			$this->db->update('orders', array('id_address_delivery' => trim($value)), '`id_order` = ' . trim($idOrder));
		}

		return;
	}

	/**
	 * @param $value
	 * @param $idOrder
	 */
	public function changeCollectionAddress($value, $idOrder)
	{

		$sql = 'UPDATE ' . _DB_PREFIX_ . 'mds_collivery_processed SET `id_collection_address` = \'' . $value . '\' where `id_order` =  \'' . $idOrder . '\'';
		$this->db->execute($sql);

		return;

	}

	/**
	 * @param $waybill
	 *
	 * @return array
	 */
	public function getDeliveryStatus($waybill)
	{
		$status = $this->collivery->getStatus($waybill);

		return $status;

	}

	/**
	 * @param $params
	 * @param $idOrder
	 *
	 * @return bool|void
	 */
	public function despatchDelivery($params, $idOrder)
	{
		$sql = 'SELECT `waybill` FROM `' . _DB_PREFIX_ . 'mds_collivery_processed` WHERE `id_order` = ' . $params['id_order'];
		$waybill = $this->db->getValue($sql);

		if ( ! $waybill) {

			try {
				$orderParams = $this->buildColliveryControlDataArray($params);
				if (Mds_RiskCover::hasCover()) {
					$orderParams['cover'] = 1;
				}
				$waybill = $this->mdsColliveryService->addCollivery($orderParams, true);

				$sql = 'UPDATE ' . _DB_PREFIX_ . 'mds_collivery_processed SET `waybill` = \'' . $waybill . '\' where `id_order` =  \'' . $idOrder . '\'';
				$this->db->execute($sql);

				return;

			} catch (\Mds\Prestashop\Exceptions\InvalidData $e) {
				return false;
			}

		}

	}

	/**
	 * @param $params
	 *
	 * @return bool
	 */
	public function getQuote($params)
	{
		try {
			$orderParams = $this->buildColliveryControlDataArray($params);
			if (Mds_RiskCover::hasCover()) {
				$orderParams['cover'] = 1;
			}

			$price = $this->getShippingCost($orderParams);

			return $price;

		} catch (\Mds\Prestashop\Exceptions\InvalidData $e) {
			return false;
		}

	}

	public function addTownsToPsDb()
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

	/**
	 * @param $params
	 * @param $shipping_costmds
	 *
	 * @return bool
	 */
	public function getShoppingCartQuote($params, $shipping_cost, $carrierId)
	{

		try {
			$prices = array();

			$orderParams = $this->buildColliveryGetPriceArray($params);

			$serviceId = Mds_Services::getServiceId($carrierId);
			$orderParams['service'] = $serviceId;

			if (Mds_RiskCover::hasCover()) {
				$orderParams['cover'] = 1;

			}
			$price = $this->getShippingCost($orderParams);

			$surchargePerc = Mds_Surcharge::get($serviceId);
			$price = $price * (1 + ($surchargePerc / 100));
			$shippingPrice = $shipping_cost + $price;

			$prices[ $carrierId ] = $shippingPrice;

			return $prices;


		} catch (\Mds\Prestashop\Exceptions\InvalidData $e) {
			return false;
		}
	}

	/**
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

		$colliveryParams = array();
		$colliveryParams = $this->setColliveryParamsArray($addressRow, $colliveryParams, $mds_town_id, $hash);

		$sql = 'SELECT email FROM ' . _DB_PREFIX_ . 'customer
		WHERE id_customer = \'' . $params['cart']->id_customer . '\'';
		$colliveryParams['email'] = $this->db->getValue($sql);

		try {
			return $this->mdsColliveryService->addColliveryAddress($colliveryParams);
		} catch (\SoapFault $e) {

			return false;
		}
	}

	/**
	 * @param $params
	 *
	 * @return mixed
	 */
	function getDefaultColliveryAddressFrom($params)
	{


		$colliveryAddressesFrom = $this->mdsColliveryService->returnDefaultAddress();

		return array_pop($colliveryAddressesFrom['contacts']);
	}

	/**
	 * @param $params
	 *
	 * @return mixed
	 */
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

	/**
	 * @param $params
	 *
	 * @return array
	 */
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

		$location_types = $this->collivery->getLocationTypes();

		$colliveryGetPriceArray = Array();
		$colliveryGetPriceArray['to_town_id'] = $mds_town_id;
		$colliveryGetPriceArray ['collivery_type'] = 2;

		$colliveryGetPriceArray['to_location_type'] = (int) array_search($addressRow['other'], $location_types);

		$colliveryGetPriceArray['collivery_from'] = $colliveryAddressFrom['address_id'];

		$colliveryGetPriceArray = $this->getParcels($cartProducts, $colliveryGetPriceArray);
		return $colliveryGetPriceArray;
	}

	/**
	 * @param $carrierId
	 *
	 * @return mixed
	 */
	protected function getServiceFromCarrierId($carrierId)
	{
		return (string) Mds_Services::getServiceId($carrierId);

	}

	/**
	 * @param $addressRow
	 * @param $colliveryParams
	 * @param $mds_town_id
	 * @param $hash
	 *
	 * @return mixed
	 */
	private function setColliveryParamsArray($addressRow, $colliveryParams, $mds_town_id, $hash)
	{
		$colliveryParams['company_name'] = $addressRow['company'];
		$colliveryParams['building'] = '';
		$colliveryParams['street'] = $addressRow['address1'];
		$colliveryParams['location_type'] = $addressRow['other'];
		$colliveryParams['suburb'] = $addressRow['city'];
		$colliveryParams['town'] = $mds_town_id;
		$colliveryParams['zip_code'] = $addressRow['postcode'];
		$colliveryParams['full_name'] = $addressRow['firstname'] . " " . $addressRow['lastname'];
		$colliveryParams['phone'] = $addressRow['phone'];
		$colliveryParams['cellphone'] = $addressRow['phone_mobile'];
		$colliveryParams['custom_id'] = $addressRow['id_address'] . "|" . $hash;

		return $colliveryParams;
	}

	/**
	 * @param $orderParams
	 *
	 * @return mixed
	 */
	private function getShippingCost($orderParams)
	{
		$colliveryPriceOptions = $this->collivery->getPrice($orderParams);
		$colliveryPrice = $colliveryPriceOptions['price']['inc_vat'];
		$price = $colliveryPrice;

		return $price;
	}

	/**
	 * @param $cartProducts
	 * @param $colliveryGetPriceArray
	 *
	 * @return mixed
	 */
	private function getParcels($cartProducts, $colliveryGetPriceArray)
	{
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
}
