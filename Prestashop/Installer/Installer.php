<?php namespace Mds\Prestashop\Installer;

use Db;

abstract class Installer {

	protected $db;

	public function __construct(Db $db)
	{
		$this->db = $db;
	}
}
