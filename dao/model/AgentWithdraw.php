<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-11-17
 * Time: 15:36
 */

namespace dao\model;

use dao\model\BaseModel;


class AgentWithdraw extends BaseModel
{
    protected $name = "agent_withdraw";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;

    /*
    protected function getAskForDateAttr($ask_for_date)
    {
        return date('Y-m-d H:i:s', $ask_for_date);
    }
    */
}