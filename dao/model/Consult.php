<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 1:19
 */

namespace dao\model;
use dao\model\BaseModel;

class  Consult extends BaseModel
{

    /*
     * 咨询表
     */
    protected $name = "consult";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}