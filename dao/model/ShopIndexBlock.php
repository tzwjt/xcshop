<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-12-16
 * Time: 11:24
 */

namespace dao\model;

use dao\model\BaseModel;


class ShopIndexBlock extends BaseModel
{
    protected $name = "shop_index_block";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}