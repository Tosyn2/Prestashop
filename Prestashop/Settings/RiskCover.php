<?php namespace Mds\Prestashop\Settings;

class RiskCover extends Settings {

	/**
	 * @return bool
	 */
	public static function hasCover()
	{
		return self::_getConfig() == 1;
	}

	/**
	 * @param bool $value
	 */
	public static function set($value)
	{
		$value = $value ? 1 : 0;
		self::_setConfig($value);
	}

	public static function delete()
	{
		self::_deleteConfig();
	}

	protected static function getConfigKey($id)
	{
		return self::$riskKey;
	}
}
