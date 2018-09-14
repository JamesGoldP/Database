<?php
namespace Nezumi\Drivers\Mysql;

interface ADatabase
{

    /**
     *  Whether auto conntction
     * 
     */
    public function open($config);

    /**
     * The databse connection method
     * 
     * @access public
     * 
     * @return resource
     * 
     */
    public function connect();
    
    /**
     * exectutes sql
     * 
     * @param string $sql 
     * 
     * @return resource or false
     * 
     */
    public function query($sql);

    /**
     * 获取sql在数据库影响的条数
     * 
     * @return int
     * 
     */
    public function affected_rows();

    /**
     * 取得上一步 INSERT 操作表产生的auto_increment,就是自增主键
     * 
     * @return int
     * 
     */
    public function insert_id();

    /**
     * 查询一条记录获取类型
     *
     * @param constant $type 返回结果集类型    
     *                  
     * 
     * @return array or false
     * 
     */
    public function fetch($type = MYSQLI_ASSOC );

    /**
     * 
     * 释放查询资源
     * 
     * 
     */
    public function free();
    
    /**
     * close connection
     * @return type
     */
    public function close();


    /**
     * get the inner error info.
     */
    public function get_error();


}