<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-04
 * Time: 19:42
 */

namespace dao\model;

namespace dao\model;


class Config extends BaseModel
{
    protected $name = "system_config";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}