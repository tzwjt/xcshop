<?php
/**
 * RewardRuleHandle.php
 * 处理奖励规则
 * @date : 2017.1.17
 * @version : v1.0.0.0
 */
namespace dao\handle\promotion;

use dao\handle\Basehandle;
use dao\model\RewardRule as RewardRuleModel;
use dao\handle\member\MemberAccountHandle;
//use dao\handle\NfxUser;
//use dao\handle\NfxPromoter;
//use dao\handle\NfxPartner;
use dao\handle\MemberHandle;
use dao\handle\ConfigHandle;
use think\Controller;

class PromoteRewardRuleHandle extends Basehandle{
    
    /**
     * 获取  奖励规则详情-okk
     */
    public function getRewardRuleDetail(){
        $reward_rule = new RewardRuleModel();
     //   $detail = $reward_rule->get($shop_id);
        $detail = $reward_rule->getInfo();
        if(empty($detail)){
            $data = array(
              //  'shop_id'                   => $shop_id,
                'sign_point'                => 0,
                'share_point'               => 0,
                'reg_member_self_point'     => 0,
                'reg_member_one_point'      => 0,
                'reg_member_two_point'      => 0,
                'reg_member_three_point'    => 0,
                'reg_promoter_self_point'   => 0,
                'reg_promoter_one_point'    => 0,
                'reg_promoter_two_point'    => 0,
                'reg_promoter_three_point'  => 0,
                'reg_partner_self_point'    => 0,
                'reg_partner_one_point'     => 0,
                'reg_partner_two_point'     => 0,
                'reg_partner_three_point'   => 0,
                'into_store_coupon'         => 0,
                'share_coupon'              => 0,
                'click_point'               => 0,
                'comment_point'             => 0
            );
            $reward_rule->save($data);
            $detail = $reward_rule->get($reward_rule->id);
        }
        return $detail;
    }
    
    /**
     * 设置  积分奖励规则-okk
     */
    public function setPointRewardRule($rule_id, $sign_point, $share_point, $reg_member_self_point, $reg_member_one_point, $reg_member_two_point, $reg_member_three_point, $reg_promoter_self_point, $reg_promoter_one_point, $reg_promoter_two_point, $reg_promoter_three_point, $reg_partner_self_point, $reg_partner_one_point, $reg_partner_two_point, $reg_partner_three_point,$click_point,$comment_point){
        $reward_rule = new RewardRuleModel();
        $data = array(
            'sign_point'                => $sign_point,
            'share_point'               => $share_point,
            'reg_member_self_point'     => $reg_member_self_point,
            'reg_member_one_point'      => $reg_member_one_point,
            'reg_member_two_point'      => $reg_member_two_point,
            'reg_member_three_point'    => $reg_member_three_point,
            'reg_promoter_self_point'   => $reg_promoter_self_point,
            'reg_promoter_one_point'    => $reg_promoter_one_point,
            'reg_promoter_two_point'    => $reg_promoter_two_point,
            'reg_promoter_three_point'  => $reg_promoter_three_point,
            'reg_partner_self_point'    => $reg_partner_self_point,
            'reg_partner_one_point'     => $reg_partner_one_point,
            'reg_partner_two_point'     => $reg_partner_two_point,
            'reg_partner_three_point'   => $reg_partner_three_point,
            'click_point'               => $click_point ,
            'comment_point'             => $comment_point
        );
        $res = $reward_rule->save($data, ['id' => $rule_id]);
        return $res;
    }
    
    /**
     * 设置  某店铺   优惠券 奖励规则-ok
     */
    public function setCouponRewardRule($shop_id=0, $into_store_coupon, $share_coupon){
        $reward_rule = new RewardRuleModel();
        $data = array(
            'into_store_coupon'         => $into_store_coupon,
            'share_coupon'              => $share_coupon,
        );
        $res = $reward_rule->save($data, ['shop_id' => $shop_id]);
        return $res;
    }
    
    /**
     * 添加积分公用函数-ok
     */
    public function addMemberPointData($shop_id=0, $uid, $number, $from_type, $text,$operation_id=0){
        if($number > 0){
            $member_account = new MemberAccountHandle();
            $res = $member_account->addMemberAccountData( 1, $uid, 1, $number, $from_type, 0, $text,$operation_id);
            return $res;
        }else {
            return 1;
        }
    }
    
    /**
     * 会员签到 操作  送积分-ok-2ok
     */
    public function memberSign($user_id){
        $member = new MemberHandle();
        //检测是否开启签到送积分的功能
        $config = new ConfigHandle();
        $config_info = $config->getIntegralConfig(0);
        if($config_info['sign_integral'] > 0){
            //判断今天是否已经签到过
            $count = $member->getIsMemberSign($user_id);
            if($count <= 0){
                //查询 当前店铺签到赠送积分数量
                $info = $this->getRewardRuleDetail();
                $number = $info['sign_point'];
                $res = $this->addMemberPointData(0, $user_id, $number, 5, '签到赠送积分', 39);
                return $res;
            }else{
                $this->error = '您今日已签到!';
                return false;
            }
        }else{
            return false;
        }
    }
    
