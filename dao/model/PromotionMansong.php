<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 15:48
 */

namespace dao\model;

use dao\model\BaseModel;

class PromotionMansong extends BaseModel
{
    /**
     * 满就送活动表
     */
    protected $name = "promotion_mansong";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;

    protected function getStartTimeAttr($start_time)
    {
        return date('Y-m-d H:i:s', $start_time);
    }

    protected function getEndTimeAttr($end_time)
    {
        return date('Y-m-d H:i:s', $end_time);
    }
}