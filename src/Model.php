<?php
/**
 * Created by PhpStorm.
 * User: PengYilong
 * Date: 2018/9/8
 * Time: 1:24 PM
 */

namespace Nezimi;

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
     * @var false or object
     */
    protected $db;

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

    public function __construct()
    {
        $db_config = Db::getConfig()['master'];
        $this->prefix = $db_config['tablepre'];
        if( empty($this->table) ){
            $this->table = $this->getModelName();
        }
        //update table of Quer options
        $this->query = new Query();
        $this->query->options['table'] = $this->table;
    }

    /**
     * @return string
     */
    public function getModelName()
    {
        $sub_arr = explode('\\', get_class($this));
        $sub_class = end($sub_arr);
        $table = $this->prefix.to_underscore($sub_class);
        return $table;
    }

    public function __call( string $name , array $arguments )
    {
        return call_user_func_array([$this->query, $name], $arguments);
    }
    



}