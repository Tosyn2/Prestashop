<?php namespace Mds\Prestashop\Settings;

use Mds\Prestashop\Collivery\ColliveryApi;
use Mds\Prestashop\Exceptions\InvalidEmail;

class Credentials extends Settings {

	public static function getEmail()
	{
		return self::getConfig('EMAIL');
	}

	public static function getPassword()
	{
		return self::getConfig('PASSWORD');
	}

	public static function update($email, $password)
	{
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			throw new InvalidEmail($email);
		}
		ColliveryApi::testAuthentication($email, $password);

		self::updateConfig('EMAIL', $email);
		self::updateConfig('PASSWORD', $password);
	}

	public static function delete()
	{
		self::deleteConfig('EMAIL');
		self::deleteConfig('PASSWORD');
	}

}
