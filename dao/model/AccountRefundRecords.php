<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-10-12
 * Time: 23:49
 */

namespace dao\model;


use dao\model\BaseModel;

class AccountRefundRecords extends BaseModel
{
    /*
     * 退款记录
     */
    protected $name = "account_refund_records";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}