<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-01
 * Time: 11:45
 */

namespace dao\model;

use dao\model\BaseModel;

class OrderGoodsExpress extends BaseModel
{

    /**
     * 商品订单物流信息表（多次发货）
     */
    protected $name = "order_goods_express";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}