    /**
     * 会员分享 送积分-ok-2ok
     */
    public function memberShareSendPoint($user_id){
        $member = new MemberHandle();
        //检测是否开启分享送积分的功能
        $config = new ConfigHandle();
        $config_info = $config->getIntegralConfig(0);
        if($config_info['share_integral'] > 0){
            //判断今天是否已经分享过
            $count = $member->getIsMemberShare($user_id);
            if($count <= 0){
                //查询 当前店铺分享赠送积分数量
                $info = $this->getRewardRuleDetail();
                $res = $this->addMemberPointData(0, $user_id, $info['share_point'], 6, '分享赠送积分',40);
                return $res;
            }else{
                $this->error="今日您已分享";
                return false;
            }
        }else{
            return false;
        }
    }
    
    /**
     *  注册会员 送积分-ok
     */
    public function RegisterMemberSendPoint($shop_id=0, $user_id){
        //检测是否开启注册送积分的功能
        $config = new ConfigHandle();
        $config_info = $config->getIntegralConfig($shop_id);
        if($config_info['register_integral'] > 0){
            $info = $this->getRewardRuleDetail();
            $this->addMemberPointData($shop_id, $user_id, $info['reg_member_self_point'], 7, '注册会员赠送积分');
          /*
            switch (NS_VERSION) {
                case NS_VER_B2C_FX:
                    //单店分销版本  上级添加积分
                    $this->SendPointMemberUpperThree($shop_id, $uid);
                    break;
            }
          */
        }else{
            return false;
        }
    }
    
    /**
     * 成为推广员  送积分-ok
     */
    public function RegisterPromoterSendPoint($shop_id=0, $user_id){
        //查询 当前店铺成为推广员赠送积分数量
        $info = $this->getRewardRuleDetail();
        $this->addMemberPointData($shop_id, $user_id, $info['reg_promoter_self_point'], 11, '成为推广员赠送积分');
        $this->SendPointPromoterUpperThree($shop_id, $user_id);
    }
    
    /**
     * 成为股东  送积分ok
     */
    public function RegisterPartnerSendPoint($shop_id=0, $user_id){
        //查询 当前店铺成为股东赠送积分数量
        $info = $this->getRewardRuleDetail();
        $this->addMemberPointData($shop_id, $user_id, $info['reg_promoter_self_point'], 15, '成为股东赠送积分');
        $this->SendPointPartnerUpperThree($shop_id, $user_id);
    }
    
    /**
     * 会员分享 送 优惠券
     */
    public function memberShareSendCoupon($shop_id, $uid){
        
    }
    
    /**
     * 会员进店 送 优惠券
     */
    public function memberIntoStoreSendCoupon(){
        
    }
    
    /**
     * 给 会员的  上级 上上级 上上上级 加积分  
     * @param  $shop_id
     * @param  $uid
     */
    public function SendPointMemberUpperThree($shop_id=0, $user_id){
        $info = $this->getRewardRuleDetail();
        $array = $this->getUpperThreeLevelUidByUid($shop_id, $user_id);
        if($array['user_one'] > 0){
            $this->addMemberPointData($shop_id, $array['user_one'], $info['reg_member_one_point'], 8, '推荐下级会员赠送积分');
        }
        if($array['user_two'] > 0){
            $this->addMemberPointData($shop_id, $array['user_two'], $info['reg_member_two_point'], 9, '推荐下下级会员赠送积分');
        }
        if($array['user_three'] > 0){
            $this->addMemberPointData($shop_id, $array['user_three'], $info['reg_member_three_point'], 10, '推荐下下下级会员赠送积分');
        }
    }
    
    /**
     * 给 推广员的  上级 上上级 上上上级 加积分
     * @param  $shop_id
     * @param  $uid
     */
    public function SendPointPromoterUpperThree($shop_id, $user_id){
        $info = $this->getRewardRuleDetail();
        $array = $this->getUpperPromoterThreeLevelUidByUid($shop_id, $user_id);
        if($array['promoter_one'] > 0){
            $this->addMemberPointData($shop_id, $array['promoter_one'], $info['reg_promoter_one_point'], 12, '推荐下级推广员赠送积分');
        }
        if($array['promoter_two'] > 0){
            $this->addMemberPointData($shop_id, $array['promoter_two'], $info['reg_promoter_two_point'], 13, '推荐下下级推广员赠送积分');
        }
        if($array['promoter_three'] > 0){
            $this->addMemberPointData($shop_id, $array['promoter_three'], $info['reg_promoter_three_point'], 14, '推荐下下下级推广员赠送积分');
        }
    }
    
