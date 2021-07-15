<?php
declare(strict_types = 1);

namespace zero\model\concern;

use zero\model\Collection as ModelCollection;
use zero\Model;

trait Conversion
{
    
    /**
     * append data
     *
     * @var array
     */
    protected $append = [];

    public function toCollection($collection)
    {
        return new ModelCollection($collection);
    }

    public function toArray()
    {
        $item = [];

        $data = array_merge($this->data, $this->relation);
       
        foreach($data as $key => $value) {
            if( $value instanceof Model || $value instanceof ModelCollection ) {
                $item[$key] = $value->toArray();
            } else {
                $item[$key] = $this->getAttr($key);
            }
        }

        foreach($this->append as $key => $value) {
            $item[$key] = $this->getAttr($key); 
        }

        return $item;
    }
}