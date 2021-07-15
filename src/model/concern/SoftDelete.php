<?php
declare(strict_types = 1);

namespace zero\model\concern;

use zero\db\Query;

trait SoftDelete
{

    /**
     * whether it includes soft delete data
     *
     * @var boolean
     */
    protected $withTrashed = false;

    /**
     * 软删除标记字段
     *
     * @var string
     */
    protected $deleteTime = 'mark';

    /**
     * 软删除默认标记
     *
     * @var string
     */
    protected $defaultSoftDelete = '0';
    
    /**
     * 判断当前实力是否被软删除
     *
     * @return boolean
     */
    public function trashed() : bool
    {
        $field = $this->getDeleteTimeField();

        if($field && isset($this->origin[$field]) ) {}

        return false;
    }

    /**
     * gets the field deleted of the table
     *
     * @return void
     */
    protected function getDeleteTimeField(bool $read = false)
    {
        $field = property_exists($this, 'deleteTime') && isset($this->deleteTime) ? $this->deleteTime : 'delete_time';

        if( false === $field ) {
            return false;
        }

        if( false === strpos($field, '.') ) {
            $field = '__TABLE__.' . $field;
        }

        if(!$read && strpos($field, '.')) {
            $array = explode('.', $field);
            $field = array_pop($array);
        }

        return $field;
    }

    /**
     * 只查询软删除数据
     *
     * @return void
     */
    public static function onlyTrashed()
    {
        $model = new static();
        $field = $model->getDeletetimeField(true);

        if($field) {
            return $model->db()->useSoftDelete($field, $model->getWithTrashedExp());
        }

        return $model->db();
    }

    /**
     * 获取软删除数据的查询条件
     *
     * @return array
     */
    protected function getWithTrashedExp() : array
    {
        if( is_null($this->defaultSoftDelete) ) {
            $condition = ['notnull', '']; 
        } else {
            $condition = ['<>', $this->defaultSoftDelete];
        }
        
        return $condition;
    }

    /**
     * 默认查询条件
     *
     * @param Query $query
     * @return void
     */
    protected function withNoTrased(Query $query)
    {
        $field = $this->getDeletetimeField(true);

        if($field) {
            if( is_null($this->defaultSoftDelete) ) {
                $condition = ['notnull', '']; 
            } else {
                $condition = ['=', $this->defaultSoftDelete];
            } 
            return $query->useSoftDelete($field, $condition);
        }
    }

    /**
     * 查询数据包括软删除数据
     *
     * @return void
     */
    public static function withTrashed()
    {
        $model = new static();

        return $model->withTrashedData(true)->db();
    }

    /**
     * 是否包含软删除数据
     *
     * @param bool $withTrashed
     * @return $this
     */
    public function withTrashedData(bool $withTrashed)
    {
        $this->withTrashed = $withTrashed;
        return $this;
    }

    /**
     * 删除当前记录
     *
     * @param boolean $force
     * @return boolean
     */
    public function delete(bool $force = false) : bool
    {
        if(!$this->isUpdate ) {
            return false;
        }

        $force = $force ?: $this->force;
        $field = $this->getDeleteTimeField();

        if($field && !$force) {
            // 软删除
            $this->data($field, $this->autoWriteTimestamp($field));

            $result = $this->isUpdate()->save();
        } else  {
            // 真实删除
            $where = $this->getWhere();

            $result = $this->db()
            ->where($where)
            ->removeOption('soft_delete')
            ->delete();
        }

        $this->isUpdate(false);

        return true;
    }

    /**
     * 删除记录静态方法
     *
     * @param mixed $data
     * @return boolean
     */
    public static function destroy($data, $force = false) : bool
    {
        if( empty($data) && 0!== $data ) {
            return false;
        }

        $query = (new static())->db();

        if( is_array($data) && key($data) !== 0 ) {
            $query->where($data);
            $data = null;
        } elseif ($data instanceof Closure) {
            $data($query);
            $data = null;
        }
        
        $resultSet = $query->select($data);

        if($resultSet) {
            foreach ($resultSet as $data) {
                $data->force($force)->delete();
            }   
        }

        return true;
    }

    /**
     * 恢复被软删除的记录
     *
     * @param array $where
     * @return boolean
     */
    public function restore(array $where = []) : bool
    {
        $field = $this->getDeleteTimeField();

        if($field) {
            if(empty($where)) {
                $pk = $this->pk;

                $where[] = [$pk, '=', $this->getData($pk)];
            }

            //恢复删除
            $this->db()
                ->where($where)
                ->useSoftDelete($field, $this->getWithTrashedExp())
                ->update([$field => $this->defaultSoftDelete]);

            return true;
        }

        return false;
    }
}