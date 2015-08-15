<?php namespace Mds\Prestashop\Settings;

class RiskCover extends Settings {

	public static function hasCover()
	{
		return self::getConfig('RISK') == 1;
	}

	public static function setColliveryRiskCover()
	{
		return self::getConfig('RISK');
	}
}
