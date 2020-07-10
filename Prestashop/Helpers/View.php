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

namespace Mds\Prestashop\Helpers;

class View
{
    protected $path = '';

    protected $obLevel = 0;

    public static function make($path, $data)
    {
        $view = new self;

        return $view->generate($path, $data);
    }

    /**
     * Get the evaluated contents of the view at the given path.
     *
     * @param  string $__path
     * @param  array  $__data
     *
     * @return string
     * @throws \Exception
     */
    protected function generate($__path, $__data)
    {
        $this->path = _MDS_DIR_ .'/views/templates/'. $__path .'.tpl';
        extract($__data);

        $this->obLevel = ob_get_level();
        ob_start();

        try {
            include $this->path;
        } catch (\Exception $e) {
            while (ob_get_level() > $this->obLevel) {
                ob_end_clean();
            }

            throw $e;
        }

        return ltrim(ob_get_clean());
    }
}
