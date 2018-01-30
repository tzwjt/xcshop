<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-08-22
 * Time: 20:44
 */

namespace dao\model;

use dao\model\BaseModel;

class MemberFavorites extends BaseModel
{
    /**
     * 收藏表
     */
    protected $name = "member_favorites";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;

}