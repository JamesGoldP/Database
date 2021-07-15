<?php
declare(strict_types = 1);

namespace zero;

class Collection implements \ArrayAccess, \Countable, \IteratorAggregate
{

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
     * whether $this->items is empty
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->items);
    }

    /**
     * countable
     *
     * @return void
     */
    public function count(): int
    {
        return count($this->items);
    }

    public function offsetExists( $offset ): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet( $offset ) 
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet( $offset, $value )
    {
        if( is_null($offset) ){
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset( $offset )
    {
        unset($this->items[$offset]);
    }

    //IteratorAggregate
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * transfer result set to array
     */
    public function toArray(): array
    {
        return array_map(function($value){
            return $value instanceof Model ? $value->toArray() : $value;
        }, $this->items);
    }

}