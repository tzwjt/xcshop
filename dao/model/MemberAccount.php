<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-08-22
 * Time: 20:33
 */

namespace dao\model;
use dao\model\BaseModel;

class MemberAccount extends BaseModel
{
    protected $name = "member_account";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}
