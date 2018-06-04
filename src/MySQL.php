<?php
namespace yilongpeng;

class MySQL extends ADatabase
{

    private $lastqueryid;  //最近数据库查询资源
 

    /**
     *  是否自动连接,入口
     * 
     */
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
        $this->lastqueryid = mysql_query($sql, $this->link);
        if( $this->lastqueryid === FALSE ){
            $this->error = 'SQL ERROR:'.$sql;
            return false;
        }
        return $this->lastqueryid; 
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
        if( $this->lastqueryid === FALSE ){
            return false;
        }
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