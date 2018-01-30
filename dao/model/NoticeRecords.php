<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-12-30
 * Time: 16:28
 */

namespace dao\model;

use dao\model\BaseModel;


class NoticeRecords extends BaseModel
{
    protected $name = "system_notice_records";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;


}