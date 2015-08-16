<?php namespace Mds\Prestashop\Settings;

use Mds\Prestashop\Exceptions\InvalidService;
use Mds\Prestashop\Exceptions\InvalidSurcharge;

class Surcharge extends Settings {

	protected static $surchargePrefix = 'SERVICE_SURCHARGE_';

	/**
	 * @param $serviceId
	 *
	 * @return int
	 */
	public static function get($serviceId)
	{
		return (int) self::getConfig(self::getSurchargeKey($serviceId));
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
		self::updateConfig(self::getSurchargeKey($serviceId), $value);
	}

	public static function delete($serviceId)
	{
		self::deleteConfig(self::getSurchargeKey($serviceId));
	}

	/**
	 * @param $serviceId
	 *
	 * @return string
	 * @throws InvalidService
	 */
	private static function getSurchargeKey($serviceId)
	{
		if (!array_key_exists($serviceId, self::$services)) {
			throw new InvalidService($serviceId);
		}

		return self::$surchargePrefix . (int) $serviceId;
	}
}
