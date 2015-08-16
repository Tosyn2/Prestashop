<?php namespace Mds\Prestashop\Settings;

class RiskCover extends Settings {

	public static function hasCover()
	{
		return self::getConfig('RISK') == 1;
	}

	/**
	 * @param bool $value
	 */
	public static function set($value)
	{
		$value = $value ? 1 : 0;
		return self::updateConfig('RISK', $value);
	}
}
