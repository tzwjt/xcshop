<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2018-01-15
 * Time: 22:36
 */

namespace dao\model;

use dao\model\BaseModel;

class WeixinUserMsgReplay extends BaseModel
{
    protected $name = "system_weixin_user_msg_replay";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}