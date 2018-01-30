<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2018-01-15
 * Time: 22:15
 */

namespace dao\model;

use dao\model\BaseModel;

class WeixinAuth extends BaseModel
{
    protected $name = "system_weixin_auth";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}