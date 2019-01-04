<?php
namespace Nezumi;

use PDO;

class Connection
{

    /**
     * @var 最近数据库查询资源
     */
    private $statement;

    /**
     * @var databse connection resource
     */
    public $link;

    /**
     * @var databse connection configuration
     */
    protected $config;

    public function open($config)
    {
        $this->config = $config;
        if( $this->config['autoconnect'] ){
            $this->connect();
        }
    }

    /**
     * 
     */
	public function connect(){
		//检查pdo类是否可用
		if(!class_exists('PDO')){
            throw new Exception('Do not support PDO');
        }
		//whether long connection
        if( $this->config['pconnect'] ){
            $this->config['params'][constant('PDO::ATTR_PERSISTENT')] = true;
        }
		//start connection
		try{
            $this->config['params'] = isset($this->config['params']) ? $this->config['params'] : array(); 
			$this->link = new PDO('mysql:host='.$this->config['hostname'].';dbname='.$this->config['database'], $this->config['username'], $this->config['password'], $this->config['params']);
		} catch (PDOException $e){
            throw new Exception($e->getMessage());
		}
        $this->link->exec('SET NAMES '.$this->config['charset']);
	    return $this->link;		
	}

    /**
     * 
     */
    public function query($sql)
    {
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
     * CUD 增改删
     * @param string $sql 
     * @return int or false
     */
    public function execute($sql)
    {
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
     * get multi records
     *
     * @param string $sql sql
     * @param constant $type return type
     *                    PDO::FETCH_BOTH  PDO::FETCH_ASSOC PDO::FETCH_NUM
     * 
     * @return array $result 
     * 
     */
    public function fetchAll($sql, $type = PDO::FETCH_ASSOC) {
		$this->query($sql);
		$result = $this->statement->fetchAll($type);
		return $result;	
	}

	/**
     * get one record
     *
     * @param string $sql sql
     * @param constant $type return type 
     *                    PDO::FETCH_BOTH  PDO::FETCH_ASSOC PDO::FETCH_NUM
     * 
     * @return array $result 
     * 
     */
	public function fetchOne($sql, $type = PDO::FETCH_ASSOC) {
		$this->query($sql);
		$result = $this->fetch($type);
		return $result;	
    }

    /**
     * get a value of the special column
     *
     * @param string $sql sql
     * @return  mixed 
     * 
     */
	public function fetchColumn($sql) {
		$this->query($sql);
        $res = $this->statement->fetchColumn();
        return $res; 
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
    public function fetch($type = PDO::FETCH_ASSOC ){
        $res = $this->statement->fetch($type);
        //如果查询失败，返回False,那么释放改资源
        if(!$res){
            $this->free();
        }
        return $res; 
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
     * 获取sql在数据库影响的条数
     * 
     * @return int
     * 
     */
    public function affectedRows()
    {
        return $this->statement->rowCount();
    }

    /**
     * 取得上一步 INSERT 操作表产生的auto_increment,就是自增主键
     * 
     * @return int
     * 
     */
    public function insertId()
    {
        return $this->link->lastInsertId();
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