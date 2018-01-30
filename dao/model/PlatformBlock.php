<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 15:07
 */

namespace dao\model;

use dao\model\BaseModel;

class PlatformBlock extends BaseModel
{
    /**
     * 首页促销板块
     */
    protected $name = "platform_block";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}