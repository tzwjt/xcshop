<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 14:43
 */

namespace dao\model;

use dao\model\BaseModel;

class PlatformAdv extends BaseModel
{
    /**
     * 广告表
     */
    protected $name = "platform_adv";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}