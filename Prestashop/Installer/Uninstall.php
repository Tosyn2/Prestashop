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

namespace Mds\Prestashop\Installer;

use Carrier;
use Configuration;
use Mds\Prestashop\Settings\Credentials;
use Mds\Prestashop\Settings\RiskCover;
use Mds\Prestashop\Settings\Services;
use Mds\Prestashop\Settings\Surcharge;

class Uninstall extends Installer
{
    public function uninstall()
    {
        $this->deleteServicesConfig();
        Credentials::delete();
        RiskCover::delete();
        $this->setCarriersDeleted();
    }

    private function deleteServicesConfig()
    {
        $services = Services::get();
        foreach (array_keys($services) as $serviceId) {
            Surcharge::delete($serviceId);
            $carrierId = Services::getCarrierId($serviceId);

            if (Configuration::get('PS_CARRIER_DEFAULT') == $carrierId) {
                $this->setDefaultCarrierToPsCarrier();
            }
            Services::delete($serviceId);
        }
    }

    private function setCarriersDeleted()
    {
        $sql = 'UPDATE '. _DB_PREFIX_ .'carrier SET `deleted` = 1 WHERE `external_module_name` = "mds";';
        $this->db->execute($sql);
    }

    private function setDefaultCarrierToPsCarrier()
    {
        $PsCarriers = Carrier::getCarriers(Configuration::get('PS_LANG_DEFAULT'), true);
        foreach ($PsCarriers as $PsCarrier) {
            if ($PsCarrier['name'] != 'mds') {
                Configuration::updateValue('PS_CARRIER_DEFAULT', $PsCarrier['id_carrier']);
            }
        }
    }
}
