<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 15:13
 */

namespace dao\model;

use dao\model\BaseModel;

class PlatformGoodsRecommendClass extends BaseModel
{
    /**
     * 店铺商品推荐类别
     */
    protected $name = "platform_goods_recommend_class";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}