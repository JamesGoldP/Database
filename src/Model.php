<?php
/**
 * Created by PhpStorm.
 * User: PengYilong
 * Date: 2018/9/8
 * Time: 1:24 PM
 */

namespace zero;

use zero\db\Query;
use Exception;
use Closure;

class Model implements \ArrayAccess, \Countable
{
    use model\concern\Attribute;
    use model\concern\Conversion;
    use model\concern\RelationShip;
    use model\concern\TimeStamp;

    /**
     * whether update is
     * @var boolean 
     */
    protected $isUpdate = false;

    /**
     * whether replace of the sql
     *
     * @var boolean
     */
    private $replace = false;

    /**
     * 是否强制更新所有数据
     *
     * @var boolean
     */
    private $force = false;

    /**
     * 更新条件
     *
     * @var [type]
     */
    private $updateWhere;

    /**
     * @var
     */
    protected $query;
    
    /**
     * the name of the model
     *
     * @var string
     */
    protected $name;

    /**
     * the name of the table
     * 
     * @var string 
     */
    protected $table;

    /**
     * @var string prefix
     */
    protected $prefix;

    /**
     * @var
     */
    protected $cache;

    /**
     * the name of the model
     */

    public function __construct($data = [])
    {
        $this->data = $data;

        if( $this->disuse ) {
            //废弃字段
            foreach( (array) $this->disuse as $key ) {
                if( array_key_exists($key, $this->data) ) {
                    unset($this->data[$key]);
                }
            }
        }

        //记录原始数据
        $this->origin = $this->data;

        $config = Db::getConfig();

        if( empty($this->name) ){
            $this->name = $this->getModelName();
        }

        if( is_null($this->autoWriteTimestamp) ) {
            //自动写入时间戳
            $this->autoWriteTimestamp = $config['auto_timestamp'];
        } 
    }

    /**
     * @return string
     */
    public function getModelName()
    {
        $arr = explode('\\', get_class($this));
        $class = end($arr);
        return $class;
    }
    
    /**
     * 
     * @param array $data
     * @return $this
     */
    public function newInstance(array $data = [], $isUpdate = false)
    {
        return (new static($data))->isUpdate($isUpdate);
    }

    public function db()
    {
        $query = $this->buildQuery();
        return $query;
    }

    /**
     * build a query
     */
    public function buildQuery()
    {
        //update table of Quer options
        $query = new Query();

        $query->model($this)->name($this->name);

        if( !empty($this->table) ){
            $query->table($this->table);
        }

        if( !empty($this->pk) ){
            $query->pk = $this->pk;
        }

        return $query;
    }

    /**
     * 保存当前数据对象
     *
     * @param array $data
     * @param array $where
     * @return boolean
     */
    public function save(array $data = [], array $where = []) : bool
    {
        //赋值给$this->data
        if( !$this->checkBeforeSave($data, $where) ){
            return false;
        }

        $result = $this->isUpdate ? $this->updateData($where) : $this->insertData();  
        
        if( false === $result ){
            return false;
        }

        return true;
    }

