<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2018-01-15
 * Time: 22:24
 */

namespace dao\model;

use dao\model\BaseModel;

class WeixinFunctionsButton extends BaseModel
{
    protected $name = "system_weixin_functions_button";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}