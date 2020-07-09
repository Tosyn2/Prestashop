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

class SettingsService
{
    protected $surchargeKeys = array();

    protected $errors = array();

    public function getSurchargesInfo()
    {
        $services = Services::get();
        $surcharges = array();
        foreach ($services as $serviceId => $serviceName) {
            $surcharges[$serviceId] = array(
                'name' => $serviceName .' Surcharge',
                'value' => Surcharge::get($serviceId)
            );
        }

        return $surcharges;
    }

    public function getColliveryEmail()
    {
        return Credentials::getEmail();
    }

    public function testCurrentCredentials()
    {
        $email = Credentials::getEmail();
        $password = Credentials::getPassword();

        ColliveryApi::testAuthentication($email, $password);
    }

    public function store($data)
    {
        if (!empty($data['email']) && !empty($data['password'])) {
            $this->updateColliveryCredentials($data['email'], $data['password']);
        }

        $this->updateSurcharges($data['surcharge']);

        RiskCover::set(!empty($data['risk-cover']));

        return $this->errors;
    }

    private function updateColliveryCredentials($email, $password)
    {
        try {
            Credentials::set($email, $password);
        } catch (InvalidData $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    private function updateSurcharges($surcharges)
    {
        foreach ($surcharges as $serviceId => $surcharge) {
            Surcharge::set($serviceId, $surcharge);
        }
    }
}
