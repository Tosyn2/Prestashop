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

namespace Mds\Prestashop\Settings;

use Configuration;
use Mds\Prestashop\Exceptions\UnableToUpdateConfiguration;

abstract class Settings
{
    private static $prefix = 'MDS_';

    protected static $emailKey = 'EMAIL';
    protected static $passwordKey = 'PASSWORD';
    protected static $serviceKey = 'SERVICE_CARRIER_ID_';
    protected static $surchargeKey = 'SERVICE_SURCHARGE_';
    protected static $riskKey = 'RISK';

    protected static $services = array(
        1 => 'Overnight before 10:00',
        2 => 'Overnight before 16:00',
        5 => 'Road Freight Express',
        3 => 'Road Freight',
    );

    final protected static function getConfig($id = null)
    {
        return Configuration::get(self::getConfigKeyLocal($id));
    }

    final protected static function setConfig($value, $id = null)
    {
        if (!Configuration::updateValue(self::getConfigKeyLocal($id), $value)) {
            throw new UnableToUpdateConfiguration();
        }
    }

    final protected static function deleteConfig($id = null)
    {
        Configuration::deleteByName(self::getConfigKeyLocal($id));
    }

    final protected static function getConfigKeyLocal($id)
    {
        return self::$prefix . static::getConfigKey($id);
    }
}
