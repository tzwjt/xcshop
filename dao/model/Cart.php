<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-08-30
 * Time: 18:40
 */

namespace dao\model;

use dao\model\BaseModel;
class Cart extends BaseModel
{
    protected $name = "cart";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}