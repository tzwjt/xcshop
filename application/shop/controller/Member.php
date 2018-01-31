<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-08-24
 * Time: 20:11
 */

namespace app\shop\controller;

use app\shop\controller\BaseController;
use dao\handle\ConfigHandle;
use dao\handle\member\MemberCouponHandle;
use dao\handle\member\MemberUserHandle;
use dao\handle\MemberHandle;
use dao\handle\AgentHandle;
use dao\handle\AddressHandle;
use dao\handle\GoodsHandle;
use dao\handle\OrderHandle;
use dao\handle\promotion\PromoteRewardRuleHandle;
use dao\handle\PromotionHandle;
use think\Session;


class Member extends BaseController
{

    /********* 微信相关*******/
    /**
     * 获取需要绑定的信息放到session中
     */
    public function getWchatBindMemberInfo()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
            $config = new ConfigHandle();
            $wchat_config = $config->getInstanceWchatConfig(0);
          //  $register_and_visit = $config->getRegisterAndVisit(0);
           // $register_config = json_decode($register_and_visit['value'], true);
            // 当前openid 没有在数据库中存在 并且 后台没有开启 强制绑定会员
            $token = "";
            $is_bind = 1;
            $info = "";
            $wx_unionid = "";
            $domain_name = \think\Request::instance()->domain();
            if (! empty($wchat_config['value']['appid']) && $register_config["is_requiretel"] == 1) {
                $wchat_oauth = new WchatOauth();
                $member_access_token = Session::get($domain_name . "member_access_token");
                if (! empty($member_access_token)) {
                    $token = json_decode($member_access_token, true);
                } else {
                    $token = $wchat_oauth->get_member_access_token();
                    if (! empty($token['access_token'])) {
                        Session::set($domain_name . "member_access_token", json_encode($token));
                    }
                }
                if (! empty($token['openid'])) {
                    $user_count = $this->user->getUserCountByOpenid($token['openid']);
                    if ($user_count == 0) {
                        // 更新会员的微信信息
                        $info = $wchat_oauth->get_oauth_member_info($token);
                        if (! empty($token['unionid'])) {
                            $wx_unionid = $token['unionid'];
                        }
                    } else {
                        $is_bind = 0;
                    }
                }
            }
            $bind_message = array(
                "token" => $token,
                "is_bind" => $is_bind,
                "info" => $info,
                "wx_unionid" => $wx_unionid
            );
            Session::set("bind_message_info", json_encode($bind_message));
        }
    }




    /************************/

    public function register() {
        //登录手机号
        $login_phone = isset($this->param['login_phone']) ? $this->param['login_phone'] : '';
        //登录密码
        $password = isset($this->param['password']) ? $this->param['password'] : '';
        //代理商识别码
        $agent_identify_code = isset($this->param['identify_code']) ? $this->param['identify_code'] : '';
       //省市区
        $province = isset($this->param['province']) ? $this->param['province'] : '';
        $city = isset($this->param['city']) ? $this->param['city'] : '';
        $district = isset($this->param['district']) ? $this->param['district'] : '';
        //
        //验证码
        $verify_code = isset($this->param['verify_code']) ? $this->param['verify_code'] : '';
        $memberUserHandle = new MemberUserHandle();
        if (empty($login_phone)) {
            return json(resultArray(2,"登录手机号不能为空"));
        }
        $count = $memberUserHandle->checkLoginPhoneIsHas($login_phone);
        if ($count > 0) {
            return json(resultArray(2,"手机号已被注册,不可重复注册"));
        }

        if (empty($verify_code)) {
            return json(resultArray(2,"验证码不能为空"));
        }

        if ($verify_code != Session::get('RegisterVerificationCode') ||   $login_phone != Session::get('RegisterPhone')) {
            return json(resultArray(2,"验证码不正确"));
        }

        if (empty($password)) {
            return json(resultArray(2,"登录密码不能为空"));
        }

        $agentHandle = new AgentHandle();
        $agentId = 0;
        $userType = 0;
        if (empty($agent_identify_code)) {
            $agent = $agentHandle->getPlatformAgent();
            $agentId = $agent['id'];
            $userType = 0;
        } else {
            $agent = $agentHandle->getValidAgentByIdentifyCode($agent_identify_code);

            if (empty($agent)) {
                return json(resultArray(2,"商家识别码无效"));
            } else {
                $agentId = $agent['id'];
                $userType = $agent['agent_type'];
            }
        }
        $user_name = $memberUserHandle->createUserName();
        $addressHandle = new AddressHandle();
        $provinceId = $addressHandle->getProvinceId($province);
        $cityId = $addressHandle->getCityIdByProviceIdAndCityName($provinceId, $city);
        $districtId = $addressHandle->getDistrictIdByCityIdAndDistrictName($cityId,$district);
        $res = $memberUserHandle->register($user_name, $login_phone, $password, $agentId, $userType,$provinceId,$cityId,$districtId );

        if ($res === false) {
            return json(resultArray(2,$memberUserHandle->getError()));
        } else {
            /*
            $res =  $memberUserHandle->login($login_phone, $password);

            if ($res === false) {
                return json(resultArray(2,$memberUserHandle->getError()));
            } else {
                return json(resultArray(0, "注册成功",  $res));
            }
            */
            return json(resultArray(0, "注册成功",  $res));
        }
    }

    public function login() {
        //登录手机号
        $login_phone = isset($this->param['login_phone']) ? $this->param['login_phone'] : '';
        //登录密码
        $password = isset($this->param['password']) ? $this->param['password'] : '';

        if (empty($login_phone)) {
            return json(resultArray(2,"登录手机号不能为空"));
        }

        if (empty($password)) {
            return json(resultArray(2,"密码不能为空"));
        }

        $memberUserHandle = new MemberUserHandle();
        $res =  $memberUserHandle->login($login_phone, $password);

       // if ($res === false)
        if (empty($res)) {
            return json(resultArray(2,$memberUserHandle->getError()));
        } else {
            $userInfo = $res['userInfo'];
            $GLOBALS['userInfo'] = $userInfo;
            $this->user_id = $userInfo['id'];
            Session::set("MEMBER_USER_ID", $userInfo['id']);


            return json(resultArray(0, "登录成功",  $res));
        }

    }

    //退出处理
    public function logout() {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");
        if ($user_id == 0) {
            return $authRet;
        }
        $this->user_id = 0;
        Session::set("MEMBER_USER_ID",0);
        $GLOBALS['userInfo'] = 0;
        cookie('cart_array', null); //清除cookiek中的购物车
        cookie(null); //清除当前的所有cookie
        return json(resultArray(0, "退出成功"));
    }


    /* 检查手机号是否可注册 */
    public function checkLoginPhoneIsRegister() {
        $login_phone = isset($this->param['login_phone']) ? $this->param['login_phone'] : '';
        if (empty($login_phone)) {
            return json(resultArray(2,"手机号不可为空"));
        }
        $memberUserHandle = new MemberUserHandle();
        $count = $memberUserHandle->checkLoginPhoneIsHas($login_phone);

        if ($count === 0) {
            return json(resultArray(0,"手机号可以注册"));
        } else if ($count > 0) {
            return json(resultArray(2,"此手机号已被注册，不可重复注册"));
        } else {
            return json(resultArray(2,"此手机号不可以注册"));
        }

    }

    /* 发送手机注册验证码 */
    public function  getMobileVerifyCode() {

        $phone = isset($this->param['login_phone']) ? $this->param['login_phone'] : '';

        if (empty($phone)) {
            return json(resultArray(2,"手机号不能为空"));
        }

        $memberUserHandle = new MemberUserHandle();
        $count = $memberUserHandle->checkLoginPhoneIsHas($phone);
        if ($count > 0) {
            return json(resultArray(2,"手机号已被注册，不可重复注册"));
        }


        $verify_code = rand(1000, 9999);
        $smsParam = array (
            "code" => $verify_code,
        );
        $response = aliDayunSmsSend( $phone, "SMS_79005082",  $smsParam);
        $info = json_decode(json_encode($response),TRUE);
        if ($info['Code'] == 'OK') {
           Session::set('RegisterVerificationCode',$verify_code );
            Session::set('RegisterPhone',$phone );
            return json(resultArray(0,"手机验证码发送成功"));
        } else {
            return json(resultArray(2,"手机验证码发送失败"));
        }

    }

    /**
    // 获取当前会员信息
     */
    public function getMemberInfo()
    {

        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");
        if ($user_id == 0) {
            return $authRet;
        }
        //身份验证通过
        $memberHandle = new MemberHandle();

        $data = $memberHandle->getMemberUserInfo($user_id);
        return json(resultArray(0,"操作成功", $data));
    }

    public function getAgentByAddress()
    {
        $province = isset($this->param['province']) ? $this->param['province'] : '';
        $city = isset($this->param['city']) ? $this->param['city'] : '';
        $district = isset($this->param['district']) ? $this->param['district'] : '';
        $addressHandle = new AddressHandle();

        if (empty($province)) {
            $provinceId = 0;
        } else {
            $provinceId = $addressHandle->getProvinceId($province);
        }
        if (empty($city)) {
            $cityId = 0;
        } else {
            $cityId = $addressHandle->getCityIdByProviceIdAndCityName($provinceId, $city);
        }
        if (empty($district)) {
            $districtId = 0;
        } else {
            $districtId = $addressHandle->getDistrictIdByCityIdAndDistrictName($cityId, $district);
        }
        $agentHandle = new AgentHandle();
        $res = $agentHandle->getValidAgentByAddress($provinceId, $cityId, $districtId );

        return json(resultArray(0,"操作成功", $res));
    }

    /**
     * 获取省列表，商品添加时用户可以设置商品所在地
     */
    public function getProvince()
    {
        $address = new AddressHandle();
        $province_list = $address->getProvinceList();

        return json(resultArray(0,"操作成功",$province_list));
    }

    /**
     * 获取城市列表
     */
    public function getCity()
    {
        $address = new AddressHandle();
        $province_id = isset($this->param['province_id']) ? $this->param['province_id'] : 0;
        $city_list = $address->getCityList($province_id);
        return json(resultArray(0,"操作成功",$city_list));
    }

    /**
     * 获取区县列表
     */
    public function getDistrict()
    {
        $address = new AddressHandle();
        $city_id = isset($this->param['city_id']) ? $this->param['city_id'] : 0;
        $district_list = $address->getDistrictList($city_id);
        return json(resultArray(0,"操作成功",$district_list));
    }

