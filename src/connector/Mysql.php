<?php
namespace Nezimi\connector;

use Nezimi\Connection;

class Mysql extends Connection
{

    protected $builderPosition = '\\Nezimi\\builder\\Mysql';

    protected function parseDsn($config)
    {
        $dsn = 'mysql:host='.$config['hostname'].';dbname='.$config['database'];
        
        if( !empty($config['hostport']) ){
            $dsn .= ';port = '. $config['hostport'];
        }
        if( !empty($config['charset']) ){
            $dsn .= ';charset = '. $config['charset'];
        }
        return $dsn;
    }
}