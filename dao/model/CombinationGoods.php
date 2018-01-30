<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-27
 * Time: 19:38
 */

namespace dao\model;

use dao\model\BaseModel;

class CombinationGoods extends BaseModel
{
    /*
     * 组合中的商品
     */
    protected $name = "combination_goods";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}