    /**
     * 给 股东的  上级 上上级 上上上级 加积分
     * @param  $shop_id
     * @param  $uid
     */
    public function SendPointPartnerUpperThree($shop_id, $user_id){
        $info = $this->getRewardRuleDetail();
        $array = $this->getUpperPartnerThreeLevelUidByUid($shop_id, $user_id);
        if($array['partner_one'] > 0){
            $this->addMemberPointData($shop_id, $array['partner_one'], $info['reg_partner_one_point'], 16, '推荐下级股东赠送积分');
        }
        if($array['partner_two'] > 0){
            $this->addMemberPointData($shop_id, $array['partner_two'], $info['reg_partner_two_point'], 17, '推荐下下级股东赠送积分');
        }
        if($array['partner_three'] > 0){
            $this->addMemberPointData($shop_id, $array['partner_three'], $info['reg_partner_three_point'], 18, '推荐下下下级股东赠送积分');
        }
    }
    /**
     * 根据 uid 查询 会员  上级  上上级  上上上级 uid --分销相关，暂不做
     * 返回 array
     * @param  $uid
     */
    private function getUpperThreeLevelUidByUid($shop_id, $user_id){
        /*
        $nfx_user = new NfxUser();
        $array = array(
            'user_one' => 0,
            'user_two' => 0,
            'user_three' => 0,
        );
        if($user_id > 0){
            $data_one = $nfx_user->getUserParent($shop_id, $uid);
            $array['user_one'] = $data_one['source_uid'] > 0 ? $data_one['source_uid'] : 0;
            if($data_one['source_uid'] > 0){
                $data_two = $nfx_user->getUserParent($shop_id, $data_one['source_uid']);
                $array['user_two'] = $data_two['source_uid'] > 0 ? $data_two['source_uid'] : 0;
                if($data_two['source_uid'] > 0){
                    $data_three = $nfx_user->getUserParent($shop_id, $data_two['source_uid']);
                    $array['user_three'] = $data_three['source_uid'] > 0 ? $data_three['source_uid'] : 0;
                }
            }
        }
        return $array;
        */
    }
    
    /**
     * 根据 uid 查询 推广员 上级 上上级 上上上级 uid --分销相关，暂不做
     */
    private function getUpperPromoterThreeLevelUidByUid($shop_id, $uid){
        /*
        $nfx_promoter = new NfxPromoter();
        $array = array(
            'promoter_one' => 0,
            'promoter_two' => 0,
            'promoter_three' => 0,
        );
        if($uid > 0){
            $data_one = $nfx_promoter->getPromoterParentByUidAndShopid($shop_id, $uid);
            $array['promoter_one'] = $data_one['parent_uid'] > 0 ? $data_one['parent_uid'] : 0;
            if($data_one['parent_uid'] > 0){
                $data_two = $nfx_promoter->getPromoterParentByUidAndShopid($shop_id, $data_one['parent_uid']);
                $array['promoter_two'] = $data_two['parent_uid'] > 0 ? $data_two['parent_uid'] : 0;
                if($data_two['parent_uid'] > 0){
                    $data_three = $nfx_promoter->getPromoterParentByUidAndShopid($shop_id, $data_two['parent_uid']);
                    $array['promoter_three'] = $data_three['parent_uid'] > 0 ? $data_three['parent_uid'] : 0;
                }
            }
        }
        return $array;
        */
    }
    
    /**
     * 根据 uid 查询  股东  上级 上上级 上上上级 uid --分销相关，暂不做
     * @param  $shop_id
     * @param  $uid
     */
    private function getUpperPartnerThreeLevelUidByUid($shop_id, $uid){
        /*
        $nfx_partner = new NfxPartner();
        $array = array(
            'partner_one' => 0,
            'partner_two' => 0,
            'partner_three' => 0,
        );
        if($uid > 0){
            $data_one = $nfx_partner->getPartnerParentByUidAndShopid($shop_id, $uid);
            $array['partner_one'] = $data_one['parent_uid'] > 0 ? $data_one['parent_uid'] : 0;
            if($data_one['parent_uid'] > 0){
                $data_two = $nfx_partner->getPartnerParentByUidAndShopid($shop_id, $data_one['parent_uid']);
                $array['partner_two'] = $data_two['parent_uid'] > 0 ? $data_two['parent_uid'] : 0;
                if($data_two['parent_uid'] > 0){
                    $data_three = $nfx_promoter->getPartnerParentByUidAndShopid($shop_id, $data_two['parent_uid']);
                    $array['partner_three'] = $data_three['parent_uid'] > 0 ? $data_three['parent_uid'] : 0;
                }
            }
        }
        return $array;
        */
    }
}