<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-01
 * Time: 11:43
 */

namespace dao\model;

use dao\model\BaseModel;

class OrderGoods extends BaseModel
{

    /**
     * 订单商品表
     */
    protected $name = "order_goods";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;

    protected function getRefundTimeAttr($refund_time)
    {
        return date('Y-m-d H:i:s', $refund_time);
    }
}