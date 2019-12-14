<?php
namespace Nezimi;

class Collection{

    /**
     * data set
     * @var array
     */
    protected $items = [];

    public function __construct($items = [])
    {
        $this->items = $items;
    }

    /**
     * transfer result set to array
     */
    public function toArray() :array
    {
        return array_map(function($value){
            return $value instanceof Model ? $value->toArray() : $value;
        }, $this->items);
    }

}