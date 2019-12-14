<?php
use Nezimi\Collection;

if( !function_exists('collection') ){
   function collection($resultSet)
   {
        return new Collection($resultSet);
   }
}

