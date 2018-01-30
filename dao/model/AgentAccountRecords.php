<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-10-13
 * Time: 0:00
 */

namespace dao\model;

use dao\model\BaseModel;

class AgentAccountRecords extends BaseModel
{
    /*
     * 代理商账户记录
     */
    protected $name = "agent_account_records";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}