<?php namespace Mds\Prestashop\Installer;

use Carrier;
use Configuration;
use Group;
use Language;
use Mds\Prestashop\Exceptions\UpdatingConfigurationException;
use RangePrice;
use RangeWeight;
use Zone;

class Install extends Installer  {

	public function install()
	{
		foreach ($this->services as $serviceId => $serviceName) {
			$carrierId = $this->setupNewCarrier($serviceName);
			$this->copyServiceLogos($serviceId, $carrierId);
			$this->updateConfig("MDS_SERVICE_CARRIER_ID_{$serviceId}", $carrierId);
			$this->updateConfig("MDS_SERVICE_SURCHARGE_{$serviceId}", '0');
		}

		foreach ($this->config as $key => $value) {
			$this->updateConfig($key, $value);
		}

		$this->addIdMdsColumnToStatesTable();
		$this->setZaContainsStates();
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

	private function updateConfig($key, $value)
	{
		if (!Configuration::updateValue($key, $value)) {
			throw new UpdatingConfigurationException();
		}
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
