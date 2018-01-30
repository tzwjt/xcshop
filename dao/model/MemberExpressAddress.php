<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-08-22
 * Time: 20:40
 */

namespace dao\model;
use dao\model\BaseModel;

class MemberExpressAddress extends BaseModel
{
    /*
     * 会员收货地址管理
     */
    protected $name = "member_express_address";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;

}