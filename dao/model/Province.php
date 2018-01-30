<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-08-20
 * Time: 21:06
 */

namespace dao\model;


class Province extends BaseModel
{
    protected $name = "system_province";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}