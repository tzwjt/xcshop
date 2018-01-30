<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2018-01-15
 * Time: 22:30
 */

namespace dao\model;
use dao\model\BaseModel;

class WeixinMediaItem extends BaseModel
{
    protected $name = "system_weixin_media_item";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}