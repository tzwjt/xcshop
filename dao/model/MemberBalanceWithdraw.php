<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 1:42
 */

namespace dao\model;

use dao\model\BaseModel;

class  MemberBalanceWithdraw extends BaseModel
{

    /*
     * 会员余额提现记录表
     */
    protected $name = "member_balance_withdraw";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}