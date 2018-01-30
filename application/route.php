<?php

use think\Route;
use think\Cookie;
use think\Request;
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
/**
return [
    '__pattern__' => [
        'name' => '\w+',
    ],
    '[hello]'     => [
        ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
        ':name' => ['index/hello', ['method' => 'post']],
    ],

];
**/

/************************************************************************************************检测入口文件*****************************************************************************************/
//检测入口文件
$base_file = Request::instance()->baseFile();
$alipay = strpos($base_file, 'alipay.php');
$weixinpay = strpos($base_file, 'weixinpay.php');
$is_api =  strpos($base_file, 'api.php');
if($alipay)
{
    Route::bind('shop/pay/aliUrlBack');
}
if($weixinpay)
{
    Route::bind('shop/pay/wchatUrlBack');
}