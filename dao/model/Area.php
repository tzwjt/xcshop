<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-08-20
 * Time: 20:54
 */

namespace dao\model;

class Area extends BaseModel
{
    protected $name = "system_area";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}