<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-10-12
 * Time: 23:59
 */

namespace dao\model;

use dao\model\BaseModel;

class AgentAccountRateRecords extends BaseModel
{
    /*
     * 代理商佣金率调整记录
     */
    protected $name = "agent_account_rate_records";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}