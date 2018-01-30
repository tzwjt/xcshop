<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-10-19
 * Time: 15:56
 */

namespace dao\model;

use dao\model\BaseModel;

class PlatformExpressAddress extends BaseModel
{
    /**
     * 平台用户
     */
    protected $name = "platform_express_address";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}