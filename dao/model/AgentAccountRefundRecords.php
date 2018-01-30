<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-10-13
 * Time: 0:01
 */

namespace dao\model;

use dao\model\BaseModel;

class AgentAccountRefundRecords extends BaseModel
{
    /*
     * 退款记录
     */
    protected $name = "agent_account_refund_records";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}