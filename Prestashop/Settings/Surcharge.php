<?php namespace Mds\Prestashop\Settings;

use Mds\Prestashop\Exceptions\InvalidService;
use Mds\Prestashop\Exceptions\InvalidSurcharge;

class Surcharge extends Settings {

	/**
	 * @param $serviceId
	 *
	 * @return int
	 */
	public static function get($serviceId)
	{
		return (int) self::_getConfig($serviceId);
	}

	/**
	 * @param $serviceId
	 * @param $value
	 */
	public static function set($serviceId, $value)
	{
		if (!is_numeric($value) || $value > 100 || $value < -100) {
			throw new InvalidSurcharge($value);
		}
		self::_setConfig($value, $serviceId);
	}

	public static function delete($serviceId)
	{
		self::_deleteConfig($serviceId);
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

		return self::$surchargeKey . (int) $serviceId;
	}
}
