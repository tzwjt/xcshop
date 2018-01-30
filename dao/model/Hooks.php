<?php
/**
 * Hooks.php
 * @date : 2018.1.17
 * @version : v1.0.0.0
 */

namespace dao\model;

use dao\model\BaseModel as BaseModel;
/**
 * Hook表
 */
class Hooks extends BaseModel {

    protected $name = "system_hooks";

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