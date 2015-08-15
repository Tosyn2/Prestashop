<?php namespace Mds\Prestashop\Collivery;

use Mds\Collivery;
use Mds\Prestashop\Settings\Credentials;

class ColliveryApi {

	/**
	 * @type Collivery
	 */
	private static $instance;

	public static function getInstance()
	{
		if (!self::$instance) {
			$settings = array(
				'user_email' => Credentials::getColliveryEmail(),
				'user_password' => Credentials::getColliveryPassword(),
			);
			self::$instance = new Collivery($settings);
		}

		return self::$instance;
	}

	public static function testAuthentication($email, $password)
	{
		$settings = array(
			'user_email' => $email,
			'user_password' => $password,
		);
		$collivery = new Collivery($settings);
		$collivery->disableCache();

		if (!$collivery->isAuthenticated()) {
			throw new InvalidCredentials();
		}
	}

}
