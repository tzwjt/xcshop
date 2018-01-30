<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-01
 * Time: 14:54
 */

namespace dao\handle;

use dao\handle\BaseHandle;
use dao\handle\member\MemberCouponHandle;
use dao\handle\member\MemberUserHandle;
use dao\model\MemberInfo;
use dao\model\MemberUser as MemberUserModel;
use dao\model\MemberAccount as MemberAccountModel;
use dao\model\MemberAccountRecords as MemberAccountRecordsModel;
use dao\model\MemberInfo as MemberInfoModel;
use dao\model\UserLog as UserLogModel;
use dao\model\MemberExpressAddress as MemberExpressAddressModel;
use dao\handle\AddressHandle;
use dao\model\MemberView as MemberViewModel;
use dao\handle\member\MemberAccountHandle;
use dao\model\MemberLevel as MemberLevelModel;
use dao\handle\AgentHandle;
use dao\model\MemberAccountRecordsView as MemberAccountRecordsViewModel;
use dao\model\Coupon as CouponModel;
use dao\model\CouponType as CouponTypeModel;


class MemberHandle extends  BaseHandle
{
    /****************会员管理**********************************************/

    /**
     * 会员列表
     *
     * @param number $page_index
     * @param number $page_size
     * @param string $condition
     * @param string $order
     * @param string $field
     */
    public function getMemberList($page_index = 1, $page_size = 0, $condition = '', $order = '', $field = '*')
    {
        $member_view = new MemberViewModel();
        $result = $member_view->getMemberViewList($page_index, $page_size, $condition, $order, $field);
        foreach ($result['data'] as $k => $v) {
            $member_account = new MemberAccountHandle();
            $result['data'][$k]['point'] = $member_account->getMemberPoint($v['id']); //积分
            $result['data'][$k]['balance'] = $member_account->getMemberBalance($v['id']); //余额
            $result['data'][$k]['coin'] = $member_account->getMemberCoin($v['id']);
            $result['data'][$k]['cunsum'] = $member_account->getMemberCunSum($v['id']); //消费总额
            $result['data'][$k]['order_sum'] = $member_account->getMemberOrderSum($v['id']); //有效订单数
           // getMemberCunSum($v['id']); //消费总额
           // getMemberCoin($v['id']);
            if ($v['status'] == 0) {
                $result['data'][$k]['status_name'] ='无效';
            } else if($v['status'] == 1) {
                $result['data'][$k]['status_name'] ='正常';
            }
        }
        return $result;
    }

    /**
     * ok-2ok
     * 得到会员详情
     */
    public function getMemberDetail($user_id)
    {
        // 获取基础信息
        if (! empty($user_id)) {
            $member_info = $this->getMemberUserInfo($user_id);
            if (empty($member_info)) {
                $member_info = array(
                    'level_id' => 0
                );
            }

            // 获取user信息

            $user_info = $this->getMemberInfo($user_id);
            $member_info['user_info'] = $user_info;

            // 获取优惠券信息
            $member_coupon = new MemberCouponHandle();
           // getUserCouponList($user_id, $type = '')
            $coupon_list = $member_coupon->getUserCouponList($user_id, '');
            $member_info['coupon_list'] = $coupon_list;
          //  $member_info['coupon_count'] = count($coupon_list);
            $coupon_list1 = $member_coupon->getUserCouponList($user_id, 1);
            $member_info['coupon_count'] = count($coupon_list1);


            $member_account = new MemberAccountHandle();


            $member_info['point'] = $member_account->getMemberPoint($user_id);
            $member_info['balance'] = $member_account->getMemberBalance($user_id);
            $member_info['coin'] = $member_account->getMemberCoin($user_id);
            $member_info['cun_sum'] = $member_account->getMemberCunSum($user_id);
            $member_info['order_sum'] = $member_account->getMemberOrderSum($user_id);
            // 会员等级名称
            $member_level = new MemberLevelModel();
            $level_name = $member_level->getInfo([
                'id' => $member_info['member_level']
            ], 'level_name');
            $member_info['level_name'] = $level_name['level_name'];
            $agent_handle = new AgentHandle();
            $member_info['agent_name'] = $agent_handle->getAgentNameById($member_info['agent_id']);
        } else {
            $member_info = '';
        }
        unset($member_info['password']);
        return $member_info;
    }
    /***************会员信息************************************************/

