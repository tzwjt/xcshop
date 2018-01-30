<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-07
 * Time: 11:18
 */
namespace dao\model;

use dao\model\BaseModel;
class RewardRule extends BaseModel
{
    protected $name = "reward_rule";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}