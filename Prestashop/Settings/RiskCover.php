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

class RiskCover extends Settings
{

    /**
     * @return bool
     */
    public static function hasCover()
    {
        return self::getConfig() == 1;
    }

    /**
     * @param bool $value
     */
    public static function set($value)
    {
        $value = $value ? 1 : 0;
        self::setConfig($value);
    }

    public static function delete()
    {
        self::deleteConfig();
    }

    protected static function getConfigKey($id)
    {
        unset($id);
        return self::$riskKey;
    }
}
