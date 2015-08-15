<?php namespace Mds\Prestashop\Settings;

use Configuration;
use Mds\Prestashop\Exceptions\UnableToUpdateConfiguration;

abstract class Settings {

	protected static $services = array(
		1 => 'Overnight before 10:00',
		2 => 'Overnight before 16:00',
		5 => 'Road Freight Express',
		3 => 'Road Freight',
	);
	private static $prefix = 'MDS_';

	protected static function getConfig($key)
	{
		return Configuration::get(self::$prefix . $key);
	}

	protected static function updateConfig($key, $value)
	{
		if (!Configuration::updateValue(self::$prefix . $key, $value)) {
			throw new UnableToUpdateConfiguration();
		}
	}

}
