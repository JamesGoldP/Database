<?php
namespace zero\model\relation;

use zero\Model;
use zero\model\Relation;
use zero\helper\Str;

class HasMany extends Relation
{
    public function __construct(Model $parent, $model, string $foreignKey, string $localKey)
    {
        $this->parent = $parent;
        $this->model  = $model;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
        $this->query = (new $model)->db();
    }

    /**
     * 延迟获取关联数据
     *
     * @return void
     */
    public function getRelation()
    {
        $localKey = $this->localKey;
        //获取关联数据
        $relationModel = $this->query
        ->removeWhereField($this->foreignKey)
        ->where($this->foreignKey, $this->parent->$localKey)
        ->select();

        return $relationModel;
    }

    public function eagerlyRelationResultSet(&$result, string $relation, string $subRelation, $closure)
    {
        $localKey = $this->localKey;
        $foreignKey = $this->foreignKey;
        $range = [];
        
        foreach($result as $value) {
            if( isset($value->$localKey) ) {
                $range[] = $value->$localKey;
            }
        }

        $this->query->removeWhereField($foreignKey);
        
        $data = $this->eagerlyWhere([
            [$foreignKey, 'in', $range],
        ], $foreignKey, $relation, $subRelation, $closure);

        $result->setRelation(Str::snake($relation), $data);
    }

    public function eagerlyRelationResult(&$result, string $relation, string $subRelation, $closure)
    {
        $localKey = $this->localKey;
        $foreignKey = $this->foreignKey;

        $this->query->removeWhereField($foreignKey);
        
        $data = $this->eagerlyWhere([
            [$foreignKey, '=', $result->$localKey],
        ], $foreignKey, $relation, $subRelation, $closure);

        $result->setRelation(Str::snake($relation), $data);
    }

    /**
     * Undocumented function
     *
     * @param array $where
     * @param string $key
     * @param string $relation
     * @param string $subRelation
     * @param Closure $closure
     * @return array
     */
    protected function eagerlyWhere(array $where, string $key, string $relation, string $subRelation = '', $closure = null)
    {
        $list = $this->query->where($where)->with($subRelation)->select();
       
        return $list;
    }
}