<?php
/**
 * AlbumClass.php
 * @date : 2017.08.03
 * @version : v1.0
 */
namespace dao\model;

class AlbumClass extends BaseModel
{
    protected $name = "system_album_class";
    
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    protected $autoWriteTimestamp = true;
}

