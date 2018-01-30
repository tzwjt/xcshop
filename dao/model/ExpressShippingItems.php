<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 1:32
 */

namespace dao\model;

use dao\model\BaseModel;

class  ExpressShippingItems extends BaseModel
{

    /*
     * 物流模板打印项
     */
    protected $name = "express_shipping_items";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}