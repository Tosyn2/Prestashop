<?php namespace Mds\Prestashop\Settings;

use Mds\Prestashop\Exceptions\InvalidService;
use Mds\Prestashop\Exceptions\InvalidSurcharge;

class Surcharge extends Settings {

	protected static $surchargePrefix = 'SERVICE_SURCHARGE_';

	/**
	 * @param $serviceId
	 *
	 * @return string
	 */
	public static function getServiceSurcharge($serviceId)
	{
		return (int) self::getConfig(self::getSurchargeKey($serviceId));
	}

	/**
	 * @param $serviceId
	 * @param $value
	 */
	public static function setServiceSurcharge($serviceId, $value)
	{
		if (!is_numeric($value) || $value > 100 || $value < -100) {
			throw new InvalidSurcharge($value);
		}
		self::updateConfig(self::getSurchargeKey($serviceId), $value);
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
