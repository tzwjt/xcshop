<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 1:13
 */

namespace dao\model;
use dao\model\BaseModel;

class AccountWithdrawRecords extends BaseModel
{
    /*
     * 金额账户提现记录
     */
    protected $name = "account_withdraw_records";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}