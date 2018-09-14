<?php
/**
 * Created by PhpStorm.
 * User: PengYilong
 * Date: 2018/9/10
 * Time: 1:42 PM
 */

namespace Nezumi;

class Db{

    protected static $config;

    /**
     * @param array $config
     */
    public static function setConfig($config = [])
    {
        self::$config = $config;
    }

    /**
     * @param array $config
     */
    public static function getConfig()
    {
        return self::$config;
    }
}