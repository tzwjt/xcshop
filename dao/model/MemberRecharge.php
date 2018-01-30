<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 1:47
 */

namespace dao\model;

use dao\model\BaseModel;

class MemberRecharge extends BaseModel
{
    /*
     * 会员充值余额记录
     */
    protected $name = "member_recharge";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;

}