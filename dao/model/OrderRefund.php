<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-01
 * Time: 11:56
 */

namespace dao\model;

use dao\model\BaseModel;

class OrderRefund extends BaseModel
{

    /** 订单商品退货退款操作表 */
    protected $name = "order_refund";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}