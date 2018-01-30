<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 1:10
 */

namespace dao\model;

use dao\model\BaseModel;

class AccountReturnRecords extends BaseModel
{
    /*
     * 平台的利润的记录
     */
    protected $name = "account_return_records";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}