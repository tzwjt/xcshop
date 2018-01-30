<?php
/**
 * MemberCoupon.php-ok
 * 会员流水账户
 * @date : 2017.08.20
 * @version : v1.0
 */
namespace dao\handle\Member;

use dao\handle\BaseHandle as BaseHandle;
use dao\model\Coupon as CouponModel;
use dao\model\CouponType as CouponTypeModel;
use dao\model\ConsultType as ConsultTypeModel;

class MemberCouponHandle extends BaseHandle
{
    function __construct(){
        parent::__construct();
    }

    /**
     * 使用优惠券-ok-2ok-3ok
     * @param  $user_id
     * @param  $coupon_id
     */
    public function useCoupon($user_id, $coupon_id, $order_id)
    {
        $coupon = new CouponModel();
        $data = array(
            'use_order_id' => $order_id,
            'status' => 2 //已使用
        );
        $res = $coupon->save($data, ['id' => $coupon_id, 'user_id' =>$user_id]);
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 用户获取优惠券 ok
     * @param  $uid
     * $get_type获取方式
     * @param  $coupon_type_idg
     */
    public function userAchieveCoupon($user_id, $coupon_type_id, $get_type)
    {
        $coupon = new CouponModel();
        //没有被领用的优惠券
        $count = $coupon->where(['coupon_type_id'=>$coupon_type_id, 'user_id'=> 0])->count();
        if($count > 0)
        {
            $data = array(
                'user_id' => $user_id,
                'status'=> 1,
                'get_type' => $get_type,
                'fetch_time' => time()
            );
            $retval = $coupon->where(['coupon_type_id'=>$coupon_type_id, 'user_id'=> 0])->limit(1)->update($data);
        }else{
            $this->error="没有可以领取的优惠券";
            return false;
          //  $restval = false;
          //  $retval = NO_COUPON;
        }
        if (empty($retval)) {
            return false;
        } else {
            return true;
        }
       // return $retval;
    
    }

    /**
     * 订单返还会员优惠券 ok
     */
    public function userReturnCoupon($coupon_id){
        $coupon = new CouponModel();
        $data = array(
            'status' => 1   //已领用（未使用）
    
        );
        $retval = $coupon->save($data,['id' => $coupon_id]);
        return $retval;
    }

    /**
     * ok-2ok
     * 获取优惠券金额 ok
     */
    public function getCouponMoney($coupon_id){
        $coupon = new CouponModel();
        $money = $coupon->getInfo(['id' => $coupon_id],'money');
        if(!empty($money['money']))
        {
            return $money['money'];
        }else{
            return 0;
        }
    }

    /**
     * 查询当前会员优惠券列表 ok-2okkk
     * @param  $type  1已领用（未使用） 2已使用 3已过期
     */
    public function getUserCouponList($user_id, $type = '')
    {
        $time = time();
        $condition['user_id'] = $user_id;
        switch ($type)
        {
            case 1:
                //未使用，已领用,未过期
               // $condition['start_time'] = array('ELT', $time);
                $condition['end_time'] = array('GT', $time);
                $condition['status'] = 1;
				break;
            case 2:
                //已使用
                $condition['status'] = 2;
				break;
            case 3:
                //已过期
                //$condition['end_time'] = array('ELT', $time);
                $condition['status'] = 3;
				break;
			default:
			    break;
        }
        /*
        if(!empty($shop_id)){
            $condition['shop_id'] = $shop_id;
        }
        */
        $coupon = new CouponModel();
        $coupon_list = $coupon->getConditionQuery($condition, '*', 'money desc');
        if(!empty($coupon_list))
        {
            $coupon_type_model = new CouponTypeModel();
            foreach ($coupon_list as $k => $v)
            {
                $type_info = $coupon_type_model->getInfo(['id' => $v['coupon_type_id']], 'coupon_name,at_least');
                $coupon_list[$k]['coupon_name'] = $type_info['coupon_name'];
                $coupon_list[$k]['at_least'] = $type_info['at_least'];
            }
        }
        
        return $coupon_list;
    }
}