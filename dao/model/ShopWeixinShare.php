<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2018-01-18
 * Time: 11:42
 */

namespace dao\model;

use dao\model\BaseModel;

class ShopWeixinShare extends BaseModel
{
    protected $name = "shop_weixin_share";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}
