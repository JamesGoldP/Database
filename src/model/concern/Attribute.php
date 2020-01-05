<?php
namespace zero\model\concern;

use InvalidArgumentException;
use zero\model\Relation;

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

    /**
     * 获取数据对象的值
     *
     * @param string $name
     * @return void
     */
    public function getAttr(string $name)
    {
        try {
            $notFound = false;
            $value    = $this->getData($name);
        } catch (InvalidArgumentException $e) {
            $notFound = true;
            $value = null;
        }

        if($notFound){
            $value = $this->getRelationAttribute($name); 
        }

        return $value;
    }

    protected function getRelationAttribute(string $name)
    {
        $relation = $this->isRelationAttr($name);

        if( $relation ){
            $modelRelation = $this->$relation();
            
            if( $modelRelation instanceof Relation ){
                $value = $this->getRelationData($modelRelation);
                $this->relation[$name] = $value;
                return $value;
            }
        }

        throw new InvalidArgumentException('The property doesn\'t exists: ' . static::class . '->' . $name);
    }

    /**
     * 获取对象原始数据, 
     *
     * @param string $name
     * @return void
     */
    public function getData(string $name = null)
    {
        if( is_null($name) ){
            return $this->data;
        } elseif ( array_key_exists($name, $this->data) ) {
            return $this->data[$name];
        } 
        
        throw new InvalidArgumentException('property doesn\'t exist: ' . static::class . '->' . $name);
    }
}