<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 1:29
 */

namespace dao\model;

use dao\model\BaseModel;

class  ExpressCompany extends BaseModel
{

    /*
     * 物流公司
     */
    protected $name = "express_company";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}