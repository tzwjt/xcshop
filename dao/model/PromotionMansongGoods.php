<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 15:50
 */

namespace dao\model;

use dao\model\BaseModel;

class PromotionMansongGoods extends BaseModel
{
    /**
     * 满减送商品
     */
    protected $name = "promotion_mansong_goods";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}