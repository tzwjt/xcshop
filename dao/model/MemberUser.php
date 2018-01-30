<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-08-22
 * Time: 20:47
 */

namespace dao\model;

use dao\model\BaseModel;

class MemberUser extends BaseModel
{
    protected $name = "member_user";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;


    protected function getLastLoginTimeAttr($last_login_time)
    {
        return date('Y-m-d H:i:s', $last_login_time);
    }



}