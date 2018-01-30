<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-12-16
 * Time: 11:29
 */

namespace dao\model;


class ShopMenu extends BaseModel
{
    protected $name = "shop_menu";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}