    /**
     * ok-2ok
     *根据会员用户id，得到会员的详情
     */
    public function getMemberUserInfo($user_id){
        $member_user = new MemberUserModel();
        $user_info = $member_user->getInfo(array("id"=>$user_id));
        $user_info['last_login_time'] =  getTimeStampTurnTime($user_info['last_login_time']);

        $agent = new AgentHandle();
        $agent_name = $agent->getAgentNameById($user_info['agent_id']);
        $user_info['agent_name'] = $agent_name;
        unset($user_info['password']);
        return $user_info;
    }

    public function getMemberInfo($user_id){
        $member_info_model = new MemberInfoModel();
        $member_info = $member_info_model->getInfo(array("user_id"=>$user_id));
        $address = new AddressHandle();
        $province = $address->getProvinceName($member_info['province_id']);
        $city = $address->getCityName($member_info['city_id']);
        $district = $address->getDistrictName($member_info['district_id']);
        $member_info['province_name'] = $province;
        $member_info['city_name'] = $city;
        $member_info['district_name'] = $district;

        return $member_info;
    }

    /**
     * ok-2ok
     * 修改会员呢称
     */
    public function modifyNickName($user_id, $nickname)
    {
        $member_info = new MemberInfoModel();
        $retval = $member_info->save([
            'nickname' => $nickname
        ], [
            'user_id' => $user_id
        ]);

        if ($retval === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 得到会员等级列表
     */
    public function getMemberLevelList($page_index = 1, $page_size = 0, $condition = '', $order = '', $field = '*')
    {
        $member_level = new MemberLevelModel();
        return $member_level->pageQuery($page_index, $page_size, $condition, $order, $field);
    }


    /**
     * okkk
     * 获取 会员等级详情
     */
    public function getMemberLevelDetail($level_id)
    {
        $member_level = new MemberLevelModel();
        return $member_level->get($level_id);
    }

    /**
     * 添加 会员等级
     *
     */
    public function addMemberLevel( $level_name, $min_integral, $quota, $upgrade, $goods_discount, $desc, $relation)
    {
        $member_level = new MemberLevelModel();
        $data = array(
            'level_name' => $level_name,
            'min_integral' => $min_integral,
            'quota' => $quota,
            'upgrade' => $upgrade,
            'goods_discount' => $goods_discount,
            'desc' => $desc,
            'relation' => $relation
        );
        $res = $member_level->save($data);
         $data['level_id'] = $member_level->id;
      //  hook("memberLevelSaveSuccess", $data);
       // return $member_level->level_id;
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 修改会员等级
     *
     */
    public function updateMemberLevel($level_id,  $level_name, $min_integral, $quota, $upgrade, $goods_discount, $desc, $relation)
    {
        $member_level = new MemberLevelModel();
        $data = array(
            'level_name' => $level_name,
            'min_integral' => $min_integral,
            'quota' => $quota,
            'upgrade' => $upgrade,
            'goods_discount' => $goods_discount,
            'desc' => $desc,
            'relation' => $relation
        );
        /* return $member_level->save($data, ['level_id' => $level_id]); */
        $res = $member_level->save($data, [
            'id' => $level_id
        ]);
        $data['level_id'] = $level_id;
      //  hook("memberLevelSaveSuccess", $data);
        if ($res === false) {
            return false;
        } else {
            return true;
        }
        /**
        if ($res == 0) {
            return 1;
        }
        return $res;
         * **/
    }

    /**
     * 删除会员等级
     *
     */
    public function deleteMemberLevel($level_id)
    {
        $member_level = new MemberLevelModel();
        $member_count = $this->getMemberLevelUserCount($level_id);
        if ($member_count > 0) {
            $this->error = "此会员等级已被使用, 不可删除!";
            return false;
            //return MEMBER_LEVEL_DELETE;
        } else {
            $res =  $member_level->destroy($level_id);
            if (empty($res)) {
                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * 查询会员的等级下是否有会员
     */
    private function getMemberLevelUserCount($level_id)
    {
        $member_count = 0;
        $member_model = new MemberUserModel();
        $member_count = $member_model->getCount([
            'member_level' => $level_id
        ]);
        return $member_count;
    }

    /**
     * 修改 会员等级 单个字段
     *
     */
    public function modifyMemberLevelField($level_id, $field_name, $field_value)
    {
        $member_level = new MemberLevelModel();
        $res =  $member_level->save([
            "$field_name" => $field_value
        ], [
            'level_id' => $level_id
        ]);

        if ($res === false) {
            return false;
        } else {
            return true;
        }
    }



    /**
     * 获取积分列表
     */
    public function getPointList($page_index, $page_size, $condition, $order = '', $field = '*')
    {
        $member_account = new MemberAccountRecordsViewModel();
        $list = $member_account->getViewList($page_index, $page_size, $condition, 'mar.create_time desc');
        if (! empty($list['data'])) {
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['type_name'] = MemberAccountHandle::getMemberAccountRecordsName($v['from_type']);
            }
        }
        return $list;
    }

    /**
     * okk
     * 获取会员帐户记录列表
     */
    public function getAccountList($page_index, $page_size, $condition, $order = '', $field = '*')
    {
        $member_account = new MemberAccountRecordsViewModel();


        $list = $member_account->getViewList($page_index, $page_size, $condition, 'mar.create_time desc');
        if (! empty($list['data'])) {
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['type_name'] = MemberAccountHandle::getMemberAccountRecordsName($v['from_type']);
            }
        }
        return $list;
    }

    /**
     * okkk
     * 会员置无效
     */
    public function userInvalid($user_id)
    {
        $user = new MemberUserModel();
        $retval = $user->save([
            'status' => 0
        ], [
            'id' => $user_id
        ]);
        if ($retval === false) {
            return false;
        }
        return true;
    }

    /**
     * 会员有效
     */
    public function userValid($user_id)
    {
        $user = new MemberUserModel();
        $retval = $user->save([
            'status' => 1
        ], [
            'id' => $user_id
        ]);
        if ($retval === false) {
            return false;
        }
        return true;
    }

    /**
     * 充值会员账户（针对会员账户充值）1.
     * 积分2. 余额 3. 购物币
     */
    public function addMemberAccount( $type, $user_id, $num, $text)
    {
        $member_account = new MemberAccountHandle();

        $operation_id = 0;
        if ($type == 1) {
            $operation_id = 28;
        } else if ($type == 2) {
            $operation_id = 29;
        } else if ($type == 3) {
            $operation_id = 30;
        }
       // addMemberAccountData($account_type, $user_id, $sign, $number, $from_type, $data_id,$text,$operation_id=0)
        $retval = $member_account->addMemberAccountData($type, $user_id, 1, $num, 10, 0, $text, $operation_id);

        $this->error = $member_account->getError();


       // addMemberAccountData($shop_id, $type, $uid, 1, $num, 10, 0, $text);
        return $retval;
    }

    /**
     * ok-2ok
     * 会员用户帐户信息
     */
    public function getMemberAccount($user_id)
    {
        $member_account = new MemberAccountModel();
        $account_info = $member_account->getInfo([
            'user_id' => $user_id,
        ], 'point');
        if (empty($account_info)) {
            $account_info["point"] = 0;
        }
        $account = new MemberAccountHandle();
        $account_info['balance'] = $account->getMemberBalance($user_id);
        $account_info['coin'] = $account->getMemberCoin($user_id);
        return $account_info;
    }


    /*************************  会员的收货地址  ***********************************************/
    /**
     * ok-2ok
     * 得到会员用户的默认发货地址
     */
    public function getDefaultExpressAddress($user_id)
    {
        $express_address_model = new MemberExpressAddressModel();
        $data = $express_address_model->getInfo([
            'user_id' => $user_id,
            'is_default' => 1
        ], '*');
        // 处理地址信息
        if (! empty($data)) {
            $address_handle = new AddressHandle();
            $address_info = $address_handle->getAddress($data['province'], $data['city'], $data['district']);
            $data['address_info'] = $address_info;
        }

        return $data;
    }



    /**
     * 增加会员的发货地址
     */
    public function addMemberExpressAddress($user_id, $consigner, $mobile, $phone, $province, $city, $district, $address, $zip_code, $alias)
    {
        try {
            $this->startTrans();
          //  $express_address_model = new MemberExpressAddressModel();
            /*
            $res = $express_address_model->save([
                 'is_default' => 0
            ], [
                'user_id' => $user_id
             ]);
            if (empty($res)) {
                $this->rollback();
                $this->error ="操作失败!";
                return false;
            }
            */
            $express_address_model = new MemberExpressAddressModel();
            $data = array(
                'user_id' => $user_id,
                 'consigner' => $consigner,
                 'mobile' => $mobile,
                'phone' => $phone,
                 'province' => $province,
                 'city' => $city,
                'district' => $district,
                'address' => $address,
                'zip_code' => $zip_code,
                'alias' => $alias,
                'is_default' => 1
             );
            $res = $express_address_model->save($data);
            if (empty($res)) {
                $this->rollback();
                $this->error =" 操作失败!";
                return false;
            }
            $this->updateAddressDefault($user_id,$express_address_model->id);
            if (empty($res)) {
                $this->rollback();
                $this->error =" 操作失败!";
                return false;
            }
            $this->commit();
            return $express_address_model->id;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error ="操作时出现异常:".$e->getMessage();
            return false;
        }

    }

    /**
     * 修改会员收货地址
     */
    public function updateMemberExpressAddress($user_id, $id, $consigner, $mobile, $phone, $province, $city, $district, $address, $zip_code, $alias)
    {
        $express_address_model = new MemberExpressAddressModel();
        $data = array(
           // 'user_id' => $this->uid,
            'consigner' => $consigner,
            'mobile' => $mobile,
            'phone' => $phone,
            'province' => $province,
            'city' => $city,
            'district' => $district,
            'address' => $address,
            'zip_code' => $zip_code,
            'alias' => $alias
        );
        $retval = $express_address_model->save($data, [
            'id' => $id,
            'user_id'=>$user_id
        ]);

        $retval = $this->updateAddressDefault($user_id,$id);

        return $retval;
    }

    /**
     * 得到会员用户的地址列表
     */
    public function getMemberExpressAddressList($user_id, $page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        $express_address_model = new MemberExpressAddressModel();
        $data = $express_address_model->pageQuery($page_index, $page_size, [
            'user_id' => $user_id
        ], ' id desc', '*');
        // 处理地址信息
        if (! empty($data)) {
            foreach ($data['data'] as $key => $val) {
                $address_handle = new AddressHandle();
                $address_info = $address_handle->getAddress($val['province'], $val['city'], $val['district']);
                $data['data'][$key]['address_info'] = $address_info;
            }
        }
        return $data;
    }

    /**
     * 得到地址细节
     */
    public function getMemberExpressAddressDetail($user_id, $id)
    {
        $express_address_model = new MemberExpressAddressModel();
        $data = $express_address_model->get($id);
        if ($data['user_id'] == $user_id) {
            return $data;
        } else {
            return '';
        }
    }

    /**
     * 删除指定的会员收货地址
     */
    public function memberAddressDelete($user_id,$id)
    {
        try {
            $this->startTrans();
            $express_address_model = new MemberExpressAddressModel();
            $count = $express_address_model->where(array(
                "user_id" => $user_id
            ))->count();
            if ($count == 1) {
                $this->rollback();
                $this->error = "这是您的唯一收货地址,不可删除!";
                return   false;  //USER_ADDRESS_DELETE_ERROR;
             } else if ($count > 1) {
                $express_address_info = $express_address_model->getInfo([
                    'id' => $id,
                    'user_id' => $user_id
                ]);

                if (!empty($express_address_info)) {
                    $res = $express_address_model->destroy($id);

                    if (empty($res)) {
                        $this->rollback();
                        $this->error = " 操作失败!";
                        return false;
                    }

                    if ($express_address_info['is_default'] == 1) {
                        $express_address_info = $express_address_model->where(array(
                            "user_id" => $user_id
                        ))->order("id desc")
                            ->limit(0, 1)
                            ->select();
                        $res = $this->updateAddressDefault($user_id, $express_address_info[0]['id']);
                        if (empty($res)) {
                            $this->rollback();
                            $this->error = " 操作失败!";
                            return false;
                        }
                    }
                }
            }
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error ="操作时出现异常:".$e->getMessage();
            return false;
        }
    }

    /**
     * 指定默认会货地址
     */
    public function updateAddressDefault($user_id, $id)
    {
        try {
            $this->startTrans();
            $express_address_model = new MemberExpressAddressModel();
            $res = $express_address_model->save([
                 'is_default' => 0
             ], [
                'user_id' => $user_id
            ]);

            if (empty($res)) {
                $this->rollback();
                $this->error = " 操作失败!";
                return false;
            }

            $res = $express_address_model->save([
                'is_default' => 1
             ], [
                'user_id' => $user_id,
                'id' => $id
             ]);

            if (empty($res)) {
                $this->rollback();
                $this->error = " 操作失败!";
                return false;
            }

            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error ="操作时出现异常:".$e->getMessage();
            return false;
        }


      //  return $res;
    }


    /**
     * 判断会员是否已经签到-ok
     * 返回 1 or 0
     */
    public function getIsMemberSign($user_id)
    {
        $member_account_records = new MemberAccountRecordsModel();
        $day_begin_time = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $day_end_time = mktime(23, 59, 59, date('m'), date('d'), date('Y'));
        $condition = array(
            'user_id' => $user_id,
         //   'shop_id' => $shop_id,
            'account_type' => 1,
            'from_type' => 5,
            'create_time' => array(
                'between',
                [
                    $day_begin_time,
                    $day_end_time
                ]
            )
        );
        $count = $member_account_records->getCount($condition);
        return $count;
    }

    /**
     * 判断 会员今天 是否已经分享过-ok-2ok
     * 返回 1 or 0
     */
    public function getIsMemberShare($user_id)
    {
        $member_account_records = new MemberAccountRecordsModel();
        $day_begin_time = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $day_end_time = mktime(23, 59, 59, date('m'), date('d'), date('Y'));
        $condition = array(
            'user_id' => $user_id,
           // 'shop_id' => $shop_id,
            'account_type' => 1,
            'from_type' => 6,
            'create_time' => array(
                'between',
                [
                    $day_begin_time,
                    $day_end_time
                ]
            )
        );
        $count = $member_account_records->getCount($condition);
        return $count;
    }

    /**
     * ok-2ok
     * 得到会员用户的帐户
     **/
    function getAccountByUser($user_id)
    {
        $userMessage = new MemberAccountModel();
        $condition = array(
            'user_id' => $user_id
        );
        $result = $userMessage->getInfo($condition,  '*');
        return $result;
    }

    /**
     * 修改会员头像-ok-2ok
     */
    public function modifyMemberHeadImg($user_id, $user_headimg)
    {
        $member_info = new MemberInfoModel();
        $data = array(
            'head_img' => $user_headimg
        );
        $res = $member_info->save($data, [
            'user_id' => $user_id
        ]);

        if ($res === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 修改会员所属代理商
     */
    public function modifyAgent($user_id, $agent_id)
    {
        $member = new MemberUserModel();
        $data = array(
            'agent_id' => $agent_id
        );
        $res = $member->save($data, [
            'id' => $user_id
        ]);

        if ($res === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 得到会员所属代理商
     * @param $user_id
     * @return array|null|static
     */
    public function getAgent($user_id) {
        $member_model = new MemberUserModel();
        $member_info = $member_model->get($user_id);
        $agent_id = $member_info['agent_id'];
        $agent_handle = new AgentHandle();
        $agent = $agent_handle->getAgentById($agent_id);
        return $agent;
    }

    /**
     * ok-2ok
     * 得到会员数
     * @param $condition
     * @return \dao\model\unknown
     */
    public function getMemberCount($condition) {
        $member_user = new MemberUserModel();
        $count = $member_user->getCount($condition);
        return $count;
    }


    /*************************会员获取优惠券********************************/
    /**
     * ok-2ok
     * 会员获取优惠券
     *
     */
    public function memberGetCoupon($user_id, $coupon_type_id, $get_type)
    {
        if ($get_type == 2 || $get_type == 3) {
            $coupon = new CouponModel();
            $count = $coupon->getCount([
                'user_id' => $user_id,
                'coupon_type_id' => $coupon_type_id
            ]);
            $coupon_type = new CouponTypeModel();
            $coupon_type_info = $coupon_type->getInfo([
                'id' => $coupon_type_id
            ], 'max_fetch');
            if ($coupon_type_info['max_fetch'] != 0) {
                if ($count >= $coupon_type_info['max_fetch']) {
                    $this->error = '您已领取了此优惠券';
                    return false;
                   // return USER_HEAD_GET;
                    exit();
                }
            }
        }
        $member_coupon = new MemberCouponHandle();
        $retval = $member_coupon->userAchieveCoupon($user_id, $coupon_type_id, $get_type);
        return $retval;
    }

    /**
     * ok-2ok
     * 获取会员下面的优惠券列表
     *
     * @param unknown $uid
     */
    public function getMemberCouponTypeList($user_id)
    {
        // 查询可以发放的优惠券类型
        $coupon_type_model = new CouponTypeModel();
        $condition = array(
            'start_time' => array(
                'ELT',
                time()
            ),
            'end_time' => array(
                'EGT',
                time()
            ),
            'is_show' => 1,
        );
        $coupon_type_list = $coupon_type_model->getConditionQuery($condition, '*', '');
        if (! empty($user_id)) {
            $list = array();
            if (! empty($coupon_type_list)) {
                foreach ($coupon_type_list as $k => $v) {
                    if ($v['max_fetch'] == 0) {
                        // 不限领
                        $list[] = $v;
                    } else {
                        $coupon = new CouponModel();
                        $count = $coupon->getCount([
                            'user_id' => $user_id,
                            'coupon_type_id' => $v['id']
                        ]);
                        if ($count < $v['max_fetch']) {
                            $list[] = $v;
                        }
                    }
                }
            }
            return $list;
        } else {
            return $coupon_type_list;
        }
    }








}