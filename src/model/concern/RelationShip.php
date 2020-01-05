<?php
namespace zero\model\concern;

use zero\Db;
use zero\helper\Str;
use zero\model\relation\HasOne;
use zero\model\Relation;

trait RelationShip
{
    /**
     *
     * @var array
     */
    protected $relation = [];

    /**
     * gets the relation of the model
     *
     * @param string $name
     * @return void
     */
    public function getRelation(string $name = null)
    {
        if( is_null($name) ){
            return $this->relation;
        } 
        return $this->relation[$name] ?? null;
    }

    public function setRelation($value)
    {
        $this->relation = $vaule;
    }

    /**
     * HAS ONE 关联定义
     *
     * @param [type] $model
     * @param string $foreignKey
     * @param string $localKey
     * @return 
     */
    public function hasOne($model, string $foreignKey = '', string $localKey = '')
    {
        //获取默认信息
        $model      = $this->parseModel($model);
        $localKey   = $localKey ?: $this->pk;
        $foreignKey = $foreignKey ?: $this->getForeignKey($this->name);

        return new HasOne($this, $model, $foreignKey, $localKey);
    }

    public function belongsTo()
    {

    }

    /**
     * 检查属性是否为关联属性
     *
     * @param string $attr
     * @return boolean
     */
    protected function isRelationAttr(string $attr)
    {
        $relation = Str::camel($attr);

        if ( method_exists($this, $relation) && !method_exists('zero\Model', $relation) ) {
            return $relation;
        }

        return false;
    }

    /**
     * 模型换成带命名空间的
     *
     * @param [type] $model
     * @return void
     */
    protected function parseModel(string $model) : string
    {
        if( false === strpos($model, '\\') ){
            $path = explode('\\', static::class);
            array_pop($path);
            array_push($path, Str::camel($model, true));
            $model = implode('\\', $path);
        }
        return $model;
    }

    protected function getRelationData(Relation $modelRelation)
    {   
        return $modelRelation->getRelation();
    }

    /**
     * gets the defalut foreign key of the model
     *
     * @param string $name the models'name
     * @return string
     */
    protected function getForeignKey(string $name) : string
    {
        return Str::snake($name) . '_id';
    }
}