<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-01
 * Time: 11:58
 */

namespace dao\model;

use dao\model\BaseModel;

class OrderShippingFee extends BaseModel
{
    /**
     * 运费模板
     */
    protected $name = "order_shipping_fee";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}