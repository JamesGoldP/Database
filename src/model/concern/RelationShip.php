<?php
namespace zero\model\concern;

use zero\Db;
use zero\helper\Str;
use zero\model\relation\HasOne;
use zero\model\relation\BelongsTo;
use zero\model\relation\HasMany;
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

    /**
     * @param string $name
     * @param [type] $value
     * @return $this
     */
    public function setRelation(string $name, $value)
    {
        $this->relation[$name] = $value;

        return $this;
    }

    /**
     * HAS ONE 关联定义
     *
     * @param Model $model         关联模型
     * @param string $foreignKey   外键(news_id)
     * @param string $localKey     当前模型主键(id)
     * @return 
     */
    public function hasOne($model, string $foreignKey = '', string $localKey = '')
    {
        //获取默认信息
        $model      = $this->parseModel($model);
        $foreignKey = $foreignKey ?: $this->getForeignKey($this->name);
        $localKey   = $localKey ?: $this->pk;

        return new HasOne($this, $model, $foreignKey, $localKey);
    }

    /**
     * Belong To 关联定义
     *
     * @param Model $model         关联模型
     * @param string $foreignKey   外键(news_id)
     * @param string $localKey     关联模型主键(id)   
     * @return 
     */   
    public function belongsTo($model, string $foreignKey = '', string $localKey = '')
    {
        //获取默认信息
        $model      = $this->parseModel($model);
        $modelInited = new $model;
        $foreignKey = $foreignKey ?: $this->getForeignKey($modelInited->name);
        $localKey   = $localKey ?: $modelInited->pk;

        return new BelongsTo($this, $model, $foreignKey, $localKey);
    }

    /**
     * HAS Many 关联定义
     *
     * @param Model $model         关联模型
     * @param string $foreignKey   外键
     * @param string $localKey     当前模型主键   
     * @return 
     */
    public function hasMany($model, string $foreignKey = '', string $localKey = '')
    {
        //获取默认信息
        $model      = $this->parseModel($model);
        $foreignKey = $foreignKey ?: $this->getForeignKey($this->name);
        $localKey   = $localKey ?: $this->pk;

        return new HasMany($this, $model, $foreignKey, $localKey);
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
    protected function parseModel(string $model): string
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
    protected function getForeignKey(string $name): string
    {
        return Str::snake($name) . '_id';
    }

    /**
     * 预载入关联查询
     *
     * @param Model $result
     * @param string|array $relation
     * @param array $withRelationAttr
     * @param boolean $join
     * @return void
     */
    public function eagerlyResultSet(&$result, $relation, array $withRelationAttr = [], $join = false)
    {
        $relations = is_string($relation) ? explode(',', $relation) : $relation;
       
        foreach($relations as $key => $relation) {
            $subRelation = '';
            $closure = null;

            $relation = Str::camel($relation, false);
            $relationName = Str::snake($relation);

            $relationResult = $this->$relation();
            
            $relationResult->eagerlyRelationResultSet($result, $relation, $subRelation, $closure, $join);
        }
    }

    /**
     * 预载入关联查询
     *
     * @param Model $result
     * @param string|array $relation
     * @param array $withRelationAttr
     * @param boolean $join
     * @return void
     */
    public function eagerlyResult(&$result, $relation, array $withRelationAttr = [], $join = false)
    {
        $relations = is_string($relation) ? explode(',', $relation) : $relation;
       
        foreach($relations as $key => $relation) {
            $subRelation = '';
            $closure = null;

            $relation = Str::camel($relation, false);
            $relationName = Str::snake($relation);

            $relationResult = $this->$relation();
            
            $relationResult->eagerlyRelationResult($result, $relation, $subRelation, $closure, $join);
        }
    }
}