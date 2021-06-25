<?php
namespace zero\model;

class Relation
{
    /**
     * Undocumented variable
     *
     * @var Model
     */
    protected $parent;

    /**
     * 当前关联的模型对象
     *
     * @var Model
     */
    protected $model;

    /**
     * 关联模型查询对象
     *
     * @var Query
     */
    protected $query;

    /**
     * 外键
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * 主键
     *
     * @var string
     */
    protected $localKey;

    public function __call($method, $args)
    {
        if($this->query) {
            
            $result = call_user_func_array([$this->query->getModel(), $method], $args);
           
            return $result === $this->query && !in_array(strtolower($method), ['fetchSql', 'fetchpdo']) ? $this : $result;
        } else {
            throw new Exception('The method doesn\'t:' . __CLASS__ . '->' . $method);
        }
    }
}