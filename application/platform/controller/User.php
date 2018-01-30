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
use dao\handle\UserGroupHandle;
use dao\handle\SiteHandle;



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
        $redirect = 'index/login';   //__URL(__URL__ . '/' . ADMIN_MODULE . "/login");
        $this->redirect($redirect);
    }

    /**
     * 用户修改密码
     */
    public function modifyPassword()
    {
        $platform_user_handle = new PlatformUserHandle();
        $uid = $this->userId;
        $old_pass = request()->post('old_pass');
        $new_pass = request()->post('new_pass');
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

    public function getUserInfo() {
        $platform_user_handle = new PlatformUserHandle();
        $userId = $this->userId;
        $ret = $platform_user_handle->getPlatfromUserById( $userId);
        return json(resultArray(0,"操作成功", $ret));

    }

    /**
     * ok-2ok
     * 新增平台用户
     * @return \think\response\Json
     */
    public function addUser() {
        $user_name = request()->post("user_name");
        $user_password = request()->post("user_password");
        $user_email = request()->post("user_email", '');
        $nick_name = request()->post("nick_name", '');
        $real_name = request()->post("real_name", '');
        $user_group_id = request()->post("user_group_id");
        $user_type=1;


        $user_handle = new PlatformUserHandle();
        $res = $user_handle->addUser($user_name,$user_password, $user_email, $nick_name, $real_name,  $user_group_id, $user_type);

        if (empty($res)) {
            if (empty($user_handle->getError())) {
                return json(resultArray(2, "操作失败"));
            } else {
                return json(resultArray(2,$user_handle->getError() ));
            }
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * ok-2ok
     * 得到平台用户列表
     * @return \think\response\Json
     */
    public function userList() {
        $user_handle = new PlatformUserHandle();

        $search_text = request()->post('search_text', '');

        $condition['is_admin']=0;

        if (!empty($search_text)) {
            $condition['user_name|user_email|nick_name|real_name'] = [
                'like',
                '%' . $search_text . '%'
            ];
        }

        $user_group_id = request()->post('user_group_id', 0);
        if (!empty($user_group_id )) {
            $condition['user_group_id']=$user_group_id;
        }
        $user_list = $user_handle->getUserList($condition, '*', ' id desc');
        return json(resultArray(0,"操作成功", $user_list));

    }

    /**
     * ok-2ok
     * 更改平台用户
     * @return \think\response\Json
     */
    public function updateUser() {
        $user_id = request()->post("user_id", 0);
        $user_name = request()->post("user_name");
        $user_email = request()->post("user_email");
        $nick_name = request()->post("nick_name");
        $real_name = request()->post("real_name");
        $user_group_id = request()->post("user_group_id");
        $user_status = request()->post("user_status");
        $user_headimg = request()->post("user_headimg");
        $user_type=1;

        if (empty($user_id)) {
            return json(resultArray(2, "指定用户为空， 操作失败"));
        }

        $user_handle = new PlatformUserHandle();
        $res = $user_handle->updateUser($user_id, $user_name,  $user_email, $nick_name, $real_name,$user_headimg, $user_status, $user_group_id, $user_type);
        ;
        if (empty($res)) {
            if (empty($user_handle->getError())) {
                return json(resultArray(2, "操作失败"));
            } else {
                return json(resultArray(2,$user_handle->getError() ));
            }
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
 * ok-2ok
 * 得到用户详情
 * @return \think\response\Json
 */
    public function userDetails() {
        $user_id = request()->post("user_id");

        if (empty($user_id)) {
            return json(resultArray(2, "指定用户为空"));
        }
        $user_handle = new PlatformUserHandle();
        $user_info = $user_handle->getUserInfoById($user_id);
        return json(resultArray(0,"操作成功",$user_info ));
    }

    /**
     * ok-2ok
     * 得到当前用户详情
     * @return \think\response\Json
     */
    public function currentUserDetails() {
        $user_id = $this->userId;
        if (empty($user_id)) {
            return json(resultArray(1,"您未登录系统,请先登录"));
        }

        $user_handle = new PlatformUserHandle();
        $user_info = $user_handle->getUserInfoById($user_id);
        return json(resultArray(0,"操作成功",$user_info ));
    }

    /**
     * ok-2ok
     * 修改当前用户的信息
     * @return \think\response\Json
     */
   public function updateCurrentUserDetails() {
       $user_id = $this->userId;
       if (empty($user_id)) {
           return json(resultArray(1,"您未登录系统,请先登录"));
       }
       $user_headimg = request()->post('user_headimg');
       $user_handle = new PlatformUserHandle();
       $res = $user_handle->setUserHeadimg($user_id, $user_headimg);

       if (empty($res)) {
           return json(resultArray(2,"操作失败"));
       } else {
           return json(resultArray(0,"操作成功"));
       }
   }

    /**
     * ok-2ok
     * 修改当前用户的呢称
     */
    public function modifyCurrentUserNickName()
    {
        $user_id = $this->userId;
        if (empty($user_id)) {
            return json(resultArray(1,"您未登录系统,请先登录"));
        }
        $nick_name = request()->post('nick_name');

        $user_handle = new PlatformUserHandle();
        $res = $user_handle->setUserNickName($user_id, $nick_name);

        if (empty($res)) {
            return json(resultArray(2,"操作失败"));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * ok-2ok
     * 修改当前用户的真实姓名
     */
    public function modifyCurrentUserRealName()
    {
        $user_id = $this->userId;
        if (empty($user_id)) {
            return json(resultArray(1,"您未登录系统,请先登录"));
        }
        $real_name = request()->post('real_name');

        $user_handle = new PlatformUserHandle();
        $res = $user_handle->setUserRealName($user_id, $real_name);

        if (empty($res)) {
            return json(resultArray(2,"操作失败"));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }




    /**
     * ok-2ok
     * 设置用户密码
     * @return \think\response\Json
     */
    public function setUserPassword() {
        $user_id = request()->post("user_id", 0);
        $user_password = request()->post("user_password");

        if (empty($user_id)) {
            return json(resultArray(2, "指定用户为空"));
        }

        $user_handle = new PlatformUserHandle();
        $res = $user_handle->setUserPassword($user_id, $user_password);

        if (empty($res)) {
            if (empty($user_handle->getError())) {
                return json(resultArray(2, "操作失败"));
            } else {
                return json(resultArray(2,$user_handle->getError() ));
            }
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * ok-2ok
     * 置有效用户
     * @return \think\response\Json
     */
    public function setValidUser() {
        $user_id = request()->post("user_id", 0);
        $user_status = 1;

        if (empty($user_id)) {
            return json(resultArray(2, "指定用户为空"));
        }

        $user_handle = new PlatformUserHandle();
        $res = $user_handle->setUserStatus($user_id, $user_status);

        if (empty($res)) {
            if (empty($user_handle->getError())) {
                return json(resultArray(2, "操作失败"));
            } else {
                return json(resultArray(2,$user_handle->getError() ));
            }
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * ok-2ok
     * 置无效用户
     * @return \think\response\Json
     */
    public function setInvalidUser() {
        $user_id = request()->post("user_id", 0);
        $user_status = 2;

        if (empty($user_id)) {
            return json(resultArray(2, "指定用户为空"));
        }

        $user_handle = new PlatformUserHandle();
        $res = $user_handle->setUserStatus($user_id, $user_status);

        if (empty($res)) {
            if (empty($user_handle->getError())) {
                return json(resultArray(2, "操作失败"));
            } else {
                return json(resultArray(2,$user_handle->getError() ));
            }
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * ok-2ok
     * 用户日志
     */
    public function userLoglist()
    {
        $page_index = request()->post('page_index',1);
        $page_size = request()->post('page_size',PAGESIZE);
        $condition = "";
        $user_handle = new PlatformUserHandle();
        $list = $user_handle->getUserLogList($page_index, $page_size, $condition, "create_time desc");
        return json(resultArray(0,"操作成功", $list));
    }

    /**
     * ok-2ok
     * 用户权限
     * @return \think\response\Json
     */
    public function userAuth() {
        $user_handle = new PlatformUserHandle();
        $auth = $user_handle->getUserAuth();
        return json(resultArray(0,"操作成功", $auth));
    }

    /**
     * ok-2ok
     * 检测用户是否可以使用模块
     * @return \think\response\Json
     */
    public function ckeckModule() {
        $module_id = request()->post('module_id');
        $user_handle = new PlatformUserHandle();
        $res = $user_handle->checkAuth($module_id);
        return json(resultArray(0,"操作成功", $res));
    }

    /*****************************************  用户组相关 ******************************************************/

    /**
     * ok-2ok
     * 得到用户组列表
     * @return \think\response\Json
     */
    public function getUserGroupList() {
        $user_group = new UserGroupHandle();
        $condition = array(
            "instance_id"=>0,
            "group_status"=>1
        );
        $field="id, group_name,group_status,is_system";
        $order = 'id asc';
        $list = $user_group->getSystemUserGroupList(1, 0, $condition, $order, $field);

        return json(resultArray(0,"操作成功", $list));
    }
    /**
     * ok-2ok
     * 设置用户组
     * @return \think\response\Json
     */
    public function setUserGroup() {
        $user_id = request()->post("user_id", 0);
        $user_group_id = request()->post("user_group_id");

        if (empty($user_id)) {
            return json(resultArray(2, "指定用户为空"));
        }

        $user_handle = new PlatformUserHandle();
        $res = $user_handle->setUserGroup($user_id, $user_group_id);

        if (empty($res)) {
            if (empty($user_handle->getError())) {
                return json(resultArray(2, "操作失败"));
            } else {
                return json(resultArray(2,$user_handle->getError() ));
            }
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * ok-2ok
     * 用户组列表
     */
    public function userGroupList()
    {
        $page_index = request()->post('page_index', 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $user_group = new UserGroupHandle();
        $list = $user_group->getSystemUserGroupList($page_index, $page_size, [
            'instance_id' => 0
        ], 'id asc');
        return json(resultArray(0,"操作成功", $list));
    }

    /**
     * ok-2ok
     * 用户的模块列表
     */
    public function userModuleList() {
        $site = new SiteHandle();
        $permissionList = $site->getInstanceModuleQuery();

        $firstArray = array();
        $p = array();
        for ($i = 0; $i < count($permissionList); $i++) {
            $per = $permissionList[$i];
            if ($per["pid"] == 0 && $per["module_name"] != null) {
                $firstArray[] = $per;
            }
        }
        for ($i = 0; $i < count($firstArray); $i++) {
            $first_per = $firstArray[$i];
            $secondArray = array();
            for ($y = 0; $y < count($permissionList); $y++) {
                $childPer = $permissionList[$y];
                if ($childPer["pid"] == $first_per["id"]) {
                    $secondArray[] = $childPer;
                }
            }
            $first_per['child'] = $secondArray;
            for ($j = 0; $j < count($secondArray); $j++) {
                $second_per = $secondArray[$j];
                $threeArray = array();
                for ($z = 0; $z < count($permissionList); $z++) {
                    $three_per = $permissionList[$z];
                    if ($three_per["pid"] == $second_per["id"]) {
                        $threeArray[] = $three_per;
                    }
                }
                $second_per['child'] = $threeArray;
            }
            $p[] = $first_per;
            $first_per = array();
        }
     //   $list[] = $p;
        return json(resultArray(0,"操作成功", $p));
    }

    /**
     * ok-2ok
     * 添加或者编辑用户组
     */
    public function addOrUpdateUserGroup()
    {
        $group_id = request()->post('group_id',0);
        $module_id_array = request()->post('module_id_array');
        $group_name = request()->post('group_name');
        if (empty($group_name)) {
            return json(resultArray(2,"用户组名不可为空 "));
        }

        $user_group = new UserGroupHandle();
        if ($group_id != 0) {
            $retval = $user_group->updateSystemUserGroup($group_id, $group_name, 1, false, $module_id_array, '');

        } else {
            $retval = $user_group->addSystemUserGroup( $group_name, false, $module_id_array, '');
        }

        if (empty($retval)) {
            return json(resultArray(2,"操作失败 ".$user_group->getError()));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }


    /**
     * ok-2ok
     * 得到用户组详情
     * @return \think\response\Json
     */
    public function userGroupDetails() {
        $group_id = request()->post('group_id',0);
        if (empty($group_id)) {
            return json(resultArray(2,"未指定用户组"));
        }
        $user_group = new UserGroupHandle();
        $data = $user_group->getSystemUserGroupDetail($group_id);
        return json(resultArray(0,"操作成功", $data));
    }


    /**
     * ok-2ok
     * 删除系统用户组
     */
    public function deleteSystemUserGroup()
    {
        $group_id = request()->post('group_id');
        if (empty($group_id)) {
            return json(resultArray(2,"未指定用户组"));
        }
        if(!is_numeric($group_id)){
            return json(resultArray(2,"请传入正确参数"));
        }
        $user_group = new UserGroupHandle();
        $retval = $user_group->deleteSystemUserGroup($group_id);

        if (empty($retval)) {
            return json(resultArray(2,"删除失败 ".$user_group->getError()));
        } else {
            return json(resultArray(0,"删除成功"));
        }
    }
}
