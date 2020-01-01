<?php
namespace zero\model\concern;

use zero\model\Collection as ModelCollection;

trait Conversion
{

    public function toCollection($collection)
    {
        return new ModelCollection($collection);
    }
}