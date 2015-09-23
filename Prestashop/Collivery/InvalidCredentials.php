<?php namespace Mds\Prestashop\Collivery;

use Mds\Prestashop\Exceptions\InvalidData;

class InvalidCredentials extends InvalidData {

	public function __construct($message = '', $code = 0, \Exception $previous = null)
	{
		$message = 'Invalid Collivery Credentials, Unable to Save Settings' . ($message ? ": $message" : '.');
		parent::__construct($message, $code, $previous);
	}
}
