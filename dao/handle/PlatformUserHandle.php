<?php
/**
 * 平台用户管理的相关业务逻辑
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-29
 * Time: 21:46
 */

namespace dao\handle;

use dao\model\PlatformUser as PlatformUserModel;
use dao\model\UserLog as UserLogModel;
use dao\model\UserGroup as UserGroupModel;
use \think\Session as Session;
use think\Request as Request;
use dao\model\Module as ModuleModel;


class PlatformUserHandle extends BaseHandle
{
    public $user;

    function __construct()
    {
        parent::__construct();
        $this->user = new PlatformUserModel();

    }

    public static function loginUserName() {
        $user_id = Session::get('userId');
        if (!empty($user_id)) {
            $user_model = new PlatformUserModel();
            $user = $user_model->get($user_id);
            if (!empty($user)) {
                return $user['user_name'];
            }
        }
        return 'platform';
    }

    public static function loginUserId() {
        $user_id = Session::get('userId');
        if (!empty($user_id)) {
           return $user_id;
        }
        return 0;
    }

    /**
     * 用户登录之后初始化数据
     */
    private function initLoginInfo($user_info)
    {
        $model = Request::instance()->module();
        Session::set('userId', $user_info['id']);

        Session::set($model.'is_admin', $user_info['is_admin']);
        $user_group = new UserGroupModel();
        $auth = $user_group->get($user_info['user_group_id']);
        $no_control = $this->getNoControlAuth();
        Session::set($model.'module_id_array', $no_control.$auth['module_id_array']);


        $data = array(
            'last_login_time' => $user_info['current_login_time'],
            'last_login_ip' => $user_info['current_login_ip'],
      //      'last_login_type' => $user_info['current_login_type'],
            'current_login_ip' => request()->ip(),
            'current_login_time' => time(),
        //    'current_login_type'  => 1
        );
        //离线购物车同步

        $retval = $this->user->save($data,['id' => $user_info['id']]);
        //用户登录成功钩子
     //   hook("userLoginSuccess", $user_info);
        return $retval;
    }

    /**
     * ok-2ok
     * 得到用户的权限
     * @return array
     */
    public function getUserAuth() {
        $model = Request::instance()->module();
        $is_admin =  Session::get($model.'is_admin');
        $module_id_array = Session::get($model.'module_id_array');
        $data = array(
            'is_admin'=>$is_admin,
            'module_id_array'=>$module_id_array
        );
        return $data;
    }

    /**
     * ok-2ok
     * 获取不控制权限模块组
     */
    private function getNoControlAuth()
    {
        $moudle = new ModuleModel();
        $list = $moudle->getConditionQuery([
            "is_control_auth" => 0
        ], "id",'');

        $str = "";
        foreach ($list as $v) {
            $str .= $v["id"] . ",";
        }
        return $str;
    }

    /**
     * 平台用户登录
     */
    public function login($user_name, $password)
    {
        Session::clear();
        $condition = array(
            'user_name' => $user_name,
            'user_password' => md5($password)
        );
        $user_info = $this->user->getInfo($condition, $field = 'id,user_status,user_name, is_admin,user_group_id,  login_count, last_login_time,last_login_ip,current_login_time,current_login_ip');


        if (empty($user_info)) {
            $condition = array(
                'user_email' => $user_name,
                'user_password' => md5($password)
            );
            $user_info = $this->user->getInfo($condition, $field = 'id,user_status,user_name, is_admin,user_group_id,  login_count, last_login_time,last_login_ip,current_login_time,current_login_ip');
        }
        if (! empty($user_info)) {
            if ($user_info['user_status'] != 1) {
                $this->error = "此帐号已被禁用!";
                return false;

            } else {
                //登录成功后增加用户的登录次数
                $this->user->where("user_name|user_email", "eq", $user_name)
                    ->setInc('login_count', 1);
                $this->initLoginInfo($user_info);

                return true;
            }
        } else
            $this->error = "用户名或密码不正码!";
           return false;

    }




    public function getSessionUserId() {
       // $this->uid = Session::get($model.'uid');
        return Session::get('userId');
    }

    public function getPlatfromUserById($userId) {
        return $this->user->get($userId);
    }

    /**
     * ok-2ok
     * 得到用户信息
     * @param $userId
     * @return mixed
     */
    public function getUserInfoById($userId)
    {
        $user = $this->user->getInfo('id=' . $userId, '*');

        unset($user['user_password']);

        $user['user_status_name'] = $this->getUserStatusName($user['user_status']);
       // $user['user_group_name'] = $this->getUserGroup($user['user_group_id']);

        if (empty($user['is_admin'])) {
            $user['user_group_name'] = $this->getUserGroup($user['user_group_id']);
        } else if ($user['is_admin'] == 1)  {
            $user['user_group_name'] = '超级管理员';
        } else {
            $user['user_group_name'] = '';
        }

        return $user;
    }

