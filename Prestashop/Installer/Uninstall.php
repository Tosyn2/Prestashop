<?php namespace Mds\Prestashop\Installer;

use Carrier;
use Configuration;

class Uninstall extends Installer {

	public function uninstall()
	{
		$this->deleteServicesConfig();
		$this->deleteMdsConfig();
		$this->setCarriersDeleted();
	}

	private function deleteServicesConfig()
	{
		foreach ($this->services as $serviceId => $serviceName) {
			$this->deleteConfig('MDS_SERVICE_SURCHARGE_' . $serviceId);
			$carrierId = Configuration::get('MDS_SERVICE_CARRIER_ID_' . $serviceId);

			if (Configuration::get('PS_CARRIER_DEFAULT') == $carrierId) {
				$this->setDefaultCarrierToPsCarrier();
			}
			$this->deleteConfig('MDS_SERVICE_CARRIER_ID_' . $serviceId);
		}
	}

	private function deleteConfig($key)
	{
		Configuration::deleteByName($key);
	}

	private function setCarriersDeleted()
	{
		$sql = 'UPDATE '. _DB_PREFIX_ .'carrier SET `deleted` = 1 WHERE `external_module_name` = "mds";';
		$this->db->execute($sql);
	}

	private function deleteMdsConfig()
	{
		foreach ($this->config as $key => $value) {
			$this->deleteConfig($key);
		}
	}

	private function setDefaultCarrierToPsCarrier()
	{
		global $cookie;

		$PsCarriers = Carrier::getCarriers($cookie->id_lang, true);
		foreach ($PsCarriers as $PsCarrier) {
			if ($PsCarrier['name'] != 'mds') {
				Configuration::updateValue('PS_CARRIER_DEFAULT', $PsCarrier['id_carrier']);
			}
		}
	}
}
