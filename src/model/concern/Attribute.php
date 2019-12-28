<?php
namespace zero\model\concern;

trait Attribute{

    public function setAttr($key, $value)
    {
        $this->data[$key] = $value;
    }
}