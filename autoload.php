<?php
/**
 * Copyright 2020 MDS Technologies (Pty) Ltd and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0).
 * It is also available through the world-wide-web at this URL: https://opensource.org/licenses/AFL-3.0
 *
 *  @author MDS Collivery <integration@collivery.co.za>
 *  @copyright  2020 MDS Technologies (Pty) Ltd
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

// Avoid direct access to the file
if (!defined('_PS_VERSION_')) {
    exit;
}

class MdsColliveryAutoloader
{
    protected static $classMap = array(
        'Mds_View'                        => '\Mds\Prestashop\Helpers\View',
        'Mds_ColliveryApi'                => '\Mds\Prestashop\Collivery\ColliveryApi',
        'Mds_Install'                     => '\Mds\Prestashop\Installer\Install',
        'Mds_Uninstall'                   => '\Mds\Prestashop\Installer\Uninstall',
        'Mds_Services'                     => '\Mds\Prestashop\Settings\Services',
        'Mds_Surcharge'                   => '\Mds\Prestashop\Settings\Surcharge',
        'Mds_RiskCover'                   => '\Mds\Prestashop\Settings\RiskCover',
        'Mds_SettingsService'             => '\Mds\Prestashop\Settings\SettingsService',
    );

    public static function autoload($class)
    {
        $classParts = explode('\\', $class);
        $vendor = array_shift($classParts);
        if ($vendor === 'Mds') {
            require _MDS_DIR_ . '/' . implode('/', $classParts) . '.php';
        } elseif (array_key_exists($class, self::$classMap)) {
            class_alias(self::$classMap[$class], $class);
        }
    }
}

spl_autoload_register(
    'MdsColliveryAutoloader::autoload',
    true
);
