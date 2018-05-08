<?php
namespace driver;
use PDO;
/**
 * 数据库CURD类.
 *
 * @author  Nezumi
 *
 return array (
    'default' => array (
        'hostname' => 'localhost',   //主机名
        'database' => 'mycms',       //数据库
        'username' => 'root',        //用户名
        'password' => 'pyl',         //密码
        'tablepre' => 'cms_',        //表前缀
        'charset' => 'utf8',         //连接字符集
        'type' => 'mysql',           //连接类型
        'debug' => true,             //是否调试
        'pconnect' => 0,             //是否长连接
        'autoconnect' => 0           //是否自动连接
        'params'=> array(
        )
    ),
);
 * 
 */
class PDOMySql{

    private $link;      //数据库连接

    private $statement;  //最近数据库查询资源
 
    private $config; //数据库连接信息

	public function __construct()
    {

	}


    /**
     *  是否自动连接,入口
     * 
     */
    public function open($config)
    {
        if(empty($config)){
            return $this->throw_exception('没有定义数据库配置');
        }
        $this->config = $config;
        if( $this->config['autoconnect'] ){
            $this->connect();
        }
    }

	public function connect(){
		//检查pdo类是否可用
		if(!class_exists('PDO')){
			return $this->throw_exception('不支持PDO，请先开启');
		}
		//是否长连接
        if( $this->config['pconnect'] ){
            $this->$config['params'][constant('PDO::ATTR_PERSISTENT')] = true;
        }
		//开始连接
		try{
			$this->link = new PDO('mysql:host='.$this->config['hostname'].';dbname='.$this->config['database'], $this->config['username'], $this->config['password'], $this->config['params']);
		} catch (PDOException $e){
			return $this->throw_exception($e->getMessage());
		}
        $this->link->exec('SET NAMES '.$this->config['charset']);
	    return $this->link;		
	}


    public function query($sql)
    {
        if($sql==''){
            return $this->throw_exception('sql不能为空');
        }
        if( !$this->link ){
            $this->connect();
        }
        //判断之前是否有结果集,如果有的话，释放结果集
        if( !empty($this->statement) ){
            $this->free();
        } 
        $this->statement = $this->link->prepare($sql);
        return  $this->statement->execute();
    }

    /**
     * 释放不需要的statement
     * 
     * 
     */
    public function free(){
        $this->statement = null;
    }


    /**
     * CUD 增改删
     * @param string $sql 
     * @return int or false
     */
    public function execute($sql)
    {
        if($sql==''){
            return $this->throw_exception('sql不能为空');
        }
        if( !$this->link ){
            $this->connect();
        }
        //判断之前是否有结果集,如果有的话，释放结果集
        if( !empty($this->statement) ){
            $this->free();
        }
        return $this->link->exec($sql);
    }

    /** 
     *  表中插入数据
     * 
     *  @access public
     *  @author Nezumi
     * 
     *  @param $data   array        插入数组
     *  @param $table  string       要插入数据的表名
     *  @param $return_insert_id boolean   是否返回插入ID
     *  @param $replace  boolean 是使用replace into 还是insert into
     * 
     *  @return boolean,query resource,int
     * 
     */
    public function insert( $data, $table, $return_insert_id = false, $replace = false )
    {
        if (empty($data)) {
            return $this->throw_exception('To insert array is required!');
        }
        $fields = array_keys($data);
        $values = array_values($data);

        array_walk($fields, array($this, 'add_special_char'));
        array_walk($values, array($this, 'add_quotation'));

        $fields_str = implode(',', $fields);
        $values_str = implode(',', $values);
        $method = $replace ? 'REPLACE' : 'INSERT';
        $insert_sql = $method.' INTO '.$table.'('.$fields_str.')'.' values('.$values_str.')';
        $return = $this->query($insert_sql);
        return $return_insert_id ? $this->insert_id() : $return;
    }

    /**
     *  表中更新数据
     *
     *  @access public
     *  @author  Nezumi
     *
     *  @param  string $data['tab_name'] 表名
     *  @param  array  $data['update_arr'] 更新数组
     *  @param  array  $data['condition'] = array(
     *  
     *  @return int 影响行数 
     * 
     */
    public function update($data, $table, $where, $return_affected_rows = false)
    {
        if (empty($data)) {
            return $this->throw_exception('To update array is required!');
        } else if (empty($where)) {
            return $this->throw_exception('The condition is required.');
        }
        $data_sql = '';  //更新sql
        //判断条件是否为空
        foreach ($data as $key => $values) {
            $data_sql .= $this->add_special_char($key).'='.$this->add_quotation($values).',';
        }
        $data_sql = substr($data_sql, 0, -1);
        $sql = 'UPDATE '.$table.' SET '.$data_sql.$this->parse_where($where);
        $return = $this->query($sql);
        return $return_affected_rows ? $this->insert_id() : $return;
    }

