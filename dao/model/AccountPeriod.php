<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 1:06
 */

namespace dao\model;

use dao\model\BaseModel;

class AccountPeriod extends BaseModel
{
    /*
     * 商城账期结算表
     */
    protected $name = "account_period";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}