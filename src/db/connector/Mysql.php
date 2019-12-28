<?php
namespace zero\db\connector;

use zero\db\Connection;
use PDO;

class Mysql extends Connection
{

    protected $builderPosition = '\\zero\\db\\builder\\Mysql';

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

    /**
     * gets the fields of the table
     */
    protected function getFields($table)
    {
        $table = $this->builder->addSymbol($table, '`');
        $sql = 'SHOW COLUMNS FROM '.$table;
        $this->query($sql);
        $result = $this->statement->fetchAll(PDO::FETCH_ASSOC);
        $info = [];
        foreach($result as $key=>$val){
            $val = array_change_key_case($val);
            $info[$val['field']] = [
                'name'    => $val['field'],
                'type'    => $val['type'],
                'notnull' => (bool)( 'NO' == $val['null'] ),
                'default' => $val['default'],
                'primary' => ( strtolower($val['key']) == 'pri' ),
                'autoinc' => ( strtolower($val['extra']) == 'auto_increment' ),
            ];
        }
        return $this->fieldCase($info);
    }

    /**
     * gets the tables of the database
     */
    public function getTables($db)
    {

    }
}