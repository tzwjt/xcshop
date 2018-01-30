<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-12-05
 * Time: 21:46
 */

namespace dao\model;

use dao\model\BaseModel;


class NoticeTemplateType  extends BaseModel
{
    protected $name = "system_notice_template_type";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}