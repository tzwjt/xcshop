<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 15:43
 */

namespace dao\model;

use dao\model\BaseModel;

class PromotionFullMail extends BaseModel
{
    /**
     * 满额包邮
     */
    protected $name = "promotion_full_mail";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}