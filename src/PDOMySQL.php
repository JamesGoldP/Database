<?php
namespace Nezumi;

use PDO;

class PDOMySql extends ADatabase
{

    /**
     * @var 最近数据库查询资源
     */
    private $statement;
 
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

	public function connect(){
		//检查pdo类是否可用
		if(!class_exists('PDO')){
			$this->error = '不支持PDO，请先开启';
            return false;
		}
		//是否长连接
        if( $this->config['pconnect'] ){
            $this->$config['params'][constant('PDO::ATTR_PERSISTENT')] = true;
        }
		//开始连接
		try{
            $this->config['params'] = isset($this->config['params']) ? $this->config['params'] : array(); 
			$this->link = new PDO('mysql:host='.$this->config['hostname'].';dbname='.$this->config['database'], $this->config['username'], $this->config['password'], $this->config['params']);
		} catch (PDOException $e){
			$this->error = $e->getMessage();
            return false;
		}
        $this->link->exec('SET NAMES '.$this->config['charset']);
	    return $this->link;		
	}


    public function query($sql)
    {
        if($sql==''){
            $this->error = 'sql不能为空';
            return false;
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
     * CUD 增改删
     * @param string $sql 
     * @return int or false
     */
    public function execute($sql)
    {
        if($sql==''){
            $this->error = 'sql不能为空';
            return false;
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
			$this->error = 'sql不能为空';
            return false;
		}
		$this->query($sql);
		$result = $this->fetch($type);
		return $result;	
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
     * 关闭连接
     * @return type
     */
    public function close()
    {
        $this->link = NULL;
    }

    /**
     * get the inner error info.
     */
    public function get_error()
    {
        return array($this->link->errorCode()=>$this->link->errorInfo());
    }
}