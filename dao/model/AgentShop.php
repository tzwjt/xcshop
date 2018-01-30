<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-11-21
 * Time: 19:05
 */

namespace dao\model;


class AgentShop extends BaseModel
{
    protected $name = "agent_shop";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}