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

use Mds\Prestashop\Exceptions\InvalidService;
use Mds\Prestashop\Exceptions\InvalidSurcharge;

class Surcharge extends Settings
{

    /**
     * @param $serviceId
     *
     * @return int
     */
    public static function get($serviceId)
    {
        return (int) self::getConfig($serviceId);
    }

    /**
     * @param $serviceId
     * @param $value
     */
    public static function set($serviceId, $value)
    {
        if (!is_numeric($value) || $value > 100 || $value < -100) {
            throw new InvalidSurcharge($value);
        }
        self::setConfig($value, $serviceId);
    }

    public static function delete($serviceId)
    {
        self::deleteConfig($serviceId);
    }

    /**
     * @param $serviceId
     *
     * @return string
     * @throws InvalidService
     */
    protected static function getConfigKey($serviceId)
    {
        if (!array_key_exists($serviceId, self::$services)) {
            throw new InvalidService($serviceId);
        }

        return self::$surchargeKey . (int) $serviceId;
    }
}
