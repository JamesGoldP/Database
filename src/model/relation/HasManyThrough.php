<?php
declare(strict_types = 1);

namespace zero\model\relation;

use zero\Model;
use zero\Model\Relation;

class HasManyThrough extends Relation
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
	 * @param Model $parent
	 * @param string $model
	 * @param string $through
	 * @param string $foreignKey
	 * @param string $throughKey
	 * @param string $localKey     当前模型主键
	 * @param string $throughPk
	 */
	public function __construct(Model $parent, string $model, string $through, string $foreignKey = '', string $throughKey = '', string $localKey = '', $throughPk = '')
	{
		$this->parent = $parent;
		$this->model = $model;
		$this->query = (new $model)->db();
		$this->through = (new $through)->db();
		$this->foreignKey = $foreignKey;
		$this->throughKey = $throughKey;
		$this->localKey = $localKey;
		$this->throughPk = $throughPk;
	}	
}