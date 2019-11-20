<?php
namespace Nezimi;

use PDO;

class Connection
{

    /**
     * @var 最近数据库查询资源
     */
    private $statement;

    /**
     * @var current databse connection resource
     */
    protected $link;

    /**
     * @var all database connection resource
     */
    protected $links;

    /**
     * @var databse connection configuration
     */
    public $config;

    /**
     * 
     */
    public $builder;

    public function __construct($config)
    {
        $this->config = $config;
        $this->builder = new $this->builderPosition;
    }

    /**
     * 
     */
	public function connect($linkNum = 0){
		//check PDO
		if(!class_exists('PDO')){
            throw new Exception('Don\'t support PDO');
        }
        
        if( !is_null($this->links[$linkNum]) ){
            return $this->links[$linkNum];
        }

		//start connection
		try{
            $config = $this->config;
            $this->parseDsn($config);
            //whether long connection
            if( $config['pconnect'] ){
                $config['params'][constant('PDO::ATTR_PERSISTENT')] = true;
            }
            $dsn = $this->parseDsn($config);
            $config['params'] = isset($config['params']) ? $config['params'] : []; 
            $this->link = $this->links[$linkNum] = new PDO($dsn, $config['username'], $config['password'], $config['params']);
            return $this->link;	
		} catch (PDOException $e){
            if( $config['autoconnect'] ){
                return $this->connect($linkNum);
            }
            throw $e;
		}
	    	
	}

    /**
     * 
     */
    public function query($sql)
    {
        $this->connect();
        if( !$this->link ){
            return false;
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
        $this->connect();
        if( !$this->link ){
            return false;
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

    /**
     * start transaction
     * @return type
     */
    public function startTrans()
    {
        if( !$this->link ){
            $this->connect();
        }
        $this->link->beginTransaction();
    }

    /**
     * auto commit enable
     * @return type
     */
    public function commit()
    {
        $this->link->commit();
    }

    /**
     * rollback sql
     * @return type
     */
    public function rollback()
    {
        $this->link->rollback();
    }

    
}