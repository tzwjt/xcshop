<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 15:46
 */

namespace dao\model;

use dao\model\BaseModel;

class PromotionGiftGoods extends BaseModel
{
    /**
     * 商品赠品表
     */
    protected $name = "promotion_gift_goods";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}