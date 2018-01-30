<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2018-01-15
 * Time: 22:32
 */

namespace dao\model;
use dao\model\BaseModel;

class WeixinMsgTemplate extends BaseModel
{
    protected $name = "system_weixin_msg_template";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}