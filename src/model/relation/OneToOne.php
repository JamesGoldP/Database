<?php
namespace zero\model\relation;

use zero\model\Relation;

class OneToOne extends Relation
{
    /**
     * 预载入方式 0 - Join  1-IN
     *
     * @var integer
     */
    protected $eagerlyType = 1;

    /**
     * 关联名
     *
     * @var 
     */
    protected $relation;
}