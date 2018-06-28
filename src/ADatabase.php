<?php
namespace Nezumi;

class ADatabase
{

    /**
     * @var databse connection resource
     */
    protected $link;  

    /**
     * @var databse connection configuration
     */
    protected $config;

    /**
     * @var database conntion error
     */
    protected $error;

    /**
     * @var string
     */
    public $options = [
        'fields' => '',
        'table' => '',
        'join' => '',
        'where' => '',
        'group' => '',
        'having' => '',
        'order' => '',
        'limit' => '',
    ];

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
     *  Whether auto conntction
     * 
     */
    public function open($config)
    {
        
    }

    /**
     * The databse connection method
     * 
     * @access public
     * 
     * @return resource
     * 
     */
    public function connect()
    {

    }
    
    /**
     * exectutes sql
     * 
     * @param string $sql 
     * 
     * @return resource or false
     * 
     */
    public function query($sql)
    {

    }

    /**
     * 获取sql在数据库影响的条数
     * 
     * @return int
     * 
     */
    public function affected_rows()
    {

    }

    /**
     * 取得上一步 INSERT 操作表产生的auto_increment,就是自增主键
     * 
     * @return int
     * 
     */
    public function insert_id()
    {

    }

    /**
     * 查询一条记录获取类型
     *
     * @param constant $type 返回结果集类型    
     *                  
     * 
     * @return array or false
     * 
     */
    public function fetch($type = MYSQLI_ASSOC )
    {

    }

    /**
     * 
     * 释放查询资源
     * 
     * 
     */
    protected function free()
    {

    }
    
    /**
     * close connection
     * @return type
     */
    protected function close()
    {

    }

    /**
     * get the inner error info.
     */
    protected function get_error()
    {

    }

   /** 
     *  Inserting data from the table
     * 
     * 
     *  @param $data   array        插入数组
     *  @param $table  string       要插入数据的表名
     *  @param $return_insert_id boolean   是否返回插入ID
     *  @param $replace  boolean 是使用replace into 还是insert into
     * 
     *  @return boolean,query resource,int
     * 
     */
    public function insert( $data = '', $table = '', $return_insert_id = false, $replace = false )
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
        if (func_num_args()!=1){
            $this->options['table'] = $table;
        }
        $insert_sql = $method.' INTO '.$this->options['table'].'('.$fields_str.')'.' values('.$values_str.')';
        $this->afterAction();
        $return = $this->query($insert_sql);
        return $return_insert_id ? $this->insert_id() : $return;
        
    }

    /**
     *  Update data from the table
     *
     *  @access public
     *  @author  Nezumi
     *
     *  @param  string $data['tab_name'] 表名
     *  @param  array  $data['update_arr'] 更新数组
     *  @param  array  $data['condition'] = array(
     *  
     *  @return int number of affected rows in previous MySQL operation 
     * 
     */
    public function update($data = '', $table = '',  $where = '', $return_affected_rows = false)
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

        if (func_num_args()!=1){
            $this->parseWhere($where);
            $this->options['table'] = $table;
        } 
        if (empty($this->options['where'])) {
            $this->error = 'The condition is required.';
            return false;
        }

        $sql = 'UPDATE '.$this->options['table'].' SET '.$data_sql.$this->options['where'];
        $this->afterAction();
        $return = $this->query($sql);
        return $return_affected_rows ? $this->affected_rows() : $return;
    }

    /**
     * Returns an array containing all of the result set rows
     * 
     * @param string $fields 
     * @param string $table 
     * @param string $where 
     * @param string $limit 
     * @param string $order 
     * @param string $group 
     * @param string $key 
     * 
     * @return type
     * 
     */
    public function select($fields='*', $table = '', $where = '', $limit = '', $order = '', $group = '', $key = '', $having = '') 
    {
        if( func_num_args()==0 ){
            $this->arrayInsert($this->options, 1, ['FROM']);
            $sql = 'SELECT '.implode(' ', $this->options);
             $this->afterAction();
        } else {
            $sql = 'SELECT  '.$this->parseFields($fields).' FROM '.$table. $this->parseWhere($where).$this->parseGroup($group).$this->parseHaving($having).$this->parseOrder($order).$this->parseLimit($limit);
        }   
        return $this->fetch_all($sql);
    }

    /**
     * gets one record
     * 
     * @param string $fields 
     * @param string $table 
     * @param string $where 
     * @param string $limit 
     * @param string $order 
     * @param string $group 
     * @param string $key 
     * 
     * @return type
     * 
     */
    public function get_one($fields= '*', $table = '', $where = '', $limit = '', $order = '', $group = '', $key = '', $having = '') 
    {
        if( func_num_args()==0 ){
            $this->arrayInsert($this->options, 1, ['FROM']);
            $sql = 'SELECT '.implode(' ', $this->options);
             $this->afterAction();
        } else {
            $sql = 'SELECT  '.$this->parseFields($fields).' FROM '.$table. $this->parseWhere($where).$this->parseGroup($group).$this->parseHaving($having).$this->parseOrder($order).$this->parseLimit($limit);
        } 
        return $this->fetch_one($sql);
    }


    /**
     *  Deletes Data
     *
     *  @param  string $$talbe
     * 
     *  @return int
     * 
     */
    public function delete($table = '', $where='')
    {
        if( func_num_args()!=0 ){  
            $this->parseWhere($where);
            $this->options['table'] = $table;
        }
        if( empty($this->options['where']) ){
            $this->error = 'The condition is required.';
            return false;
        }  
        $sql = 'DELETE FROM  '.$this->options['table'].$this->options['where'];
        $this->afterAction();
        return $this->query($sql);

    }


    protected function afterAction()
    {
        $this->resetOptions();
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
    public function get_byprimary($table, $primary, $fields = '*') 
    {
        $sql = 'select %s from %s where '.$this->get_primary($table).'=%d';
        $sprintf_sql = sprintf($sql, $this->parseFields($fields), $table, $primary);
        return  $this->fetch_one($sprintf_sql);
    }   

    /**
     * 获取数据表主键
     * 
     * @param $table  数据表
     * 
     * @return string 
     * 
     */
    public function get_primary($table) 
    {
        $this->query('DESC '.$table);
        while($row = $this->fetch()){
             if( $row['Key']=='PRI' ){
                  $primary = $row['Field']; 
                  break;
             } 
        }
        return $primary;
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
            'fields' => '',
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

    /**
     * @param array $array  
     * @param int $position position of to insert array
     * @param to insert array
     * @return array $array 
     */
    public function arrayInsert(&$array, $position, $insert_array) {
        $first_array = array_splice ($array, 0, $position);
        $array = array_merge ($first_array, $insert_array, $array);
    }
    
}