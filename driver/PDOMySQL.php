<?php
namespace driver;

use PDO;

/**
 * 数据库CURD类.
 *
 * @author  Nezumi
 *
 * 
 */
class PDOMySql extends ADatabase
{

    private $statement;  //最近数据库查询资源
 

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


}