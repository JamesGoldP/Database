<?php
/**
 * Created by PhpStorm.
 * User: PengYilong
 * Date: 2018/10/14
 * Time: 1:09 PM
 */

namespace Nezumi;

use Exception;

class Query{

    /**
     * @var 
     */
    protected $db;

    /**
     * @var 
     */
    protected $builder;

    /**
     * query params
     * @var string
     * 
     */
    public $options = [
        'field' => '*',
        'table' => '',
        'join' => '',
        'where' => '',
        'group' => '',
        'having' => '',
        'order' => '',
        'limit' => '',
        'data' => '',
        'fetch_sql' => false,
    ];

    public function __construct()
    {
        $this->db = $this->getDatabase();
        
    }

    /**
     * 
     */
    public function table($table)
    {
        $this->setOption('table', $table);
        return $this;
    }

    /**
     * 
     */
    public function alias($name)
    {
        $this->setOption('table', $this->getOptions('table').' AS '.$name);
        return $this;
    }

    /*
     * @return false or object
     */
    public function getDatabase( $id = 'master' )
    {
        $key = 'database_'.$id;
        $databaseConfig = Db::getConfig();
        if( empty($databaseConfig) ){
            throw new Exception('No config');
        }
        if( $id == 'master' ){
            $dbConfig = $databaseConfig['master'];
        } else {
            $dbConfig = $databaseConfig[array_rand($databaseConfig['slave'])];
        }
        $buildClass = 'Nezumi\\builder\\'.ucfirst($dbConfig['type']);
        $this->builder = new $buildClass;
        $db = Register::get($key);
        if( !$db ){
            $connectorClass = 'Nezumi\\connector\\'.ucfirst($dbConfig['type']); 
            $db = new $connectorClass;
            $db->open($dbConfig);
            Register::set($key, $db);
        }
        return $db;
    }

    public function __call($name ,$arguments)
    {
        if( in_array($name, ['field', 'table', 'where', 'group', 'having', 'order', 'limit']) ){
            $method = 'parse'.ucwords($name);
            $result = call_user_func_array([$this->builder, $method], $arguments);
            $this->options[$name] = $result;
            return $this;
        } else if( 'join' == $name ) {
            $method = 'parse'.ucwords($name);
            $result = call_user_func_array([$this->builder, $method], $arguments);
            $this->options[$name] .= $result;
            return $this; 
        }
    }

    /**
     *  Inserting data from the table
     *
     *
     *  @param $data   array        插入数组
     *  @param $return_insert_id boolean   是否返回插入ID
     *  @param $replace  boolean 是使用replace into 还是insert into
     *
     *  @return boolean,query resource,int
     *
     */
    public function insert( $data, $return_insert_id = false, $replace = false )
    {
        $this->options['data'] = $data; 
        $this->beforeAction();
        $sql = $this->builder->insert($this, $replace);
        $fetchSql = $this->getOptions('fetch_sql');
        $this->afterAction();
        if( $fetchSql ){
            return $sql;
        }
        $return = $this->db->query($sql);
        return $return_insert_id ? $this->db->insertId() : $return;
    }

    /**
     *  Update data from the table
     *
     *  @return int number of affected rows in previous MySQL operation
     *
     */
    public function update($data, $return_affected_rows = false)
    {
        if (empty($this->options['where'])) {
            throw new Exception('The condition is required.');
        }
        $this->setOption('data', $data); 
        $this->beforeAction();
        $sql = $this->builder->update($this);
        $fetchSql = $this->getOptions('fetch_sql');
        $this->afterAction();
        if( $fetchSql ){
            return $sql;
        }
        $return = $this->db->query($sql);
        return $return_affected_rows ? $this->affectedRows() : $return;
    }

    /**
     * Returns an array containing all of the result set rows
     *
     */
    public function select()
    {
        $this->beforeAction();
        $sql = $this->buildSelectSql();
        $fetchSql = $this->getOptions('fetch_sql');
        $this->afterAction();
        if( $fetchSql ){
            return $sql;
        }
        return $this->db->fetchAll($sql);
    }

    /**
     * gets one record
     *
     * @return type
     *
     */
    public function find()
    {
        $this->beforeAction();
        $sql = $this->buildSelectSql();
        $this->afterAction();
        return $this->db->fetchOne($sql);
    }

    /**
     * gets select sql
     *
     * @return string
     *
     */
    public function buildSelectSql($sub = false)
    {
        $sql = $this->builder->select($this);
        return $sub ? '('.$sql.')' : $sql;
    }

    /**
     * 根据主键获取一条记录
     *
     * @param string $sql 查询sql
     * @param string $type 类型
     *
     * @return array or false
     *
     */
    public function getByPrimary($table, $primary, $field = '*')
    {
        $sql = 'select %s from %s where '.$this->getPrimary($table).'=%d';
        $sprintf_sql = sprintf($sql, $this->parsefield($field), $table, $primary);
        return  $this->fetchOne($sprintf_sql);
    }

