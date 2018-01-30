<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 1:44
 */

namespace dao\model;

use dao\model\BaseModel;

class  MemberBankAccount extends BaseModel
{

    /*
     * 会员提现账号
     */
    protected $name = "member_bank_account";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}