<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-01
 * Time: 11:36
 */
/*商品点赞记录表*/

namespace dao\model;

use dao\model\BaseModel;

class ClickFabulous extends BaseModel
{

    /*
     * 商品点赞记录表
     */
    protected $name = "click_fabulous";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}