    /**
     *  Deletes Data
     *
     *  @param  string $$talbe
     *
     *  @return int
     *
     */
    public function delete()
    {
        if( empty($this->options['where']) ){
            throw new Exception('The condition is required.');
        }
        // $sql = 'DELETE FROM  '.$this->options['table'].$this->options['where'];
        $this->beforeAction();
        $sql = $this->builder->delete($this);
        $fetchSql = $this->getOptions('fetch_sql');
        $this->afterAction();
        if( $fetchSql ){
            return $sql;
        }
        return $this->db->query($sql);
    }

    /**
     * gets primary key of table
     *
     * @return string
     *
     */
    public function getPrimary()
    {
        $this->db->query('DESC '.$this->options['table']);
        while($row = $this->fetch()){
            if( $row['Key']=='PRI' ){
                $primary = $row['Field'];
                break;
            }
        }
        return $primary;
    }

    protected function beforeAction()
    {

    }

    protected function afterAction()
    {
        $this->resetOptions();
    }

    /**
     *
     * data to table
     *
     * @param string $sql
     *
     * @return string
     *
     */
    public function displayTable($data)
    {
        $out = '';
        $out .= '<table border=1><tr>';
        foreach ($data[0] as $key => $value) {
            $out .= "<td>$key</td>";
        }
        $out .= '</tr>';
        foreach ($data as $key => $value) {
            $out .= '<tr>';
            foreach ($value as $k => $v) {
                $out .= '<td> &nbsp;'.$v.'</td>';
            }
            $out .= '</tr>';
        }
        $out .= '</table>';
        return $out;
    }

    /**
     * 
     */
    public function resetOptions()
    {
        if( !empty($this->options['table']) ){
            $table = $this->options['table'];
        }
        $this->options = [
            'fields' => '*',
            'table' => '',
            'join' => '',
            'where' => '',
            'group' => '',
            'having' => '',
            'order' => '',
            'limit' => '',
            'data' => '',
            'fetch_sql' => false,
        ];
        if( !empty($table) ){
            $this->setOption('table', $table);
        }
    }

    /**
     * 
     */  
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
    }    

    /**
     * @return mixed 
     */  
    public function getOptions($name = '')
    {
        if( '' === $name ){
            return $this->options;
        }
        return isset($this->options[$name]) ? $this->options[$name] : NULL;
    }   
    
    /**
     * 
     */  
    public function removeOption($name)
    {
        unset($this->options[$name]);
    }

    /**
     * 
     */
    public function count($field = '*')
    {
        if( !empty($this->getOptions('group')) ){
            $fieldValue = 'COUNT('.$field.')';
            $this->setOption('field', $fieldValue);
            $fetchSql = $this->getOptions('fetch_sql');
            $table = $this->buildSelectSql(true);
            $this->table($table);
            $this->resetOptions();
            return $this->aggregate('COUNT', '*', true, $fetchSql);  
        }
        return $this->aggregate('COUNT', $field); 
    }

    /**
     * 
     */
    public function max($field)
    {
        return $this->aggregate('MAX', $field); 
    }

    /**
     * 
     */
    public function min($field)
    {
        return $this->aggregate('MIN', $field); 
    }

    /**
     * 
     */
    public function avg($field)
    {
        return $this->aggregate('AVG', $field); 
    }

    /**
     * 
     */
    public function sum($field)
    {
        return $this->aggregate('SUM', $field); 
    }

    /**
     * 
     */
    public function aggregate($name, $field, $group = false, $fetchSql = false)
    {
        if( !empty($this->options['field']) ){
            $this->removeOption('field');
        }
        $fielValue = $name.'('.$field.') AS tmp_'.strtolower($name);
        $this->setOption('field', $fielValue);
        $this->setOption('limit', $this->builder->parseLimit(1));
        if( !$fetchSql ){
            $fetchSql = $this->getOptions('fetch_sql'); 
        }
        if( $group ){
            $this->alias('_group_count');
        }
        
        $sql = $this->buildSelectSql();
        //whether return sql
        if( $fetchSql  ){
            return $sql;
        }
        return $this->db->fetchColumn($sql);
    }

    /**
     * 
     */
    public function fetchSql($fetch = true)
    {
        $this->setOption('fetch_sql', $fetch);
        return $this;
    }

    /**
     * 
     */
    public function startTrans()
    {
        $this->db->startTrans();
    }

    /**
     * 
     */
    public function commit()
    {
        $this->db->commit();
    }

    /**
     * 
     */
    public function rollback()
    {
        $this->db->rollback();
    }



}