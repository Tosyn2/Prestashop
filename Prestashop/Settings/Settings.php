<?php namespace Mds\Prestashop\Settings;

use Configuration;
use Mds\Prestashop\Exceptions\UnableToUpdateConfiguration;

abstract class Settings {

	private static $prefix = 'MDS_';

	protected static $emailKey = 'EMAIL';
	protected static $passwordKey = 'PASSWORD';
	protected static $serviceKey = 'SERVICE_CARRIER_ID_';
	protected static $surchargeKey = 'SERVICE_SURCHARGE_';
	protected static $riskKey = 'RISK';

	protected static $services = array(
		1 => 'Overnight before 10:00',
		2 => 'Overnight before 16:00',
		5 => 'Road Freight Express',
		3 => 'Road Freight',
	);

	final protected static function _getConfig($id = null)
	{
		return Configuration::get(self::_getConfigKey($id));
	}

	final protected static function _setConfig($value, $id = null)
	{
		if (!Configuration::updateValue(self::_getConfigKey($id), $value)) {
			throw new UnableToUpdateConfiguration();
		}
	}

	final protected static function _deleteConfig($id = null)
	{
		Configuration::deleteByName(self::_getConfigKey($id));
	}

	final protected static function _getConfigKey($id)
	{
		return self::$prefix . static::getConfigKey($id);
	}
}
