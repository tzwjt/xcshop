<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-08-23
 * Time: 23:20
 */

namespace dao\model;

use dao\model\BaseModel;

class UserLog extends BaseModel
{
    protected $name = "system_user_log";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;



}