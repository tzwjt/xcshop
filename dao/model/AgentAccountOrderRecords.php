<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-10-12
 * Time: 23:54
 */

namespace dao\model;

use dao\model\BaseModel;

class AgentAccountOrderRecords extends BaseModel
{
    /*
     * 代理商订单记录
     */
    protected $name = "agent_account_order_records";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}