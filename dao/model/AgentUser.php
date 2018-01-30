<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-08-22
 * Time: 20:31
 */

namespace dao\model;
use dao\model\BaseModel;


class AgentUser extends BaseModel
{
    protected $name = "agent_user";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}