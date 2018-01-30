<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 15:33
 */

namespace dao\model;

use dao\model\BaseModel;

class PromotionBundling extends BaseModel
{
    /**
     * 组合销售活动表
     */
    protected $name = "promotion_bundling";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}