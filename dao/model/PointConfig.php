<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 15:29
 */

namespace dao\model;

use dao\model\BaseModel;

class PointConfig extends BaseModel
{
    /**
     * 积分设置表
     */
    protected $name = "point_config";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}