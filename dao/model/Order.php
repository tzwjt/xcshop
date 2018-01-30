<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-01
 * Time: 11:38
 */


namespace dao\model;

use dao\model\BaseModel;

class Order extends BaseModel
{

    /** 订单主表 */
    protected $name = "order";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}