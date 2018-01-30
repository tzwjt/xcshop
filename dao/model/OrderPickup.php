<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-01
 * Time: 11:48
 */

namespace dao\model;

use dao\model\BaseModel;

class OrderPickup extends BaseModel
{

    /** 订单自提点管理*/
    protected $name = "order_pickup";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}