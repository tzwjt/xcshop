<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-10-12
 * Time: 23:12
 */

namespace dao\model;


use dao\model\BaseModel;

class AccountCommissionRecords extends BaseModel
{
    /*
     * 商城平台支出的佣金统计记录
     */
    protected $name = "account_commission_records";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}