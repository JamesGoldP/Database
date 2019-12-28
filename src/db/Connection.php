<?php
namespace zero\db;

use PDO;
use zero\Db;
use zero\Register;

abstract class Connection
{

    /**
     * @var 最近数据库查询资源
     */
    protected $statement;

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

    /**
     * @var whether field of table case
     */
    protected $fieldCase = PDO::CASE_LOWER;

    /**
     * @var string the sql of the query
     */
    protected $querySql;

    /**
     * equal $query->bind
     */
    protected $bind;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->builder = new $this->builderPosition($this);
    }

    /*
     * @return false or object
     */
    public static function instance( $id = 'master' )
    {
        $key = 'database_'.$id;
        $databaseConfig = Db::getConfig();
        if( empty($databaseConfig) ){
            throw new Exception('No config');
        }
        if( $id == 'master' ){
            $dbConfig = $databaseConfig['master'];
        } else {
            $dbConfig = $databaseConfig[array_rand($databaseConfig['slave'])];
        }
        $db = Register::get($key);
        if( !$db ){
            $connectorClass = 'zero\\db\\connector\\'.ucfirst($dbConfig['type']); 
            $db = new $connectorClass($dbConfig);
            Register::set($key, $db);
        }
        return $db;
    }

    /**
     * get the fields of the table
     */
    abstract protected function getFields($table);

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
     * R
     * @return 
     * @throw PDOException
     */
    public function query($sql, $bind = [])
    {
        $this->connect();
        if( !$this->link ){
            return false;
        }
        
        $this->bind = $bind;
        $this->querySql = $sql;
        //判断之前是否有结果集,如果有的话，释放结果集
        if( !empty($this->statement) ){
            $this->free();
        }
        
        try{
            $this->statement = $this->link->prepare($sql);
            $this->bindParam($bind);
            return $result = $this->statement->execute();
        } catch (\PDOException $e) {
            throw $e;
        }   
       
    }

    /**
     * CUD 
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

    protected function bindParam($data)
    {
        foreach($data as $key=>$val){
            $this->statement->bindValue($key, $val[0], $val[1]);
        }
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
    public function select(Query $query, $type = PDO::FETCH_ASSOC) {
        $options = $query->getOptions();
		$sql = $this->builder->select($query);

        if( $options['fetch_sql'] ){
            return $this->getRealSql($sql, $query->bind);
        }

        $this->query($sql, $query->bind);
		$result = $this->statement->fetchAll($type);
		return $result;	
	}

	/**
     * get one record
     * 
     * @param object Query
     * @param string $sql sql
     * @param constant $type return type 
     *                    PDO::FETCH_BOTH  PDO::FETCH_ASSOC PDO::FETCH_NUM
     * 
     * @return array $result 
     * 
     */
	public function find(Query $query, $type = PDO::FETCH_ASSOC) {
        $options = $query->getOptions();
        $query->setOption('limit', 1);
        $sql = $this->builder->select($query);

        if( $options['fetch_sql'] ){
            return $this->getRealSql($sql, $query->bind);
        }

        $this->query($sql, $query->bind);
        $result = $this->fetch($type);
		return $result;	
    }

    public function update(Query $query)
    {
        $options = $query->getOptions();
        $sql = $this->builder->update($query);

        if( $options['fetch_sql'] ){
            return $this->getRealSql($sql, $query->bind);
        }
        $result = $this->query($sql, $query->bind);
		return $result;	
    }

    public function insert(Query $query, $replace)
    {
        $options = $query->getOptions();
        $sql = $this->builder->insert($query, $replace);
        if( $options['fetch_sql'] ){
            return $this->getRealSql($sql, $query->bind);
        }
        $result = $this->query($sql, $query->bind);
		return $result;	
    }

    public function delete(Query $query)
    {
        $options = $query->getOptions();
        $sql = $this->builder->delete($query);

        if( $options['fetch_sql'] ){
            return $this->getRealSql($sql, $query->bind);
        }
        $result = $this->query($sql, $query->bind);
		return $result;	
    }

    /**
     * get a value of the special column
     *
     * @param string $sql sql
     * @return  mixed 
     * 
     */
	public function fetchColumn(Query $query, $sql) {
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
     * 
     */
    protected function getRealSql($sql, array $bind = [])
    {
        foreach($bind as $key=>$val){
            $value = $val[0];
            $type  = $val[1];
            if( PDO::PARAM_STR == $type ){
                $value = $this->builder->addSymbol(addslashes($value));
            } else if( PDO::PARAM_INT == $type ){
                $value = (float)$value;
            }
            
            $sql = str_replace(
                [$key],
                [$value],
                $sql
            );
        }
        return $sql;
    }

    /**
     * convert table from __TABNLE_NAME__ to prefix.table_name
     */
    public function parseSqlTable($table)
    {
        if( false !== strpos($table, '__') ){
            $prefix = $this->config['prefix'];
            preg_replace_callback('/__(A-Z0-9_-)__/U', function ($match) use ($prefix){
                return $prefix . strtolower($match(1)); 
            }, $sql);
        }
        return $sql;
    }

    public function getFieldBindType($type)
    {
        if( preg_match('/int|double|float|decimal|real|numeric|serial|bit/i', $type) ){
            $bindType = PDO::PARAM_INT;
        } elseif( preg_match('/bool/i', $type) ){
            $bindType = PDO::BOOL;    
        } else {
            $bindType = PDO::PARAM_STR;
        }
        return $bindType;
    }

    /**
     * 
     */
    public function getTableInfo($table, $method = '')
    {
        $info = $this->getFields($table);
        $fields = array_keys($info);
        
        $bind = $type = [];
        foreach($info as $key=>$val){
            $type[$key] = $val['type'];
            $bind[$key] = $this->getFieldBindType($val['type']); 
            if( !empty($val['primary']) ){
                $pk[] = $key;
            }
        }

        if( isset($pk) ){
            $pk = count($pk) > 1 ? $pk : $pk[0];
        } else {
            $pk = NUll;
        }
        $result = [
            'field' => array_keys($info),
            'type'  => $type,
            'bind'  => $bind,
            'pk'    => $pk,
        ];

        return $method ? $result[$method] : $result;
    }

    public function getLastSql()
    {
        return $this->getRealSql($this->querySql, $this->bind);
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
    
    public function fieldCase($info)
    {
        switch($this->fieldCase) {
            case PDO::CASE_LOWER:
                $info = array_change_key_case($info);
                break;
            case PDO::CASE_UPPER:
                $info = array_change_key_case($info, CASE_UPPER);
                break;
        }
        return $info;
    }

    /**
     * close the link
     * @return type
     */
    public function close()
    {
        $this->link = NULL;
    }


}