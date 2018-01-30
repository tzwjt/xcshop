<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 1:21
 */

namespace dao\model;

use dao\model\BaseModel;

class  ConsultType extends BaseModel
{

    /*
     * 咨询类型表
     */
    protected $name = "consult_type";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}