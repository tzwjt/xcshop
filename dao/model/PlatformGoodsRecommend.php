<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 15:10
 */

namespace dao\model;

use dao\model\BaseModel;

class PlatformGoodsRecommend extends BaseModel
{
    /**
     * 平台商品推荐
     */
    protected $name = "platform_goods_recommend";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}