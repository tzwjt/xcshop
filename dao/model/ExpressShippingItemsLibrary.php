<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 1:35
 */

namespace dao\model;

use dao\model\BaseModel;

class  ExpressShippingItemsLibrary extends BaseModel
{

    /*
     * 物流模版打印项库
     */
    protected $name = "express_shipping_items_library";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}