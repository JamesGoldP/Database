<?php
namespace zero\model\concern;

/**
 * 自动写入时间戳到表
 */
trait TimeStamp
{

    /**
     * 是否需要自动写入时间戳
     *
     * @var [type]
     */
    protected $autoWriteTimestamp;

    /**
     * 创建时间字段
     * @var
     */
    protected $createTime = 'create_time';

    /**
     * 更新时间字段
     * @var
     */
    protected $updateTime = 'update_time';

    /**
     * 更新时间字段
     *
     * @var [type]
     */
    protected $dataFormat;

    protected function checkTimestampWrite()
    {
        if( $this->autoWriteTimestamp ) {
            if( $this->createTime && !isset($this->data[$this->createTime]) ) {
                $this->data[$this->createTime] = $this->autoWriteTimestamp($this->createTime);
            }
            if( $this->updateTime && !isset($this->data[$this->updateTime]) ) {
                $this->data[$this->updateTime] = $this->autoWriteTimestamp($this->updateTime);
            }
        }
    }

    protected function autoWriteTimestamp($name) 
    {
        return time();
    }
}