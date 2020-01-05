<?php
namespace zero\model;

class Relation
{
    /**
     * Undocumented variable
     *
     * @var Model
     */
    protected $parent;

    /**
     * 当前关联的模型对象
     *
     * @var Model
     */
    protected $model;

    /**
     * the query of the model
     *
     * @var Query
     */
    protected $query;

    /**
     * 外键
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * 主键
     *
     * @var string
     */
    protected $localKey;
}