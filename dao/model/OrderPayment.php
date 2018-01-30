<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 1:55
 */

namespace dao\model;

use dao\model\BaseModel;

class OrderPayment extends BaseModel
{

    /**
     * 订单支付表
     */
    protected $name = "order_payment";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}