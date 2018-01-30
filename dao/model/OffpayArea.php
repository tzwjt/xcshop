<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 1:49
 */

namespace dao\model;

use dao\model\BaseModel;

class OffpayArea extends BaseModel
{
    /*
     * 货到付款支持地区表
     */
    protected $name = "offpay_area";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;

}