<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 15:15
 */

namespace dao\model;

use dao\model\BaseModel;

class PlatformHelpClass extends BaseModel
{
    /**
     * 平台说明类型
     */
    protected $name = "platform_help_class";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}