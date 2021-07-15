<?php
declare(strict_types = 1);

namespace zero\model\relation;

use zero\Model;
use zero\helper\Str;
use zero\Model\Relation;

class HasOneThroughAnother extends Relation
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
     * Undocumented variable
     *
     * @var string
     */
    protected $modelPk;

	/**
	 * Undocumented function
	 *
	 * @param Model $parent
	 * @param string $model        关联模型
	 * @param string $through      中间模型 
	 * @param string $foreignKey   当前模型外键
	 * @param string $throughKey   中间模型关联键
	 * @param string $localKey     当前模型主键
	 * @param string $throughPk    关联模型主键
	 */
	public function __construct(Model $parent, string $model, string $through, string $foreignKey = '', string $throughKey = '', string $localKey = '', string $modelPk = '')
	{
		$this->parent = $parent;
		$this->model = $model;
		$this->query = (new $model)->db();
		$this->through = (new $through)->db();
		$this->foreignKey = $foreignKey;
		$this->throughKey = $throughKey;
		$this->localKey = $localKey;
        $this->modelPk = $modelPk;
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
     * @param string $key
     * @param string $relation
     * @param string $subRelation
     * @param Closure $closure
     * @return array
     */
    protected function eagerlyWhere(array $where): array
    {
        $keys = $this->through->where($where)->column($this->throughKey, $this->foreignKey);

		$list = $this->query->where($this->modelPk, 'in', $keys)->select();
        $newList = [];

        foreach($list as $key => $value) {
            $newList[$value->{$this->modelPk}] = $value;
        }

        $data = [];

        foreach($keys as $key => $value) {
            $data[$key] = $newList[$value];
        }

        return $data;
    }
}