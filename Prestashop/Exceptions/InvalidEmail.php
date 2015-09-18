<?php namespace Mds\Prestashop\Exceptions;

class InvalidEmail extends InvalidData {

	public function __construct($message = '', $code = 0, \Exception $previous = null)
	{
		$message = 'Invalid Email Address' . ($message ? ": $message" : '.');
		parent::__construct($message, $code, $previous);
	}
}
