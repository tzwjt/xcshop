<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-10-21
 * Time: 15:35
 */

namespace dao\model;

use dao\model\BaseModel;

class AccountPaymentRecords extends BaseModel
{
    /*
     * 订单支付记录
     */
    protected $name = "account_payment_records";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}