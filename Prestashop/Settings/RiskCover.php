<?php namespace Mds\Prestashop\Settings;

class RiskCover extends Settings {

	public static function hasCover()
	{
		return self::getConfig('RISK') == 1;
	}

	public static function setColliveryRiskCover($value)
	{
		$value = $value ? 1 : 0;
		return self::updateConfig('RISK', $value);
	}
}
