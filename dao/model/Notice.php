<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-12-05
 * Time: 21:52
 */

namespace dao\model;
use dao\model\BaseModel;


class Notice extends BaseModel
{
    protected $name = "system_notice";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}