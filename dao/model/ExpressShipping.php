<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 1:30
 */

namespace dao\model;

use dao\model\BaseModel;

class  ExpressShipping extends BaseModel
{

    /*
     * 运单模板
     */
    protected $name = "express_shipping";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}