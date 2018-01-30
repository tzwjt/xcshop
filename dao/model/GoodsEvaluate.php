<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 1:40
 */

namespace dao\model;

use dao\model\BaseModel;

class  GoodsEvaluate extends BaseModel
{

    /*
     * 商品评价表
     */
    protected $name = "goods_evaluate";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;
}