    /**
     * 删除当前的记录
     *
     * @return boolean
     */
    public function delete() : bool
    {
        if( !$this->isUpdate ) {
            return false;
        }

        //读取更新条件
        $where = $this->getWhere();

        $db = $this->db();
        $db->startTrans();
        try {
            //删除当前数据模型
            $db->where($where)->delete();

            $db->commit();

            $this->isUPdate = false;

            return true;
        } catch(Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * 删除记录静态方法
     *
     * @param mixed $data
     * @return boolean
     */
    public static function destroy($data) : bool
    {
        if( empty($data) && 0!== $data ) {
            return false;
        }

        $model = new static();

        $query = $model->db();

        if( is_array($data) && key($data) !== 0 ) {
            $query->where($data);
            $data = null;
        } elseif ($data instanceof Closure) {
            $data($query);
            $data = null;
        }

        $resultSet = $query->select($data);

        if($resultSet) {
            foreach ($resultSet as $data) {
                $data->delete();
            }   
        }

        return true;
    }
    
    /**
     * 写入之前赋值给$this->data
     *
     * @param array $data
     * @param array $where
     * @return void
     */
    protected function checkBeforeSave(array $data, array $where)
    {
        if( !empty($data) ){
            foreach($data as $key => $value) {
                $this->setAttr($key, $value);
            }

            if( !empty($where) ){
                $this->isUpdate = true;
                $this->updateWhere = $where;
            }
        }

        return true;
    }

    /**
     * 获取当前的更新条件
     *
     * @return void
     */
    protected function getWhere()
    {
        $where = [];

        if( is_string($this->pk) && isset($this->data[$this->pk]) ) {
            $where[] = [$this->pk, '=', $this->data[$this->pk]];
        }

        if( empty($where) ) {
            $where = !is_null($this->updateWhere) ? $this->updateWhere : [];
        }

        return $where;
    }

    /**
     * 保存多个数据到对象
     *
     * @param array $dataSet
     * @param boolean $replace 是否自动识别更新和写入
     * @return void
     */
    public function saveAll(array $dataSet = [], bool $replace = true)
    {
        $db = $this->db();
        $db->startTrans();

        try {

            if( is_string($this->pk) && $replace){
                $auto = true;
            }

            $result = [];

            foreach($dataSet as $key => $data) {
                if( $this->isUpdate || ( $auto && isset($data[$this->pk]) ) ) {
                    $result[$key] = self::update($data, [], $this->field);
                } else {
                    $result[$key] = self::create($data, $this->field, $this->replace);
                }
            }
            
            $db->commit();

            return $this->toCollection($result);
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    public function isUpdate($update = true, $where = null)
    {
        if( is_bool($update) ) {
            $this->isUpdate = $update;

            if( !empty($where) ) {
                $this->updateWhere = $where;
            }
        } else {
            $this->isUpdate = true;
            $this->updateWhere = $update;
        }
        
        return $this;
    }

    /**
     * 新增写入数据
     *
     * @return void
     */
    public function insertData()
    {
        $this->checkTimestampWrite();

        //检查允许字段
        $allowFields  = $this->checkAllowFields();
        
        $db = $this->db();
        $db->startTrans();

        try {
            $result = $db->strict()
            ->field($allowFields)
            ->insert($this->data, $this->replace, false);

            if( $result && $insertId = $db->connection->getLastInsId() ) {
                $pk = $this->pk;
                if( !isset($this->data[$pk]) || '' == $this->data[$pk] ){
                    $this->data[$pk] = $insertId;
                }
            }

            $db->commit();

            $this->isUpdate = true;

            return true;
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * Undocumented function
     *
     * @param [type] $where
     * @return void
     */
    public function updateData($where)
    {
        $data = $this->getChangeData();

        $allowFields = $this->checkAllowFields();

        //自动更新更新时间
        if( $this->autoWriteTimestamp && $this->updateTime && !isset($this->data[$this->updateTime]) ) {
            $this->data[$this->updateTime] = $data[$this->updateTime] = $this->autoWriteTimestamp($this->updateTime);
        }

        //保留主键数据
        foreach ($this->data as $key => $val) {
            if($key == $this->pk) {
                $data[$key] = $val;
            }
        }

        $array = [];

        if( isset($data[$this->pk]) ) {
            $array[] = [$this->pk, '=', $data[$this->pk]];
            unset($data[$this->pk]);
        }

        if( !empty($array) ) {
            $where = $array;
        }

        //模型更新
        $db = $this->db();
        $db->startTrans();

        try {
            $db->where($where)
                ->strict(false)
                ->field($allowFields)
                ->update($data);

            $db->commit();

            return true;
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
        
    }

    /**
     * 检查数据是否允许写入
     *
     * @return void
     */
    protected function checkAllowFields()
    {
        if( empty($this->field) ) {
            $query = $this->db();
            $table = $this->table ?: $query->getTable();
            $field = $this->field = $query->connection->getTableInfo($table, 'fields');
        }

        $field = array_diff($field, (array)$this->disuse); 

        return $field;
    }

    /**
     * 批量写入数据里面的写入
     *
     * @param array $data
     * @param [type] $field
     * @param boolean $replace
     * @return void
     */
    public static function create(array $data = [], $field = null, bool $replace = false)
    {
        $model = new static();

        if( !empty($field) ) {
            $model->allowField($field);
        }

        $model->isUpdate(false)->replace($replace)->save($data);

        return $model;
    }

    /**
     * 批量更新数据里面的更新
     *
     * @param array $data
     * @param array $where
     * @param [type] $field
     * @return void
     */
    public static function update(array $data = [], array $where = [], $field = null)
    {
        $model = new static();

        if( !empty($field) ) {
            $model->allowField($field);
        }
        
        $model->isUpdate(true)->save($data, $where);

        return $model;
    }

    /**
     * 新增数据是否使用replace
     *
     * @param boolean $replace
     * @return void
     */
    public function replace(bool $replace = true) 
    {
        $this->replace = $replace;

        return $this;
    }

    /**
     * 是否强制更新数据, 根据getChangedData方法来
     *
     * @param boolean $forec
     * @return void
     */
    public function force($force = true)
    {
        $this->force = true;

        return $this;
    }

    /**
     * countable
     *
     * @return void
     */
    public function count(): int
    {
        return count($this->data);
    }

    public function offsetExists( $offset ) : bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet( $offset ) 
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet( $offset, $value ) : void
    {
        if( is_null($offset) ){
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetUnset( $offset ) : void
    {
        unset($this->data[$offset]);
    }

    public function toArray()
    {
        return $this->data;
    }

    /**
     * Undocumented function
     *
     * @param string $name
     * @return void
     */
    public function __get($name)
    {
        return $this->getAttr($name);
    }

    public function __set($name, $value)
    {
        return $this->setAttr($name, $value);
    }

    public function __call( string $name , array $arguments )
    {
        return call_user_func_array([$this->db(), $name], $arguments);
    }

    public static function __callStatic( string $name , array $arguments )
    {
        $model = new static();
        return call_user_func_array([$model->db(), $name], $arguments);
    }
}