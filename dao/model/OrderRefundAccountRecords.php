<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-12-22
 * Time: 20:33
 */

namespace dao\model;

use dao\model\BaseModel;


class OrderRefundAccountRecords extends BaseModel
{

    /** 订单退款账号记录表 */
    protected $name = "order_refund_account_records";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}
