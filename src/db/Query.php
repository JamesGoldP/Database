<?php
/**
 * Created by PhpStorm.
 * User: PengYilong
 * Date: 2018/10/14
 * Time: 1:09 PM
 */

namespace zero\db;

use Exception;
use zero\Db;
use zero\Model;
use zero\db\Connection;

class Query{

    /**
     * @var 
     */
    protected $connection;

    /**
     * query params
     * @var string
     * 
     */
    public $options = [];

    /**
     * current model
     */
    private $model;

    /**
     * the name of the table with no prefix
     */
    protected $name;

    /**
     * @var string prefix
     */
    protected $prefix;

    /**
     * @var array
     */
    public $bind = [];

    public function __construct()
    {
        $this->connection = Connection::instance();
        $this->prefix = $this->connection->config['prefix'];
    }

    public function newInstance()
    {
        return new static();
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
     * table name with no prefix
     */
    public function name($table)
    {
        $this->name = $table;
        return $this;
    }

    /**
     * gets table
     */
    public function getTable($table = '')
    {
        if( is_null($table) && isset($this->options['table']) ){
            return $this->options['table'];
        }
        $table = $table ?: $this->name;
        return $this->prefix. Db::parseName($table);    
    }

    /**
     * 
     */
    public function alias($name)
    {
        $this->setOption('table', $this->getOptions('table').' AS '.$name);
        return $this;
    }

    /**
     * 
     */
    public function model(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Inserting one data
     *
     * @param array $data
     * @param boolean $replace
     * @param boolean $getLastInsId
     * @return void
     */
    public function insert( array $data, bool $replace = false, bool $getLastInsId = false )
    {
        $data = array_merge($this->options['data'], $data);
        $this->setOption('data', $data);
        $this->beforeAction();
        $result = $this->connection->insert($this, $replace);
        $this->afterAction();

        return $result;
    }

    /**
     * insert multi record
     *
     * @param array $dataSet
     * @param boolean $replace
     * @return void
     */
    public function insertAll( array $dataSet = [], bool $replace = false )
    {
        $dataSet = array_merge($this->options['data'], $dataSet);
        $this->setOption('data', $dataSet);
        $this->beforeAction();
        $result = $this->connection->insertAll($this, $replace);
        $this->afterAction();
        return $result;
    }

    /**
     *  Update data from the table
     *
     *  @return int number of affected rows in previous MySQL operation
     *
     */
    public function update($data = [], $return_affected_rows = false)
    {
        $this->setOption('data', $data);
        $this->beforeAction();
        $result = $this->connection->update($this);
        $this->afterAction();

        if( $this->options['fetch_sql'] ){
            return $result;
        }
        return $return_affected_rows ? $this->affectedRows() : $result;
    }

    /**
     * Returns an array containing all of the result set rows
     *
     */
    public function select()
    {
        $this->beforeAction();
        $resultSet = $this->connection->select($this);
        $this->afterAction();

        if( $this->options['fetch_sql'] ){
            return $resultSet;
        }

        //result
        if( !empty($this->model) ){
            return $this->resultSetToModelCollection($resultSet);
        }
        return $resultSet;
    }

    /**
     * 转换结果集
     */
    protected function resultSetToModelCollection(array $resultSet)
    {
        foreach($resultSet as &$value){
            $this->resultToModel($value);
        }
        return $resultSet;
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
        $result = $this->connection->find($this);
        $this->afterAction();

        if( $this->options['fetch_sql'] ){
            return $result;
        }

        //result
        if( !empty($this->model) ){
            return $this->resultToModel($result);
        } 
        return $result;
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
        $this->beforeAction();
        $result = $this->connection->delete($this);
        $this->afterAction();

        if( $this->options['fetch_sql'] ){
            return $result;
        }

        return $result;
    }

    /**
     * set data
     *
     * @param [type] $field
     * @param [type] $value
     * @return void
     */
    public function data($field, $value = null)
    {
        if( is_null($value) ){
            $this->options['data'] = !empty($this->options['data']) ? array_merge($this->options['data'], $field) : $field; 
        } else {
            $this->options['data'][$field] = $value;
        }   
        return $this;
    }

    /**
     * 指定字段
     *
     * @param mixed $field
     * @return $this
     */
    public function field($field)
    {
        if( empty($field) ){
            return $this;
        }

        if( is_string($field) ){
            $field = array_map('trim', explode(',', $field));
        }

        if( isset($this->options['field']) ){
            $field = array_merge($this->options['field'], $field);
        }

        $this->options['field'] = array_unique($field);

        return $this;
    }

    /**
     * gets select sql
     *
     * @return string
     *
     */
    public function buildSelectSql($sub = false)
    {
        $sql = $this->connection->builder->select($this);
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
        return  $this->find($this, $sprintf_sql);
    }

    /**
     * gets primary key of table
     *
     * @return string
     *
     */
    public function getPrimary()
    {
        $this->connection->query('DESC '.$this->options['table']);
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
        $this->parseOptions();
    }

    protected function afterAction()
    {
        
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
    public function parseOptions()
    {
        $options = $this->options;

        //获取数据表
        if( empty($options['table']) ){
            $this->setOption('table', $this->getTable());
        }

        if( !isset($options['field']) ){
            $options['field'] = '*';
        }   

        foreach(['data', 'join', 'where'] as $name){
            if( !isset($options[$name]) ){
                $options[$name] = [];
            }
        }

        foreach(['fetch_sql'] as $name){
            if( !isset($options[$name]) ){
                $options[$name] = false;
            }
        }

        foreach(['group', 'having', 'limit', 'order'] as $name){
            if( !isset($options[$name]) ){
                $options[$name] = '';
            }
        }
        $this->options = $options;

        return $options;
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
        return $this->options[$name] ?? null;
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
            $this->parseOptions();
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
        $this->setOption('limit', $this->connection->builder->parseLimit(1));
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
        return $this->connection->fetchColumn($this, $sql);
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
        $this->connection->startTrans();
    }

    /**
     * 
     */
    public function commit()
    {
        $this->connection->commit();
    }

    /**
     * 
     */
    public function rollback()
    {
        $this->connection->rollback();
    }

    public function query($sql)
    {
        return $this->connection->find($this, $sql);
    }

    public function resultToModel(&$result)
    {
        return $result = $this->model->newInstance($result); 
    }

    /**
     * 
     */
    public function where($field, $operator = NULL, $condition = NULL, $param = [])
    {
        $param = func_get_args();
        $this->parseWhereExp('AND', $field, $operator, $condition, $param);
        return $this;
    }

    /**
     * 
     */
    public function whereOr($field, $operator = NULL, $condition = NULL)
    {
        $this->parseWhereExp('OR', $field, $operator, $condition);
        return $this;
    }

    protected function parseWhereExp($logic, $field, $operator, $condition, $param = [])
    {
        $logic = strtoupper($logic);

        if( is_array($field) ){
            return $this->parseArrayWhereItems($field, $logic);
        } else if($field instanceof \Closure){
            $where = $field;
            $field = '';
        } else if( is_string($field) ){
            if( preg_match('/[\<\>=]/', $field) ){
                return $this->whereRaw($field, $operator, $logic);
            }
            $where = $this->parseWhereItem($field, $operator, $condition, $param);
        }

        if( isset($this->options['where'][$logic][$field]) ){
            $this->options['where'][$logic][] = $where;
        } else {
            $this->options['where'][$logic][$field] = $where;
        }
    }

    public function whereRaw($field, $operator, $logic)
    {
        $this->options['where'][$logic][] = $this->raw($field);   
    }

    public function raw($value)
    {
        return new Expression($value);
    }

    protected function parseWhereItem($field, $operator, $condition, $param)
    {
        if( is_array($operator) ){
            $where = $param;
        } else if( is_null($condition) ){
            $where = [$field, '=', $operator];
        } else {
            $where = [$field, $operator, $condition]; 
        }
        return $where;
    }

    protected function parseArrayWhereItems($field, $logic)
    {
        if( key($field)!==0 ){
            foreach($field as $key=>$val){
                $where[] = [$key, is_array($val) ? 'IN' : '=', $val];
            } 
        } else {
            $where = $field;
        }
        $whereItem = &$this->options['where']; 
        $whereItem[$logic] = isset($whereItem[$logic]) ? array_merge($whereItem[$logic], $where) : $where;
    }

    public function bind($value, $type, $name = NULL)
    {
        $name = $name ?: ':Bind_' . (count($this->bind)+1) . '_';
        $this->bind[$name] = [$value, $type];
        return $name;
    }

    public function isBind($key)
    {
        return isset($this->bind[$key]);
    }

    public function getLastSql()
    {
        return $this->connection->getLastSql();
    }
   
}