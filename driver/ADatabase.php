<?php
namespace driver;

abstract class ADatabase
{

    protected $link;   //数据库连接资源

    protected $result;  //最近数据库查询资源

    protected $statement;  //PDO最近数据库查询资源

    protected $config; //数据库连接信息

    abstract public function open($config);
    abstract public function connect();
    /**
     * sql执行
     * 
     * @param string $sql 
     * 
     * @return resource or false
     * 
     */
    abstract public function query($sql);

	
}