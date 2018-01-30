<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2018-01-15
 * Time: 22:33
 */

namespace dao\model;

use dao\model\BaseModel;

class WeixinQrcodeTemplate extends BaseModel
{
    protected $name = "system_weixin_qrcode_template";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}