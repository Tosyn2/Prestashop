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

namespace Mds\Prestashop\Collivery;

use Mds\Collivery;
use Mds\Prestashop\Settings\Credentials;

class ColliveryApi
{

    /**
     * @type Collivery
     */
    private static $instance;

    public static function getInstance()
    {
        if (!self::$instance) {
            $settings = array(
                'user_email' => Credentials::getEmail(),
                'user_password' => Credentials::getPassword(),
            );
            self::$instance = new Collivery($settings);
        }

        return self::$instance;
    }

    public static function testAuthentication($email, $password)
    {
        $settings = array(
            'user_email' => $email,
            'user_password' => $password,
        );
        $collivery = new Collivery($settings);
        $collivery->disableCache();

        if (!$collivery->isAuthenticated()) {
            throw new InvalidCredentials();
        }
    }
}
