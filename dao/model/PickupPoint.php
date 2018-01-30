<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 14:40
 */

namespace dao\model;

use dao\model\BaseModel;

class PickupPoint extends BaseModel
{
    /**
     * 自提点管理
     */
    protected $name = "pickup_point";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}