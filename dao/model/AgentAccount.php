<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-10-12
 * Time: 23:51
 */

namespace dao\model;


use dao\model\BaseModel;

class AgentAccount extends BaseModel
{
    /*
     * 代理商帐户统计
     */
    protected $name = "agent_account";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}