/***********************  会员收货地址 ****************************************************************/
    /**
     * 会员地址管理
     * (需验证身份)
     */
    public function memberAddress()
    {
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }

        $memberHandle = new MemberHandle();
      //  getMemberExpressAddressList($user_id, $page_index = 1, $page_size = 0, $condition = '', $order = '')
        $addresslist = $memberHandle->getMemberExpressAddressList($user_id);

        return json(resultArray(0,"操作成功",$addresslist));
        /*
        $this->assign("list", $addresslist);
        $flag = isset($_GET['flag']) ? $_GET['flag'] : "";
        $url = isset($_GET['url']) ? $_GET['url'] : "";
        $pre_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        // dump($pre_url);
        $_SESSION['address_pre_url'] = $pre_url;
        $this->assign("pre_url", $pre_url);
        $this->assign("flag", $flag);
        $this->assign("url", $url);
        return view($this->style . "/Member/memberAddress");
        */
    }

    /**
     * 添加收货地址
     * (需验证身份)
     */
    public function addMemberAddress()
    {
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }

        $memberHandle = new MemberHandle();
        $consigner = $this->param['consigner'];
        $mobile = $this->param['mobile'];
        $phone = isset($this->param['phone']) ? $this->param['phone'] : '';
        $province = $this->param['province'];
        $city = $this->param['city'];
        $district = $this->param['district'];
        $address = $this->param['address'];
        $zip_code = isset($this->param['zip_code']) ? $this->param['zip_code'] : '';
        $alias = isset($this->param['alias']) ? $this->param['alias'] : '';
        $retval = $memberHandle->addMemberExpressAddress($user_id, $consigner, $mobile, $phone, $province, $city, $district, $address, $zip_code, $alias);
        if (empty($retval)) {
            return json(resultArray(2, $memberHandle->getError()));
        } else {
            $addresslist = $memberHandle->getMemberExpressAddressList($user_id);
            return json(resultArray(0, "操作成功", $addresslist ));
        }
    }

    /**
     * 修改会员地址
     *  (需验证身份)
     */
    public function updateMemberAddress()
    {
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");
        if ($user_id == 0) {
            return $authRet;
        }
        $memberHandle = new MemberHandle();
        $id = $this->param['id'];
        $consigner = $this->param['consigner'];
        $mobile = $this->param['mobile'];
        $phone = isset($this->param['phone']) ? $this->param['phone'] : '';
        $province = $this->param['province'];
        $city = $this->param['city'];
        $district = $this->param['district'];
        $address = $this->param['address'];
        $zip_code = isset($this->param['zip_code']) ? $this->param['zip_code'] : '';
        $alias = isset($this->param['alias']) ? $this->param['alias'] : '';
        $retval = $memberHandle->updateMemberExpressAddress($user_id, $id, $consigner, $mobile, $phone, $province, $city, $district, $address, $zip_code, $alias);
        if (empty($retval)) {
            return json(resultArray(2, "操作失败"));
        } else {
            $addresslist = $memberHandle->getMemberExpressAddressList($user_id);
            return json(resultArray(0, "操作成功",$addresslist ));
        }

         //   return view($this->style . "/Member/updateMemberAddress");
    }

    /**
     * 获取用户地址详情
     * (需验证身份)
     */
    public function getMemberAddressDetail()
    {
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");
        if ($user_id == 0) {
            return $authRet;
        }
        $address_id = isset($this->param['id']) ? $this->param['id'] : 0;
        $memberHandle = new MemberHandle();
        $info = $memberHandle->getMemberExpressAddressDetail($user_id, $address_id);
        return json(resultArray(0, "操作成功",$info ));
     //   return $info;
    }

    /**
     * 会员地址删除
     * (需验证身份)
     */
    public function memberAddressDelete()
    {
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");
        if ($user_id == 0) {
            return $authRet;
        }
        $id = $this->param['id'];
      //  $id = isset($this->param['id']) ? $this->param['id'] : 0;
       //     $id = request()->post('id','');
        $memberHandle = new MemberHandle();
        $retval = $memberHandle->memberAddressDelete($user_id, $id);
        if (empty($retval)) {
            return json(resultArray(2, "操作失败"));
        } else {
            $addresslist = $memberHandle->getMemberExpressAddressList($user_id);
            return json(resultArray(0, "操作成功",$addresslist ));
        }
    }

    /**
     * 修改会员地址
     * (需验证身份)
     */
    public function updateAddressDefault()
    {
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");
        if ($user_id == 0) {
            return $authRet;
        }
        $id = $this->param['id'];
        $memberHandle = new MemberHandle();
        $retval = $memberHandle->updateAddressDefault($user_id, $id);
        if (empty($retval)) {
            return json(resultArray(2, "操作失败"));
        } else {
            $addresslist = $memberHandle->getMemberExpressAddressList($user_id);
            return json(resultArray(0, "操作成功" , $addresslist));
        }
      //  return AjaxReturn($res);
    }

    /******************************** Web店 会员地址 ***************************************/
    /**
     * 收货地址列表
     * (需验证身份)
     */
    public function addressList()
    {
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");
        if ($user_id == 0) {
            return $authRet;
        }
        $memberHandle = new MemberHandle();
        $page_index = isset($this->param['page']) ? $this->param['page'] : '1';
        $addresslist = $memberHandle->getMemberExpressAddressList($user_id, $page_index, 5, '', '');
      //  $member_detail = $this->user->getMemberDetail($this->instance_id);
     //   $this->assign("member_detail", $member_detail);
        $data = array (
           'address_list' =>$addresslist,
           'page' => $page_index
            );
        return json(resultArray(0, "操作成功", $data ));
   //     $this->assign('page_count', $addresslist['page_count']);
    //    $this->assign('total_count', $addresslist['total_count']);
    //    $this->assign('page', $page_index);
    //    $this->assign('list', $addresslist);
    //    return view($this->style . "/Member/addressList");
    }

    /**
     * 会员地址管理
     * 添加地址
     *
     */
    /*
    public function addressInsert()
    {
        if (request()->isAjax()) {
            $member = new MemberService();
            $consigner = $_POST['consigner'];
            $mobile = $_POST['mobile'];
            $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
            $province = $_POST['province'];
            $city = $_POST['city'];
            $district = $_POST['district'];
            $address = $_POST['address'];
            $zip_code = isset($_POST['zip_code']) ? $_POST['zip_code'] : '';
            $alias = isset($_POST['alias']) ? $_POST['alias'] : '';
            $retval = $member->addMemberExpressAddress($consigner, $mobile, $phone, $province, $city, $district, $address, $zip_code, $alias);
            return AjaxReturn($retval);
        } else {
            $member_detail = $this->user->getMemberDetail($this->instance_id);
            $this->assign("member_detail", $member_detail);
            $address_id = isset($_GET['addressid']) ? $_GET['addressid'] : 0;
            $this->assign("address_id", $address_id);

            return view($this->style . "/Member/addressInsert");
        }
    }
    */
