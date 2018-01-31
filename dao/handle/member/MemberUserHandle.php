<?php

/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-08-22
 * Time: 20:56
 */
namespace dao\handle\member;

use dao\handle\BaseHandle;
use dao\handle\promotion\PromoteRewardRuleHandle;
use dao\model\MemberUser as MemberUserModel;
use dao\model\MemberAccount as MemberAccountModel;
use dao\model\MemberLevel as MemberLevelModel;
use dao\model\MemberInfo as MemberInfoModel;
use dao\model\UserLog as UserLogModel;
use dao\handle\GoodsHandle as GoodsHandle;

class MemberUserHandle extends BaseHandle
{
    private $member_user;

    function __construct()
    {
        parent::__construct();
        $this->member_user = new MemberUserModel();
    }

/*    public function getUserInfo()
    {
        $res = $this->user->getInfo('uid=' . $this->uid, '*');
        return $res;
    }
*/
    /**
    根据用户id得到用户信息
     */
    public function getUserInfoById($uid)
    {
        $res = $this->member_user->getInfo('id=' . $uid, '*');

        return $res;
    }

    /**
     * 根据用户名获取用户信息
     */
    public function getUserInfoByUserName($username)
    {
        $res = $this->member_user->getInfo(['user_name'=>$username],'*');

        return $res;
    }

    /**
    根据指定条件得到用户信息
     */
    public function getUserInfoByCondition($condition, $fields)
    {
        $res = $this->member_user->getInfo($condition, $fields);

        return $res;
    }

    /**
     * 根据用户名修改密码
     */
    public function  updatePasswordByUserName($username,$password){

        $data = array(
            'password' => md5($password)
        );
        $retval = $this->member_user->save($data, ['user_name' => $username]);
        return $retval;
    }



    /**
     * 根据登录手机号获取用户信息
     */
    public function getUserInfoByLoginPhone($phone)
    {
        $res = $this->member_user->getInfo(['login_phone'=>$phone],'*');

        return $res;
    }

