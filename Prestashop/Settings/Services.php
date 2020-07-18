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

use Mds\Prestashop\Exceptions\InvalidData;
use Mds\Prestashop\Exceptions\InvalidService;

class Services extends Settings
{
    public static function get()
    {
        return self::$services;
    }

    public static function set($serviceId, $carrierId)
    {
        self::setConfig($carrierId, $serviceId);
    }

    public static function getServiceId($carrierId)
    {
        $serviceMappings = self::getServiceMappings();

        if (!array_key_exists($carrierId, $serviceMappings)) {
            throw new InvalidData('Invalid Carrier Id: '. $carrierId);
        }

        return $serviceMappings[$carrierId];
    }

    /**
     * @param $serviceId
     *
     * @return string
     */
    public static function getCarrierId($serviceId)
    {
        return self::getConfig($serviceId);
    }

    public static function delete($serviceId)
    {
        self::deleteConfig($serviceId);
    }

    /**
     * @return array
     */
    private static function getServiceMappings()
    {
        $serviceMappings = array();

        foreach (array_keys(self::$services) as $serviceId) {
            $carrierId = self::getCarrierId($serviceId);
            $serviceMappings[$carrierId] = $serviceId;
        }

        return $serviceMappings;
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

        return self::$serviceKey . $serviceId;
    }
}
