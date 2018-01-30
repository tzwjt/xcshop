<?php
/**
 * Login.php
 * @date : 2017.09.17
 * @version : v1.0.0.0
 */
/**
 * 后台登录控制器
 */
namespace app\platform\controller;

use app\common\controller\CommonController;
use dao\handle\PlatformUserHandle as PlatformUserHandle;
//use data\service\Config as WebConfig;
//use data\service\WebSite as WebSite;
use think\Controller;

class Login extends CommonController
{

    public function __construct()
    {
        parent::__construct();

    }

    /**
     * 用户登录
     *
     * @return number
     */
    public function login()
    {
        $user_name = request()->post('userName');
        $password = request()->post('password');
        $vertification = request()->post('vertification');
        if (! captcha_check($vertification)) {
            return json(resultArray(2,"验证码错误"));

        }

        $platform_user_handle = new PlatformUserHandle();
        $retval = $platform_user_handle->login($user_name, $password);

        if (empty($retval)) {
            if (empty($platform_user_handle->getError())) {
                return json(resultArray(2, "登录失败!"));
            } else {
                return json(resultArray(2, $platform_user_handle->getError()));
            }
        }
        return json(resultArray(0, "登录成功"));

    }


}
