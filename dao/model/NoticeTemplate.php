<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-12-05
 * Time: 21:50
 */

namespace dao\model;

use dao\model\BaseModel;


class NoticeTemplate extends BaseModel
{
    protected $name = "system_notice_template";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}