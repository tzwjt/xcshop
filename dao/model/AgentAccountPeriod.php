<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-10-12
 * Time: 23:55
 */

namespace dao\model;

use dao\model\BaseModel;

class AgentAccountPeriod extends BaseModel
{
    /*
     * 代理商账期结算表
     */
    protected $name = "agent_account_period";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}