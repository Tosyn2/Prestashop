<?php

namespace helperClasses;

use Exception;

/**
 * MdsColliveryService
 */
class MdsColliveryService
{
	/**
	 * self
	 */
	private static $instance;

	/**
	 * @type
	 */
	var $collivery;

	/**
	 * @type
	 */
	var $cache;

	/**
	 * @type
	 */
	var $validated_data;

	/**
	 * @type
	 */
	var $settings;

	/**
	 * @type
	 */
//	var $WC_MDS_Collivery;

	/**
	 * @param null $settings
	 * @return MdsColliveryService
	 * @throws Exception
	 */
	public static function getInstance($settings = null)
	{
		//	if (! self::$instance) {

		self::$instance = new self($settings);
		//	}

		return self::$instance;
	}

	private function __construct($settings)
	{
		require_once("Cache.php");
		require_once("Collivery.php");
		require_once("UnitConverter.php");

		$this->settings = $settings;

		$this->converter = new UnitConverter();

		$this->cache = new Cache();

		//	$this->collivery = new Collivery();

		$this->initMdsCollivery($this->settings);
	}

	/**
	 * Instantiates the MDS Collivery class
	 *
	 * @param null|array $settings
	 */
	public function initMdsCollivery($settings = null)
	{
		if ($settings) {
			$username = $settings['mds_user'];
			$password = $settings['mds_pass'];
		} else {
			$username = $this->settings['mds_user'];
			$password = $this->settings['mds_pass'];

		}

		$this->collivery = new Collivery(array(

			'demo' => false,
		));
	}

	/**
	 * Work through our shopping cart
	 * Convert lengths and weights to desired unit
	 *
	 * @param $package
	 * @return null|array
	 */


	/**
	 * Validate the package before using the package to get prices
	 *
	 * @param $package
	 * @return bool
	 */


	/**
	 * Used to build the package for use out of the shipping class
	 *
	 * @return array
	 */


	/**
	 * Work through our order items and return an array of parcels
	 *
	 * @param $items
	 * @return array
	 */


	/**
	 * @param WC_Order $order
	 * @param $message
	 * @param $processing
	 * @param null $status
	 */


	/**
	 * Adds the delivery request to MDS Collivery
	 *
	 * @param array $array
	 * @param bool $accept
	 * @return bool
	 */
	public function addCollivery(array $array, $accept = true)
	{
		$this->validated_data = $this->validateCollivery($array);

		if (isset($this->validated_data['time_changed']) && $this->validated_data['time_changed'] == 1) {
			$id = $this->validated_data['service'];
			$services = $this->collivery->getServices();

			if (!empty($this->settings["wording_$id"])) {
				$reason = preg_replace('|' . preg_quote($services[$id]) . '|', $this->settings["wording_$id"], $this->validated_data['time_changed_reason']);
			} else {
				$reason = $this->validated_data['time_changed_reason'];
			}

			$reason = preg_replace('|collivery|i', 'delivery', $reason);
			$reason = preg_replace('|The delivery time has been CHANGED to|i', 'the approximate delivery day is', $reason);

		}

		$collivery_id = $this->collivery->addCollivery($this->validated_data);

		if ($accept) {
			return ($this->collivery->acceptCollivery($collivery_id)) ? $collivery_id : false;
		}

		return $collivery_id;
	}

	/**
	 * Validate delivery request before adding the request to MDS Collivery
	 *
	 * @param array $array
	 * @throws Exception
	 * @return bool|array
	 */
	public function validateCollivery(array $array)
	{
		if (empty($array['collivery_from'])) {
			throw new Exception("Invalid collection address");
		}

		if (empty($array['collivery_to'])) {
			throw new Exception("Invalid destination address");
		}

		if (empty($array['contact_from'])) {
			throw new Exception("Invalid collection contact");
		}

		if (empty($array['contact_to'])) {
			throw new Exception("Invalid destination contact");
		}

		if (empty($array['collivery_type'])) {
			throw new Exception("Invalid parcel type");
		}

		if (empty($array['service'])) {
			throw new Exception("Invalid service");
		}

		if ($array['cover'] != 1 && $array['cover'] != 0) {
			throw new Exception("Invalid risk cover option");
		}

		if (empty($array['parcels']) || !is_array($array['parcels'])) {
			throw new Exception("Invalid parcels");
		}

		return $this->collivery->validate($array);
	}

	/**
	 * @param $order_id
	 * @param bool $processing
	 */

