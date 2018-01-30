<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 1:37
 */

namespace dao\model;

use dao\model\BaseModel;

class  GiftGrantRecords extends BaseModel
{

    /*
     * 赠品发放记录
     */
    protected $name = "gift_grant_records";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}