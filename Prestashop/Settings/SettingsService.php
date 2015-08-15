<?php namespace Mds\Prestashop\Settings;

use Mds\Prestashop\Collivery\ColliveryApi;
use Mds\Prestashop\Exceptions\InvalidData;

class SettingsService {

	protected $surchargeKeys = array();

	protected $errors = array();

	public function getSurchargesInfo()
	{
		$services = Service::getServices();
		foreach ($services as $serviceId => $serviceName) {
			$surcharges[$serviceId] = array(
				'name' => $serviceName .' Surcharge',
				'value' => Surcharge::getServiceSurcharge($serviceId)
			);
		}

		return $surcharges;
	}

	public function getColliveryEmail()
	{
		return Credentials::getColliveryEmail();
	}

	public function hasRiskCover()
	{
		return RiskCover::hasCover();
	}

	public function testCurrentCredentials()
	{
		$email = Credentials::getColliveryEmail();
		$password = Credentials::getColliveryPassword();

		ColliveryApi::testAuthentication($email, $password);
	}

	public function store($data)
	{
		if (!empty($data['email']) && !empty($data['password'])) {
			$this->updateColliveryCredentials($data['email'], $data['password']);
		}

		$this->updateSurcharges($data['surcharge']);

		RiskCover::setColliveryRiskCover(!empty($data['risk-cover']));

		return $this->errors;
	}

	private function updateColliveryCredentials($email, $password)
	{
		try {
			Credentials::update($email, $password);
		} catch (InvalidData $e) {
			$this->errors[] = $e->getMessage();
		}
	}

	private function updateSurcharges($surcharges)
	{
		foreach ($surcharges as $serviceId => $surcharge) {
			Surcharge::setServiceSurcharge($serviceId, $surcharge);
		}
	}
}
