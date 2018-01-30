<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 21:03
 */

namespace dao\model;

use dao\model\BaseModel;

class MemberLevel extends BaseModel
{
    /*
     * 会员等级
     */
    protected $name = "member_level";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;

}