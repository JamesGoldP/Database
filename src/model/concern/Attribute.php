<?php
namespace Nezimi\model\concern;

trait Attribute{

    public function setAttr($key, $value)
    {
        $this->data[$key] = $value;
    }
}