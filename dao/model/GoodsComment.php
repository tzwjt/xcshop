<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 1:39
 */

namespace dao\model;

use dao\model\BaseModel;

class  GoodsComment extends BaseModel
{

    /*
     * 商品评论送积分记录表
     */
    protected $name = "goods_comment";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}