/****************************** 2017-11-02 新操作************************************************/
    /**
     * ok-2ok
     * 进入会员中心
     * @return \think\response\Json
     */
    public function memberIndex()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }

        $member = new MemberHandle();
        $member_info = $member->getMemberDetail($user_id);
        // 头像
        if (! empty($member_info['user_info']['head_img'])) {
            $member_img = $member_info['user_info']['head_img'];
        } else {
            $member_img = '';
        }

        // 判断是否开启了签到送积分
        $config = new ConfigHandle();
        $integralconfig = $config->getIntegralConfig(0);

        // dump($integralconfig);
        // 判断用户是否签到
        $dataMember = new MemberHandle();
        $isSign = $dataMember->getIsMemberSign($user_id);

        // 待支付订单数量
        $order = new OrderHandle();
        $unpaidOrder = $order->getOrderNumByOrderStatu([
            'order_status' => 0,
            "buyer_id" => $user_id
        ]);


        // 待发货订单数量
        $shipmentPendingOrder = $order->getOrderNumByOrderStatu([
            'order_status' => 1,
            "buyer_id" => $user_id
        ]);

        // 待收货订单数量
        $goodsNotReceivedOrder = $order->getOrderNumByOrderStatu([
            'order_status' => 2,
            "buyer_id" => $user_id
        ]);

        // 退款订单
        $condition['order_status'] = array(
            'in',
            [
                - 1,
                - 2
            ]
        );
        $condition['buyer_id'] = $user_id;
        $refundOrder = $order->getOrderNumByOrderStatu($condition);


       $data = array(
           'integralconfig'=>$integralconfig,//积分配置
           "isSign"=>$isSign, //用户是否签到,返回0或1
           "unpaidOrder"=>$unpaidOrder, // 待支付订单数量
        "shipmentPendingOrder"=>$shipmentPendingOrder, // 待发货订单数量
        "goodsNotReceivedOrder"=>$goodsNotReceivedOrder,// 待收货订单数量
        "refundOrder"=>$refundOrder,// 退款订单数量
        'member_info'=> $member_info,
        'member_img'=>$member_img
       );
        return json(resultArray(0, "操作成功" , $data));
    }


    /**
     * ok-2ok
     * 会员帐户
     */
    public function memberAccount()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }
        $member = new MemberHandle();
        $data = $member->getAccountByUser($user_id);
        return json(resultArray(0, "操作成功" , $data));
    }

    /**
     * ok-2ok
     * 会员详情
     */
    public function memberDetail()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }
        $member = new MemberHandle();
        $data = $member->getMemberDetail($user_id);
        return json(resultArray(0, "操作成功" , $data));
    }


    /**
     * ok-2ok
     * 积分流水
     */
    public function integralWater()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }

        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", PAGESIZE);
        $condition['mar.user_id'] = $user_id;
        $condition['mar.account_type'] = 1;
        // 查看用户在该商铺下的积分消费流水
        $member = new MemberHandle();
        $order = 'mar.create_time desc';
        $member_point_list = $member->getAccountList($page_index, $page_size, $condition, $order, '*');


        // 查看积分总数
        $menber_info = $member->getMemberDetail($user_id);
        // 查找店铺积分说明
        $pointConfig = new PromotionHandle();
        $pointconfiginfo = $pointConfig->getPointConfig();
        $data = [
            "point" => $menber_info['point'],
            "member_point_list" => $member_point_list,
            "pointconfiginfo" => $pointconfiginfo
        ];
        return json(resultArray(0, "操作成功" , $data));
    }

    /**
     * 会员余额
     */
    public function balance()
    {

    }

    /**
     * ok-2ok
     * 会员余额流水
     */
    public function balanceWater()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", PAGESIZE);
        // 余额流水
        $member = new MemberHandle();
        $condition['mar.user_id'] = $user_id;
        $condition['mar.account_type'] = 2;
        $order = 'mar.create_time desc';
        $list = $member->getAccountList($page_index, $page_size, $condition, $order, '*');
        // 用户在该店铺的账户余额总数
        $member_info = $member->getMemberDetail($user_id);
        $config = new ConfigHandle();
     //  $balanceConfig = $config->getBalanceWithdrawConfig($shopid);
    //    $this->assign("is_use", $balanceConfig['is_use']);


        $data = array(
            'balance'=>$member_info['balance'],
            "balance_list"=> $list
        );
        return json(resultArray(0, "操作成功" , $data));
    }

    /**ok-2ok
     * 会员优惠券
     */
    public function memberCoupon()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }

        $type = request()->post('type', '');
        $member_coupon = new MemberCouponHandle();
        $counpon_list = $member_coupon->getUserCouponList($user_id, $type);

        foreach ($counpon_list as $key => $item) {
            $counpon_list[$key]['start_time'] = date("Y-m-d", $item['start_time']);
            $counpon_list[$key]['end_time'] = date("Y-m-d", $item['end_time']);
        }
        return json(resultArray(0, "操作成功" , $counpon_list));
    }

    /**
     * ok-2ok
     * 会员个人中心主界面
     */
    public function personalData()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }


        $member = new MemberHandle();
        $member_info = $member->getMemberDetail($user_id);
        $member_img='';

        if (! empty($member_info['user_info']['head_img'])) {
            $member_img = $member_info['user_info']['head_img'];
        }
        //elseif (! empty($member_info['user_info']['qq_openid'])) {
      //      $member_img = $member_info['user_info']['qq_info_array']['figureurl_qq_1'];
     //   } elseif (! empty($member_info['user_info']['wx_openid'])) {
      //      $member_img = '0';
     //   } else {
      //      $member_img = '0';
      //  }
     //   $this->assign("shop_id", $shop_id);
      //  $this->assign('qq_openid', $member_info['user_info']['qq_openid']);
        $data = array(
            'member_info'=> $member_info,
            'member_img'=> $member_img
        );
        return json(resultArray(0, "操作成功" , $data));
    }

    /**
     * ok-2ok
     * 修改密码
     */
    public function modifyPassword()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }

        $member = new MemberUserHandle();

        $old_password = request()->post('old_password');
        $new_password = request()->post('new_password');
        $retval = $member->modifyUserPassword($user_id, $old_password, $new_password);
        if (empty($retval)) {
            if (empty($member->getError())) {
                return json(resultArray(2, "密码修改失败"));
            } else {
                return json(resultArray(2, $member->getError()));
            }
        } else {
            return json(resultArray(0, "密码修改成功"));
        }
    }

    /**
     * 修改邮箱
     */
    public function modifyEmail()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }
        $member = new MemberHandle();
        /*
        $uid = $this->user->getSessionUid();
        $email = request()->post('email');
        $retval = $member->modifyEmail($uid, $email);
        return AjaxReturn($retval);
        */
    }

    /*
     * ok-2ok
    * 发送原登录手机验证码
    */
    public function  getOldLoginPhoneVerifyCode() {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }

        $member_handle = new MemberUserHandle();
        $member = $member_handle->getUserInfoById($user_id);

        $login_phone = $member['login_phone'];

        $verify_code = rand(1000, 9999);
        $smsParam = array (
            "code" => $verify_code,
        );
        $response = aliDayunSmsSend( $login_phone, "SMS_79005082",  $smsParam);
        $info = json_decode(json_encode($response),TRUE);
        if ($info['Code'] == 'OK') {
            Session::set('OldVerificationCode',$verify_code );
            Session::set('OldPhone',$login_phone );
            return json(resultArray(0,"手机验证码发送成功"));
        } else {
            return json(resultArray(2,"手机验证码发送失败"));
        }

    }

    /**
     * ok-2ok
     * 检查原手机号的验证码
     * @return \think\response\Json
     */
    public function checkOldVerifyCode() {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }
        $old_verify_code = isset($this->param['old_verify_code']) ? $this->param['old_verify_code'] : '';
        $member_handle = new MemberUserHandle();
        $member = $member_handle->getUserInfoById($user_id);
        $login_phone = $member['login_phone'];

        if (empty($old_verify_code)) {
            return json(resultArray(2,"验证码不能为空"));
        }

        if ($old_verify_code != Session::get('OldVerificationCode') ||   $login_phone != Session::get('OldPhone')) {
            return json(resultArray(2,"验证码不正确"));
        } else {
            return json(resultArray(0,"验证码正确"));
        }

    }

    /*
     * ok-2ok
     *  发送新手机注册验证码
     * */
    public function  getNewLoginPhoneVerifyCode() {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }

        $phone = isset($this->param['login_phone']) ? $this->param['login_phone'] : '';

        if (empty($phone)) {
            return json(resultArray(2,"手机号不能为空"));
        }

        $memberUserHandle = new MemberUserHandle();
        $count = $memberUserHandle->checkLoginPhoneIsHas($phone);
        if ($count > 0) {
            return json(resultArray(2,"手机号已被使用"));
        }


        $verify_code = rand(1000, 9999);
        $smsParam = array (
            "code" => $verify_code,
        );
        $response = aliDayunSmsSend( $phone, "SMS_79005082",  $smsParam);
        $info = json_decode(json_encode($response),TRUE);
        if ($info['Code'] == 'OK') {
            Session::set('NewVerificationCode',$verify_code );
            Session::set('NewPhone',$phone );
            return json(resultArray(0,"手机验证码发送成功"));
        } else {
            return json(resultArray(2,"手机验证码发送失败"));
        }

    }

    /**
     * ok-2ok
     * 修改登录手机号
     */
    public function modifyLoginPhone()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }
        //登录手机号
        $login_phone = isset($this->param['login_phone']) ? $this->param['login_phone'] : '';
        //验证码
        $verify_code = isset($this->param['verify_code']) ? $this->param['verify_code'] : '';


        if (empty($login_phone)) {
            return json(resultArray(2,"登录手机号不能为空"));
        }
      //  $count = $memberUserHandle->checkLoginPhoneIsHas($login_phone);
      //  if ($count > 0) {
      //      return json(resultArray(2,"此手机号已被使用"));
      //  }


        if (empty($verify_code)) {
            return json(resultArray(2,"验证码不能为空"));
        }

        if ($verify_code != Session::get('NewVerificationCode') ||   $login_phone != Session::get('NewPhone')) {
            return json(resultArray(2,"验证码不正确"));
        }



        $member = new MemberUserHandle();
        $retval = $member->updateLoginPhone($user_id, $login_phone);

        if (empty($retval)) {
            if (empty($member->getError())) {
                return json(resultArray(2, "操作失败"));
            } else {
                return json(resultArray(2, $member->getError()));
            }
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 修改昵称
     */
    public function modifyNickName()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }

        $nickname = request()->post('nickname');
        $member = new MemberHandle();
        $retval = $member->modifyNickName($user_id, $nickname);
        if (empty($retval)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }




    /**
     * 账户列表
     */
    public function accountList()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }
        $flag = request()->get('flag', '0'); // 标识，1：从会员中心的提现账号进来，0：从申请提现进来
        if ($flag != 0) {
            $_SESSION['account_flag'] = $flag;
        } else {
            if (! empty($_SESSION['account_flag'])) {
                $flag = $_SESSION['account_flag'];
            }
        }
        $account_list = 1;
        $this->assign('flag', $flag);
        $member = new MemberService();
        $account_list = $member->getMemberBankAccount();
        $this->assign('account_list', $account_list);
        return view($this->style . "/Member/accountList");
    }

    /**
     * 添加账户
     * 任鹏强
     * 2017年3月13日10:53:06
     */
    public function addAccount()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }
        if (request()->isAjax()) {
            $member = new MemberService();
            $uid = $this->uid;
            $realname = request()->post('realname', '');
            $mobile = request()->post('mobile', '');
            $bank_type = request()->post('bank_type', '1');
            $account_number = request()->post('account_number', '');
            $branch_bank_name = request()->post('branch_bank_name', '');
            $retval = $member->addMemberBankAccount($uid, $bank_type, $branch_bank_name, $realname, $account_number, $mobile);
            return AjaxReturn($retval);
        } else {
            return view($this->style . "/Member/addAccount");
        }
    }

    /**
     * 修改账户信息
     */
    public function updateAccount()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }
        $member = new MemberService();
        if (request()->isAjax()) {
            $uid = $this->uid;
            $account_id = request()->post('id', '');
            $realname = request()->post('realname', '');
            $mobile = request()->post('mobile', '');
            $bank_type = request()->post('bank_type', '1');
            $account_number = request()->post('account_number', '');
            $branch_bank_name = request()->post('branch_bank_name', '');
            $retval = $member->updateMemberBankAccount($account_id, $branch_bank_name, $realname, $account_number, $mobile);
            return AjaxReturn($retval);
        } else {
            $id = request()->get('id', '');
            if (! is_numeric($id)) {
                $this->error('未获取到信息');
            }
            $result = $member->getMemberBankAccountDetail($id);
            if (empty($result)) {
                $this->error("没有获取到该账户信息");
            }
            $this->assign('result', $result);
            return view($this->style . "/Member/updateAccount");
        }
    }

    /**
     * 删除账户信息
     */
    public function delAccount()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }
        if (request()->isAjax()) {
            $member = new MemberService();
            $uid = $this->uid;
            $account_id = request()->post('id', '');
            $retval = $member->delMemberBankAccount($account_id);
            return AjaxReturn($retval);
        }
    }

    /**
     * 设置选中账户
     */
    public function checkAccount()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }
        if (request()->isAjax()) {
            $member = new MemberService();
            $uid = $this->uid;
            $account_id = request()->post('id', '');
            $retval = $member->setMemberBankAccountDefault($uid, $account_id);
            return AjaxReturn($retval);
        }
    }

    // 用户签到
    public function signIn()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }

        $rewardRule = new PromoteRewardRuleHandle();
        $res = $rewardRule->memberSign($user_id);
        if (empty($res)) {
            if (empty($rewardRule->getError())) {
                return json(resultArray(2, "操作失败"));
            } else {
                return json(resultArray(2,$rewardRule->getError() ));
            }
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    // 分享送积分-ok-2ok
    public function shareGivePoint()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }

        $rewardRule = new PromoteRewardRuleHandle();
        $res = $rewardRule->memberShareSendPoint($user_id);

        if (empty($res)) {
            if (empty($rewardRule->getError())) {
                return json(resultArray(2, "操作失败"));
            } else {
                return json(resultArray(2,$rewardRule->getError() ));
            }
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * 更改用户头像-ok-2ok
     */
    public function modifyFace()
    {
        //身份验证
        $authRet = $this->checkAuth();
        $user_id = $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }
        $member = new MemberHandle();
        $user_headimg = request()->post('member_headimg');
        $res = $member->modifyMemberHeadImg($user_id, $user_headimg);
        if (empty($res)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * 得到会员头像-ok-2ok
     * @return \think\response\Json
     */
    public function getMemberHeadImg() {
        //身份验证
        $authRet = $this->checkAuth();
        $user_id = $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }
        $member = new MemberHandle();
        $member_info = $member->getMemberDetail($user_id);
        $member_img = $member_info['user_info']['head_img'];
        $data = array(
            'member_headimg'=> $member_img
        );
        return json(resultArray(0, "操作成功",$data));
    }

    /**
     * 更改会员所属代理商-ok-2ok
     */
    public function modifyAgent()
    {
        //身份验证
        $authRet = $this->checkAuth();
        $user_id = $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }
        $member = new MemberHandle();
        $agent_id = request()->post('agent_id');
        $res = $member->modifyAgent($user_id, $agent_id);
        if (empty($res)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**得到会员所属代理商-ok-2ok
     * @return \think\response\Json
     */
    public function getAgentByMember() {
        //身份验证
        $authRet = $this->checkAuth();
        $user_id = $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }
        $member = new MemberHandle();
        $agent = $member->getAgent($user_id);
        return json(resultArray(0, "操作成功", $agent));
    }

    /**
     * ok-2ok
     * 领用优惠券
     */
    public function getCoupon()
    {
        //身份验证
        $authRet = $this->checkAuth();
        $user_id = $this->user_id;
        if ($user_id == 0) {
            return $authRet;
        }
        $coupon_type_id = request()->post('coupon_type_id');
        $get_type = request()->post('get_type', 3);
        $member = new MemberHandle();
        $retval = $member->memberGetCoupon($user_id, $coupon_type_id, $get_type);

        if (empty($retval)) {
            if (empty($member->getError())) {
                return json(resultArray(2, "领取失败"));
            } else {
                return json(resultArray(2, $member->getError()));
            }
        } else {
            return json(resultArray(0, "领取成功"));
        }
    }


    /**
     * ok-2ok
     * 得到会员的代理商体验店详情
     * @return \think\response\Json
     */
    public function memberAgentShop() {
        //身份验证
        $authRet = $this->checkAuth();
        $user_id = $this->user_id;
        $agent_handle = new AgentHandle();

        $shop = '';
        if ($user_id != 0) {
            $member = new MemberHandle();
            $agent = $member->getAgent($user_id);
            if (!empty($agent)) {
                $agent_id =  $agent['id'];
                $condition = array (
                    'agent_id'=>$agent_id,
                    'status'=>1
                );
                $shop = $agent_handle->getAgentShopDetails($condition);
                if (empty($shop)) {
                    $agent_id = $agent['p_agent_id'];
                    $condition = array (
                        'agent_id'=>$agent_id,
                        'status'=>1
                    );
                    $shop = $agent_handle->getAgentShopDetails($condition);
                }
            }
        }

        if (!empty($shop)) {
            return json(resultArray(0, "操作成功", $shop));
        } else {
            $agent_id = $agent_handle->getPlatformAgentId();
            $agent = new AgentHandle();
            $condition = array(
                'agent_id' => $agent_id,
                'status' => 1
            );
            $shop = $agent->getAgentShopDetails($condition);
            return json(resultArray(0, "操作成功", $shop));
        }
    }

    /**
     * ok-2ok
     * 根据id得到代理商体验店详情
     * @return \think\response\Json
     */
    public function getAgentShopById() {
        $agent_shop_id = $this->param['shopid'];
        $agent = new AgentHandle();
        $condition = array (
            'id'=>$agent_shop_id
        );
        $shop = $agent->getAgentShopDetails($condition);
        return json(resultArray(0, "操作成功", $shop));
    }

    /**
     * ok-2ok
    * 重置密码时给登录手机发送验证码
    */
    public function  loginPhonVerifyCode() {

        $login_phone = request()->post('login_phone');
        if (empty($login_phone)) {
            return json(resultArray(2,"登录手机号不能为空"));
        }
        $member_handle = new MemberUserHandle();
        $member = $member_handle->getUserInfoByLoginPhone($login_phone);
        if (empty($member)) {
            return json(resultArray(2,"登录手机号未注册"));
        }
       // getUserInfoById($user_id);

        $login_phone = $member['login_phone'];

        $verify_code = rand(1000, 9999);
        $smsParam = array (
            "code" => $verify_code,
        );
        $response = aliDayunSmsSend( $login_phone, "SMS_79005082",  $smsParam);
        $info = json_decode(json_encode($response),TRUE);
        if ($info['Code'] == 'OK') {
            Session::set('ResetVerificationCode',$verify_code );
            Session::set('ResetPhone',$login_phone );
            return json(resultArray(0,"手机验证码发送成功"));
        } else {
            return json(resultArray(2,"手机验证码发送失败"));
        }
    }

    /**
     * ok-2ok
     * 忘记密码后重置
     */
    public function resetPassword()
    {

        $login_phone = request()->post('login_phone');
        $verify_code = request()->post('verify_code');
        $password = request()->post('password');

        if (empty($login_phone)) {
            return json(resultArray(2,"登录手机号不能为空"));
        }
        if (empty($verify_code)) {
            return json(resultArray(2,"验证码不能为空"));
        }
        if (empty($password)) {
            return json(resultArray(2,"密码不能为空"));
        }

        $member_handle = new MemberUserHandle();
        $member = $member_handle->getUserInfoByLoginPhone($login_phone);
        if (empty($member)) {
            return json(resultArray(2,"登录手机号未注册"));
        }
        $login_phone1 = $member['login_phone'];

        if (Session::get('ResetPhone') != $login_phone1 ||
            Session::get('ResetVerificationCode') != $verify_code) {
            return json(resultArray(2,"验证码不正确"));
        }
        $retval = $member_handle->updatePasswordByLoginPhone($login_phone1,$password);

        if (empty($retval)) {
            return json(resultArray(2, "密码重置失败 ".$member->getError()));
        } else {
            return json(resultArray(0, "密码重置成功"));
        }
    }
}