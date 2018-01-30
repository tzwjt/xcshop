<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-08-22
 * Time: 20:46
 */

namespace dao\model;

use dao\model\BaseModel;

class MemberGift extends BaseModel
{
    /*
     * 会员赠品表
     */
    protected $name = "member_gift";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;

}