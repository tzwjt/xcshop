<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-12-16
 * Time: 11:27
 */

namespace dao\model;
use dao\model\BaseModel;

class ShopIntroduce extends BaseModel
{
    protected $name = "shop_introduce";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}
