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

use Mds\Prestashop\Collivery\ColliveryApi;
use Mds\Prestashop\Exceptions\InvalidData;
use Mds\Prestashop\Exceptions\InvalidEmail;

class Credentials extends Settings
{
    const EMAIL = 1;
    const PASSWORD = 2;

    public static function getEmail()
    {
        return self::getConfig(self::EMAIL);
    }

    public static function getPassword()
    {
        return self::getConfig(self::PASSWORD);
    }

    public static function set($email, $password)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmail($email);
        }
        ColliveryApi::testAuthentication($email, $password);

        self::setConfig($email, self::EMAIL);
        self::setConfig($password, self::PASSWORD);
    }

    public static function delete()
    {
        self::deleteConfig(self::EMAIL);
        self::deleteConfig(self::PASSWORD);
    }

    protected static function getConfigKey($id)
    {
        if ($id === self::EMAIL) {
            return self::$emailKey;
        } elseif ($id === self::PASSWORD) {
            return self::$passwordKey;
        } else {
            throw new InvalidData;
        }
    }
}
