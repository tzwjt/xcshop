<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-10-12
 * Time: 23:52
 */

namespace dao\model;

use dao\model\BaseModel;

class AgentAccountCommissionRecords extends BaseModel
{
    /*
     * 代理商的佣金统计记录
     */
    protected $name = "agent_account_commission_records";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}