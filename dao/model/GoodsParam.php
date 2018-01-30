<?php
/**
 * GoodsParam.php
 * 商品参数模型
 * @author :wjt
 * @date : 2017.08.01
 * @version : v1.0
 */

namespace dao\model;

class GoodsParam extends BaseModel
{
    protected $name = "goods_param";
    
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    protected $autoWriteTimestamp = true;
}

