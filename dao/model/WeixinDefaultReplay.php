<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2018-01-15
 * Time: 22:17
 */

namespace dao\model;
use dao\model\BaseModel;

class WeixinDefaultReplay extends BaseModel
{
    protected $name = "system_weixin_default_replay";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}