<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-10-09
 * Time: 22:48
 */

namespace app\platform\controller;

/**
 * 平台用户控制器
 */
namespace app\platform\controller;


use dao\handle\PlatformUserHandle as PlatformUserHandle;
use app\platform\controller\BaseController;



class User extends BaseController
{

    /**
     * 当前版本的路径
     *
     * @var string
     */
    public $style;

    // 验证码配置
    public $login_verify_code;

    public function __construct()
    {
        parent::__construct();

    }

    /**
     * 退出登录
     */
    public function logout()
    {
        $platform_user_handle = new PlatformUserHandle();
        $platform_user_handle->logout();
        $redirect = __URL(__URL__ . '/' . ADMIN_MODULE . "/login");
        $this->redirect($redirect);
    }

    /**
     * 用户修改密码
     */
    public function modifyPassword()
    {
        $platform_user_handle = new PlatformUserHandle();
        $uid = $this->userId;
        $old_pass = request()->post('old_pass','');
        $new_pass = request()->post('new_pass','');
        $retval = $platform_user_handle->modifyUserPassword($uid, $old_pass, $new_pass);

        if (empty($retval)) {
            if (empty($platform_user_handle->getError())) {
                return json(resultArray(2,"操作失败"));
            } else {
                return json(resultArray(2,$platform_user_handle->getError()));
            }
        }
        return json(resultArray(0,"密码修改成功"));
    }
}
