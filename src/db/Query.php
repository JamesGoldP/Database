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
use zero\helper\Str;

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
     * current the prefix of the table
     * 
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
        if( is_string($table) ){
            if( strpos($table, ',') ){
                $tables = explode(',', $table);
                $table = [];

                foreach($tables as $item){
                    list($item, $alias) = explode(' ', trim($item));
                    if($alias){
                        $this->alias([$item => $alias]);
                        $table[$item] = $alias;
                    } else {
                        $talbe[] = $item;
                    }
                }   
            } elseif (strpos($table, ' ')){
                list($table, $alias) = explode(' ', $table);
                $table = [$table => $alias];
                $this->alias($table);
            }
        } elseif (is_array($table)){
            $tables = $table;
            $table = [];

            foreach($tables as $key=>$val){
                if( is_numeric($key) ){
                    $table[] = $val;
                } else {
                    $this->alias([$key => $val]);
                    $table[$key] = $val;
                }
            }
        }

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
     *
     * @param string $table
     * @return void
     */
    public function getTable(string $table = ''): string
    {
        if( is_null($table) && isset($this->options['table']) ){
            return $this->options['table'];
        }

        $table = $table ?: $this->name;

        return $this->prefix. Str::snake($table);    
    }

    /**
     * the alias of the table 
     *
     * @param array|string $alias
     * @return void
     */
    public function alias($alias)
    {
        if( is_array($alias) ){
            foreach($alias as $key => $val){
                if( false !== strpos($table, '__') ){
                    $table = $this->connection->parseSqlTable($table);
                } else {
                    $table = $key;
                }
                $this->options['alias'][$table] = $val;
            } 
        } else {
            if( isset($this->options['table']) ){
                $table = is_array($this->options['table']) ? key($this->options['table']) : $this->options['table'];
                if( false !== strpos($table, '__') ){
                    $table = $this->connection->parseSqlTable($table);
                }
            } else {
                $table = $this->getTable();
            }

            $this->options['alias'][$table] = $alias;
        }

        return $this;
    }

    /**
     * 
     */
    public function model(Model $model)
    {
        $this->model = $model;
        return $this;
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
     * 转换结果集
     */
    protected function resultSetToModelCollection(array $resultSet)
    {
        foreach($resultSet as &$result){
            $this->resultToModel($result);
        }
        return $result->toCollection($resultSet);
    }

    public function get($data)
    {
        return $this->find($data);
    }

    /**
     * gets one record
     *
     * @return type
     *
     */
    public function find($data = null)
    {
        $this->parseOptions();

        if( !is_null($data) ){
            $this->parsePkWhere($data);
        }

        $result = $this->connection->find($this);

        if( $this->options['fetch_sql'] ){
            return $result;
        }

        //result
        if( !empty($this->model) ){
            return $this->resultToModel($result);
        } 
        return $result;
    }
    public function all($data = null)
    {
        return $this->select($data);
    }

    /**
     * Returns an array containing all of the result set rows
     *
     */
    public function select($data = null)
    {
        $this->parseOptions();

        if( !is_null($data) ){
            $this->parsePkWhere($data);
        }

        $resultSet = $this->connection->select($this);

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
     * 指定查询数量
     *
     * @param mixed $offset
     * @param int $length
     * @return $this
     */
    public function limit($offset, $length = null)
    {
        if( is_null($length) && strpos($offset, ',') ){
            list($offset, $length) = explode(',', $offset);
        }

        $this->options['limit'] = intval($offset) . ($length ? ','. intval($length) : '');
        
        return $this;
    }

    public function join($join, $condition = null, $type = 'INNER')
    {
        if( empty($condition) ){
            //如果为数组，则表示有多个join，需要循环调用
            foreach($join as $key => $value) {
                if( is_array($value) && 2 <= count($value) ){
                    $this->join($value[0], $value[1], $value[2] ?? $type);
                }
            }
        } else {
            $table = $this->getJoinTable($join);

            $this->options['join'][] = [$table, strtoupper($type), $condition];
        }
        return $this;
    }

    /**
     * gets the table and alias of the join
     *
     * @param array|string $join
     * @return void
     */
    public function getJoinTable($join)
    {
        if( is_array($join) ){
            $table = $join;
        } else {
            $join = trim($join);
            $prefix = $this->prefix;
            
            if( strpos($join, ' ') ){
                //存在别名
                list($table, $alias) = explode(' ', $join);
            } else {
                $table = $join;
                //[$table=>$prefix.$table]
                if( false === strpos($join, '.') && 0 !== strpos($join, '__') ){
                    $alias = $join;
                }
            }

            if( $prefix && false === strpos($table, '.') && 0 !== strpos($table, '__') ){
                $table = $this->getTable($table);
            }

            if( isset($alias) && $table != $alias ){
                $table = [$table => $alias];
            }
        }
        return $table;
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
     * 把主键转换为查询条件
     *
     * @param  $data
     * @return void
     */
    public function parsePkWhere($data)
    {
        $pk = $this->getPk($this->options);

        $table = $this->options['table'];

        $alias = $this->options['alias'][$table] ?? null;

        $key = !is_null($alias) ? $alias . '.' . $pk : $pk;
        
        if( is_array($data) ){
            $where[$pk] = isset($data[$pk]) ? [$key, '=', $data[$pk]] : [$key, 'in', $data];
        } else {
            $where[$pk] = strpos($data, ',') ? [$key, 'IN', $data] : [$key, '=', $data];
        }
        
        if( !empty($where) ){
            if( isset($this->options['where']['AND']) ){
                $this->options['where']['AND'] = array_merge($this->options['where']['AND'], $where);
            } else {
                $this->options['where']['AND'] = $where;
            }
        }
    }

    public function getPk($options = '')
    {
        if( !empty($this->pk) ){
            $pk = $this->pk;
        } else {
            $pk = $this->connection->getTableInfo( is_array($options) ? $options['table'] : $this->getTable(), 'pk'  );
        }
        return $pk;
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
            $options['table'] = $this->getTable(); 
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

    /**
     * remove the condition of the where
     *
     * @param string $filed
     * @param string $logic
     * @return void
     */
    public function removeWhereField(string $filed, string $logic = 'AND')
    {
        $logic = strtoupper($logic);

        if( isset($this->options['where'][$logic]) ){
            foreach( $this->options['where'][$logic] as $key => $val ) {
                if( is_array($val) && $val[0] == $filed ){
                    unset($this->options['where'][$logic][$key]);
                }
            }
        }
        
        return $this;
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