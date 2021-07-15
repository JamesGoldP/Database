<?php
declare(strict_types = 1);

namespace zero\db;

class Expression{

    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }
}