    /**
     * 查询多条记录.
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
    function select($fields='*', $table, $where = '', $limit = '', $order = '', $group = '', $key = '', $having = '') 
    {
        $sql = 'SELECT  '.$this->parse_fields($fields).' FROM '.$table. $this->parse_where($where).$this->parse_group($group).$this->parse_having($having).$this->parse_order($order).$this->parse_limit($limit);
        return $this->fetch_all($sql);
    }

    /**
     * 查询一条记录.
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
    function get_one($fields='*', $table, $where = '', $limit = '', $order = '', $group = '', $key = '', $having = '') 
    {
        $sql = 'SELECT  '.$this->parse_fields($fields).' FROM '.$table. $this->parse_where($where).$this->parse_group($group).$this->parse_having($having).$this->parse_order($order).$this->parse_limit($limit);
        return $this->fetch_one($sql);
    }

    /**
     *  Delete Datas
     *
     *  @param  string $$talbe
     * 
     *  @return int
     * 
     */
    public function delete($table, $where)
    {
        if( empty($where) ){
            return $this->throw_exception('The condition is required.');
        }
        $sql = 'DELETE FROM  '.$table.$this->parse_where($where);
        return $this->query($sql);
    }

    /**
     * 查询多条记录.
     *
     * @param string $sql 查询sql
     * @param constant $type 返回结果集类型 
     *                    PDO::FETCH_BOTH  PDO::FETCH_ASSOC PDO::FETCH_NUM
     * 
     * @return array $result 
     * 
     */
    public function fetch_all($sql, $type = PDO::FETCH_ASSOC) {
		$this->query($sql);
		$result = $this->statement->fetchAll($type);
		return $result;	
	}


	/**
     * 查询一条记录.
     *
     * @param string $sql 查询sql
     * @param constant $type 返回结果集类型 
     *                    PDO::FETCH_BOTH  PDO::FETCH_ASSOC PDO::FETCH_NUM
     * 
     * @return array $result 
     * 
     */
	public function fetch_one($sql, $type = PDO::FETCH_ASSOC) {
    	if($sql==''){
			return $this->throw_exception('sql不能为空');
		}
		$this->query($sql);
		$result = $this->statement->fetch($type);
		return $result;	
	}	

    /**
     * 获取sql在数据库影响的条数
     * 
     * @return int
     * 
     */
    public function affected_rows()
    {
        return $this->statement->rowCount();
    }

    /**
     * 取得上一步 INSERT 操作表产生的auto_increment,就是自增主键
     * 
     * @return int
     * 
     */
    public function insert_id()
    {
        return $this->link->lastInsertId();
    }

    /**
     * 根据主键获取一条记录
     *
     * @param string $sql 查询sql
     * @param string $type 类型
     * 
     * @return array $result 
     * 
     */
    public function get_byprimary($table, $primary, $fields = '*') 
    {
        $sql = 'select %s from %s where '.$this->get_primary($table).'=%d';
        $sql = sprintf($sql, $this->parse_fields($fields), $table, $primary);
        $result = $this->fetch_one($sql);
        return $result; 
    }   

    /**
     * 获取数据表主键
     * 
     * @param $table        数据表
     * 
     * @return array
     * 
     */
    public function get_primary($table) 
    {
        $this->statement = $this->link->query('DESC '.$table);
        while($row = $this->statement->fetch(PDO::FETCH_ASSOC)){
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
     * @param string or array 字段添加`
     * 
     * @return string 
     * 
     */
    public function parse_fields($fields){
        $fields_str = '';
        if( is_string($fields) && trim($fields)== '*'){
            $fields_str = '*';
        } else if( is_string($fields) ){
            $arr = explode(',', $fields);
            $fields_str = implode(',', $arr);
        } else if( is_array($fields)  ){
            $fields_str = implode(',', $fields);
        } else {
            $fields_str = '*';
        }
        return $fields_str;
    }
    

    /**
     * Parse where
     *
     * @param string $where 
     * 
     * @return string 
     * 
     */
    public function parse_where($where)
    {
        $where_str = '';
        if( $where == '' ){
            return $where_str;
        } else if( is_string($where) ){
            $where_str = ' where '.$where;
        } 
        return $where_str;
    }

    /**
     * Parse group
     *
     * @param string $group 
     * 
     * @return string 
     * 
     */
    public function parse_group($group)
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
    public function parse_having($having)
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
     * Parse order
     *
     * @param string $order 
     * 
     * @return string 
     * 
     */
    public function parse_order($order)
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
    public function parse_limit($limit)
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
     * Add `
     *
     * @param string $fields
     * 
     * @return string 
     * 
     */
    public function add_special_char(&$value){
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
    public function add_quotation(&$value, $key = '' , $user_data = '', $quotation=1){
        if($quotation){
            $quot = '\'';
        } else {
            $quot = '';
        }
        $value = $quot.$value.$quot;
        return $value; 
    }


    /**
     * 如果调试的话输出错误信息
     * @param string $errMsg 
     * @param string $sql 
     * @return boolean
     */
    public function throw_exception($errMsg = '' , $sql = '')
    {
        if( $this->config['debug'] ){
            $output = ''; 
            echo $sql.$errMsg;
        }
        return false;
    }

    /**
     * 关闭连接
     * @return type
     */
    public function close()
    {
        $this->link = NULL;
    }


}