<?php

// Avoid direct access to the file
if ( ! defined('_PS_VERSION_')) {
	exit;
}

class MdsColliveryAutoloader {

	protected static $classMap = array(
		'Mds_View'             => '\Mds\Prestashop\Helpers\View',
		'Mds_ColliveryApi'     => '\Mds\Prestashop\Collivery\ColliveryApi',
		'Mds_Install'          => '\Mds\Prestashop\Installer\Install',
		'Mds_Uninstall'        => '\Mds\Prestashop\Installer\Uninstall',
		'Mds_Services'         => '\Mds\Prestashop\Settings\Services',
		'Mds_Surcharge'        => '\Mds\Prestashop\Settings\Surcharge',
		'Mds_RiskCover'        => '\Mds\Prestashop\Settings\RiskCover',
		'Mds_SettingsService'  => '\Mds\Prestashop\Settings\SettingsService',
		'Mds_Transaction'      => '\Mds\Prestashop\Transaction\Transaction',
		'Mds_TransactionTable' => '\Mds\Prestashop\Transaction\TransactionTable',
		'Mds_TransactionView' => '\Mds\Prestashop\Transaction\TransactionView',

	);

	public static function autoload($class)
	{
		$classParts = explode('\\', $class);
		$vendor = array_shift($classParts);
		if ($vendor === 'Mds') {
			require _MDS_DIR_ . '/' . implode('/', $classParts) . '.php';
		} elseif (array_key_exists($class, self::$classMap)) {
			class_alias(self::$classMap[ $class ], $class);
		}
	}
}

spl_autoload_register(
	'MdsColliveryAutoloader::autoload',
	true
);
