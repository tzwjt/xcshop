<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-08-25
 * Time: 1:06
 */


namespace dao\model;
use dao\model\BaseModel;


class Agent extends BaseModel
{
    protected $name = "agent";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}