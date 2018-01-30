<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-08-22
 * Time: 20:38
 */

namespace dao\model;
use dao\model\BaseModel;


class MemberAccountRecords extends BaseModel
{
    protected $name = "member_account_records";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;

}