    /**
     * ok-2ok
     * 得到登录用户的信息
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function getLoginUserInfo()
    {
        $user_id = $this->getSessionUserId();
        $user = $this->user->getInfo('id=' . $user_id, '*');
        unset($user['user_password']);

        $user['user_status_name'] =$this->getUserStatusName($user['user_status']);

        if (empty($user['is_admin'])) {
            $user['user_group_name'] = $this->getUserGroup($user['user_group_id']);
        } else if ($user['is_admin'] == 1)  {
            $user['user_group_name'] = '超级管理员';
        } else {
            $user['user_group_name'] = '';
        }

        return $user;
    }

    /**
     * 用户退出
     */
    public function logout()
    {
       // $model = $this->getRequestModel();
        Session::set('userId', '');


     //   Session::set($model.'module_id_array', '');
     //   Session::set($model.'instance_name', '');


    }

    /**
     * 系统用户修改密码
     */
    public function modifyUserPassword($userId, $old_password, $new_password)
    {
        $condition = array(
            'id' => $userId,
            'user_password' => md5($old_password)
        );
        $res = $this->user->getInfo($condition, $field = "id");
        if (! empty($res['id'])) {
            $data = array(
                'user_password' => md5($new_password)
            );
            $res = $this->user->save($data, [
                'id' => $userId
            ]);
            return $res;
        } else
            $this->error="旧密码不正确";
            return false;
           // return PASSWORD_ERROR;
    }

    /**
     * 添加用户登录日志
     */
    public function addUserLog($user_id, $user_type, $controller, $method, $ip, $get_data)
    {
        $data = array(
            'user_id' => $user_id,
            'user_type'=> $user_type,
          //  'is_system' => $is_system,
            'controller' => $controller,
            'method' => $method,
            'ip' => $ip,
            'data' => $get_data,
            'create_time' => time()
        );
        $user_log = new UserLogModel();
        $res = $user_log->save($data);
        return $res;
    }

    /**
     * ok-2ok
     * 添加平台用户
     */
    public function addUser($user_name,$user_password, $user_email, $nick_name, $real_name,  $user_group_id, $user_type=1)
    {
        if (empty($user_name)) {
            $this->error = "用户名不可为空";
            return false;
        }

        if (! empty($user_name)) {
            $count = $this->user->where([
                'user_name' => $user_name
            ])->count();
            if ($count > 0) {
                $this->error = "用户名已存在";
                return false;
            }
            if (empty($nick_name)) {
                $nick_name = $user_name;
            }
        }

        if(!empty($user_email))
        {
            $count = $this->user->where([
                'user_email' => $user_email
            ])->count();
            if ($count > 0) {
                $this->error = "用户邮箱已存在";
                return false;
            }
        } else {
            $user_email = null;
        }

        if (empty($user_password)) {
            $this->error = "密码不可为空";
            return false;
        }

        $data = array(
            'user_name' => $user_name,
            'user_password' => md5($user_password),
            'user_email'=>$user_email,
            'user_status' => 1,
            'user_type'=>$user_type,
            'user_group_id'=>$user_group_id,
            'real_name'=>$real_name,
            'nick_name' => $nick_name,
        );
        $res = $this->user->save($data);
     //   $uid = $this->user->uid;
        //用户添加成功后
       if (empty($res)) {
           return false;
       } else {
           return true;
       }
    }

