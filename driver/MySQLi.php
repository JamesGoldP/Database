<?php
namespace driver;


/**
 * 数据库CURD类.mysqli
 *
 * @author  Nezumi
 *
 * 
 */

class MySQLi extends ADatabase
{

    private $result;  //最近数据库查询资源


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
            return $this->connect();
        }
    }

    /**
     * 连接数据库方法
     * 
     * @access public
     * 
     * @return resource
     * 
     */
    public function connect()
    {
        $this->link = new \mysqli($this->config['hostname'], $this->config['username'], $this->config['password'], $this->config['database']);
        if( $this->link->connect_error ){
            return $this->throw_exception('连接数据库失败');
        }
        if( !$this->link->set_charset($this->config['charset']) ){
            return $this->throw_exception('设置默认字符编码失败');
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
        return $this->result; 
    }

    /**
     * 查询多条记录.
     * 
     * @param string $sql
     *  
     * @return array
     * 
     */
    public function fetch_all($sql) 
    {
        $this->query($sql);
        $result = array();
        while ($row = $this->fetch()) {
            $result[] = $row;
        }
        return $result;
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
     * 通过sql语句得到的值显示成表格
     * 
     * @param string $sql 
     * 
     * @return string
     * 
     */
    public function display_table($sql)
    {
        $display_que = $this->query($table);
        while ($display_arr = $this->fetch()) {
            $display_result[] = $display_arr;
        }
        $display_out = '';
        $display_out .= '<table border=1><tr>';
        foreach ($display_result as $display_key => $display_val) {
            if (0 == $display_key) {
                foreach ($display_val as $display_ky => $display_vl) {
                    $display_out .= "<td>$display_ky</td>";
                }
            } else {
                break;
            }
        }
        $display_out .= '</tr>';
        foreach ($display_result as $display_k => $display_v) {
            $display_out .= '<tr>';
            foreach ($display_v as $display_kid => $display_vname) {
                $display_out .= "<td> &nbsp;$display_vname</td>";
            }
            $display_out .= '</tr>';
        }
        $display_out .= '</table>';

        return $display_out;
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

}