<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 15:27
 */

namespace dao\model;

use dao\model\BaseModel;

class PlatformLink extends BaseModel
{
    /**
     * 友情链接表
     */
    protected $name = "platform_link";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}