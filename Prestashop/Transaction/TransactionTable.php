<?php namespace Mds\Prestashop\Transaction;

use Mds\Prestashop;
use Mds;
use Mds_Services;
use Mds_ColliveryApi;
use SoapClient;
use SoapFault;

class TransactionTable extends Transaction {

	/**
	 * @param $params
	 */
	public function createTransaction($params)
	{
		$this->collivery = Mds_ColliveryApi::getInstance();

		try {
			$this->client = new SoapClient( // Setup the soap client
				'http://www.collivery.co.za/wsdl/v2', // URL to WSDL File
				array('cache_wsdl' => WSDL_CACHE_NONE) // Don't cache the WSDL file
			);
		} catch (SoapFault $e) {
			echo "Unable to connect to the API, plugin not operational";

			return false;
		}

		$orderId = $params[ objOrder ]->id;
		$deliveryAddressId = $params[ objOrder ]->id_address_delivery;

		$carrierId = $params[ objOrder ]->id_carrier;
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
		$mobile = $contact['phone_mobile'];

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

		$sql = 'INSERT INTO ' . _DB_PREFIX_ . 'mds_collivery_processed(id_order,id_collection_address,id_service,id_delivery_address,status)
		VALUES
		(\'' . $orderId . '\',\'' . $defaultMdsAddressPsId['id_address'] . '\',\'' . $serviceId . '\', \'' . $deliveryAddressId . '\', "Not yet sent")';
		$this->db->execute($sql);
	}

}
