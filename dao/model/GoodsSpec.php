<?php
namespace dao\model;

class GoodsSpec extends BaseModel
{
    protected $name = "goods_spec";
    
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    protected $autoWriteTimestamp = true;  
}

