<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 1:08
 */

namespace dao\model;

use dao\model\BaseModel;

class AccountRecords extends BaseModel
{
    /*
     * 金额账户记录
     */
    protected $name = "account_records";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}