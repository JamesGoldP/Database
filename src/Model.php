<?php
/**
 * Created by PhpStorm.
 * User: PengYilong
 * Date: 2018/9/8
 * Time: 1:24 PM
 */

namespace Nezimi;
use Nezimi\db\Query;

class Model{

    /**
     * @var
     */
    protected $data;

    /**
     * @var
     */
    protected $autoWriteTimestamp;

    /**
     * @var
     */
    protected $createTime;

    /**
     * @var
     */
    protected $updateTime;

    /**
     * @var string prefix
     */
    protected $prefix;

    /**
     * @var string name of table
     */
    protected $table;

    /**
     * @var
     */
    protected $cache;

    /**
     * @var
     */
    protected $query; 

    /**
     * @var boolean whether update is
     */
    protected $update = false;

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
    
    public function newInstance($data)
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
        if( !empty($this->table) ){
            $query->table($this->table);
        } else {
            $query->name($this->name);
        }
        return $query;
    }

    /**
     * save data
     */
    public function save()
    {

    }

    /**
     * delete data
     */
    public function delete()
    {

    }

    public function __call( string $name , array $arguments )
    {
        $query = $this->buildQuery();
        $query->model($this);
        return call_user_func_array([$query, $name], $arguments);
    }
}