	/**
	 * Adds an address to MDS Collivery
	 *
	 * @param array $array
	 * @return array
	 * @throws Exception
	 */
	public function addColliveryAddress(array $array)
	{

		$towns = $this->collivery->getTowns();
		$location_types = $this->collivery->getLocationTypes();

		if (!is_numeric($array['town'])) {
			$town_id = (int)array_search($array['town'], $towns);
		} else {
			$town_id = $array['town'];
		}

		$suburbs = $this->collivery->getSuburbs($town_id);

		if (!is_numeric($array['suburb'])) {
			$suburb_id = (int)array_search($array['suburb'], $suburbs);
		} else {
			$suburb_id = $array['suburb'];
		}

		if (!is_numeric($array['location_type'])) {
			$location_type_id = (int)array_search($array['location_type'], $location_types);
		} else {
			$location_type_id = $array['location_type'];
		}

		if (empty($array['location_type']) || !isset($location_types[$location_type_id])) {
			throw new Exception("Invalid location type");
		}

		if (empty($array['town']) || !isset($towns[$town_id])) {
			throw new Exception("Invalid town");
		}

		if (empty($array['suburb']) || !isset($suburbs[$suburb_id])) {
			throw new Exception("Invalid suburb");
		}

		if (empty($array['cellphone']) || !is_numeric($array['cellphone'])) {
			throw new Exception("Invalid cellphone number");
		}

		if (empty($array['email']) || !filter_var($array['email'], FILTER_VALIDATE_EMAIL)) {
			throw new Exception("Invalid email address");
		}

		$newAddress = array(
			'company_name' => $array['company_name'],
			'building' => $array['building'],
			'street' => $array['street'],
			'location_type' => $location_type_id,
			'suburb_id' => $suburb_id,
			'town_id' => $town_id,
			'full_name' => $array['full_name'],
			'phone' => (!empty($array['phone'])) ? $array['phone'] : '',
			'cellphone' => $array['cellphone'],
			'custom_id' => 'new_test_custom_id',
			'email' => $array['email'],
		);


		// Before adding an address lets search MDS and see if we have already added this address
		$searchAddresses = $this->searchAndMatchAddress([
			'custom_id' => 'new_test_custom_id',
			'suburb_id' => $suburb_id,
			'town_id' => $town_id,
		], $newAddress);

		if (is_array($searchAddresses)) {
			return $searchAddresses;
		} else {
			$this->cache->clear(['addresses', 'contacts']);
			return $this->collivery->addAddress($newAddress);
		}
	}

	/**
	 * Searches for an address and matches each important field
	 *
	 * @param array $filters
	 * @param array $newAddress
	 * @return bool
	 */
	public function searchAndMatchAddress(array $filters, array $newAddress)
	{
		$searchAddresses = $this->collivery->getAddresses($filters);
		if (!empty($searchAddresses)) {
			$match = true;

			$matchAddressFields = array(
				'company_name' => 'company_name',
				'building_details' => 'building',
				'street' => 'street',
				'location_type' => 'location_type',
				'suburb_id' => 'suburb_id',
				'town_id' => 'town_id',
				'custom_id' => 'custom_id',
			);

			foreach ($searchAddresses as $address) {
				foreach ($matchAddressFields as $mdsField => $newField) {
					if ($address[$mdsField] != $newAddress[$newField]) {
						$match = false;
					}
				}

				if ($match) {
					if (!isset($address['contact_id'])) {
						$contacts = $this->collivery->getContacts($address['address_id']);
						list($contact_id) = array_keys($contacts);
						$address['contact_id'] = $contact_id;
					}

					return $address;
				}
			}
		} else {
			$this->collivery->clearErrors();
		}

		return false;
	}

	/**
	 * Get Town and Location Types for Checkout selects from MDS
	 */
	public function returnFieldDefaults()
	{
		$towns = $this->collivery->getTowns();
		$location_types = $this->collivery->getLocationTypes();
		return array('towns' => array_combine($towns, $towns), 'location_types' => array_combine($location_types, $location_types));
	}

	/**
	 * Returns the MDS Collivery class
	 *
	 * @param null $settings
	 *
	 * @return \Mds\Collivery
	 */
	public function returnColliveryClass($settings = null)
	{
		if ($settings) {
			$username = $settings['mds_user'];
			$password = $settings['mds_pass'];
		} else {
			$username = $this->settings['mds_user'];
			$password = $this->settings['mds_pass'];

		}

		return $this->collivery = new Collivery(array(
			'app_name' => 'Default App Name', // Application Name
			'app_version' => '0.0.1',            // Application Version
			'app_host' => '', // Framework/CMS name and version, eg 'Wordpress 3.8.1 WooCommerce 2.0.20' / 'Joomla! 2.5.17 VirtueMart 2.0.26d'
			'app_url' => '', // URL your site is hosted on
			'user_email' => $username,
			'user_password' => $password,
			'demo' => false,
		));
	}

	/**
	 * Returns the MDS Cache class
	 *
	 * @return \Mds\Cache
	 */
	public function returnCacheClass()
	{
		return $this->cache;
	}

	/**
	 * Returns the UnitConverter class
	 *
	 * @return UnitConverter
	 */
	public function returnConverterClass()
	{
		return $this->converter;
	}

	/**
	 * Gets default address of the MDS Account
	 *
	 * @return array
	 */
	public function returnDefaultAddress()
	{
		$default_address_id = $this->collivery->getDefaultAddressId();
		$data = array(
			'address' => $this->collivery->getAddress($default_address_id),
			'default_address_id' => $default_address_id,
			'contacts' => $this->collivery->getContacts($default_address_id)
		);
		return $data;
	}

	/**
	 * @return null|array
	 */
	public function returnColliveryValidatedData()
	{
		return $this->validated_data;
	}

	/**
	 * Adds markup to price
	 *
	 * @param $price
	 * @param $markup
	 * @return float|string
	 */
	public function addMarkup($price, $markup)
	{
		$price += $price * ($markup / 100);
		return (isset($this->settings['round']) && $this->settings['round'] == 'yes') ? $this->round($price) : $this->format($price);
	}

	/**
	 * Format a number with grouped thousands
	 *
	 * @param $price
	 * @return string
	 */
	public function format($price)
	{
		return number_format($price, 2, '.', '');
	}

	/**
	 * Rounds number up to the next highest integer
	 *
	 * @param $price
	 * @return float
	 */
	public function round($price)
	{
		return ceil($this->format($price));
	}

}
