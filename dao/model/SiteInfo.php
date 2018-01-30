<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-11-25
 * Time: 17:49
 */

namespace dao\model;


class SiteInfo extends BaseModel
{
    protected $name = "system_site_info";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;


}