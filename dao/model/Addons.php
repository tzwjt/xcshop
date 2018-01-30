<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2018-01-20
 * Time: 23:01
 */

namespace dao\model;

use dao\model\BaseModel;

/*
 * 插件表
 */
class Addons extends BaseModel
{
    protected $name = "system_addons";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;

    protected $rule = [
        'id'  =>  '',
        'config'  =>  'no_html_parse',
    ];
    protected $msg = [
        'id'  =>  '',
        'config'  =>  '',
    ];
}