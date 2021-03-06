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

use Mds\Prestashop\Exceptions\InvalidData;

class InvalidCredentials extends InvalidData
{
    public function __construct($message = '', $code = 0, \Exception $previous = null)
    {
        $message = 'Invalid Collivery Credentials' . ($message ? ": $message" : '.');
        parent::__construct($message, $code, $previous);
    }
}
