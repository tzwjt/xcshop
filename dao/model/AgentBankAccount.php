<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-11-17
 * Time: 16:06
 */

namespace dao\model;

use dao\model\BaseModel;


class AgentBankAccount extends BaseModel
{
    protected $name = "agent_bank_account";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}