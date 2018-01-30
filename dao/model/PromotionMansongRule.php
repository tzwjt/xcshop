<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 15:52
 */

namespace dao\model;

use dao\model\BaseModel;

class PromotionMansongRule extends BaseModel
{
    /**
     * 满就送活动规则表
     */
    protected $name = "promotion_mansong_rule";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}