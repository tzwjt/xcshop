<?php

/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-08-24
 * Time: 20:01
 */
namespace dao\handle\agent;

use dao\handle\AgentHandle;
use dao\handle\BaseHandle;
use dao\model\AgentUser as AgentUserModel;
use think\Session;
use dao\model\UserLog as UserLogModel;

class AgentUserHandle extends BaseHandle
{
    private $agent_user;

    function __construct()
    {
        parent::__construct();
        $this->agent_user = new AgentUserModel();
    }

    public function addAgentUser($user_name, $password, $agent_id, $real_name, $user_level, $user_type,
                                    $status) {
        if (empty($user_name)) {
            $this->error = "用户名(帐号)不能为空";
            return false;
        }
        if (empty($password)) {
            $this->error = "用户密码不能为空";
            return false;
        }
        $count = $this->agent_user->where([
            'user_name' => $user_name
        ])->count();
        if ($count > 0) {
            $this->error = "此用户名(帐号)已存在";
            return false; //USER_REPEAT;
        }

        $data = array(
            'user_name' =>$user_name,
            'password' => md5($password),
            'agent_id' => $agent_id,
            'real_name' => $real_name,
            'user_level'=> $user_level,
            'user_type' => $user_type,
            'status' => $status,
            'login_count' => 0,
            'last_login_time' => 0,
        );
        $agent_user = new AgentUserModel();
        $agent_user->save($data);
        $uid = $agent_user->id;
        if ($uid < 1) {
            $this->error ="保存代理商用户时出错, 操作失败!";
            return false;
        }


        return $uid;
    }

    /**
     * 重置用户密码
     * @param $user_id
     * @param $password
     * @return false|int
     */
    public function setPassword($user_id, $password) {
        $data = array(
            'password' => md5($password),
        );
        $agent_user = new AgentUserModel();
        $res = $agent_user->save($data,['id' => $user_id]);
        return $res;
    }

    /**
     * ok-2ok
     * 代理商用户登录
     */
    public function login($user_name, $password)
    {
        Session::clear();
        $condition = array(
            'user_name' => $user_name,
            'password' => md5($password)
        );
        $agent_user_info = $this->agent_user->getInfo($condition, $field = 'id, status,user_name, agent_id,  login_count, current_login_time,current_login_ip, last_login_time,last_login_ip');


        if (! empty($agent_user_info)) {
            if ($agent_user_info['status'] != 1) {
                $this->error = "此帐号已被禁用!";
                return false;

            } else {
                //登录成功后增加用户的登录次数
                $this->agent_user->where("user_name", "eq", $user_name)
                    ->setInc('login_count', 1);

                $this->initLoginInfo($agent_user_info);

                return true;
            }
        } else
            $this->error = "用户名或密码不正码!";
        return false;
    }

    /**
     * ok-2ok
     * 得到登录的代理商用户名
     * @return null|static
     */
    public static function loginUserName() {
        $agent_user_model = new AgentUserModel();
        $user_id = Session::get('agentUserId');
        if (!empty($user_id)) {
            $agent_user = $agent_user_model->get($user_id);
            if (!empty($agent_user)) {
                return $agent_user['user_name'];
            }
        }
        return 'agent';
    }

    /**
     * ok-2ok
     * 用户登录之后初始化数据
     */
    private function initLoginInfo($agent_user_info)
    {
        Session::set('agentUserId', $agent_user_info['id']);
        Session::set('agentId', $agent_user_info['agent_id']);


        $data = array(
            'last_login_time' => $agent_user_info['current_login_time'],
            'last_login_ip' => $agent_user_info['current_login_ip'],
            //      'last_login_type' => $user_info['current_login_type'],
            'current_login_ip' => request()->ip(),
            'current_login_time' => time(),
            //    'current_login_type'  => 1
        );


        $retval = $this->agent_user->save($data,['id' => $agent_user_info['id']]);
        //用户登录成功钩子
        //   hook("userLoginSuccess", $user_info);
        return $retval;
    }

