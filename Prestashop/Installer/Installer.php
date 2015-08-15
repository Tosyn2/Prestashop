<?php namespace Mds\Prestashop\Installer;

use Db;

abstract class Installer {

	protected $db;

	protected $services = array(
		1 => 'Overnight before 10:00',
		2 => 'Overnight before 16:00',
		5 => 'Road Freight Express',
		3 => 'Road Freight',
	);

	protected $config = array(
		'MDS_EMAIL'    => 'api@collivery.co.za',
		'MDS_PASSWORD' => 'api123',
		'MDS_RISK'     => '0',
	);

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
