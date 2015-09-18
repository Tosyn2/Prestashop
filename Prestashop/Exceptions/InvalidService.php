<?php namespace Mds\Prestashop\Exceptions;

class InvalidService extends InvalidData {

	public function __construct($message = '', $code = 0, \Exception $previous = null)
	{
		$message = 'Invalid Service' . ($message ? ": $message" : '.');
		parent::__construct($message, $code, $previous);
	}
}
