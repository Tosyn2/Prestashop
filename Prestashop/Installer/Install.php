<?php namespace Mds\Prestashop\Installer;

use Carrier;
use Configuration;
use Group;
use Language;
use Mds\Prestashop\Exceptions\UnableToUpdateConfiguration;
use Mds\Prestashop\Exceptions\UnmetSystemRequirements;
use Mds\Prestashop\Settings\Credentials;
use Mds\Prestashop\Settings\RiskCover;
use Mds\Prestashop\Settings\Services;
use Mds\Prestashop\Settings\Surcharge;
use RangePrice;
use RangeWeight;
use Zone;

class Install extends Installer {

	public function install()
	{
		$this->checkSystemRequirements();
		$services = Services::get();
		foreach ($services as $serviceId => $serviceName) {
			$carrierId = $this->setupNewCarrier($serviceName);
			$this->copyServiceLogos($serviceId, $carrierId);
			Services::set($serviceId, $carrierId);
			Surcharge::set($serviceId, 10);
		}

		Credentials::set('api@collivery.co.za', 'api123');
		RiskCover::set(false);

		$this->addIdMdsColumnToStatesTable();
		$this->setZaContainsStates();
		$this->addMdsTransactionTable();
		$this->addMdsManufacturer();
	}

	/**
	 * @return array
	 */
	private function checkSystemRequirements()
	{
		$errors = array();
		if (version_compare(PHP_VERSION, '5.3.0') < 0) {
			$errors[] = 'MDS Collivery requires PHP 5.3 in order to run. Please upgrade before installing.';
		}

		if (!extension_loaded('soap')) {
			$errors[] = 'MDS Collivery requires SOAP to be enabled on the server. Please make sure its enabled before installing.';
		}

		if (!empty($errors)) {
			throw new UnmetSystemRequirements($errors);
		}
	}

	private function setupNewCarrier($serviceName)
	{
		$carrier = $this->addCarrier($serviceName);

		$carrierId = (int) $carrier->id;

		$this->setupGroups($carrierId);

		$rangePrice = $this->createNewPriceRange($carrierId);
		$rangeWeight = $this->createNewWeightRange($carrierId);
		$this->setupZones($carrierId, $rangePrice->id, $rangeWeight->id);

		return $carrierId;
	}

	/**
	 * @param $serviceName
	 *
	 * @return \Carrier
	 */
	private function addCarrier($serviceName)
	{
		$carrier = new Carrier();
		$carrier->name = $serviceName;
		$carrier->id_tax_rules_group = 0;
		$carrier->active = 1;
		$carrier->deleted = 0;
		$carrier->shipping_handling = 1;
		$carrier->range_behavior = 1;
		$carrier->is_module = 1;
		$carrier->shipping_external = 1;
		$carrier->external_module_name = 'mds';
		$carrier->need_range = 1;

		$lang_id = Language::getIdByIso('en');
		$carrier->delay[ $lang_id ] = $serviceName;
		$carrier->add();

		return $carrier;
	}

	/**
	 * @param $carrierId
	 *
	 * @throws \PrestaShopDatabaseException
	 */
	private function setupGroups($carrierId)
	{
		$groups = Group::getGroups(true);
		foreach ($groups as $group) {
			$groupId = (int) $group['id_group'];
			$this->addCarrierGroup($carrierId, $groupId);
		}
	}

	/**
	 * @param $carrierId
	 * @param $groupId
	 *
	 * @throws \PrestaShopDatabaseException
	 */
	private function addCarrierGroup($carrierId, $groupId)
	{
		$this->db->insert('carrier_group', array(
				'id_carrier' => $carrierId,
				'id_group'   => $groupId
			)
		);
	}

	/**
	 * @param $carrierId
	 *
	 * @return \RangePrice
	 */
	protected function createNewPriceRange($carrierId)
	{
		$rangePrice = new RangePrice();
		$rangePrice->id_carrier = $carrierId;
		$rangePrice->delimiter1 = '0';
		$rangePrice->delimiter2 = '10000';
		$rangePrice->add();

		return $rangePrice;
	}

	/**
	 * @param $carrierId
	 *
	 * @return \RangeWeight
	 */
	protected function createNewWeightRange($carrierId)
	{
		$rangeWeight = new RangeWeight();
		$rangeWeight->id_carrier = $carrierId;
		$rangeWeight->delimiter1 = '0';
		$rangeWeight->delimiter2 = '10000';
		$rangeWeight->add();

		return $rangeWeight;
	}

