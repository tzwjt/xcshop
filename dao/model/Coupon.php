<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 1:25
 */

namespace dao\model;

use dao\model\BaseModel;

class  Coupon extends BaseModel
{

    /*
     * 优惠券表
     */
    protected $name = "coupon";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;

}