<?php namespace Mds\Prestashop\Installer;

use Carrier;
use Configuration;
use Mds\Prestashop\Settings\Credentials;
use Mds\Prestashop\Settings\RiskCover;
use Mds\Prestashop\Settings\Service;
use Mds\Prestashop\Settings\Surcharge;

class Uninstall extends Installer {

	public function uninstall()
	{
		$this->deleteServicesConfig();
		Credentials::delete();
		RiskCover::delete();
		$this->setCarriersDeleted();
	}

	private function deleteServicesConfig()
	{
		$services = Service::getServices();
		foreach ($services as $serviceId => $serviceName) {
			Surcharge::delete($serviceId);
			$carrierId = Service::getCarrierIdFromServiceId($serviceId);

			if (Configuration::get('PS_CARRIER_DEFAULT') == $carrierId) {
				$this->setDefaultCarrierToPsCarrier();
			}
			Service::delete($serviceId);
		}
	}

	private function setCarriersDeleted()
	{
		$sql = 'UPDATE '. _DB_PREFIX_ .'carrier SET `deleted` = 1 WHERE `external_module_name` = "mds";';
		$this->db->execute($sql);
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
