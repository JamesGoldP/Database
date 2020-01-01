<?php
namespace zero\model\concern;

trait TimeStamp
{
    /**
     * @var
     */
    protected $autoWriteTimestamp;

    /**
     * @var
     */
    protected $createTime;

    /**
     * @var
     */
    protected $updateTime;
}