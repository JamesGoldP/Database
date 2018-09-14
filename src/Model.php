<?php
/**
 * Created by PhpStorm.
 * User: PengYilong
 * Date: 2018/9/8
 * Time: 1:24 PM
 */

namespace Nezumi;

use Nezumi\Drivers\Mysql\PDOMySql;

class Model{

    /**
     * @var
     */
    protected $data;

    /**
     * @var
     */
    protected $autoWriteTimestamp;

    /**
     * @var
     */
    protected $createTime;

    /**
     * @var
     */
    protected $updateTime;

    /**
     * @var
     */
    protected $db;

    /**
     * @var string prefix
     */
    protected $prefix;

    /**
     * @var string name of table
     */
    protected $table = NULL;

    /**
     * @var
     */
    protected $cache;

    /**
     * @var string
     */
    public $options = [
        'fields' => '*',
        'table' => '',
        'join' => '',
        'where' => '',
        'group' => '',
        'having' => '',
        'order' => '',
        'limit' => '',
    ];

    public function __construct()
    {
        $this->getDatabase();
        $db_config = Db::getConfig()['master'];
        $this->prefix = $db_config['tablepre'];
        $this->table = $this->getModelName();
        $this->options['table'] = $this->table;
    }

    public function getModelName()
    {
        $sub_arr = explode('\\', get_class($this));
        $sub_class = end($sub_arr);
        return  $this->prefix.to_underscore($sub_class);
    }

    public function getDatabase( $id = 'master' )
    {
        $key = 'database_'.$id;
        $database_config = Db::getConfig();
        if( empty($database_config) ){
            return false;
        }
        if( $id == 'master' ){
            $db_config = $database_config['master'];
        } else {
            $db_config = $database_config[array_rand($database_config['slave'])];
        }
        $db = Register::get($key);
        if( !$db ){
            switch ($db_config['type']) {
                case 'pdo':
                    $db = new PDOMySql();
                    break;
                default:
                    $db = new PDOMySql();
            }
            $db->open($db_config);
            Register::set($key, $db);
        }
        $this->db = $db;

    }

