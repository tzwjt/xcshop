<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-29
 * Time: 21:55
 */

namespace dao\model;

use dao\model\BaseModel;

class PlatformUser extends BaseModel
{
    /**
     * 平台用户
     */
    protected $name = "platform_user";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}