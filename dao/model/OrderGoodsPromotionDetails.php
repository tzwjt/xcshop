<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-01
 * Time: 11:46
 */

namespace dao\model;

use dao\model\BaseModel;

class OrderGoodsPromotionDetails extends BaseModel
{

    /**
     * 订单商品优惠详情
     */
    protected $name = "order_goods_promotion_details";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}