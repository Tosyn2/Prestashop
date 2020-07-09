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

namespace Mds;

class Cache
{

    /**
     * @type string
     */
    private $cache_dir;

    /**
     * @type
     */
    private $cache;

    /**
     * @param string $cache_dir
     */
    public function __construct($cache_dir = 'cache/mds_collivery/')
    {
        if ($cache_dir === null) {
            $cache_dir = 'cache/mds_collivery/';
        }

        $this->cache_dir = $cache_dir;
    }

    /**
     * Creates the cache directory
     *
     * @param $dir_array
     */
    protected function createDir($dir_array)
    {
        if (!is_array($dir_array)) {
            $dir_array = explode('/', $this->cache_dir);
        }

        array_pop($dir_array);
        $dir = implode('/', $dir_array);

        if ($dir!='' && ! is_dir($dir)) {
            $this->createDir($dir_array);
            mkdir($dir);
        }
    }

    /**
     * Loads a specific cache file else creates the cache directory
     *
     * @param $name
     * @return mixed
     */
    protected function load($name)
    {
        if (! isset($this->cache[ $name ])) {
            if (file_exists($this->cache_dir . $name) && $content = file_get_contents($this->cache_dir . $name)) {
                $this->cache[ $name ] = json_decode($content, true);
                return $this->cache[ $name ];
            } else {
                $this->createDir($this->cache_dir);
            }
        } else {
            return $this->cache[ $name ];
        }
    }

    /**
     * Determines if a specific cache file exists and is valid
     *
     * @param $name
     * @return bool
     */
    public function has($name)
    {
        $cache = $this->load($name);
        if (is_array($cache)) {
            if ($cache['valid'] === 0 || ($cache['valid'] - 30) > time()) {
                return (!empty($cache['value'])) ? true : false;
            }
        }

        return false;
    }

    /**
     * Gets a specific cache files contents
     *
     * @param $name
     * @return null
     */
    public function get($name)
    {
        $cache = $this->load($name);
        if (is_array($cache)) {
            if ($cache['valid'] === 0 || ($cache['valid'] - 30) > time()) {
                return (!empty($cache['value'])) ? $cache['value'] : null;
            }
        }

        return null;
    }

    /**
     * Creates a specific cache file
     *
     * @param $name
     * @param $value
     * @param int $time
     * @return bool
     */
    public function put($name, $value, $time = 1440)
    {
        $cache = array( 'value' => $value, 'valid' => time() + ($time*60) );
        if (file_put_contents($this->cache_dir . $name, json_encode($cache))) {
            $this->cache[ $name ] = $cache;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Forgets a specific cache file
     *
     * @param $name
     * @return bool
     */
    public function forget($name)
    {
        $cache = array( 'value' => '', 'valid' => 0 );
        if (file_put_contents($this->cache_dir . $name, json_encode($cache))) {
            $this->cache[ $name ] = $cache;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Clears all cache or only files matching a group name
     *
     * @param null|string|array $group
     */
    public function clear($group = null)
    {
        $map = $this->directoryMap();
        if (is_array($group)) {
            foreach ($group as $row) {
                $this->forgetEachFile($map, $row);
            }
        } else {
            $this->forgetEachFile($map, $group);
        }
    }

    /**
     * Forgets each file in the directory map or only files matching a group name
     *
     * @param array $map
     * @param null $group
     */
    private function forgetEachFile(array $map, $group = null)
    {
        foreach ($map as $row) {
            if ($group) {
                if (preg_match('|' . preg_quote($group) . '|', $row)) {
                    $this->forget($row);
                }
            } else {
                $this->forget($row);
            }
        }
    }

    /**
     * Maps all files in the cache directory
     *
     * @return array|bool
     */
    private function directoryMap()
    {
        if ($fp = @opendir($this->cache_dir)) {
            $file_data = array();

            while (false !== ($file = readdir($fp))) {
                // Remove '.', '..', and hidden files
                if (!trim($file, '.') or ($file[0] == '.')) {
                    continue;
                }

                $file_data[] = $file;
            }

            closedir($fp);
            return $file_data;
        }

        return false;
    }
}
