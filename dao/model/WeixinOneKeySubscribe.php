<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2018-01-15
 * Time: 22:38
 */

namespace dao\model;
use dao\model\BaseModel;

class WeixinOneKeySubscribe extends BaseModel
{
    protected $name = "system_wexin_onekeysubscribe";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}