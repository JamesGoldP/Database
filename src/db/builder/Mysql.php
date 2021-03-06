<?php
namespace zero\db\builder;

use zero\db\Builder;
use zero\db\Query;

class Mysql extends Builder
{

    /**
     * 
     */
    protected $selectSql = 'SELECT %FIELD% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT%'; 

    /**
     * 
     */
    protected $deleteSql = 'DELETE FROM %TABLE% %WHERE%';  

    /**
     * 
     */
    protected $updateSql = 'UPDATE %TABLE% SET %DATA% %WHERE%';  
    
    /**
     * insert single record  
     *
     * @var string
     */
    protected $insertSql = '%INSERT% INTO %TABLE%(%FIELD%) VALUES (%DATA%)';

    /**
     * insert multi record  
     *
     * @var string
     */
    protected $insertAllSql = '%INSERT% INTO %TABLE%(%FIELD%) VALUES %DATA%';
    
    /**
     * deal with fields and table's name
     *
     * @param Query $query
     * @param [type] $key
     * @param boolean $strict
     * @return string
     */
    public function parseKey(Query $query, $key, bool $strict = false): string
    {
        if( is_numeric($key) ){
            return $key;
        }

        $key = trim($key);

        if(strpos($key, '.') && !preg_match('/[,\'\"\(\)`\s]/', $key)) {
            list($table, $key) = explode('.', $key, 2);

            $alias = $query->getOptions('alias');

            if('__TABLE__' == $table) {
                $table = $query->getOptions('table');
                $table = is_array($table) ? array_shift($table) : $table;
            }

            if( isset($alias[$table]) ) {
                $table = $alias[$table];
            }

        }

        if( $strict && !preg_match('/^[\w\.\*]+$/', $key) ){
            throw new Exception('not support data:'. $key);
        }

        if( '*' != $key && !preg_match('/[,\'\"\*\(\)`\.\s]/', $key) ){
            $key = $this->addSymbol($key, '`'); 
        }

        if( isset($table) ) {
            $key = $this->addSymbol($table, '`') . '.' . $key;
        }

        return $key;
    }

    /**
     * build to insert multi record of sql
     *
     * @param Query $query
     * @param array $dataSet
     * @param boolean $replace
     * @return bool|string
     */
    public function insertAll(Query $query, array $dataSet = [], bool $replace = false)
    {
        $options = $query->getOptions();

        //获取合法字段
        if( '*' == $options['field'] ){
            $allowFields = $this->connection->getTableInfo($options['table'], 'fields');
        } else {
            $allowFields = $options['field'];
        }

        $bind = $this->connection->getTableInfo($options['table'], 'bind');

        $dataSet = $options['data'];
        foreach($dataSet as $k => $data){
            $data = $this->parseData($query, $data, $allowFields, $bind);
            $values[] = '(' . implode(',', array_values($data)) . ')';  
            //字段取第一个数组的key
            if( !isset($inserFields) ){
                $insertFileds = array_keys($data);
            }
        }

        $fields = [];

        foreach($insertFileds as $field){
            $fields[] = $this->parseKey($query, $field);
        }

        return str_replace(
            ['%INSERT%', '%TABLE%', '%FIELD%', '%DATA%'], 
            [
                $replace ? 'REPLACE' : 'INSERT', 
                $this->parseTable($query, $options['table']), 
                implode(',', $fields), 
                implode(',', $values)
            ], 
            $this->insertAllSql);
    }
}