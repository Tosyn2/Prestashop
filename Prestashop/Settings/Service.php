<?php namespace Mds\Prestashop\Settings;

use Mds\Prestashop\Exceptions\InvalidData;

class Service extends Settings {

	public static function getServices()
	{
		return self::$services;
	}

	public static function getServiceIdFromCarrierId($carrierId)
	{
		$serviceMappings = self::getServiceMappings();

		if (!array_key_exists($carrierId, $serviceMappings)) {
			throw new InvalidData('Invalid Carrier Id: '. $carrierId);
		}

		return $serviceMappings[$carrierId];
	}

	/**
	 * @return array
	 */
	private static function getServiceMappings()
	{
		$serviceMappings = array();

		foreach (self::$services as $serviceId => $serviceName) {
			$carrierId = self::getCarrierIdFromServiceId($serviceId);
			$serviceMappings[$carrierId] = $serviceId;
		}

		return $serviceMappings;
	}

	/**
	 * @param $serviceId
	 *
	 * @return string
	 */
	public static function getCarrierIdFromServiceId($serviceId)
	{
		return self::getConfig(self::getCarrierKey($serviceId));
	}

	public static function setCarrierId($serviceId, $carrierId)
	{
		self::updateConfig(self::getCarrierKey($serviceId), $carrierId);
	}

	public static function delete($serviceId)
	{
		self::deleteConfig(self::getCarrierKey($serviceId));
	}

	/**
	 * @param $serviceId
	 *
	 * @return string
	 */
	private static function getCarrierKey($serviceId)
	{
		return 'SERVICE_CARRIER_ID_' . $serviceId;
	}
}
