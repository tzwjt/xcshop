<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-10-12
 * Time: 23:14
 */

namespace dao\model;

use dao\model\BaseModel;

class AccountOperation extends BaseModel
{
    /*
     * 统计操作名录
     */
    protected $name = "account_operation";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}
