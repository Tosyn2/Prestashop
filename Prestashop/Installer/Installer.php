<?php namespace Mds\Prestashop\Installer;

use Db;

abstract class Installer {

	protected $db;

	protected $hooks = array(
		'displayFooter',
		'actionOrderStatusPostUpdate',
		'displayShoppingCart'
	);

	public function __construct(Db $db)
	{
		$this->db = $db;
	}

	/**
	 * @return array
	 */
	public function getHooks()
	{
		return $this->hooks;
	}
}
