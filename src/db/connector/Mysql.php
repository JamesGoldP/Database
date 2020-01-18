<?php
namespace zero\db\connector;

use zero\db\Connection;
use PDO;

class Mysql extends Connection
{

    protected $builderPosition = '\\zero\\db\\builder\\Mysql';

    /**
     * 解析pdo连接的dsn信息
     *
     * @param array $config
     * @return string
     */
    protected function parseDsn(array $config) : string
    {
        if( !empty($config['hostport']) ) {
            $dsn = 'mysql:host=' . $config['hostname'] . ';port' . $config['hostport'];
        } else {
            $dsn = 'mysql:host=' . $config['hostname'];
        }
        $dsn .= ';dbname=' . $config['database'];

        if(!empty($config['charset'])) {
            $dsn .= ';charse=' . $config['charset'];
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
        $result = $this->query($sql);
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