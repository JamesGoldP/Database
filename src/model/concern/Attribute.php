<?php
namespace zero\model\concern;

trait Attribute
{
    /**
     * 数据表主键
     *
     * @var string
     */
    protected $id = 'id';

    /**
     * @var
     */
    protected $data;

    public function setAttr($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function getAttr($name)
    {
        return $this->data[$name];
    }
}