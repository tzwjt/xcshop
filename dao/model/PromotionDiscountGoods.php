<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 15:40
 */

namespace dao\model;

use dao\model\BaseModel;

class PromotionDiscountGoods extends BaseModel
{
    /**
     * 限时折扣商品列表
     */
    protected $name = "promotion_discount_goods";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}