<?php
namespace dao\model;

class GoodsBrand extends BaseModel
{
    protected $name = "goods_category";
    
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    protected $autoWriteTimestamp = true;
    
    protected $insert = [
        'status' => 1,
    ];
    
}

