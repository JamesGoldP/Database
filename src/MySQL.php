<?php
namespace Nezumi;

class MySQL extends ADatabase
{

    /**
     * @var 最近数据库查询资源
     */
    private $lastqueryid;
 

    public function open($config)
    {
        if(empty($config)){
            $this->error = '没有定义数据库配置';
            return false;
        }
        $this->config = $config;
        if( $this->config['autoconnect'] ){
            $this->connect();
        }
    }

    public function connect()
    {
        if (!is_resource($this->link)) {
            //是否长连接
            $func = $this->config['pconnect'] ? 'mysql_pconnect' : 'mysql_connect';
            $this->link = $func($this->config['hostname'], $this->config['username'], $this->config['password']);
            if (mysql_select_db($this->config['database'])) {
                mysql_query('set names '.$this->config['charset']);
            } else {
                $this->error = '不能选择数据库';
                return false;
            }
        }
        return $this->link; 
    }

    /**
     * sql query
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
        $this->lastqueryid = mysql_query($sql, $this->link);
        if( $this->lastqueryid === FALSE ){
            $this->error = 'SQL ERROR:'.$sql;
            return false;
        }
        return $this->lastqueryid; 
    }


    /**
     *  Returns an array containing all of the result set rows
     * 
     * @param string $sql
     *  
     * @return array
     * 
     */
    public function fetch_all($sql) 
    {
        $this->query($sql);
        if( $this->lastqueryid === FALSE ){
            return false;
        }
        $data = [];
        while ($row = $this->fetch()) {
            $data[] = $row;
        }
        return $data;
    }

    /**
     * 查询一条记录
     *
     * @param string $sql 
     * 
     * @return array or false
     * 
     */
    public function fetch_one($sql) 
    {
        $this->query($sql);
        if( $this->lastqueryid === FALSE ){
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
    public function fetch($type = MYSQL_ASSOC ){
        $res = mysql_fetch_array($this->lastqueryid, $type);
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
       if( is_resource($this->lastqueryid) ){
            mysql_free_result($this->lastqueryid);
       } 
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
        $total_recordque = $this->query('select * from'.$table);
        return mysql_num_rows($total_recordque);
    }

    /**
     * 获取sql在数据库影响的条数
     * 
     * @return int
     * 
     */
    public function affected_rows()
    {
        return mysql_affected_rows($this->link);
    }

    /**
     * 取得上一步 INSERT 操作表产生的auto_increment,就是自增主键
     * 
     * @return int
     * 
     */
    public function insert_id()
    {
        return mysql_insert_id($this->link);
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
        return mysql_get_server_info($this->link);
    }

    /**
     * 关闭连接
     * @return type
     */
    public function close()
    {
        mysql_close($this->link);
        if(is_resource($this->link)){
            mysql_close($this->link);
        }
    }

    public function get_error()
    {
        return array(mysql_errno($this->link)=>mysql_error($this->link));
    }
}