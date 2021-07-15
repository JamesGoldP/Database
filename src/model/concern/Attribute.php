<?php
declare(strict_types = 1);

namespace zero\model\concern;

use InvalidArgumentException;
use zero\model\Relation;
use zero\helper\Str;

trait Attribute
{
    /**
     * 数据表主键
     *
     * @var string
     */
    protected $pk = 'id';

    /**
     * 数据表字段信息 留空则自动获取
     *
     * @var array
     */
    protected $field = [];

    /**
     * 数据表废弃字段
     *
     * @var array
     */
    protected $disuse = [];

    /**
     * 数据库只读字段
     *
     * @var array
     */
    protected $readonly = [];

    /**
     * 数据库表字段类型
     *
     * @var array
     */
    protected $type = [];

    /**
     * @var
     */
    protected $data = [];

    /**
     * 原始数据
     *
     * @var array
     */
    private $origin = [];

    public function setAttr(string $key, $value)
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

        $method = 'get' . Str::camel($name, true) . 'Attr';

        if( method_exists($this, $method) ) {
            $value = $this->$method($name, $this->data);
        } else if($notFound){
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
        
        throw new InvalidArgumentException('The property doesn\'t exist: ' . static::class . '->' . $name);
    }

    /**
     * 获取变化的数据,并排除只读数据
     *
     * @return array
     */
    public function getChangeData(): array
    {
      
        if( $this->force ) {
            $data = $this->data;
        } else {
            $data = array_udiff_assoc($this->data, $this->origin, function($a, $b){
                if( (empty($a) || empty($b) ) && $a!=$b ) {
                    return 1;
                }

                return $a==$b ? 0 : 1;
            });
        }

        if( !empty($this->readonly) ) {
            //只读字段不允许更新
            foreach ($this->readonly as $key => $field) {
                if( isset($data[$field]) ) {
                    unset($data[$field]);
                }
            }
        }
        
        return $data;
    }

    /**
     * 设置写入的字段
     *
     * @param string|array|true $field 如果为true只允许写入数据表字段
     * @return void
     */
    public function allowField($field)
    {
        if( is_string($field) ){
            $field = explode(',', $field);
        }

        $this->field = $field;

        return $this;
    }

    /**
     * 设置数据对象的值
     *
     * @param mixed $data
     * @param mixed $value
     * @return $this
     */
    public function data($data, $value = null)
    {
        $this->data[$data] = $value;

        return $this;
    }
}