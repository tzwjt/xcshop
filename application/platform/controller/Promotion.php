<?php
/**
 * 营销活动
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-10-16
 * Time: 0:38
 */

namespace app\platform\controller;

use app\platform\controller\BaseController;
use dao\handle\AddressHandle;
use dao\handle\PromotionHandle as PromotionHandle;
use dao\handle\promotion\PromoteRewardRuleHandle;
use dao\handle\ConfigHandle;


class Promotion extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 优惠券类型列表
     */
    public function couponTypeList()
    {

        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", PAGESIZE);
        $search_text = request()->post('search_text', '');
        $coupon = new PromotionHandle();
        $condition = array(
            'coupon_name' => array(
                'like',
                '%' . $search_text . '%'
            )
        );
        $list = $coupon->getCouponTypeList($page_index, $page_size, $condition, 'start_time desc');
        return json(resultArray(0,"操作成功", $list));
    }

    /**
     * 得到有效优惠券类型列表
     */
    public function getValidCouponTypeList()
    {
        $coupon = new PromotionHandle();
        $list = $coupon->getVilidCouponTypeList();
        return json(resultArray(0,"操作成功", $list));
    }

    /**
     * 删除优惠券类型
     */
    public function delCouponType()
    {
        $coupon_type_id = request()->post('coupon_type_id', '');
        if (empty($coupon_type_id)) {
            $this->error("没有获取到优惠券信息");
        }
        $coupon = new PromotionHandle();
        $res = $coupon->deleteCouponType($coupon_type_id);
        if (empty($res)) {
            $error = $coupon->getError();
            if (empty($error)) {
                return json(resultArray(2,"操作失败"));
            } else {
                return json(resultArray(2,"操作失败: " .$error));
            }
        }
        return json(resultArray(0,"操作成功"));
    }

    /**
     * 添加优惠券类型
     */
    public function addCouponType()
    {

        $coupon_name = request()->post('coupon_name');
        $money = request()->post('money');
        $count = request()->post('count');
        $max_fetch = request()->post('max_fetch', 1);
        $at_least = request()->post('at_least', 0);
        $need_user_level = request()->post('need_user_level', 0);
        $range_type = request()->post('range_type');
        $start_time = request()->post('start_time', '');
        $end_time = request()->post('end_time', '');
        $is_show = request()->post('is_show', 0);
        $goods_list = request()->post('goods_list', '');
        $coupon = new PromotionHandle();
      //  addCouponType($coupon_name, $money, $count, $max_fetch, $at_least, $need_user_level, $range_type, $start_time, $end_time, $is_show, $goods_list)
  // addCouponType($coupon_name, $money, $count, $max_fetch, $at_least, $need_user_level, $range_type, $start_time, $end_time, $is_show, $goods_list)

        $retval = $coupon->addCouponType($coupon_name, $money, $count, $max_fetch, $at_least, $need_user_level, $range_type, $start_time, $end_time, $is_show, $goods_list);
        if (empty($retval)) {
            $error = $coupon->getError();
            if (empty($error)) {
                return json(resultArray(2, "操作失败"));
            } else {
                return json(resultArray(2, "操作失败: " . $error));
            }
        } else {
            return json(resultArray(0, "操作成功"));
        }

    }

    public function updateCouponType()
    {
        $coupon_type_id = request()->post('coupon_type_id');
        $coupon_name = request()->post('coupon_name');
        $money = request()->post('money');
        $count = request()->post('count');
        $repair_count = request()->post('repair_count');
        $max_fetch = request()->post('max_fetch');
        $at_least = request()->post('at_least', '');
        $need_user_level = request()->post('need_user_level');
        $range_type = request()->post('range_type');
        $start_time = request()->post('start_time');
        $end_time = request()->post('end_time');
        $is_show = request()->post('is_show');
        $goods_list = request()->post('goods_list');
        $coupon = new PromotionHandle();
        //    updateCouponType($coupon_type_id, $coupon_name, $money, $count, $repair_count, $max_fetch, $at_least, $need_user_level, $range_type, $start_time, $end_time, $is_show, $goods_list)
  // updateCouponType($coupon_type_id, $coupon_name, $money, $count, $repair_count, $max_fetch, $at_least, $need_user_level, $range_type, $start_time, $end_time, $is_show, $goods_list)

        $retval = $coupon->updateCouponType($coupon_type_id, $coupon_name, $money, $count, $repair_count, $max_fetch, $at_least, $need_user_level, $range_type, $start_time, $end_time, $is_show, $goods_list);
        if (empty($retval)) {
            $error = $coupon->getError();
            if (empty($error)) {
                    return json(resultArray(2, "操作失败"));
            } else {
                    return json(resultArray(2, "操作失败: " . $error));
            }
        } else {
                return json(resultArray(0, "操作成功"));
        }

    }

    /**
     * 得到CouponType用于修改
     */
    public function getCouponTypeById()
    {
        $coupon = new PromotionHandle();
        $coupon_type_id = request()->get('coupon_type_id', 0);
        if ($coupon_type_id == 0) {
            $this->error("没有获取到类型");
        }
        $coupon_type_data = $coupon->getCouponTypeDetail($coupon_type_id);
        $goods_id_array = array();
        foreach ($coupon_type_data['goods_list'] as $k => $v) {
            $goods_id_array[] = $v['goods_id'];
        }
        $coupon_type_data['goods_id_array'] = $goods_id_array;

        $this->assign("coupon_type_info", $coupon_type_data);

        return json(resultArray(0,"操作成功", $coupon_type_data));

    }

    /**
     * 获取优惠券详情
     */
    public function getCouponTypeDetail()
    {
        $coupon = new PromotionHandle();
        $coupon_type_id = request()->post('coupon_type_id');
        $coupon_type_data = $coupon->getCouponTypeDetail($coupon_type_id);
        return json(resultArray(0,"操作成功", $coupon_type_data));
    }

    /**
     * 功能：积分管理
     */
    public function setPointConfig()
    {
        $pointConfig = new PromotionHandle();
        $config_id = request()->post('config_id');
        $convert_rate = request()->post('convert_rate');
        $is_open = request()->post('is_open', 0);
        $desc = request()->post('desc', '');
        $retval = $pointConfig->setPointConfig($config_id, $convert_rate, $is_open, $desc);
        if (empty($retval)) {
            return json(resultArray(2,"操作失败"));

        } else {
            return json(resultArray(0,"操作成功"));
        }

    }


    public function getPointConfig() {
        $pointConfig = new PromotionHandle();
        $child_menu_list = array(
            array(
                'url' => "promotion/getPointConfig",
                'menu_name' => "积分管理",
                "active" => 1
            ),
            array(
                'url' => "promotion/getIntegral",
                'menu_name' => "积分奖励",
                "active" => 0
            )
        );
        $pointconfiginfo = $pointConfig->getPointConfig();
        $data = array (
            'child_menu_list'=> $child_menu_list,
            "pointconfiginfo" => $pointconfiginfo
        );

        return json(resultArray(0,"操作成功", $data));

    }

    /**
     * 积分奖励
     */
    public function setIntegral()
    {
         //   $shop_id = $this->instance_id;
        $rule_id = request()->post('rule_id');
        $sign_point = request()->post('sign_point', 0);
        $share_point = request()->post('share_point', 0);
        $reg_member_self_point = request()->post('reg_member_self_point', 0);
        $reg_member_one_point = 0;
        $reg_member_two_point = 0;
        $reg_member_three_point = 0;
        $reg_promoter_self_point = 0;
        $reg_promoter_one_point = 0;
        $reg_promoter_two_point = 0;
        $reg_promoter_three_point = 0;
        $reg_partner_self_point = 0;
        $reg_partner_one_point = 0;
        $reg_partner_two_point = 0;
        $reg_partner_three_point = 0;
        $click_point = request()->post("click_point", 0);
        $comment_point = request()->post("comment_point", 0);
        $rewardRule = new PromoteRewardRuleHandle();
        $retval = $rewardRule->setPointRewardRule($rule_id, $sign_point, $share_point, $reg_member_self_point,
            $reg_member_one_point, $reg_member_two_point, $reg_member_three_point, $reg_promoter_self_point, $reg_promoter_one_point,
            $reg_promoter_two_point, $reg_promoter_three_point, $reg_partner_self_point, $reg_partner_one_point, $reg_partner_two_point,
            $reg_partner_three_point,$click_point,$comment_point);
        if (empty($retval)) {
            return json(resultArray(2,"操作失败"));

        } else {
            return json(resultArray(0,"操作成功"));
        }


    }

    public function getIntegral() {
        $child_menu_list = array(
            array(
                'url' => "promotion/getPointConfig",
                'menu_name' => "积分管理",
                "active" => 0
            ),
            array(
                'url' => "promotion/getIntegral",
                'menu_name' => "积分奖励",
                "active" => 1
            )
        );
        $rewardRule = new PromoteRewardRuleHandle();
        $res = $rewardRule->getRewardRuleDetail();
        $config = new ConfigHandle();
        $integralConfig = $config->getIntegralConfig(0);

        $data = array(
            'child_menu_list'=>$child_menu_list,
            "integralConfig" => $integralConfig,
            "integral" => $res,
        );
        return json(resultArray(0,"操作成功", $data));
    }

    /**
     * 设置积分奖励的配置
     * @return Ambigous <multitype:unknown, multitype:unknown unknown string >
     */
    public function setIntegralConfig()
    {
        $register = request()->post('register', 0);
        $sign = request()->post('sign', 0);
        $share = request()->post('share', 0);
        $config = new ConfigHandle();
        $retval = $config->setIntegralConfig(0, $register, $sign, $share);
        if (empty($retval)) {
            return json(resultArray(2,"操作失败"));

        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * 满减送 列表
     */
    public function mansongList()
    {

        $page_index = request()->post("page_index", 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $search_text = request()->post('search_text', '');
        $status = request()->post('status', '-1');


        $child_menu_list = array(
            array(
                'url' => "promotion/mansongList",
                'menu_name' => "全部",
                "active" => $status == '-1' ? 1 : 0
            ),
            array(
                'url' => "promotion/mansongList?status=0",
                'menu_name' => "未发布",
                "active" => $status == 0 ? 1 : 0
            ),
            array(
                'url' => "promotion/mansongList?status=1",
                'menu_name' => "进行中",
                "active" => $status == 1 ? 1 : 0
            ),
            array(
                'url' => "promotion/mansongList?status=3",
                'menu_name' => "已关闭",
                "active" => $status == 3 ? 1 : 0
            ),
            array(
                'url' => "promotion/mansongList?status=4",
                'menu_name' => "已结束",
                "active" => $status == 4 ? 1 : 0
            )
        );

        $condition = array(
           // 'shop_id' => $this->instance_id,
            'mansong_name' => array(
                'like',
                '%' . $search_text . '%'
            )
        );
        if ($status !== '-1') {
            $condition['status'] = $status;
            $mansong = new PromotionHandle();
         //   getPromotionMansongList($page_index = 1, $page_size = 0, $condition = '', $order = 'create_time desc')
            $list = $mansong->getPromotionMansongList($page_index, $page_size, $condition);
        } else {
            $mansong = new PromotionHandle();
            $list = $mansong->getPromotionMansongList($page_index, $page_size, $condition);
        }

        $data = array(
            'child_menu_list'=>$child_menu_list,
            'mansong_list' => $list

        );
        return json(resultArray(0,"操作成功", $data));
        //    return $list;

    }

    /**
     * 添加满减送活动
     */
    public function addMansong()
    {
        $mansong = new PromotionHandle();

        $mansong_name = request()->post('mansong_name');
        $start_time = request()->post('start_time', '');
        $end_time = request()->post('end_time', '');
        //    $shop_id = $this->instance_id;
        $type = request()->post('type', '');
        $range_type = request()->post('range_type', '');
        $rule = request()->post('rule');
        $goods_id_array = request()->post('goods_id_array', '');
       // addPromotionMansong($mansong_name, $start_time, $end_time,  $remark, $type, $range_type, $rule, $goods_id_array)
        //     addPromotionMansong($mansong_name, $start_time, $end_time,  $remark, $type, $range_type, $rule, $goods_id_array)
        $res = $mansong->addPromotionMansong($mansong_name, $start_time, $end_time,  '', $type, $range_type, $rule, $goods_id_array);
        if (empty($res)) {
            return json(resultArray(2,"操作失败 ".$mansong->getError()));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * 修改 满减送活动
     */
    public function updateMansong()
    {
        $mansong = new PromotionHandle();

        $mansong_id = request()->post('mansong_id');
        $mansong_name = request()->post('mansong_name');
        $start_time = request()->post('start_time');
        $end_time = request()->post('end_time');

        $type = request()->post('type');
        $range_type = request()->post('range_type');
        $rule = request()->post('rule');
        $goods_id_array = request()->post('goods_id_array');

        // updatePromotionMansong($mansong_id, $mansong_name, $start_time, $end_time, $remark, $type, $range_type, $rule, $goods_id_array)
        $res = $mansong->updatePromotionMansong($mansong_id, $mansong_name, $start_time, $end_time,  '', $type, $range_type, $rule, $goods_id_array);
        if (empty($res)) {
            return json(resultArray(2,"操作失败 ".$mansong->getError()));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    public function getMansongById() {
        $mansong_id = isset($this->param['mansong_id']) ? $this->param['mansong_id'] : 0;
        if (empty($mansong_id)) {
            return json(resultArray(2,"没有获取到满减送信息"));
        }
        $mansong = new PromotionHandle();
        $info = $mansong->getPromotionMansongDetail($mansong_id);
        $condition ='';
        $coupon_type_list = $mansong->getCouponTypeList(1, 0, $condition);
        $gift_list = $mansong->getPromotionGiftList(1, 0, $condition);

      //  $this->assign('gift_list', $gift_list);

        $data = array(
            'coupon_type_list'=> $coupon_type_list,
            'gift_list' => $gift_list,

            'mansong_info'=> $info
        );

        return json(resultArray(0,"操作成功", $data));

    }

    /**
     * 获取满减送详情
     */
    public function getMansongDetail()
    {
        $mansong_id =  isset($this->param['mansong_id']) ? $this->param['mansong_id'] : 0;
        if (empty($mansong_id)) {
            return json(resultArray(2,"没有获取到满减送信息"));
        }
        $mansong = new PromotionHandle();
        $detail = $mansong->getPromotionMansongDetail($mansong_id);
        return json(resultArray(0,"操作成功", $detail));

    }

    /**
     * 删除满减送活动
     *
     * @return []
     */
    public function delMansong()
    {
        $mansong_id = request()->post('mansong_id', 0);
        if (empty($mansong_id)) {
            return json(resultArray(2,"没有获取到满减送信息"));
        }
        $mansong = new PromotionHandle();
        $res = $mansong->delPromotionMansong($mansong_id);
        if (empty($res)) {
            return json(resultArray(2,"操作失败 ".$mansong->getError()));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * 关闭满减送活动
     *
     * @return
     */
    public function closeMansong()
    {
        $mansong_id = request()->post('mansong_id', 0);
        if (empty($mansong_id)) {
            return json(resultArray(2,"没有获取到满减送信息"));
        }
        $mansong = new PromotionHandle();
        $res = $mansong->closePromotionMansong($mansong_id);
        if (empty($res)) {
            return json(resultArray(2,"操作失败 ".$mansong->getError()));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * 获取限时折扣列表
     */
    public function getDiscountList()
    {
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $search_text = request()->post('search_text', '');
        $status = request()->post('status', '-1');

        $child_menu_list = array(
            array(
                'url' => "promotion/getDiscountList",
                'menu_name' => "全部",
                "active" => $status == '-1' ? 1 : 0
            ),
            array(
                'url' => "promotion/getDiscountList?status=0",
                'menu_name' => "未发布",
                "active" => $status == 0 ? 1 : 0
            ),
            array(
                'url' => "promotion/getDiscountList?status=1",
                'menu_name' => "进行中",
                "active" => $status == 1 ? 1 : 0
            ),
            array(
                'url' => "promotion/getDiscountList?status=3",
                'menu_name' => "已关闭",
                "active" => $status == 3 ? 1 : 0
            ),
            array(
                'url' => "promotion/getDiscountList?status=4",
                'menu_name' => "已结束",
                "active" => $status == 4 ? 1 : 0
            )
        );

        $discount = new PromotionHandle();

        $condition = array(
            'discount_name' => array(
                'like',
                '%' . $search_text . '%'
            )
        );
        if ($status !== '-1') {
            $condition['status'] = $status;
           // getPromotionDiscountList($page_index = 1, $page_size = 0, $condition = '', $order = 'create_time desc')
            $list = $discount->getPromotionDiscountList($page_index, $page_size, $condition);
        } else {
            $list = $discount->getPromotionDiscountList($page_index, $page_size, $condition);
        }

          //  return $list;
        $data = array(
            "status"=> $status,
            'child_menu_list'=> $child_menu_list,
            'discount_list' => $list
        );
        return json(resultArray(0,"操作成功", $data));
    }

    /**
     * 添加限时折扣
     */
    public function addDiscount()
    {
        $discount = new PromotionHandle();
        $discount_name = request()->post('discount_name');
        $start_time = request()->post('start_time');
        $end_time = request()->post('end_time');
        $remark = '';
        $goods_id_array = request()->post('goods_id_array');
        //addPromotionDiscount($discount_name, $start_time, $end_time, $remark, $goods_id_array)
        $retval = $discount->addPromotiondiscount($discount_name, $start_time, $end_time, $remark, $goods_id_array);
        if (empty($retval)) {
            return json(resultArray(2,"操作失败 ".$discount->getError()));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * 修改限时折扣
     */
    public function updateDiscount()
    {
        $discount = new PromotionHandle();
        $discount_id = request()->post('discount_id');
        $discount_name = request()->post('discount_name');
        $start_time = request()->post('start_time');
        $end_time = request()->post('end_time');
        $remark = '';
        $goods_id_array = request()->post('goods_id_array');
       // updatePromotionDiscount($discount_id, $discount_name, $start_time, $end_time, $remark, $goods_id_array)

        $retval = $discount->updatePromotionDiscount($discount_id, $discount_name, $start_time, $end_time, $remark, $goods_id_array);
        if (empty($retval)) {
            return json(resultArray(2,"操作失败 ".$discount->getError()));
        } else {
            return json(resultArray(0,"操作成功"));
        }

    }

    /**
     * 获取限时折扣,用于修改
     */
    public function getDiscountById() {
        $discount_id = isset($this->param['discount_id']) ? $this->param['discount_id'] : 0;
        if (empty($discount_id)) {
            return json(resultArray(2,"没有获取到折扣信息"));
        }
        $discount = new PromotionHandle();
        $info = $discount->getPromotionDiscountDetail($discount_id);

        if (! empty($info['goods_list'])) {
            foreach ($info['goods_list'] as $k => $v) {
                $goods_id_array[] = $v['goods_id'];
            }
        }
        $info['goods_id_array'] = $goods_id_array;
        return json(resultArray(0,"操作成功", $info));
    }

    /**
     * 获取限时折扣详情
     */
    public function getDiscountDetail()
    {
        $discount_id = isset($this->param['discount_id']) ? $this->param['discount_id'] : 0;
        if (empty($discount_id)) {
            return json(resultArray(2,"没有获取到折扣信息"));
        }
        $discount = new PromotionHandle();
        $detail = $discount->getPromotionDiscountDetail($discount_id);
        return json(resultArray(0,"操作成功", $detail));
    }

    /**
     * 删除限时折扣
     */
    public function delDiscount()
    {
        $discount_id = request()->post('discount_id', 0);
        if (empty($discount_id)) {
            return json(resultArray(2,"没有获取到折扣信息"));
        }
        $discount = new PromotionHandle();
        $retval = $discount->delPromotionDiscount($discount_id);
        if (empty($retval)) {
            return json(resultArray(2,"操作失败 ".$discount->getError()));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * 关闭正在进行的限时折扣
     */
    public function closeDiscount()
    {
        $discount_id = request()->post('discount_id', 0);
        if (empty($discount_id)) {
            return json(resultArray(2,"没有获取到折扣信息"));
        }
        $discount = new PromotionHandle();
        $retval = $discount->closePromotionDiscount($discount_id);
        if (empty($retval)) {
            return json(resultArray(2,"操作失败 ".$discount->getError()));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * 设置满额包邮
     */
    public function setFullShipping()
    {
        $full = new PromotionHandle();

        $is_open = request()->post('is_open');
        $full_mail_money = request()->post('full_mail_money');
        $no_mail_province_id_array = request()->post('no_mail_province_id_array');
        $no_mail_city_id_array = request()->post("no_mail_city_id_array");
       // updatePromotionFullMail( $is_open, $full_mail_money, $no_mail_province_id_array, $no_mail_city_id_array)
        $retval = $full->updatePromotionFullMail( $is_open, $full_mail_money, $no_mail_province_id_array, $no_mail_city_id_array);
        if (empty($retval)) {
            return json(resultArray(2,"操作失败 "));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * 满额包邮
     */
    public function fullShipping() {
        $full = new PromotionHandle();
        //getPromotionFullMail()
        $info = $full->getPromotionFullMail();
           // $this->assign("info", $info);
        $existing_address_list['province_id_array'] = explode(',', $info['no_mail_province_id_array']);
        $existing_address_list['city_id_array'] = explode(',', $info['no_mail_city_id_array']);
        $address = new AddressHandle();
        // 目前只支持省市，不支持区县，
        $address_list = $address->getAreaTree($existing_address_list);
          //  $this->assign("address_list", $address_list);
        $no_mail_province_id_array = '';
        foreach ($existing_address_list['province_id_array'] as $v) {
            $no_mail_province_id_array[] = $address->getProvinceName($v);
        }
        $no_mail_province = implode(',', $no_mail_province_id_array);
          //  $this->assign("no_mail_province", $no_mail_province);

        $data = array (
            'full_mail_info' => $info,
            "address_list" => $address_list,
            "no_mail_province"=> $no_mail_province
        );

        return json(resultArray(0,"操作成功", $data));

    }


}