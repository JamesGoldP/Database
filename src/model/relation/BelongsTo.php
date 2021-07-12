<?php
namespace zero\model\relation;

use zero\Model;
use zero\helper\Str;

class BelongsTo extends OneToOne
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
        $foreignKey = $this->foreignKey;

        //获取关联数据
        $relationModel = $this->query
        ->removeWhereField($this->localKey)
        ->where($this->localKey, $this->parent->$foreignKey)
        ->find();

        return $relationModel;
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
    protected function eagerlySet(&$resultSet, string $relation)
    {
        $localKey = $this->localKey;
        $foreignKey = $this->foreignKey;
        $range = [];
        
        foreach($resultSet as $key => $value) {
            $range[] = $value->$foreignKey; 
        }

        if( !empty($range) ) {
            
            $data = $this->eagerlyWhere([
                [$localKey, 'in', $range],
            ], $localKey, $relation);

            foreach($resultSet as $key => $value) {
                if( !isset($data[$value->$foreignKey]) ) {
                    $relationModel = null;
                } else  {
                    $relationModel = $data[$value->$foreignKey];
                    $relationModel->isUpdate(true);
                }

                $value->setRelation(Str::snake($relation), $relationModel);
            }
        }
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
            [$localKey, '=', $result->$foreignKey],
        ], $localKey, $relation);

        if( !isset($data[$result->$foreignKey]) ) {
            $relationModel = null;
        } else  {
            $relationModel = $data[$result->$foreignKey];
            $relationModel->isUpdate(true);
        }

        $result->setRelation(Str::snake($relation), $relationModel);
    }
}