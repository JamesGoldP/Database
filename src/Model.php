<?php
/**
 * Created by PhpStorm.
 * User: PengYilong
 * Date: 2018/9/8
 * Time: 1:24 PM
 */

namespace zero;
use zero\db\Query;

class Model implements \ArrayAccess, \Countable
{
    use model\concern\Attribute;
    use model\concern\Conversion;
    use model\concern\RelationShip;
    use model\concern\SoftDelete;
    use model\concern\TimeStamp;

    /**
     * @var boolean whether update is
     */
    protected $isUpdate = false;

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
        $config = Db::getConfig()['master'];
        if( empty($this->name) ){
            $this->name = $this->getModelName();
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
    public function newInstance($data = [])
    {
        return new static($data);
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
            $query->pk($this->pk);
        }

        return $query;
    }

    /**
     * save data
     */
    public function save($data = [])
    {
        return $this->insert($data);   
    }

    /**
     * save all of data
     */
    public function saveAll($data = [])
    {
        foreach($data as $key=>$value){
            $this->insert($value);
        }
    }

    public function isUpdate($update = true)
    {
        $this->isUpdate = $update;
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

    public function __call( string $name , array $arguments )
    {
        $query = $this->buildQuery();
        return call_user_func_array([$query, $name], $arguments);
    }

    public static function __callStatic( string $name , array $arguments )
    {
        $model = new static();
        return call_user_func_array([$model->buildQuery(), $name], $arguments);
    }
}