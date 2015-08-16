<?php namespace Mds\Prestashop\Settings;

use Mds\Prestashop\Exceptions\InvalidData;

class Services extends Settings {

	public static function get()
	{
		return self::$services;
	}

	public static function getServiceId($carrierId)
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
			$carrierId = self::getCarrierId($serviceId);
			$serviceMappings[$carrierId] = $serviceId;
		}

		return $serviceMappings;
	}

	/**
	 * @param $serviceId
	 *
	 * @return string
	 */
	public static function getCarrierId($serviceId)
	{
		return self::getConfig(self::getConfigKey($serviceId));
	}

	public static function setCarrierId($serviceId, $carrierId)
	{
		self::updateConfig(self::getConfigKey($serviceId), $carrierId);
	}

	public static function delete($serviceId)
	{
		self::deleteConfig(self::getConfigKey($serviceId));
	}

	/**
	 * @param $serviceId
	 *
	 * @return string
	 */
	private static function getConfigKey($serviceId)
	{
		return 'SERVICE_CARRIER_ID_' . $serviceId;
	}
}