	/**
	 * @param $carrierId
	 * @param $rangePriceId
	 * @param $rangeWeightId
	 */
	protected function setupZones($carrierId, $rangePriceId, $rangeWeightId)
	{
		$zones = Zone::getZones(true);
		foreach ($zones as $zone) {
			$zoneId = (int) $zone['id_zone'];
			$this->addCarrierZone($carrierId, $zoneId);
			$this->addDeliveryPriceRange($carrierId, $rangePriceId, $zoneId);
			$this->addDeliveryWeightRange($carrierId, $rangeWeightId, $zoneId);
		}
	}

	/**
	 * @param $carrierId
	 * @param $zoneId
	 *
	 * @throws \PrestaShopDatabaseException
	 */
	protected function addCarrierZone($carrierId, $zoneId)
	{
		$this->db->insert('carrier_zone', array(
				'id_carrier' => $carrierId,
				'id_zone'    => $zoneId
			)
		);
	}

	/**
	 * @param $carrierId
	 * @param $rangePriceId
	 * @param $zoneId
	 *
	 * @throws \PrestaShopDatabaseException
	 */
	protected function addDeliveryPriceRange($carrierId, $rangePriceId, $zoneId)
	{
		$this->db->insert('delivery', array(
				'id_carrier'      => $carrierId,
				'id_range_price'  => $rangePriceId,
				'id_range_weight' => null,
				'id_zone'         => $zoneId,
				'price'           => '0'
			),
			true // Null Values
		);
	}

	/**
	 * @param $carrierId
	 * @param $rangeWeightId
	 * @param $zoneId
	 *
	 * @throws \PrestaShopDatabaseException
	 */
	protected function addDeliveryWeightRange($carrierId, $rangeWeightId, $zoneId)
	{
		$this->db->insert('delivery', array(
				'id_carrier'      => $carrierId,
				'id_range_price'  => null,
				'id_range_weight' => $rangeWeightId,
				'id_zone'         => $zoneId,
				'price'           => '0'
			),
			true // Null Values
		);
	}

	/**
	 * @return string
	 */
	private function addIdMdsColumnToStatesTable()
	{
		$table = _DB_PREFIX_ . 'state';
		$sql = "SELECT * FROM {$table} WHERE id_mds";
		$hasIdMds = $this->db->query($sql);
		if ( ! $hasIdMds) {
			$sql = "ALTER TABLE {$table} ADD id_mds INT NULL AFTER  iso_code";
			$this->db->execute($sql);

			return $sql;
		}
	}

	/**
	 * @return string
	 */
	private function addMdsTransactionTable()
	{
		$sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mds_collivery_processed` (
						`id_order` int(11) NOT NULL AUTO_INCREMENT,
						`id_collection_address` int(11) NOT NULL,
						`id_delivery_address` int(11) NOT NULL,
						`id_service` TEXT NOT NULL,
						`waybill` int(1) NOT NULL DEFAULT 1,
						PRIMARY KEY (`id`)
						) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';
		$this->db->execute($sql);
		return $sql;

	}


	private function addMdsManufacturer()
	{
		$sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'manufacturer` WHERE `name` = "MDS Collection Addresses" ';
		$mdsManufacturer = $this->db->getValue($sql);

		if(!$mdsManufacturer) {

			$this->db->insert('manufacturer', array(
					'name' => "MDS Collection Addresses",
					'active' => 1
				)
			);

			$sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'manufacturer` WHERE `name` = "MDS Collection Addresses" ';
			$mdsManufacturer = $this->db->getValue($sql);

			$sql = 'SELECT `id_shop` FROM `' . _DB_PREFIX_ . 'shop` WHERE `active` = 1 ';
			$idShop = $this->db->getValue($sql);

			$this->db->insert('manufacturer_shop', array(
					'id_manufacturer' => $mdsManufacturer,
					'id_shop' => $idShop
				)
			);

			$sql = 'SELECT `id_lang` FROM `' . _DB_PREFIX_ . 'lang_shop` WHERE `id_shop` =' . $idShop;
			$idLang = $this->db->getValue($sql);

			$this->db->insert('manufacturer_lang', array(
					'id_manufacturer' => $mdsManufacturer,
					'id_lang' => $idLang
				)
			);

		}

	}

	/**
	 * @param $serviceId
	 * @param $carrierId
	 */
	private function copyServiceLogos($serviceId, $carrierId)
	{
		$mdsIconsDirectory = _MDS_DIR_ . '/icons';
		$prestashopImageDirectory = _PS_SHIP_IMG_DIR_;

		copy("{$mdsIconsDirectory}/{$serviceId}.jpg", "{$prestashopImageDirectory}/{$carrierId}.jpg");
	}

	private function setZaContainsStates()
	{
		$table = _DB_PREFIX_ . 'country';
		$sql = "UPDATE {$table} SET contains_states= 1 WHERE iso_code= 'ZA'";
		$this->db->execute($sql);
	}
}
