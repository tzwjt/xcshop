<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 0:51
 */

namespace dao\model;

use dao\model\BaseModel;

class Account extends BaseModel
{
    /*
     * 商城资金统计
     */
    protected $name = "account";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}