    /**
     * ok-2ok
     * 取得session中的agentUserId
     * @return mixed
     */
    public function getSessionAgentUserId() {
        // $this->uid = Session::get($model.'uid');
        return Session::get('agentUserId');
    }

    /**
     * ok-2ok
     * 取得session中的agentId
     * @return mixed
     */
    public function getSessionAgentId() {
        // $this->uid = Session::get($model.'uid');
        return Session::get('agentId');
    }

    /**
     * 用户退出
     */
    public function logout()
    {
        // $model = $this->getRequestModel();
        Session::set('agentUserId', '');
        Session::set('agentId', '');
    }

    /**
     * ok-2ok
     * 代理商用户修改密码
     */
    public function modifyUserPassword($userId, $old_password, $new_password)
    {
        $condition = array(
            'id' => $userId,
            'password' => md5($old_password)
        );
        $res = $this->agent_user->getInfo($condition, $field = "id");
        if (! empty($res['id'])) {
            $data = array(
                'password' => md5($new_password),
                'update_time'=>time()
            );
            $res = $this->agent_user->save($data, [
                'id' => $userId
            ]);
            if (empty($res)) {
                $this->error='密码修改失败';
                return false;
            } else {
                return true;
            }
        } else {
            $this->error = "旧密码不正确";
        }
        return false;
        // return PASSWORD_ERROR;
    }


    /**
     * ok-2ok
     * 添加代理商用户登录日志
     */
    public function addAgentUserLog($user_id,  $controller, $method, $ip, $get_data)
    {
        $data = array(
            'user_id' => $user_id,
            'user_type'=> 2,  //2表示代理商用户
            //  'is_system' => $is_system,
            'controller' => $controller,
            'method' => $method,
            'ip' => $ip,
            'data' => $get_data,
            'create_time' => time()
        );
        $user_log = new UserLogModel();
        $res = $user_log->save($data);
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 通过代理商用户id,得到代理商用户信息
     * @param $agentUserId
     * @return null|static
     */
    public function getAgentUserById($agentUserId) {
        return $this->agent_user->get($agentUserId);
    }

    /**
     * ok-2ok
     * 通过代理商用户id,得到代理商信息
     * @param $agentUserId
     * @return null|static
     */
    public function getAgentInfoByUserId($agentUserId) {
        $agent['agent_user'] =  $this->agent_user->get($agentUserId);
        unset($agent['agent_user']['password']);
        $agent_handle = new AgentHandle();
        $agent['agent_info'] = $agent_handle->getAgentInfoById($agent['agent_user']['agent_id'], '*');
        return $agent;
    }

    /**
     * ok-2ok
     * 通过代理商用户id,得到代理商用户信息
     * @param $agentUserId
     * @return null|static
     */
    public function getAgentUserInfoById($agentUserId) {
        $agent_user =  $this->agent_user->get($agentUserId);
        unset($agent_user['password']);
        if ($agent_user['status'] ==1) {
            $agent_user['user_status_name'] = '正常';
        }
        if ($agent_user['status'] ==0) {
            $agent_user['user_status_name'] = '禁用';
        }
        return $agent_user;
    }

    /**
     * ok-2ok
     * 设置代理商用户昵称
     */
    public function setAgentUserNickName($agentUserId, $nick_name)
    {
        $data = array(
            'nick_name' => $nick_name,
            'update_time'=>time()
        );
        $res = $this->agent_user->save($data, [
            'id' => $agentUserId
        ]);

        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 设置代理商用户头像
     */
    public function setAgentUserHeadimg($agentUserId, $user_headimg)
    {
        $data = array(
            'user_headimg' => $user_headimg,
            'update_time'=>time()
        );
        $res = $this->agent_user->save($data, [
            'id' => $agentUserId
        ]);

        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 设置代理商用户真实姓名
     */
    public function setAgentUserRealName($agentUserId, $real_name)
    {
        $data = array(
            'real_name' => $real_name,
            'update_time'=>time()
        );
        $res = $this->agent_user->save($data, [
            'id' => $agentUserId
        ]);

        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }
}