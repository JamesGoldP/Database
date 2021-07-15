<?php
declare(strict_types = 1);

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

    public function eagerlyRelationResultSet(&$resultSet, string $relation)
    {
        $localKey = $this->localKey;
        $foreignKey = $this->foreignKey;
        $range = [];
        
        foreach($resultSet as $key => $value) {
            $range[] = $value->$localKey; 
        }
       
        if( !empty($range) ) {
            
            $data = $this->eagerlyWhere([
                [$localKey, 'in', $range],
            ], $localKey, $relation);

            foreach($resultSet as $key => $value) {
                if( !isset($data[$value->$localKey]) ) {
                    $relationValue = [];
                } else  {
                    $relationValue = $this->resultSetBuild($data[$value->$localKey]);
                }

                $value->setRelation(Str::snake($relation), $relationValue);
            }
        };
    }

    /**
     * Undocumented function
     *
     * @param Model $result
     * @param string $relation
     * @param string $subRelation
     * @param Closure $closure
     * @return void
     */
    protected function eagerlyOne(&$result, string $relation)
    {
        $localKey = $this->localKey;
        $foreignKey = $this->foreignKey;

        $data = $this->eagerlyWhere([
            [$foreignKey, '=', $result->$localKey],
        ], $foreignKey, $relation, $subRelation, $closure);

        if( !isset($data[$result->$localKey]) ) {
            $relationModel = null;
        } else  {
            $relationModel = $data[$result->$localKey];
        }

        $result->setRelation(Str::snake($relation), $relationModel);
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
    protected function eagerlyWhere(array $where, string $key, string $relation)
    {
        $list = $this->query->field($this->throughPk)->where($where)->select();
        p($list);
        exit();

        $data = [];

        foreach ($list as $set) {
            $data[$set->$key][] = $set;   
        }

        return $data;
    }
}