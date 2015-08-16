<?php

// Avoid direct access to the file
if (!defined('_PS_VERSION_')) {
	exit;
}

// Hack for "use" statements
$colliveryClassMap = array(
	'Mds_View'                        => '\Mds\Prestashop\Helpers\View',
	'Mds_ColliveryApi'                => '\Mds\Prestashop\Collivery\ColliveryApi',
	'Mds_Install'                     => '\Mds\Prestashop\Installer\Install',
	'Mds_Uninstall'                   => '\Mds\Prestashop\Installer\Uninstall',
	'Mds_Service'                     => '\Mds\Prestashop\Settings\Service',
	'Mds_SettingsService'             => '\Mds\Prestashop\Settings\SettingsService',

	// Exceptions
	'Mds_ColliveryException'          => '\Mds\Prestashop\Exceptions\ColliveryException',
	'Mds_InvalidData'                 => '\Mds\Prestashop\Exceptions\InvalidData',
	'Mds_UnableToRegisterHook'        => '\Mds\Prestashop\Exceptions\UnableToRegisterHook',
	'Mds_UnableToUpdateConfiguration' => '\Mds\Prestashop\Exceptions\UnableToUpdateConfiguration',
	'Mds_InvalidCredentials'          => '\Mds\Prestashop\Collivery\InvalidCredentials',
);

spl_autoload_register(
	function ($class) use ($colliveryClassMap) {
		$classParts = explode('\\', $class);
		$vendor = array_shift($classParts);
		if ($vendor === 'Mds') {
			require _MDS_DIR_ . '/' . implode('/', $classParts) . '.php';
		} elseif (array_key_exists($class, $colliveryClassMap)) {
			class_alias($colliveryClassMap[$class], $class);
		}
	},
	true
);
