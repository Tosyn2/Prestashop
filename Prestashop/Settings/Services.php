<?php namespace Mds\Prestashop\Settings;

use Mds\Prestashop\Exceptions\InvalidData;
use Mds\Prestashop\Exceptions\InvalidService;

class Services extends Settings {

	public static function get()
	{
		return self::$services;
	}

	public static function set($serviceId, $carrierId)
	{
		self::_setConfig($carrierId, $serviceId);
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
	 * @param $serviceId
	 *
	 * @return string
	 */
	public static function getCarrierId($serviceId)
	{
		return self::_getConfig($serviceId);
	}

	public static function delete($serviceId)
	{
		self::_deleteConfig($serviceId);
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
	 * @throws InvalidService
	 */
	protected static function getConfigKey($serviceId)
	{
		if (!array_key_exists($serviceId, self::$services)) {
			throw new InvalidService($serviceId);
		}

		return self::$serviceKey . $serviceId;
	}
}
