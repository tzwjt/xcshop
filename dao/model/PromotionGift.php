<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 15:45
 */

namespace dao\model;

use dao\model\BaseModel;

class PromotionGift extends BaseModel
{
    /**
     * 赠品活动表
     */
    protected $name = "promotion_gift";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}