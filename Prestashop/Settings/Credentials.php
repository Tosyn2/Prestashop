<?php namespace Mds\Prestashop\Settings;

use Mds\Prestashop\Collivery\ColliveryApi;
use Mds\Prestashop\Exceptions\InvalidData;
use Mds\Prestashop\Exceptions\InvalidEmail;

class Credentials extends Settings {

	const EMAIL = 1;
	const PASSWORD = 2;

	public static function getEmail()
	{
		return self::_getConfig(self::EMAIL);
	}

	public static function getPassword()
	{
		return self::_getConfig(self::PASSWORD);
	}

	public static function set($email, $password)
	{
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			throw new InvalidEmail($email);
		}
		ColliveryApi::testAuthentication($email, $password);

		self::_setConfig($email, self::EMAIL);
		self::_setConfig($password, self::PASSWORD);
	}

	public static function delete()
	{
		self::_deleteConfig(self::EMAIL);
		self::_deleteConfig(self::PASSWORD);
	}

	protected static function getConfigKey($id)
	{
		if ($id === self::EMAIL) {
			return self::$emailKey;
		} elseif ($id === self::PASSWORD) {
			return self::$passwordKey;
		} else {
			throw new InvalidData;
		}
	}
}
