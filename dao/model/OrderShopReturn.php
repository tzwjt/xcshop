<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-07
 * Time: 21:47
 */

namespace dao\model;

use dao\model\BaseModel;

class OrderShopReturn extends BaseModel
{

    /** 订单商品退货退款操作表 */
    protected $name = "order_shop_return";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}