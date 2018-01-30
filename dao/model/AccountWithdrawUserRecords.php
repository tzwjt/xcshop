<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 1:15
 */

namespace dao\model;

use dao\model\BaseModel;

class AccountWithdrawUserRecords extends BaseModel
{
    /*
     * 会员提现记录表
     */
    protected $name = "account_withdraw_user_records";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}