    public function __call($name ,$arguments)
    {
        if( array_key_exists($name, $this->options) ){
            $method = 'parse'.ucwords($name);
            $result = $this->$method($arguments[0]);
            $this->options[$name] = $result;
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
    public function insert( $data = '', $return_insert_id = false, $replace = false )
    {

        if (empty($data)) {
            $this->error = 'The insert array is required!';
            return false;
        }
        $fields = array_keys($data);
        $values = array_values($data);

        array_walk($fields, array($this, 'addBackquote'));
        array_walk($values, array($this, 'addQuotes'));

        $fields_str = implode(',', $fields);
        $values_str = implode(',', $values);
        $method = $replace ? 'REPLACE' : 'INSERT';

        $this->beforeAction();
        $insert_sql = $method.' INTO '.$this->options['table'].'('.$fields_str.')'.' values('.$values_str.')';
        $this->afterAction();
        $return = $this->db->query($insert_sql);
        return $return_insert_id ? $this->db->insert_id() : $return;
    }

    /**
     *  Update data from the table
     *
     *  @return int number of affected rows in previous MySQL operation
     *
     */
    public function update($data = [], $return_affected_rows = false)
    {
        if (empty($data)) {
            $this->error = 'To update array is required!';
            return false;
        }
        $data_sql = '';
        foreach ($data as $key => $values) {
            $data_sql .= $this->addBackquote($key).'='.$this->addQuotes($values).',';
        }
        $data_sql = substr($data_sql, 0, -1);

        if (empty($this->options['where'])) {
            $this->error = 'The condition is required.';
            return false;
        }
        $this->beforeAction();
        $sql = 'UPDATE '.$this->options['table'].' SET '.$data_sql.$this->options['where'];
        $this->afterAction();
        $return = $this->db->query($sql);
        return $return_affected_rows ? $this->affected_rows() : $return;
    }

    /**
     * Returns an array containing all of the result set rows
     *
     */
    public function select()
    {
        $sql = $this->builcSelectSql();
        return $this->db->fetch_all($sql);
    }

    /**
     * gets one record
     *
     * @return type
     *
     */
    public function get_one()
    {
        $sql = $this->builcSelectSql();
        return $this->db->fetch_one($sql);
    }

    /**
     * gets sql
     *
     * @return string
     *
     */
    public function builcSelectSql()
    {
        $this->beforeAction();
        $this->options = array_insert($this->options, 1, ['FROM']);
        $sql = 'SELECT '.implode(' ', $this->options);
        $this->afterAction();
        return $sql;
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
    public function getByPrimary($table, $primary, $fields = '*')
    {
        $sql = 'select %s from %s where '.$this->getPrimary($table).'=%d';
        $sprintf_sql = sprintf($sql, $this->parseFields($fields), $table, $primary);
        return  $this->fetch_one($sprintf_sql);
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
            $this->error = 'The condition is required.';
            return false;
        }
        $sql = 'DELETE FROM  '.$this->options['table'].$this->options['where'];
        $this->afterAction();
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
     * Parse fields
     *
     * @param string or array
     *
     * @return string
     */
    public function parseFields($data){
        $str = '';
        if( is_string($data) && trim($data)== '*'){
            $str = '*';
        } else if( is_string($data) ){
            $arr = explode(',', $data);
            $str = implode(',', $arr);
        } else if( is_array($data)  ){
            $str = implode(',', $data);
        } else {
            $str = '*';
        }
        return $str;
    }

    /**
     * Parse fields
     *
     * @param string or array
     *
     * @return string
     */
    public function parseTable($str){
        return $str;
    }

    /**
     * Parse where
     *
     * @param string $where
     *
     * @return string
     *
     */
    public function parseWhere($data)
    {
        $str = '';
        if( $data == '' ){
            return $str;
        } else if( is_string($data) ){
            $str = ' WHERE '.$data;
        } else if( is_array($data) ){
            $i = 0;
            $str .= ' WHERE ';
            foreach ($data as $key => $values) {
                $link = $i!=0 ? ' AND ' : '';
                $str .= $link.$this->addBackquote($key).'='.$this->addQuotes($values);
                $i++;
            }
        }
        return $str;
    }

    /**
     * Parse group
     *
     * @param string $group
     *
     * @return string
     *
     */
    public function parseGroup($group)
    {
        $group_str = '';
        if( $group == '' ){
            return $group_str;
        } else if( is_string($group) ){
            $group_str = ' GROUP BY '.$group;
        } else if( is_array($group) ){
            $group_str = ' GROUP BY '.implode(',', $group);
        }
        return $group_str;
    }

    /**
     * Parse having
     *
     * @param string $having
     *
     * @return string
     *
     */
    public function parseHaving($having)
    {
        $having_str = '';
        if( $having == '' ){
            return $having_str;
        } else if( is_string($having) ){
            $having_str = ' HAVING '.$having;
        }
        return $having_str;
    }

    /**
     *
     *
     * @param
     *
     * @return string
     *
     */
    public function parseJoin($data)
    {
        $str = '';
        if( $data == '' ){
            return $str;
        } else if( is_string($data) ){
            $str = ' LEFT JOIN '.$data;
        }
        return $str;
    }

    /**
     * Parse order
     *
     * @param string $order
     *
     * @return string
     *
     */
    public function parseOrder($order)
    {
        $order_str = '';
        if( $order == '' ){
            return $order_str;
        } else if( is_string($order) ){
            $order_str = ' ORDER BY '.$order;
        } else if( is_array($order) ){
            $order_str = ' ORDER BY '.implode(',', $order);
        }
        return $order_str;
    }

    /**
     * Parse limit
     *
     * @param string $limit
     *
     * @return string
     *
     */
    public function parseLimit($limit)
    {
        $limit_str = '';
        if( $limit == '' ){
            return $limit_str;
        } else if( is_string($limit) || is_numeric($limit) ){
            $limit_str = ' LIMIT '.$limit;
        } else if( is_array($limit) ){
            if( count($limit)==1 ){
                $limit_str = ' LIMIT '.$limit[0];
            } else {
                $limit_str = ' LIMIT '.$limit[0].','.$limit[1];
            }
        }
        return $limit_str;
    }

    /**
     * Add backquote
     *
     * @param string $fields
     *
     * @return string
     *
     */
    public function addBackquote(&$value){
        if( strpos($value,'`') ===false ){
            $value = '`'.trim($value).'`';
        }
        return $value;
    }

    /**
     * Add ''
     *
     * @param string $fields
     *
     * @return string
     *
     */
    public function addQuotes(&$value, $key = '' , $user_data = '', $quotation=1){
        if($quotation){
            $quot = '\'';
        } else {
            $quot = '';
        }
        $value = $quot.$value.$quot;
        return $value;
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
    public function display_table($data)
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
     * 如果调试的话输出错误信息
     * @param string $errMsg
     * @param string $sql
     * @return boolean
     */
    public function getError()
    {
        if( $this->config['debug'] ){
            if( !empty($this->error) ){
                return $this->error;
            } else {
                return $this->getThisError();
            }
        }
        return false;
    }

    public function throwException($message)
    {
        echo $message;
        exit();
    }


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
        ];
        if( !empty($table) ){
            $this->options['table'] = $table;
        }
    }

}