<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-10-13
 * Time: 0:03
 */

namespace dao\model;

use dao\model\BaseModel;

class AgentAccountWithdrawRecords extends BaseModel
{
    /*
     * 金额账户提现记录
     */
    protected $name = "agent_account_withdraw_records";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}