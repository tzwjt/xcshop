<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-08-30
 * Time: 2:05
 */


namespace dao\model;
use dao\model\BaseModel;

class MemberInfo extends BaseModel
{
    protected $name = "member_info";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}
