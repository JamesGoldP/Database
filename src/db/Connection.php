<?php
namespace zero\db;

use PDO;
use PDOException;
use zero\Db;
use zero\Register;
use Exception;

abstract class Connection
{

    /**
     * @var 最近数据库查询资源
     */
    protected $statement;

    /**
     * @var string the sql of the query
     */
    protected $querySql;

    /**
     * 返回或者影响记录数
     *
     * @var integer
     */
    protected $numRows = 0;

    /**
     * @var current databse connection resource
     */
    protected $link;

    /**
     * @var all database connection resource
     */
    protected $links;

    /**
     * 查询结果类型
     *
     * @var [type]
     */
    protected $fetchType = PDO::FETCH_ASSOC;

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
     * equal $query->bind
     */
    protected $bind;

    /**
     * PDO 连接参数
     *
     * @var array
     */
    protected $params = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ];

    /**
     * 事务执行次数
     *
     * @var integer
     */
    protected $transTimes = 0;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->builder = new $this->builderPosition($this);
    }

    /**
     * Undocumented function
     *
     * @param string $name
     * @param boolean $force
     * @return void
     */
    public static function instance(string $name = null, bool $force = false)
    {
        $dbConfig = Db::getConfig();
        $db = Register::get($name);
        if( $force || !$db ){
            $connectorClass = 'zero\\db\\connector\\'.ucfirst($dbConfig['type']); 
            $db = new $connectorClass($dbConfig);
            Register::set($name, $db);
        }
        return $db;
    }

    /**
     * get the fields of the table
     */
    abstract protected function getFields($table);

    /**
     * 连接数据库方法
     *
     * @param array $config  连接参数
     * @param integer $linkNum 连接序号
     * @param boolean $autoConnection 是否自动连接主数据库
     * @return void
     */
	public function connect(array $config = [], int $linkNum = 0, bool $autoConnection = false){
		//check PDO
		if(!class_exists('PDO')){
            throw new Exception('Don\'t support PDO');
        }
        
        if( isset($this->links[$linkNum]) ){
            return $this->links[$linkNum];
        }

        if(!$config) {
            $config = $this->config;
        } else {
            $config = array_merge($this->config, $config);
        }

        // 连接参数
        if ( isset($config['params']) && is_array($config['params']) ) {
            $params = array_merge($config['params'], $this->params);
        } else {
            $params = $this->params;
        }

		//start connection
		try{
            if ( empty($config['dsn'] ) ) {
                $config['dsn'] = $this->parseDsn($config);;
            }
            
            //whether long connection
            if( $config['pconnect'] ){
                $config['params'][constant('PDO::ATTR_PERSISTENT')] = true;
            }
            $this->link = $this->links[$linkNum] = new PDO($config['dsn'], $config['username'], $config['password'], $params);
            return $this->link;	
		} catch (PDOException $e){
            if( $config['autoconnect'] ){
                return $this->connect($config, $linkNum, $autoConnection);
            } else {
                throw $e;
            }
		}
	}

    /**
     * 执行查询 返回数据集
     *
     * @param string $sql
     * @param array $bind
     * @return void
     */
    public function query(string $sql, $bind = []) : array
    {
        $this->initConnect();

        if( !$this->link ){
            return false;
        }
        
        $this->bind = $bind;
        
        // 记录SQL语句
        $this->querySql = $sql;

        //判断之前是否有结果集,如果有的话，释放结果集
        if( !empty($this->statement) ){
            $this->free();
        }
        
        try {
            $this->statement = $this->link->prepare($sql);
            $this->bindParam($bind);
            $this->statement->execute();
            
            //返回结果集
            return $this->getResult();
        } catch (PDOException $e) {
            throw $e;
        } catch(Exception $e) {
            throw $e;
        } 
       
    }

    /**
     * 执行语句 
     *
     * @param string $sql
     * @param array $bind
     * @return int
     */
    public function execute(string $sql, array $bind = []) : int
    {
        $this->initConnect();

        if( !$this->link ){
            return false;
        }
        
        $this->bind = $bind;
        
        // 记录SQL语句
        $this->querySql = $sql;

        //判断之前是否有结果集,如果有的话，释放结果集
        if( !empty($this->statement) ){
            $this->free();
        }
        
        try {
            $this->statement = $this->link->prepare($sql);
            $this->bindParam($bind);
            $this->statement->execute();
            
            $this->numRows = $this->statement->rowCount();

            return $this->numRows;
        } catch (PDOException $e) {
            throw $e;
        } catch(Exception $e) {
            throw $e;
        } 
    }

    protected function bindParam($data)
    {
        foreach($data as $key => $val){
            $result = $this->statement->bindValue($key, $val[0], $val[1]);
        }
        
    }

    /**
     * 获得数据集数组
     *
     * @return void
     */
    protected function getResult() : array
    {
        $result = $this->statement->fetchAll($this->fetchType);
        $this->numRows = count($result);

        return $result;
    }

    /**
     * get multi records
     *
     * @param Query $query
     * @return $result get multi records
     */
    public function select(Query $query) {
        $options = $query->getOptions();
		$sql = $this->builder->select($query);

        if( $options['fetch_sql'] ){
            return $this->getRealSql($sql, $query->bind);
        }

        $result = $this->query($sql, $query->bind);
		return $result;	
	}

    /**
     * get one record
     *
     * @param Query $query
     * @return $result array | string
     */
	public function find(Query $query) {
        $options = $query->getOptions();
        $query->setOption('limit', 1);
        $sql = $this->builder->select($query);
        
        if( $options['fetch_sql'] ){
            return $this->getRealSql($sql, $query->bind);
        }

        $resultSet = $this->query($sql, $query->bind);
        $result = $resultSet[0] ?? [];        

		return $result;	
    }

    /**
     * update records
     *
     * @param Query $query
     * @return $result array | int 
     */
    public function update(Query $query)
    {
        $options = $query->getOptions();
        $sql = $this->builder->update($query);

        if( $options['fetch_sql'] ){
            return $this->getRealSql($sql, $query->bind);
        }
        $result = $this->execute($sql, $query->bind);
		return $result;	
    }

    /**
     * Undocumented function
     *
     * @param Query $query
     * @param boolean $replace
     * @return int | string
     *  
     */
    public function insert(Query $query, bool $replace, bool $getLastInsId = false)
    {
        $options = $query->getOptions();

        $sql = $this->builder->insert($query, $replace);

        if( $options['fetch_sql'] ){
            return $this->getRealSql($sql, $query->bind);
        }

        $result = $this->execute($sql, $query->bind);

        if($result) {
            $lastInsId = $this->getLastInsId();

            if($getLastInsId) {
                return $lastInsId;
            }
        }

		return $result;	
    }

    /**
     * Undocumented function
     *
     * @param Query $query
     * @param boolean $replace
     * @return int | string
     *  
     */
    public function insertAll(Query $query, bool $replace, bool $getLastInsId = false)
    {
        $options = $query->getOptions();
        $sql = $this->builder->insertAll($query);
        if( $options['fetch_sql'] ){
            return $this->getRealSql($sql, $query->bind);
        }
        $result = $this->execute($sql, $query->bind);
		return $result;	
    }

    /**
     * 删除记录
     *
     * @param Query $query
     * @return string|int
     */
    public function delete(Query $query)
    {
        $options = $query->getOptions();
        $data = $options['data'];

        if( true !== $data && empty($options['where']) ) {
            throw new Exception('delele without condition!');
        }

        $sql = $this->builder->delete($query);

        if( $options['fetch_sql'] ){
            return $this->getRealSql($sql, $query->bind);
        }

        $result = $this->execute($sql, $query->bind);
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
     * gets a real sql
     *
     * @param string $sql
     * @param array $bind
     * @return string
     */
    protected function getRealSql(string $sql, array $bind = []) : string
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
     *
     * @param string $table
     * @return string
     */
    public function parseSqlTable(string $table): string
    {
        if( false !== strpos($table, '__') ){
            $prefix = $this->config['prefix'];
            preg_replace_callback('/__(A-Z0-9_-)__/U', function ($match) use ($prefix){
                return $prefix . strtolower($match(1)); 
            }, $table);
        }
        return $table;
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
     * Undocumented function
     *
     * @param [type] $table
     * @param string $method
     * @return void
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
            $pk = null;
        }

        $result = [
            'fields' => $fields,
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
     * 取得上一步 INSERT 操作表产生的auto_increment,就是自增主
     *
     * @return void
     */
    public function getLastInsId()
    {
        return $this->link->lastInsertId();
    }

    /**
     * start transaction
     * @return type
     */
    public function startTrans()
    {
        $this->initConnect(true);

        if( !$this->link ){
            return false;
        }

        ++$this->transTimes;

        try {
            if( 1 == $this->transTimes ){
                $this->link->beginTransaction();
            }
        } catch(Exception $e) {
            throw $e;
        }
        
    }

    protected function initConnect($master = true)
    {
        if( $this->config['deploy'] ){

        } elseif (!$this->link) {
            $this->link = $this->connect();
        }
    }


    /**
     * auto commit enable
     * @return type
     */
    public function commit()
    {
        if( 1 == $this->transTimes ){
            $this->link->commit();
        }
        
        --$this->transTimes;
    }

    /**
     * rollback
     * @return type
     */
    public function rollback()
    {
        if( 1 == $this->transTimes ){
            $this->link->rollback();
        }

        $this->transTimes = max(0, $this->transTimes-1);
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