<?php namespace Mds\Prestashop\Exceptions;

class InvalidSurcharge extends InvalidData {

	public function __construct($message = '', $code = 0, \Exception $previous = null)
	{
		$message = 'Invalid Surcharge. Value should be between -100% and 100%' . ($message ? ". Got $message%" : '.');
		parent::__construct($message, $code, $previous);
	}
}
