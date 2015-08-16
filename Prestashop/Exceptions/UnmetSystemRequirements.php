<?php namespace Mds\Prestashop\Exceptions;

class UnmetSystemRequirements extends ColliveryException {

	protected $errors;

	public function __construct(array $errors)
	{
		$this->errors = $errors;
	}

	public function getErrors()
	{
		$this->getErrors();
	}
}
