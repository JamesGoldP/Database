<?php
namespace zero\model\relation;

use zero\model\Relation;
use zero\db\Query;
use zero\helper\Str;
use zero\model\relation\BelongsTo;

class OneToOne extends Relation
{
    /**
     * 预载入方式 0 - Join  1-IN
     *
     * @var integer
     */
    protected $eagerlyType = 1;

    /**
     * 当前关联的join类型
     *
     * @var string
     */
    protected $joinType;

    /**
     * 关联名
     *
     * @var 
     */
    protected $relation;

    /**
     * eager loading(join method)
     *
     * @param Query    $query     查询对象  
     * @param string   $relation  关联名
     * @param mixed    $field     关联字段
     * @param string   $joinType  JOIN方式
     * @param \Closure $closure   闭包条件
     * @param boolean  $first
     * @return void
     */
    public function eagerly(Query $query, string $relation, $field, string $joinType, $closure, bool $first) : void 
    {
        $name = Str::snake(basename(str_replace('\\', '/', get_class($this->parent))));

        $table = $query->getTable();
        $query->table([$table => $name]);

        if( $query->getOptions('field') ) {
            $masterField = $query->getOptions('field');
            $query->removeOptions('field');
        } else {
            $masterField = true;
        }

        $query->field($masterField, false, $table, $name);

        // 预载入封装
        $joinType = $joinType ?: $this->joinType;
        $joinTable = $this->query->getTable();
        $joinAlias = $relation;

        // condition
        if($this instanceof BelongsTo) {
            $joinOn = $name . '.' . $this->foreignKey . '=' . $joinAlias . '.' . $this->localKey;
        } else {
            $joinOn = $name . '.' . $this->localKey . '=' . $joinAlias . '.' . $this->foreignKey;
        }

        $query->field($field, false, $joinTable, $joinAlias, $relation . '__')
            ->join([$joinTable => $joinAlias], $joinOn , $joinType);
    }

    /**
     * 预载入关联查询分支入口
     *
     * @param [type] $result
     * @param string $relation
     * @param string $subRelation
     * @param [type] $closure
     * @param boolean $join
     * @return void
     */
    public function eagerlyRelationResultset(&$result, string $relation, string $subRelation, $closure, bool $join = false)
    {
        if( 0 == $this->eagerlyType || $join ) {
            // 模型JOIN关联组装
            $this->match($this->model, $relation, $result);
        } else {
            $this->eagerlySet($result, $relation, $subRelation, $closure);
        }
    }

    /**
     * 预载入关联查询分支入口
     *
     * @param [type] $result
     * @param string $relation
     * @param string $subRelation
     * @param [type] $closure
     * @param boolean $join
     * @return void
     */
    public function eagerlyRelationResult(&$result, string $relation, string $subRelation, $closure, bool $join = false)
    {
        if( 0 == $this->eagerlyType || $join ) {
            // 模型JOIN关联组装
            $this->match($this->model, $relation, $result);
        } else {
            $this->eagerlyOne($result, $relation, $subRelation, $closure);
        }
    }

    /**
     * 一对一 关联模型预查询数据拼装
     *
     * @param string $model
     * @param string $relation
     * @param Model $result
     * @return void
     */
    protected function match(string $model, string $relation, &$result)
    {
        foreach($result->getData() as $key => $val) {
            if(strpos($key, '__')) {
                list($name, $attr) = explode('__', $key, 2);
                if($name == $relation) {
                    $list[$name][$attr] = $val;
                    unset($result->$key);
                } 
            }
        }

        if( isset($list[$relation]) ) {
            $array = array_unique($list[$relation]);

            if( count($array) == 1 && null === current($array) ) {
                $relationModel = null;
            } else {
                $relationModel = new $model($list[$relation]);
                $relationModel->isUpdate(true);
            }
        } else {
            $relationModel = null;
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
    protected function eagerlyWhere(array $where, string $key, string $relation, string $subRelation = '', $closure = null) : array
    {
        $list = $this->query->where($where)->with($subRelation)->select();

        $data = [];

        foreach ($list as $set) {
            $data[$set->$key] = $set;   
        }

        return $data;
    }
}