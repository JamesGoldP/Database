<?php
namespace zero;

use zero\db\Query;

class Db
{

    /**
     * database configs
     *
     * @var array
     */
    protected static $config = [];

    /**
     * @param array $config
     */
    public static function setConfig(array $config = []): array
    {
        return self::$config = $config;
    }

    /**
     * gets configs
     *
     * @param string $name
     * @return string | array
     */
    public static function getConfig(string $name = null)
    {
        if( $name ){
            return self::$config[$name] ?? [];
        } else {
            return self::$config;
        }
    }

    public static function __callStatic( string $name , array $arguments )
    {
        return call_user_func_array([new Query, $name], $arguments);
    }
}