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
     * 字段和表名处理
     *
     * @param Query $query
     * @param mixed $key
     * @param boolean $strict
     * @return string
     */
    public function parseKey(Query $query, $key, $strict = false): string
    {
        if( is_numeric($key) ){
            return $key;
        }

        $key = trim($key);

        if( $strict && !preg_match('/^[\w\.\*]+$/', $key) ){
            throw new Exception('not support data:'. $key);
        }

        if( '*' != $key && !preg_match('/[,\'\"\*\(\)`\.\s]/', $key) ){
            $key = $this->addSymbol($key, '`'); 
        }
        return $key;
    }

    /**
     * build a insert sql
     */
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
                $this->parseTable($options['table']), 
                implode(',', $fields), 
                implode(',', $values)
            ], 
            $this->insertAllSql);
    }
}