<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 15:36
 */

namespace dao\model;

use dao\model\BaseModel;

class PromotionBundlingGoods extends BaseModel
{
    /**
     * 组合销售活动商品表
     */
    protected $name = "promotion_bundling_goods";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}