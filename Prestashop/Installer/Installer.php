<?php namespace Mds\Prestashop\Installer;

use Db;

abstract class Installer {

	protected $db;

	protected $hooks = array(
		'displayFooter',
		'actionOrderStatusPostUpdate',
		'displayShoppingCart'
	);

	public function __construct()
	{
		$this->db = Db::getInstance();
	}

	/**
	 * @return array
	 */
	public function getHooks()
	{
		return $this->hooks;
	}
}
