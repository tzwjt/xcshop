<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-11-29
 * Time: 19:06
 */

namespace dao\model;

use dao\model\BaseModel;


class UserGroup extends BaseModel
{
    protected $name = "system_user_group";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}
