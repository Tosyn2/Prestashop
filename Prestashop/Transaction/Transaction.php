<?php namespace Mds\Prestashop\Transaction;

use Db;

class Transaction {

	protected $db;

	public function __construct(Db $db)
	{
		$this->db = $db;

	}

}
