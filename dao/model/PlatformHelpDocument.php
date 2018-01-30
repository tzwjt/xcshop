<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 15:25
 */

namespace dao\model;


use dao\model\BaseModel;

class PlatformHelpDocument extends BaseModel
{
    /**
     * 平台说明内容
     */
    protected $name = "platform_help_document";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}