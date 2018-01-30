<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-08-20
 * Time: 21:17
 */

namespace dao\model;


class District extends BaseModel
{
    protected $name = "system_district";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}