<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-01
 * Time: 11:40
 */


namespace dao\model;

use dao\model\BaseModel;

class OrderAction extends BaseModel
{

    /**
     * 订单操作表
     */
    protected $name = "order_action";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}