    /**
     * ok-2ok
     * 修改平台用户
     */
    public function updateUser($user_id, $user_name,  $user_email, $nick_name, $real_name,$user_headimg, $user_status, $user_group_id, $user_type=1)
    {
        $user_info = $this->user->getInfo(['id'=>$user_id],  'user_name, user_email');
        if (empty($user_info)) {
            $this->error = "指定用户不存在";
            return false;
        }

        if (empty($user_name)) {
            $this->error = "用户名不可为空";
            return false;
        }

        if (! empty($user_name)) {
            if ($user_info['user_name'] != $user_name) {
                $count = $this->user->where([
                    'user_name' => $user_name
                ])->count();
                if ($count > 0) {
                    $this->error = "新用户名已存在";
                    return false;
                }
                if (empty($nick_name)) {
                    $nick_name = $user_name;
                }
            }
        }

        if(!empty($user_email))
        {
            if ($user_info['user_email'] != $user_email) {
                $count = $this->user->where([
                    'user_email' => $user_email
                ])->count();
                if ($count > 0) {
                    $this->error = "新用户邮箱已存在";
                    return false;
                }
            }
        } else {
            $user_email = null;
        }

        $data = array(
            'user_email'=>$user_email,
            'user_status' => $user_status,
            'user_type'=>$user_type,
            'user_group_id'=>$user_group_id,
            'real_name'=>$real_name,
            'nick_name' => $nick_name,
            'user_headimg'=>$user_headimg,
            'update_time'=>time()
        );
        $res = $this->user->save($data, ['id'=>$user_id]);

        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 得到用户状态名
     * @param $user_status
     * @return string
     */
    private function getUserStatusName($user_status) {
        if ($user_status == 1) {
             return  '正常';
        } else if ($user_status == 2) {
            return  '禁用';
        } else {
            $user_status;
        }
    }

    /**
     * ok-2ok
     * 得到用户组别名
     * @param $user_group_id
     * @return string
     */
    private function getUserGroup($user_group_id) {

        $user_group_model = new UserGroupModel();

        $user_group = $user_group_model->get($user_group_id);

        if (!empty($user_group)) {
            return $user_group['group_name'];
        } else {
            return '';
        }
    }

    /**
     * ok-2ok
     * 得到用户列表
     * @param $condition
     * @param string $field
     * @param string $order
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getUserList($condition, $field='*', $order= ' id desc') {
        $user_list = $this->user->getConditionQuery($condition, $field, $order);

        foreach ($user_list as $k=>$v) {
            unset($user_list[$k]['user_password']);
            $user_list[$k]['user_status_name'] = $this->getUserStatusName($v['user_status']);
            $user_list[$k]['user_group_name'] = $this->getUserGroup($v['user_group_id']);
        }
       return $user_list;
    }

    /**
     * ok-2ok
     * 重置用户密码
     */
    public function setUserPassword($userId, $user_password)
    {
        if (empty($user_password)) {
            $this->error = "密码不可为空";
            return false;
        }

        $data = array(
            'user_password' => md5($user_password),
            'update_time'=>time()
        );
        $res = $this->user->save($data, [
            'id' => $userId
        ]);

        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 设置用户状态
     */
    public function setUserStatus($userId, $user_status)
    {
        $data = array(
            'user_status' => $user_status,
            'update_time'=>time()
        );
        $res = $this->user->save($data, [
            'id' => $userId
        ]);

        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 设置用户组别
     */
    public function setUserGroup($userId, $user_group_id)
    {
        $data = array(
            'user_group_id' => $user_group_id,
            'update_time'=>time()
        );
        $res = $this->user->save($data, [
            'id' => $userId
        ]);

        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 设置用户昵称
     */
    public function setUserNickName($userId, $nick_name)
    {
        $data = array(
            'nick_name' => $nick_name,
            'update_time'=>time()
        );
        $res = $this->user->save($data, [
            'id' => $userId
        ]);

        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 设置用户头像
     */
    public function setUserHeadimg($userId, $user_headimg)
    {
        $data = array(
            'user_headimg' => $user_headimg,
            'update_time'=>time()
        );
        $res = $this->user->save($data, [
            'id' => $userId
        ]);

        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 设置用户真实姓名
     */
    public function setUserRealName($userId, $real_name)
    {
        $data = array(
            'real_name' => $real_name,
            'update_time'=>time()
        );
        $res = $this->user->save($data, [
            'id' => $userId
        ]);

        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
    * 平台用户日志列表
    */
    public function getUserLogList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        $user_log = new UserLogModel();
        $list = $user_log->pageQuery($page_index, $page_size, $condition, $order, '*');
        foreach ($list['data'] as $k=>$v) {
            if ($v['user_type'] == 1) {
                $list['data'][$k]['user_type_name']='平台用户';
            } else if ($v['user_type'] == 2) {
                $list['data'][$k]['user_type_name']='代理商用户';
            }
        }
        return $list;
    }

    /**
     * ok-2ok
     * 检测用户是否具有打开权限
     */
    public function checkAuth($module_id)
    {
        $user = $this->getLoginUserInfo();
        $is_admin = $user['is_admin'];
        if ($is_admin) {
            return 1;
        } else {
            $user_group_id = $user['user_group_id'];
            $user_group_model = new UserGroupModel();
            $user_group = $user_group_model->get($user_group_id);
            $module_id_array_str = $user_group['module_id_array'];
            $module_id_array = explode(',', $module_id_array_str);
            if (in_array($module_id, $module_id_array)) {
                return 1;
            } else {
                return 0;
            }
        }
    }

}