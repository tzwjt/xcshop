<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 1:28
 */

namespace dao\model;

use dao\model\BaseModel;

class  CouponType extends BaseModel
{

    /*
     * 优惠券类型表
     */
    protected $name = "coupon_type";

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