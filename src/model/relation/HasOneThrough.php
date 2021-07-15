<?php
declare(strict_types = 1);

namespace zero\model\relation;

use zero\Model;
use zero\helper\Str;

class HasOneThrough extends HasManyThrough
{

	/**
	 * Undocumented variable
	 *
	 * @var string
	 */
	protected $through;

	/**
	 * Undocumented variable
	 *
	 * @var string
	 */
	protected $throughKey;

	/**
	 * Undocumented variable
	 *
	 * @var string
	 */
	protected $throughPk;

    /**
     * Undocumented function
     *
     * @param Model $result
     * @param string $relation
     * @param string $subRelation
     * @param Closure $closure
     * @return void
     */
    public function eagerlyRelationResultset(&$resultSet, string $relation)
    {
        $localKey = $this->localKey;
        $foreignKey = $this->foreignKey;
        $range = [];
        
        foreach($resultSet as $key => $value) {
            $range[] = $value->$localKey; 
        }
       
        if( !empty($range) ) {
            
            $data = $this->eagerlyWhere([
                [$foreignKey, 'in', $range],
            ]);
          
            foreach($resultSet as $result) {
                if( !isset($data[$result->$localKey]) ) {
                    $relationModel = null;
                } else  {
                    $relationModel = $data[$result->$localKey];
                }

                $result->setRelation(Str::snake($relation), $relationModel);
            }
        }

    }

	public function eagerlyRelationResult($result, $relation, bool $join)
	{
        $localKey = $this->localKey;
        $foreignKey = $this->foreignKey;

        $data = $this->eagerlyWhere([
            [$this->foreignKey, '=', $result->$localKey],
        ]);

        $relationModel = $data[$result->$localKey] ?? null; 
       
        $result->setRelation(Str::snake($relation), $relationModel);
	}

    /**
     * Undocumented function
     *
     * @param array $where
     * @return array
     */
    protected function eagerlyWhere(array $where): array
    {
        $keys = $this->through->where($where)->column($this->throughPk, $this->foreignKey);
        
		$list = $this->query->where($this->throughKey, 'in', $keys)->select();
       
        $data = [];

        $keys = array_flip($keys);

        foreach($list as $set) {
            $data[$keys[$set->{$this->throughKey}]] = $set;
        }

        return $data;
    }
}