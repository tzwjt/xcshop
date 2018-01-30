<?php
namespace dao\model;

class AlbumPicture extends BaseModel
{
    /*
     * 相册图片表
     */
    protected $name = "system_album_picture";
    
    protected $createTime = 'create_time';
    protected $updateTime = false;
    
    protected $autoWriteTimestamp = true;
}

