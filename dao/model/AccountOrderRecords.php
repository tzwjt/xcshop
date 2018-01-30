<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 1:05
 */

namespace dao\model;

use dao\model\BaseModel;

class AccountOrderRecords extends BaseModel
{
    /*
     * 金额订单记录
     */
    protected $name = "account_order_records";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}