    /**
     * ok-2ok
     * 根据用户登录手机号修改密码
     */
    public function  updatePasswordByLoginPhone($phone,$password){
        $data = array(
            'password' => md5($password),
            'update_time'=>time()
        );
        $retval = $this->member_user->save($data, ['login_phone' => $phone]);
        if ($retval > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 根据用户id修改密码
     */
    public function  updatePasswordByUserId($userid,$password){

        $data = array(
            'password' => md5($password)
        );
        $retval = $this->member_user->save($data, ['id' => $userid]);
        return $retval;
    }

    /**
     * 根据用户登录手机号修改用户信息
     */
    public function  updateUserByLoginPhone($login_phone,$data){
        if (isset($data['password'])) {
            $data['password'] = md5($data['password']);
        }


        $retval = $this->member_user->allowField([ 'password', 'level',
            'agent_id', 'user_type', 'login_count', 'last_login_time', 'status',
            'create_time', 'update_time'])->save($data, ['login_phone' => $login_phone]);
        return $retval;
    }

    /**
     * 根据用户id修改密码
     */
    public function  updateUserByUserId($user_id,$data){

        if (isset($data['login_password'])) {
            $data['login_password'] = md5($data['login_password']);
        }

        $login_phone = '';
        if (isset($data['login_phone'])) {
            $login_phone = $data['login_phone'];
        }

        if (!empty($login_phone)) {
            $user_info = $this->member_user->getInfo(['id' => $user_id], '*');

            if ($user_info['login_phone'] != $login_phone) {
                $count = $this->member_user->where([
                    'login_phone' => $login_phone
                ])->count();
                if ($count > 0) {
                    $this->error = "手机号" . $login_phone . "已被注册，不可重复使用";
                    return false;
                    // return USER_REPEAT;
                }
            }
        }
        $allowFields = ['login_phone', 'password', 'level',
            'agent_id', 'user_type', 'login_count', 'last_login_time', 'status',
            'create_time', 'update_time'];
        $retval = $this->member_user->allowField($allowFields)->save($data, ['id' => $user_id]);
        return $retval;
    }

    /**
     * 根据用户名修改密码
     */
    public function  updateUserByUserName($user_name,$data){

        if (isset($data['login_password'])) {
            $data['login_password'] = md5($data['login_password']);
        }

        $login_phone = '';
        if (isset($data['login_phone'])) {
            $login_phone = $data['login_phone'];
        }

        if (!empty($login_phone)) {
            $user_info = $this->member_user->getInfo(['user_name' => $user_name], '*');

            if ($user_info['login_phone'] != $login_phone) {
                $count = $this->member_user->where([
                    'login_phone' => $login_phone
                ])->count();
                if ($count > 0) {
                    $this->error = "手机号" . $login_phone . "已被注册，不可重复使用";
                    return false;
                    // return USER_REPEAT;
                }
            }
        }

        $allowFields = ['login_phone', 'password', 'level',
            'agent_id', 'user_type', 'login_count', 'last_login_time', 'status',
            'create_time', 'update_time'];
        $retval = $this->member_user->allowField($allowFields)->save($data, ['user_name' => $user_name]);
      //  $retval = $this->member_user->save($data, ['id' => $user_id]);
        return $retval;
    }





    /**
     * ok-2ok
     * 会员用户注册
     * @param $user_name
     * @param $login_phone
     * @param $password
     * @param $agent_id
     * @param $user_type
     * @param $province_id
     * @param $city_id
     * @param $district_id
     * @param $wx_openid
     * @param $wx_unionid
     * @param $wx_info
     * @return bool|mixed
     */
    public function registerMember($user_name, $login_phone, $password, $user_type, $province_id, $city_id,  $district_id, $wx_openid, $wx_unionid,$wx_info,$agent_id=1 ) {
        if (empty($login_phone)) {
            $this->error = "手机号不能为空";
            return false;
        }
        $count = $this->member_user->where([
            'login_phone' => $login_phone
        ])->count();
        if ($count > 0) {
            $this->error = "此手机号已被注册，不可重复注册";
            return false; //USER_REPEAT;
        }
        $nick_name ='';
        $user_head_img = '';
        if (! empty($wx_openid) || !empty($wx_unionid)) {
            $wx_info_array = json_decode($wx_info);
            $nick_name = $this->filterStr($wx_info_array->nickname);
            $user_head_img = $wx_info_array->headimgurl;
            $wx_info = $this->filterStr($wx_info);
        } else {
            $user_head_img = '';
        }
        $local_path = '';
        if(!empty($user_head_img))
        {
            if(!file_exists('upload/user')){
                $mode = intval('0777',8);
                mkdir('upload/user',$mode,true);
                if(!file_exists('upload/user'))
                {
                    die('upload/user不可写，请检验读写权限!');
                }
            }
            $local_path = 'upload/user/'.time().rand(111,999).'.png';
            save_weixin_img($local_path, $user_head_img);

        }

        // 获取默认会员等级id
        $member_level = new MemberLevelModel();
        $level_info = $member_level->getInfo([
            'is_default' => 1
        ], 'id');
        $member_level = $level_info['id'];
        try {

            $this->startTrans();
            $data = array(
                'user_name' =>$user_name,
                'login_phone' => $login_phone,
                'password' => md5($password),
                //    'level' => 1,
                'agent_id' => $agent_id,
                'user_type' => $user_type,
                'member_level' => $member_level,

                'wx_openid' => $wx_openid,
                'wx_unionid'  => $wx_unionid,
                'wx_is_sub' => 0,
                'wx_sub_time' => 0,
                'wx_notsub_time' => 0,
                'wx_info' => $wx_info,
                'login_count' => 0,
                'last_login_time' => 0,
                'status' => 1,
                'create_time'=>time(),
                'update_time'=>time()
            );
            $member_user = new MemberUserModel();
            $member_user->save($data);
            $uid = $member_user->id;
            if ($uid < 1) {
                $this->rollback();
                $this->error ="保存会员用户出错, 操作失败!";
                return false;
            }
            //用户添加成功后
            //添加用户帐户
            $data_account = array (
                'user_id' => $uid,
                'point' => 0,
                'balance' => 0,
                'coin'=> 0,
                'member_cunsum'=> 0,
                'member_sum_point'=> 0,
                'member_sum_order'=> 0,
                'create_time'=>time(),
                'update_time'=>time());
            $member_account = new MemberAccountModel();
            $member_account->save($data_account);
            $account_id = $member_account->id;
            if ($account_id < 1) {
                $this->rollback();
                $this->error ="新增会员帐号出错, 操作失败!";
                return false;
            }

            //添加用户资料
            if (empty($province_id)) {
                $province_id = 0;
            }
            if (empty($city_id)) {
                $city_id = 0;
            }
            if (empty($district_id)) {
                $district_id = 0;
            }
            $data_info = array (
                'user_id' => $uid,
                'province_id' => $province_id,
                'city_id' => $city_id,
                'district_id'=> $district_id,
                'nickname' => $nick_name,
                'head_img' => $local_path,
                'create_time'=>time(),
                'update_time'=>time()
            );
            $member_info = new MemberInfoModel();
            $member_info->save($data_info);
            $info_id = $member_info->id;
            if ($info_id < 1) {
                $this->rollback();
                $this->error ="新增会员资料出错, 操作失败!";
                return false;
            }

            // 注册会员送积分
            $promote_reward_rule = new PromoteRewardRuleHandle();
            // 添加关注
            // 平台赠送积分
            $promote_reward_rule->RegisterMemberSendPoint(0, $uid);
           // RegisterMemberSendPoint($shop_id=0, $user_id)
            $this->commit();

          //  hook('memberRegisterSuccess', $data);
            return $uid;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error ="操作时出现异常:".$e->getMessage();
            return false;
        }

    }



    /**
     * 系统用户基础添加方式
     */
    public function addUser($user_name, $login_phone, $password, $agent_id, $user_type, $province_id, $city_id,  $district_id) {
        if (empty($login_phone)) {
            $this->error = "登录手机号不能为空";
            return false;
        }
        $count = $this->member_user->where([
                'login_phone' => $login_phone
            ])->count();
        if ($count > 0) {
            $this->error = "此手机号已被注册，不可重复注册";
            return false; //USER_REPEAT;
        }
        try {

            $this->startTrans();
            $data = array(
                'user_name' =>$user_name,
                'login_phone' => $login_phone,
                 'password' => md5($password),
             //    'level' => 1,
                'agent_id' => $agent_id,
                 'user_type' => $user_type,
                 'login_count' => 0,
                 'last_login_time' => 0,
                 'status' => 1
             );
            $member_user = new MemberUserModel();
             $member_user->save($data);
            $uid = $member_user->id;
            if ($uid < 1) {
                $this->rollback();
                $this->error ="保存会员用户出错, 操作失败!";
                return false;
             }
        //用户添加成功后
        //添加用户帐户
            $data_account = array (
                 'user_id' => $uid,
                'point' => 0,
                 'balance' => 0,
                'coin'=> 0,
                'member_cunsum'=> 0,
                'member_sum_point'=> 0,
                'member_sum_order'=> 0);
             $member_account = new MemberAccountModel();
             $member_account->save($data_account);
            $account_id = $member_account->id;
            if ($account_id < 1) {
                $this->rollback();
                $this->error ="新增会员帐号出错, 操作失败!";
                return false;
            }

            //添加用户资料
            if (empty($province_id)) {
                $province_id = 0;
            }
            if (empty($city_id)) {
                $city_id = 0;
            }
            if (empty($district_id)) {
                $district_id = 0;
            }
            $data_info = array (
                'user_id' => $uid,
                'province_id' => $province_id,
                'city_id' => $city_id,
                'district_id'=> $district_id,
              );
            $member_info = new MemberInfoModel();
            $member_info->save($data_info);
            $info_id = $member_info->id;
            if ($info_id < 1) {
                $this->rollback();
                $this->error ="新增会员资料出错, 操作失败!";
                return false;
            }
            $this->commit();
            return $uid;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error ="操作时出现异常:".$e->getMessage();
            return false;
        }

    }

    /**
     * 过滤特殊字符
     */
    private function filterStr($str)
    {
        if($str){
            $name = $str;
            $name = preg_replace_callback('/\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]/',function ($matches) { return '';}, $name);
            $name = preg_replace_callback('/xE0[x80-x9F][x80-xBF]‘.‘|xED[xA0-xBF][x80-xBF]/S',function ($matches) { return '';}, $name);
            // 汉字不编码
            $name = json_encode($name);
            $name = preg_replace_callback("/\\\ud[0-9a-f]{3}/i", function ($matches) { return '';}, $name);
            if(!empty($name))
            {
                $name = json_decode($name);
                return $name;
            }else{
                return '';
            }

        }else{
            return '';
        }
    }


    /**修改用户登录手机号
     * ok-2ok
     * @param $user_id
     * @param $login_phone
     * @return bool
     */
    public function updateLoginPhone($user_id, $login_phone)
    {
        //前期判断
        if (empty($login_phone)) {
            $this->error = "登录手机号不能为空!";
            return false;
        }
        $user_info = $this->member_user->getInfo(['id' => $user_id], '*');
        if($user_info['login_phone'] != $login_phone)
        {
                $count = $this->member_user->where([
                    'login_phone' => $login_phone
                ])->count();
                if ($count > 0) {
                    $this->error = "手机号".$login_phone."已被注册，不可重复使用";
                    return false;
                   // return USER_REPEAT;
                }
        }


        $data = array(
            'login_phone' => $login_phone
        );
        $retval = $this->member_user->save($data, ['id' => $user_id]);
        if ($retval === false) {
            return false;
        } else {
            return true;
        }
      //  return $retval;
    }


    /*检查登录手机号是否可以注册*/
    public function checkLoginPhone($login_phone)
    {
        $count = $this->member_user->where([
                'login_phone' => $login_phone
            ])->count();
        if ($count > 0) {
                $this->error = "手机号".$login_phone."已被注册，不可再次注册";
                return false;
                // return USER_REPEAT;
        }

        return true;
    }

    /**
     * 用户修改密码
     *
     */
    public function modifyUserPassword($user_id, $old_password, $new_password)
    {
        $condition = array(
            'id' => $user_id,
            'password' => md5($old_password)
        );
        $res = $this->member_user->getInfo($condition, $field = "id");
        if (! empty($res['id'])) {
            $data = array(
                'password' => md5($new_password)
            );
            $res = $this->member_user->save($data, [
                'id' => $user_id
            ]);
            if ($res === false) {
               // $this->error = '密码修改失败';
                return false;
            }
            return true;
        } else {
            $this->error = "旧密码不正确,密码修改失败!";
            return false; //PASSWORD_ERROR;
        }
    }

    /**
     *修改用户名
     */
    public function modifyUserName($uid, $user_name)

    {
        $info = $this->member_user->get($uid);
        if ($info['user_name'] == $user_name) {
            return true;
        }
        $count = $this->member_user->where([
            'user_name' => $user_name
        ])->count();
        if ($count > 0) {
            $this->error =  '用户名 '.$user_name.' 已被注册，不可再次使用';
            return false; //USER_REPEAT;
        }
        $data = array(
            'user_name' => $user_name
        );
        $res = $this->member_user->save($data, [
            'id' => $uid
        ]);
        if ($res > 0) {
            return true;
        } else {
            $this->error = '操作失败!';
            return false;
        }
    }

    /**
     * 添加用户登录日志
     */
    public function addUserLog($user_id, $user_type, $controller, $method, $ip, $get_data)
    {
        $data = array(
            'user_id' => $user_id,
            'user_type' => $user_type,
            'controller' => $controller,
            'method' => $method,
            'ip' => $ip,
            'data' => $get_data,
        );
        $user_log = new UserLogModel();
        $res = $user_log->save($data);
        return $res;
    }

    /*
   * 前台会员注册
   */
    public function register($user_name, $login_phone, $password, $agent_id, $user_type, $province_id, $city_id,  $district_id)
    {
        // if (! empty($user_name)) {
        // if (! preg_match("/^(?!\d+$)[\da-zA-Z]*$/i", $user_name)) {
        // return USER_WORDS_ERROR;
        // }
        // }

     //   addUser($user_name, $login_phone, $login_password, $agent_id, $login_count=0, $last_login_time=0)
        $res = $this->addUser($user_name, $login_phone, $password, $agent_id,$user_type,  $province_id, $city_id,  $district_id);
        if (!$res) {
            return false;
        }
        if ($res > 0) {
            // 注册会员送体验券
         //   $promote_reward_rule = new PromoteRewardRule();

            // 注册会员送体验券
         //   $promote_reward_rule->RegisterMemberSendPoint(0, $res);
            // 直接登录
          //  $this->login($login_phone, $login_password);
        }
        return $res;

    }

   /*
    * 会员用户登录
    */
    public function login($login_phone, $password)
    {
        if (empty($login_phone)) {
            $this->error = '登录手机号不能为空';
            return false;
        }
        if (empty($password)){
            $this->error = '密码不能为空';
            return false;
        }
        /*
        if (config('IDENTIFYING_CODE') && !$type) {
            if (!$verifyCode) {
                $this->error = '验证码不能为空';
                return false;
            }
            $captcha = new HonrayVerify(config('captcha'));
            if (!$captcha->check($verifyCode)) {
                $this->error = '验证码错误';
                return false;
            }
        }
     */
        $map['login_phone'] = $login_phone;
        $userInfo = $this->member_user->where($map)->find();
        if (!$userInfo) {
            $this->error = '此手机号对应的帐号不存在';
            return false;
        }
        if (md5($password) !== $userInfo['password']) {
            $this->error = '密码错误';
            return false;
        }
        if ($userInfo['status'] != 1) {
            $this->error = '此帐号已被禁用';
            return false;
        }
        // 获取菜单和权限

        $data = array(
            'login_count' => $userInfo['login_count'] + 1,
            'last_login_time'=> time()
        );

        $this->member_user->save($data, ['id' =>  $userInfo['id']]);

       ////离线购物车同步
        $goods_handle = new GoodsHandle();
        $goods_handle->syncUserCart($userInfo['id']);
        //// $goods->syncUserCart($user_info['uid']);



    //    if ($isRemember || $type) {
        $secret['login_phone'] = $login_phone;
        $secret['password'] = $password;
        $data['rememberKey'] = encrypt($secret);
      //  }

        // 保存缓存
        session_start();
        $info['userInfo'] = $userInfo;
        $info['sessionId'] = session_id();
        $authKey = user_md5($userInfo['login_phone'].$userInfo['password'].$info['sessionId']);
       // $info['_AUTH_LIST_'] = $dataList['rulesList'];
        $info['authKey'] = $authKey;
        cache('Auth_'.$authKey, null);
        cache('Auth_'.$authKey, $info, config('LOGIN_SESSION_VALID'));
        // 返回信息
        $resData['authKey']		= $authKey;
        $resData['sessionId']		= $info['sessionId'];
        $resData['userInfo']		= $userInfo;
      //  $data['authList']		= $dataList['rulesList'];
      //  $data['menusList']		= $dataList['menusList'];
        return  $resData;
    }

    /**
     * 创建生成用户名
     */
    public function createUserName()
    {
        $user_name = "n" . date("ymdHis" . rand(1111, 9999));
        return $user_name;
    }

    /*
     *得到符合条件的会员用户数量
     */
    public function getMemberUserCount($condition)
    {

        $user= new MemberUserModel();
        $user_list = $user->getConditionQuery($condition, "count(*) as count", '');
        return $user_list[0]["count"];
    }

    /**
      *检查登录手机号是否存在
     */
    public function checkLoginPhoneIsHas($loginPhone){
        $user= new MemberUserModel();
        $count = $user->getCount(['login_phone' => $loginPhone]);
        return $count;
    }

    /**
     * (non-PHPdoc)
     *根据会员用户id，得到会员用户的详情
     */
    public function getMemberUserInfoDetail($uid){
        $user_info = $this->member_user->getInfo(array("id"=>$uid));
        return $user_info;
    }

    /**
     * 会员等级自动升级
     */
    public function updateUserLevel( $user_id){

      //  $shop_server=new Shop();
        #得到会员的消费金额
      //  $money=$shop_server->getShopUserConsume($shop_id, $user_id);
        #得到会员的累计积分
        /*
        $member_account_model=new MemberAccountModel();
        $member_account_obj=$member_account_model->getInfo(["user_id"=>$user_id], "member_sum_point");
        $member_sum_point=0;
        if(!empty($member_account_obj)){
            $member_sum_point=$member_account_obj["member_sum_point"];
        }
        #得到会员的信息
        $member_user_model=new MemberUserModel();
        $member_obj=$member_user_model->get($user_id);
        if(!empty($member_obj)){
            $level_id=$member_obj["member_level"];
            $member_level_model=new MemberLevelModel();
            $level_list=$member_level_model->getConditionQuery("(upgrade=3 and relation=1 and (min_integral<=".$member_sum_point." or quota<=".$money.")) or (upgrade=3 and relation=2 and min_integral<=".$member_sum_point." and quota<=".$money.") or (upgrade=1 and min_integral<=".$member_sum_point." ) or (upgrade=2 and quota<=".$money.")", "*", "goods_discount");
            $member_level_model=new NsMemberLevelModel();
            $member_level_obj=$member_level_model->get($level_id);
            if(!empty($level_list) && count($level_list)>0){
                $update_level_obj=$level_list[0];
                $update_goods_discount=$update_level_obj["goods_discount"];
                $goods_discount=1;
                if(!empty($member_level_obj)){
                    $goods_discount=$member_level_obj["goods_discount"];
                }
                if($update_goods_discount<$goods_discount){
                    $member_model->save(["member_level"=>$update_level_obj["level_id"]], ["uid"=>$user_id]);
                }
            }
        }
        */
    }


}