<?php
namespace Nezumi\Drivers\Mysql;

class MySQLi implements ADatabase
{

    /**
     * @var 最近数据库查询资源
     */
    private $result;

    public function open($config)
    {
        if(empty($config)){
            $this->error = '没有定义数据库配置';
            return false;
        }
        $this->config = $config;
        if( $this->config['autoconnect'] ){
            return $this->connect();
        }
    }

    public function connect()
    {
        $this->link = new \mysqli($this->config['hostname'], $this->config['username'], $this->config['password'], $this->config['database']);
        if( $this->link->connect_error ){
            $this->error = '连接数据库失败';
            return false;
        }
        if( !$this->link->set_charset($this->config['charset']) ){
            $this->error = '设置默认字符编码失败';
            return false;
        }
        return $this->link; 
    }

    /**
     * sql执行
     * 
     * @param string $sql 
     * 
     * @return resource or false
     * 
     */
    public function query($sql)
    {
        if ($sql == '') {
            return false;
        }
        //如果autoconnect关闭，那么连接的时候这里检查来启动mysql实例化
        if (!is_resource($this->link)) {
            $this->connect();
        }
        $this->result = $this->link->query($sql);
        if( $this->result === FALSE ){
            $this->error = 'SQL ERROR:'. $sql;
            return false;
        }
        return $this->result; 
    }

    /**
     * Returns an array containing all of the result set rows
     * 
     * @param string $sql
     *  
     * @return array
     * 
     */
    public function fetch_all($sql) 
    {
        $this->query($sql);
        if( $this->result === FALSE ){
            return false;
        }
        $row = [];
        $data = [];
         while ($row = $this->fetch()) {
            $data[] = $row;
        }           
        return $data;
    }

    /**
     * Returns an array containing a result set rows
     *
     * @param string $sql 
     * 
     * @return array or false
     * 
     */
    public function fetch_one($sql) 
    {
        $this->query($sql);
        if( $this->result === FALSE ){
            return false;
        }
        return $this->fetch();
    }

    /**
     * 查询一条记录获取类型
     *
     * @param constant $type 返回结果集类型    
     *                  MYSQL_ASSOC，MYSQL_NUM 和 MYSQL_BOTH
     * 
     * @return array or false
     * 
     */
    public function fetch($type = MYSQLI_ASSOC ){
        $res = $this->result->fetch_array($type);
        //如果查询失败，返回False,那么释放改资源
        if(!$res){
            $this->free();
        }
        return $res; 
    }

    /**
     * 
     * 释放查询资源
     * 
     * 
     */
    public function free(){
       $this->result = NULL;
    }

    /**
     * 查询表的总记录条数 total_record(表名)
     * 
     * @param string $table 
     * 
     * @return int
     * 
     */
    public function total_record($table)
    {
        $this->result = $this->query('select * from'.$table);
        return $this->result->num_rows;
    }

    /**
     * 获取sql在数据库影响的条数
     * 
     * @return int
     * 
     */
    public function affected_rows()
    {
        return $this->link->affected_rows;
    }

    /**
     * 取得上一步 INSERT 操作表产生的auto_increment,就是自增主键
     * 
     * @return int
     * 
     */
    public function insert_id()
    {
        return $this->link->insert_id;
    }

    /**
     * 显示表配置信息(表引擎)
     * 
     * @param string $table 
     * 
     * @return string
     * 
     */
    public function table_config($table)
    {
        $sql = 'SHOW TABLE STATUS from '.$this->config['database'].' where Name=\''.$table.'\'';
        return $this->display_table($table_config_que);
    }

    /**
     * 显示数据库表信息
     * 
     * @param string $table 
     * 
     * @return string
     * 
     */
    public function tableinfo($table)
    {
        $sql = 'SHOW CREATE TABLE '.$table;
        return $this->display_table($sql);;
    }

    /**
     * 显示服务器信息
     * 
     * @param string $table 
     * 
     * @return string
     * 
     */
    public function serverinfo()
    {
        return $this->link->server_info;
    }

    /**
     * 关闭连接
     * @return type
     */
    public function close()
    {
        if(is_resource($this->link)){
            $this->link->close();
        }
    }

    /**
     * get the inner error info.
     */
    public function get_error()
    {
        return array($this->link->errno=>$this->link->error);
    }
}