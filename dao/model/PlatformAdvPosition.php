<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 15:05
 */

namespace dao\model;

use dao\model\BaseModel;

class PlatformAdvPosition extends BaseModel
{
    /**
     * 广告位表
     */
    protected $name = "platform_adv_position";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}