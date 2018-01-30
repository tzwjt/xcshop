<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-10-12
 * Time: 23:57
 */

namespace dao\model;

use dao\model\BaseModel;

class AgentAccountProceedsRecords extends BaseModel
{
    /*
     * 代理商收入记录
     */
    protected $name = "agent_account_proceeds_records";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}