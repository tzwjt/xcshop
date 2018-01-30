<?php
/**
 * InstanceType.php
 * @date : 2018.1.17
 * @version : v1.0.0.0
 */

namespace dao\model;
use think\Db;
use dao\model\BaseModel as BaseModel;
/**
 * 系统实例类型表(店铺类型)
 */

class InstanceType extends BaseModel {

    protected $name = "system_instance_type";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;


    protected $rule = [
        'id'  =>  '',
    ];
    protected $msg = [
        'id'  =>  '',
    ];
}