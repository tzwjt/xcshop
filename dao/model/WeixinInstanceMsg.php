<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2018-01-15
 * Time: 22:25
 */

namespace dao\model;
use dao\model\BaseModel;

class WeixinInstanceMsg extends BaseModel
{
    protected $name = "system_weixin_instance_msg";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}