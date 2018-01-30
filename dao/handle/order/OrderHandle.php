<?php
/**
 * OrderHandle.php
 * 订单操作类--ok
 * @date : 2017.8.17
 * @version : v1.0
 */
namespace dao\handle\order;

use dao\handle\agent\AgentUserHandle;
use dao\handle\GoodsHandle;
use dao\handle\order\OrderStatusHandle;
use dao\handle\PlatformUserHandle;
use dao\model\Order as OrderModel;
use dao\model\OrderAction as OrderActionModel;
use dao\model\MemberUser as MemberUserModel;
use dao\model\OrderGoods as OrderGoodsModel;
use dao\model\ExpressCompany as OrderExpressCompanyModel;
use dao\handle\member\MemberAccountHandle;
use dao\handle\member\MemberCouponHandle;
use dao\model\GoodsSku as GoodsSkuModel;
use dao\model\OrderGoodsExpress as OrderGoodsExpressModel;
use dao\handle\Basehandle;
use dao\handle\promotion\GoodsExpressHandle; //暂不用
use dao\handle\promotion\GoodsPreferenceHandle;
use dao\model\AlbumPicture as AlbumPictureModel;
use dao\handle\promotion\GoodsMansongHandle;
use dao\model\OrderPromotionDetails as OrderPromotionDetailsModel;
use dao\model\OrderGoodsPromotionDetails as OrderGoodsPromotionDetailsModel;
use dao\model\PromotionMansongRule as PromotionMansongRuleModel;
//use dao\handle\Shop;
use think\Model;
use dao\handle\UnifyPayHandle;
//use dao\handle\WebSite;
use dao\model\PromotionFullMail as PromotionFullMailModel;
use think\Log;
//use dao\model\NsMemberModel;
use dao\handle\AddressHandle;
use dao\handle\ConfigHandle;
use dao\model\PickupPoint as PickupPointModel;
use dao\model\OrderPickup as OrderPickupModel;
use dao\model\Config as ConfigModel;
use dao\handle\MemberHandle;
use dao\model\AccountOrderRecords as AccountOrderRecordsModel ;

use dao\model\Goods as GoodsModel;
use dao\handle\PromotionHandle;
use dao\model\AgentUser as AgentUserModel;
use dao\model\OrderRefundAccountRecords as OrderRefundAccountRecordsModel;


class OrderHandle extends BaseHandle
{

    public $order;
    // 订单主表
    function __construct()
    {
        parent::__construct();
        $this->order = new OrderModel();
    }

    /**
     * 订单创建--ok
     * （订单传入积分系统默认为使用积分兑换商品）
     *
     * @param  $order_type            
     * @param  $out_trade_no            
     * @param  $pay_type            
     * @param  $shipping_type            
     * @param  $order_from            
     * @param  $buyer_ip            
     * @param  $buyer_message            
     * @param  $buyer_invoice            
     * @param  $shipping_time            
     * @param  $receiver_mobile            
     * @param  $receiver_province            
     * @param  $receiver_city            
     * @param  $receiver_district            
     * @param  $receiver_address            
     * @param  $receiver_zip            
     * @param  $receiver_name            
     * @param  $point            
     * @param  $point_money            
     * @param  $coupon_money            
     * @param  $coupon_id            
     * @param  $user_money            
     * @param  $promotion_money            
     * @param  $shipping_money            
     * @param  $pay_money            
     * @param  $give_point            
     * @param  $goods_sku_list            

     */
    public function orderCreate($user_id, $user_type, $order_type, $out_trade_no, $pay_type, $shipping_type, $order_from, $buyer_ip, $buyer_message, $buyer_invoice, $shipping_time, $receiver_mobile, $receiver_province, $receiver_city, $receiver_district, $receiver_address, $receiver_zip, $receiver_name, $point, $coupon_id, $user_money, $goods_list, $platform_money, $pick_up_id, $shipping_company_id, $coin)
    {
        $this->startTrans();
        
        try {
            // 设定不使用会员余额支付
            $user_money = 0;
            // 查询商品对应的店铺ID
            $order_goods_preference = new GoodsPreferenceHandle();
            $shop_id = 0; // $order_goods_preference->getGoodsSkuListShop($goods_sku_list);
            // 单店版查询网站内容
        //    $web_site = new WebSite();
         //   $web_info = $web_site->getWebSiteInfo();
        //    $shop_name = $web_info['title'];
            // 获取优惠券金额
            $coupon = new MemberCouponHandle();
            $coupon_money = $coupon->getCouponMoney($coupon_id);
            
            // 获取购买人信息
            $buyer = new MemberUserModel();
            $buyer_info = $buyer->getInfo([
                'id' => $user_id
            ], 'login_phone, agent_id');
            // 订单商品费用
           // getGoodsListPrice($user_id, $goods_list)
            $goods_money = $order_goods_preference->getGoodsListPrice($user_id,$goods_list);

           // $point = $order_goods_preference->getGoodsListExchangePointByGoodsId($goods_list); //getGoodsListExchangePoint($goods_sku_list);



            // 获取订单邮费,订单自提免除运费
            if ($shipping_type == 1) {
                $order_goods_express = new GoodsExpressHandle();
                $deliver_price = $order_goods_express->getGoodsListExpressFee($goods_list, $shipping_company_id, $receiver_province, $receiver_city, $receiver_district,$user_id);


                if ($deliver_price === null) {
                    //获得固定运费
                    $goods_handle = new GoodsHandle();
                    $deliver_price = $goods_handle->getFixedShippingFeeByGoodsList($goods_list);
                }

             //   getGoodsListExpressFee($goods_list, $express_company_id, $province, $city, $district)

              //  getSkuListExpressFee($goods_sku_list, $shipping_company_id, $receiver_province, $receiver_city, $receiver_district);
                if ($deliver_price < 0) {
                    $this->rollback();
                    return $deliver_price;
                }

                //注： 获取订单邮费，暂不处理,这个要处理一下,现在都是不要邮费
              //  $deliver_price = 0;
            } else { //自提
                //根据自提点服务费用计算
                $deliver_price = $order_goods_preference->getPickupMoney($goods_money);
            }
            
      
            
            // 积分兑换抵用金额
            $account_flow = new MemberAccountHandle();
            /*
             * $point_money = $order_goods_preference->getPointMoney($point, $shop_id);
             */
            $point_money = 0;
            $promotion = new PromotionHandle();
            $point_config = $promotion->getPointConfig();

            if (!empty( $point_config)) {
                $point_convert_rate = $point_config['convert_rate'];
                if (!empty($point_convert_rate)) {
                    $point_money = $point * $point_convert_rate;
                }
            }
            /*
             * if($point > 0)
             * {
             * //积分兑换抵用商品金额+邮费
             * $point_money = $goods_money;
             * //订单为已支付
             * if($deliver_price == 0)
             * {
             * $order_status = 1;
             * }else
             * {
             * $order_status = 0;
             * }
             *
             * //赠送积分为0
             * $give_point = 0;
             * //不享受满减送优惠
             * $promotion_money = 0;
             *
             * }else{
             */
            // 订单来源
            //以后实现
            if (isWeixin()) {
                $order_from = 1; // 微信
            } elseif (request()->isMobile()) {
                $order_from = 2; // 手机App
            } else {
                $order_from = 3; // 电脑
            }
            // 订单支付方式
            
            // 订单待支付
            $order_status = 0;
            // 购买商品获取积分数
            $give_point = $order_goods_preference->getGoodsListGivePoint($goods_list); //getGoodsSkuListGivePoint($goods_sku_list);
            // 订单满减送活动优惠
            $goods_mansong = new GoodsMansongHandle();
           // getGoodsListMansong($goods_list)
            //
            $mansong_array = $goods_mansong->getGoodsListMansong($user_id,$goods_list);
            $promotion_money = 0;
            $mansong_rule_array = array();
            $mansong_discount_array = array();
            if (! empty($mansong_array)) {
                foreach ($mansong_array as $k_mansong => $v_mansong) {
                    foreach ($v_mansong['discount_detail'] as $k_rule => $v_rule) {
                        $rule = $v_rule[1];
                       // array($rule, $goods_id.":".$sku_id.":".$goods_promotion_price);
                        $discount_money_detail = explode(':', $rule);
                        $mansong_discount_array[] = array(
                            $discount_money_detail[0],
                            $discount_money_detail[1],
                            $discount_money_detail[2],
                            $v_rule[0]['id']
                        );
                        $promotion_money += $discount_money_detail[2]; // round($discount_money_detail[1],2);
                                                                       // 添加优惠活动信息
                        $mansong_rule_array[] = $v_rule[0];
                    }
                }
                $promotion_money = round($promotion_money, 2);
            }
            $full_mail_array = array();
            // 计算订单的满额包邮
            $full_mail_model = new PromotionFullMailModel();
            // 店铺的满额包邮
            $full_mail_obj = $full_mail_model->getInfo([
                "shop_id" => 0 //$shop_id
            ], "*");

            //
            $no_mail = $this->checkIdIsinIdArr($receiver_city, $full_mail_obj['no_mail_city_id_array']);
            if($no_mail)
            {
                $full_mail_obj['is_open'] = 0;
            }
            if (! empty($full_mail_obj)) {
                $is_open = $full_mail_obj["is_open"];
                $full_mail_money = $full_mail_obj["full_mail_money"];
                $order_real_money = $goods_money - $promotion_money - $coupon_money - $point_money;
                if ($is_open == 1 && $order_real_money >= $full_mail_money && $deliver_price > 0) {
                    // 符合满额包邮 邮费设置为0
                    $full_mail_array["promotion_id"] = $full_mail_obj["id"];
                    $full_mail_array["promotion_type"] = 'MANEBAOYOU';
                    $full_mail_array["promotion_name"] = '满额包邮';
                    $full_mail_array["promotion_condition"] = '满' . $full_mail_money . '元,包邮!';
                    $full_mail_array["discount_money"] = $deliver_price;
                    $deliver_price = 0;
                }
            }
            
            // 订单费用(具体计算)
            $order_money = $goods_money + $deliver_price - $promotion_money - $coupon_money - $point_money;
            
            if ($order_money < 0) {
                $order_money = 0;
                $user_money = 0;
                $platform_money = 0;
            }
            
            if (! empty($buyer_invoice)) {
                // 添加税费
                $config = new ConfigHandle();
                $tax_value = $config->getConfig(0, 'ORDER_INVOICE_TAX');
                if (empty($tax_value['value'])) {
                    $tax = 0;
                } else {
                    $tax = $tax_value['value'];
                }
                $tax_money = $order_money * $tax / 100;
            } else {
                $tax_money = 0;
            }
            $order_money = $order_money + $tax_money;
            
            if ($order_money < $platform_money) {
                $platform_money = $order_money;
            }

            $pay_money = $order_money - $user_money - $platform_money;
            if ($pay_money <= 0) {
               // $pay_money = 0;
              //  $order_status = 1;
              //  $pay_status = 2;
              //  $pay_type = 10;

                $pay_money = 0;
                $order_status = 0;
                $pay_status = 0;
            } else {
                $order_status = 0;
                $pay_status = 0;
            }
        
            // 积分返还类型
            $config = new ConfigModel();
            $config_info = $config->getInfo([
                "instance_id" => 0, //$shop_id,
                "key" => "SHOPPING_BACK_POINTS"
            ], "value");
            $give_point_type = $config_info["value"];
            
            // 店铺名称
            
            $data_order = array(
                'order_type' => $order_type,
              //  'order_no' => $this->createOrderNo($shop_id),
                'order_no' => $this->createOrderNo(0),
                'out_trade_no' => $out_trade_no,
                'payment_type' => $pay_type,
                'shipping_type' => $shipping_type,
                'order_from' => $order_from,
                'agent_id' => $buyer_info['agent_id'],
                'buyer_id' => $user_id,
                'user_name' => $buyer_info['login_phone'], // $buyer_info['nick_name'],
                'buyer_ip' => $buyer_ip,
                'buyer_message' => $buyer_message,
                'buyer_invoice' => $buyer_invoice,
                'shipping_time' => $this->getTimeTurnTimeStamp($shipping_time), // datetime NOT NULL COMMENT '买家要求配送时间',
                'receiver_mobile' => $receiver_mobile, // varchar(11) NOT NULL DEFAULT '' COMMENT '收货人的手机号码',
                'receiver_province' => $receiver_province, // int(11) NOT NULL COMMENT '收货人所在省',
                'receiver_city' => $receiver_city, // int(11) NOT NULL COMMENT '收货人所在城市',
                'receiver_district' => $receiver_district, // int(11) NOT NULL COMMENT '收货人所在街道',
                'receiver_address' => $receiver_address, // varchar(255) NOT NULL DEFAULT '' COMMENT '收货人详细地址',
                'receiver_zip' => $receiver_zip, // varchar(6) NOT NULL DEFAULT '' COMMENT '收货人邮编',
                'receiver_name' => $receiver_name, // varchar(50) NOT NULL DEFAULT '' COMMENT '收货人姓名',
             //   'shop_id' => 0 $shop_id, // int(11) NOT NULL COMMENT '卖家店铺id',
             //   'shop_name' => $shop_name, // varchar(100) NOT NULL DEFAULT '' COMMENT '卖家店铺名称',
                'goods_money' => $goods_money, // decimal(19, 2) NOT NULL COMMENT '商品总价',
                'tax_money' => $tax_money, // 税费
                'order_money' => $order_money, // decimal(10, 2) NOT NULL COMMENT '订单总价',
                'point' => $point, // int(11) NOT NULL COMMENT '订单消耗积分',
                'point_money' => $point_money, // decimal(10, 2) NOT NULL COMMENT '订单消耗积分抵多少钱',
                'coupon_money' => $coupon_money, // _money decimal(10, 2) NOT NULL COMMENT '订单代金券支付金额',
                'coupon_id' => $coupon_id, // int(11) NOT NULL COMMENT '订单代金券id',
                'user_money' => $user_money, // decimal(10, 2) NOT NULL COMMENT '订单预存款支付金额',
                'promotion_money' => $promotion_money, // decimal(10, 2) NOT NULL COMMENT '订单优惠活动金额',
                'shipping_money' => $deliver_price, // decimal(10, 2) NOT NULL COMMENT '订单运费',
                'pay_money' => $pay_money, // decimal(10, 2) NOT NULL COMMENT '订单实付金额',
                'refund_money' => 0, // decimal(10, 2) NOT NULL COMMENT '订单退款金额',
                'give_point' => $give_point, // int(11) NOT NULL COMMENT '订单赠送积分',
                'order_status' => $order_status, // tinyint(4) NOT NULL COMMENT '订单状态',
                'pay_status' => $pay_status, // tinyint(4) NOT NULL COMMENT '订单付款状态',
                'shipping_status' => 0, // tinyint(4) NOT NULL COMMENT '订单配送状态',
                'review_status' => 0, // tinyint(4) NOT NULL COMMENT '订单评价状态',
                'feedback_status' => 0, // tinyint(4) NOT NULL COMMENT '订单维权状态',
                'user_platform_money' => $platform_money, // 平台余额支付
                'coin_money' => $coin,
                'create_time' => time(),
                "give_point_type" => $give_point_type,
                'shipping_company_id' => $shipping_company_id
            ); // datetime NOT NULL DEFAULT 'CURRENT_TIMESTAMP' COMMENT '订单创建时间',
            if($pay_status==2){
                $data_order["pay_time"]=time();
            }
            $order = new OrderModel();
            $order->save($data_order);
            $order_id = $order->id;
            $pay = new UnifyPayHandle();
          //  createPayment( $out_trade_no, $pay_body, $pay_detail, $pay_money, $type, $type_alis_id)
          //  createPayment($agent_id, $out_trade_no, $pay_body, $pay_detail, $pay_money, $type, $type_alis_id)
           // createPayment($out_trade_no,$agent_id, $pay_body, $pay_detail, $pay_money, $type, $type_alis_id)
            $config = new ConfigHandle();
            $pay_info_x = $config->getPayInfoConfig(0);
        // //   Log::write("pay_info_x:".$pay_info_x);
            $pay_body = $pay_info_x['value']['pay_body'];
            $pay_details = $pay_info_x['value']['pay_details'];
            if (empty($pay_body)) {
                $pay_body = '订单';
            }
            if (empty($pay_details)) {
                $pay_details = '订单';
            }
            $order_x = new OrderModel();
            $order_info_x = $order_x->get($order_id);
            $pay_details = $pay_details.' 订单号:'.$order_info_x['order_no'].' 购买者:'.$order_info_x['user_name'];

            Log::write("pay_body:".$pay_body."  pay_details:".$pay_details);
            $pay->createPayment( $out_trade_no, $buyer_info['agent_id'], $pay_body,  $pay_details, $pay_money, 1, $order_id);

          //  $pay->createPayment( $out_trade_no, $buyer_info['agent_id'], "订单",  "订单", $pay_money, 1, $order_id);

           // createPayment($out_trade_no,$agent_id, $pay_body, $pay_detail, $pay_money, $type, $type_alis_id)

            // 如果是订单自提需要添加自提相关信息
            if ($shipping_type == 2) {
                if (! empty($pick_up_id)) {
                    $pickup_model = new PickupPointModel();
                    $pickup_point_info = $pickup_model->getInfo([
                        'id' => $pick_up_id
                    ], '*');
                    $order_pick_up_model = new OrderPickupModel();
                    $data_pickup = array(
                        'order_id' => $order_id,
                        'name' => $pickup_point_info['name'],
                        'address' => $pickup_point_info['address'],
                        'contact' => $pickup_point_info['address'],
                        'phone' => $pickup_point_info['phone'],
                        'city_id' => $pickup_point_info['city_id'],
                        'province_id' => $pickup_point_info['province_id'],
                        'district_id' => $pickup_point_info['district_id'],
                        'supplier_id' => $pickup_point_info['supplier_id'],
                        'longitude' => $pickup_point_info['longitude'],
                        'latitude' => $pickup_point_info['latitude'],
                        'create_time' => time()
                    );
                    $order_pick_up_model->save($data_pickup);
                }
            }
            // 满额包邮活动
            if (! empty($full_mail_array)) {
                $order_promotion_details = new OrderPromotionDetailsModel();
                $data_promotion_details = array(
                    'order_id' => $order_id,
                    'promotion_id' => $full_mail_array["promotion_id"],
                    'promotion_type_id' => 2,
                    'promotion_type' => $full_mail_array["promotion_type"],
                    'promotion_name' => $full_mail_array["promotion_name"],
                    'promotion_condition' => $full_mail_array["promotion_condition"],
                    'discount_money' => $full_mail_array["discount_money"],
                    'used_time' => time()
                );
                $order_promotion_details->save($data_promotion_details);
            }
            // 满减送详情，添加满减送活动优惠情况
            if (! empty($mansong_rule_array)) {
                
                $mansong_rule_array = array_unique($mansong_rule_array);
                foreach ($mansong_rule_array as $k_mansong_rule => $v_mansong_rule) {
                    $order_promotion_details = new OrderPromotionDetailsModel();
                    $data_promotion_details = array(
                        'order_id' => $order_id,
                        'promotion_id' => $v_mansong_rule['id'],
                        'promotion_type_id' => 1,
                        'promotion_type' => 'MANJIAN',
                        'promotion_name' => '满减送活动',
                        'promotion_condition' => '满' . $v_mansong_rule['price'] . '元，减' . $v_mansong_rule['discount'],
                        'discount_money' => $v_mansong_rule['discount'],
                        'used_time' => time()
                    );
                    $order_promotion_details->save($data_promotion_details);
                }
                // 添加到对应商品项优惠满减
                if (! empty($mansong_discount_array)) {
                    foreach ($mansong_discount_array as $k => $v) {
                        $order_goods_promotion_details = new OrderGoodsPromotionDetailsModel();
                        $data_details = array(
                            'order_id' => $order_id,
                            'promotion_id' => $v[3],
                            'goods_id' => $v[0],
                            'sku_id' => $v[1],
                            'promotion_type' => 'MANJIAN',
                            'discount_money' => $v[2],
                            'used_time' => time()
                        );
                        $order_goods_promotion_details->save($data_details);
                    }
                }
            }
            // 添加到对应商品项优惠优惠券使用详情
            if ($coupon_id > 0) {
               // $coupon_details_array = $order_goods_preference->getGoodsCouponPromoteDetail($coupon_id, $coupon_money, $goods_sku_list);
              //  getGoodsCouponPromoteDetailByGoodsList($user_id, $coupon_id, $coupon_money, $goods_list)
                 $coupon_details_array = $order_goods_preference->getGoodsCouponPromoteDetailByGoodsList($user_id,$coupon_id, $coupon_money, $goods_list);
                foreach ($coupon_details_array as $k => $v) {
                    $order_goods_promotion_details = new OrderGoodsPromotionDetailsModel();
                    $data_details = array(
                        'order_id' => $order_id,
                        'promotion_id' => $coupon_id,
                        'goods_id' => $v['goods_id'],
                        'sku_id' => $v['sku_id'],
                        'promotion_type' => 'COUPON',
                        'discount_money' => $v['money'],
                        'used_time' => time()
                    );
                    $order_goods_promotion_details->save($data_details);
                }
            }
            // 使用积分
            if ($point > 0) {
            //    addMemberAccountData($account_type, $user_id, $sign, $number, $from_type, $data_id,$text)
               // addMemberAccountData($account_type, $user_id, $sign, $number, $from_type, $data_id,$text,$operation_id=0)
                $retval_point = $account_flow->addMemberAccountData( 1, $user_id, 0, $point * (- 1), 1, $order_id, '商城订单',10);
                if (empty($retval_point)) {
                    $this->rollback();
                    $this->error =$account_flow->getError();
                    return false;
                 //   return  -1;   //ORDER_CREATE_LOW_POINT;
                }
            }
            if ($coin > 0) {
                $retval_point = $account_flow->addMemberAccountData( 3, $user_id, 0, $coin * (- 1), 1, $order_id, '商城订单',11);
                if (empty($retval_point)) {
                    $this->rollback();
                    $this->error =$account_flow->getError();
                    return false;
                  //  return -1; //LOW_COIN;
                }
            }
            if ($user_money > 0) {
                $retval_user_money = $account_flow->addMemberAccountData(2, $user_id, 0, $user_money * (- 1), 1, $order_id, '商城订单',12);
                if (empty($retval_user_money)) {
                    $this->rollback();
                    $this->error =$account_flow->getError();
                    return false;
                    //return -1;
                    //return ORDER_CREATE_LOW_USER_MONEY;
                }
            }
            if ($platform_money > 0) {
                $retval_platform_money = $account_flow->addMemberAccountData( 2, $user_id, 0, $platform_money * (- 1), 1, $order_id, '商城订单',12);
                if (empty($retval_platform_money)) {
                    $this->rollback();
                    $this->error =$account_flow->getError();
                    return false;
                  //  return -1;

                   // return ORDER_CREATE_LOW_PLATFORM_MONEY;
                }
            }
            // 使用优惠券
            if ($coupon_id > 0) {
                $retval = $coupon->useCoupon($user_id, $coupon_id, $order_id);
                if (empty($retval)) {
                    $this->rollback();
                    $this->error="使用优惠券失败";
                    return false;
                }
            }
           //增加订单数
            $retval_order_sum = $account_flow->addMemberAccountData( 4, $user_id, 1, 1, 1, $order_id, '商城订单',13);
            if (empty($retval_order_sum)) {
                $this->rollback();
                $this->error =$account_flow->getError();
                return false;
                //  return -1;

                // return ORDER_CREATE_LOW_PLATFORM_MONEY;
            }
            // 添加订单项
            $order_goods = new OrderGoodsHandle(); //wu
          //  addOrderGoods($user_id,$order_id, $goods_list,  $adjust_money = 0)
           // $res_order_goods = $order_goods->addOrderGoods($order_id, $goods_sku_list);
             $res_order_goods = $order_goods->addOrderGoods($user_id, $order_id, $goods_list);
            if ($res_order_goods === false) {
                $this->error = $order_goods->getError();
                $this->rollback();
                return false;
            }
            if (! ($res_order_goods > 0)) {
                $this->error = $order_goods->getError();
                $this->rollback();
                //return $res_order_goods;
                return false;
            }
            $this->addOrderAction($order_id, $user_id, '创建订单', $user_type);
          
           $this->commit();
            return $order_id;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 订单支付--ok-2ok
     *
     * @param  $order_pay_no            
     * @param  $pay_type(10:线下支付)            
     * @param  $status
     *            0:订单支付完成 1：订单交易完成
     */
    public function orderPay($user_id, $user_type, $order_pay_no, $pay_type, $status)
    {
        $this->startTrans();
        try {
            // 改变订单状态
            $this->order->where([
                'out_trade_no' => $order_pay_no
            ])->select();
            
            // 添加订单日志
            // 可能是多个订单
            $order_id_array = $this->order->where([
                'out_trade_no' => $order_pay_no
            ])->column('id');
            foreach ($order_id_array as $k => $order_id) {
                // 赠送赠品
                $uid = $this->order->getInfo([
                    'id' => $order_id
                ], 'buyer_id,pay_money');
                if ($pay_type == 10) {
                    // 线下支付
                    $res = $this->addOrderAction($order_id, $user_id, '线下支付', $user_type);
                } else {
                    // 查询订单购买人ID
                    
                    $res = $this->addOrderAction($order_id, $uid['buyer_id'], '订单支付', $user_type);
                }
                if (empty($res)) {
                    $this->rollback();
                    Log::write('this->addOrderAction');
                    return false;
                }
                // 增加会员累计消费
                $account = new MemberAccountHandle();
               // addMmemberConsum($user_id, $consum)
             //   $account->addMmemberConsum(0, $uid['buyer_id'], $uid['pay_money']);
                $res = $account->addMmemberConsum($uid['buyer_id'], $uid['pay_money']);
                if (empty($res)) {
                    $this->rollback();
                    $this->error = $account->getError();
                    return false;
                }
                // 修改订单状态
                $data = array(
                    'payment_type' => $pay_type,
                    'pay_status' => 2,
                    'pay_time' => time(),
                    'order_status' => 1
                ); // 订单转为待发货状态
                
                $order = new OrderModel();
                $res = $order->save($data, [
                    'id' => $order_id
                ]);
                if ($res === false) {
                    $this->rollback();
                    Log::write('order->save 出错');
                    return false;
                }
                if ($status == 1) {
                 //   orderComplete($user_id, $user_type, $orderid)
                    $res = $this->orderComplete($user_id, $user_type,$order_id);
                    if (empty($res)) {
                        $this->rollback();
                        return false;
                    }
                    // 执行订单交易完成
                }
            }
           $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            Log::write("订单支付出错".$e->getMessage());
            $this->error = "订单支付出错".$e->getMessage();
            return false;
        }
    }

    /**
     * 添加订单操作日志--ok-2okkk
     */
    public function addOrderAction($order_id, $user_id, $action_text, $user_type=1)
    {
        $this->startTrans();
        try {
            $order_status = $this->order->getInfo([
                'id' => $order_id
            ], 'order_status');
            if ($user_type == 1) {
                if ($user_id != 0) {
                    $user = new MemberUserModel();
                    $user_name = $user->getInfo([
                        'id' => $user_id
                    ], 'login_phone');
                    // $action_name = $user_name['nick_name'];
                    $action_name = $user_name['login_phone'];
                } else {
                    $action_name = 'system';
                }
            }else if ($user_type == 2) {
                $action_name =  PlatformUserHandle::loginUserName(); // 'platform';
            } else if ($user_type == 3) {
                $action_name = AgentUserHandle::loginUserName();
            } else if ($user_type == 4) {
                $action_name = 'system';
            } else {
                $action_name = 'other';
            }
            
            $data_log = array(
                'order_id' => $order_id,
                'action' => $action_text,
                'user_id' => $user_id,
                'user_name' => $action_name,
                'user_type'=>$user_type,
                'order_status' => $order_status['order_status'],
                'order_status_text' => $this->getOrderStatusName($order_id),
                'action_time' => time()
            );
            $order_action = new OrderActionModel();
            $res = $order_action->save($data_log);
            if (empty($res)) {
                $this->rollback();
                Log::write('order_action->save 出错');
                return false;
            }
           $this->commit();
            return true;

            //$order_action->id;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }



    /**
     * 获取订单当前状态 名称-ok
     */
    public function getOrderStatusName($order_id)
    {
        $order_status = $this->order->getInfo([
            'id' => $order_id
        ], 'order_status');
        $status_array = OrderStatusHandle::getOrderCommonStatus();
        foreach ($status_array as $k => $v) {
            if ($v['status_id'] == $order_status['order_status']) {
                return $v['status_name'];
            }
        }
        return false;
    }

    /**
     * 通过店铺id 得到订单的订单号-ok
     */
    public function createOrderNo($shop_id=0)
    {
        $time_str = date('YmdHis');
        $order_model = new OrderModel();
        $order_list = $order_model->getConditionQuery([
            //"shop_id" => $shop_id
        ], "order_no", "create_time DESC");
        $num = 0;
        if (! empty($order_list)) {
            $order_obj = $order_list[0];
            $order_no_max = $order_obj["order_no"];
            if (empty($order_no_max)) {
                $num = 1;
            } else {
                if (substr($time_str, 0, 8) == substr($order_no_max, 0, 8)) {
                    $max_no = substr($order_no_max, 14, 9);
                    $num = str_replace("0", "", $max_no) + 1;
                } else {
                    $num = 1;
                }
            }
        } else {
            $num = 1;
        }
        $order_no = $time_str . sprintf("%09d", $num);
        return $order_no;
    }

    /**
     * 创建订单支付编号-ok
     */
    public function createOutTradeNo()
    {
        $pay_no = new UnifyPayHandle();
        return $pay_no->createOutTradeNo();
    }

    /**
     * 订单重新生成支付编号-ok
     */
    public function createNewOutTradeNo($orderid)
    {
        $order = new OrderModel();
        $new_no = $this->createOutTradeNo();
        $data = array(
            'out_trade_no' => $new_no
        );
        $retval = $order->save($data, [
            'id' => $orderid
        ]);
        if ($retval) {
            return $new_no;
        } else {
            return '';
        }
    }

    /**ok-2ok
     * 订单发货(整体发货)(不考虑订单项)--ok
     */
    public function orderDoDelivery($user_id, $user_type, $orderid)
    {
        $this->startTrans();
        try {
            $order_item = new OrderGoodsModel();
            $count = $order_item->where([
                'order_id' => $orderid,
                'shipping_status' => 0
            ])->count();
            if ($count == 0) {
                $data_delivery = array(
                    'shipping_status' => 1,
                    'order_status' => 2,    //已发货
                    'consign_time' => time()
                );
                $order_model = new OrderModel();
                $order_model->save($data_delivery, [
                    'id' => $orderid
                ]);
                $this->addOrderAction($orderid, $user_id, '订单发货', $user_type);
            }
            
           $this->commit();
            return true;
        } catch (\Exception $e) {
            
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
           // return $e->getMessage();
        }
    }

    /**
     * 订单收货--ok-2ok
     */
    public function orderTakeDelivery($user_id,$user_type, $orderid)
    {
        $this->startTrans();
        try {
            $data_take_delivery = array(
                'shipping_status' => 2,
                'order_status' => 3,
                'sign_time' => time()
            );
            $order_model = new OrderModel();
            $res = $order_model->save($data_take_delivery, [
                'id' => $orderid
            ]);
            if ($res === false) {
                $this->rollback();
                Log::write('order_model->save 出错');
                return false;
            }

            $res = $this->addOrderAction($orderid, $user_id, '订单收货', $user_type);

            if (empty($res)) {
                $this->rollback();
                Log::write('this->addOrderAction 出错');
                return false;
            }

            // 判断是否需要在本阶段赠送积分
            $this->giveGoodsOrderPoint($orderid, 2);
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 订单自动收货--ok
     */
    public function orderAutoDelivery($orderid)
    {
        $this->startTrans();
        try {
            $data_take_delivery = array(
                'shipping_status' => 2,
                'order_status' => 3,
                'sign_time' => time()
            );
            $order_model = new OrderModel();
            $order_model->save($data_take_delivery, [
                'id' => $orderid
            ]);
            
            $this->addOrderAction($orderid, 0, '订单自动收货',4);

            // 判断是否需要在本阶段赠送积分
            $this->giveGoodsOrderPoint($orderid, 2);
           $this->commit();
            return 1;
        } catch (\Exception $e) {
            
            $this->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 执行订单交易完成--ok-2ok
     */
    public function orderComplete($user_id, $user_type, $orderid)
    {
        $this->startTrans();
        try {
            $data_complete = array(
                'order_status' => 4,
                "finish_time" => time()
            );
            $order_model = new OrderModel();
            $res = $order_model->save($data_complete, [
                'id' => $orderid
            ]);
            if ($res === false) {
                $this->rollback();
                Log::write('order_model->save 出错');
                return false;
            }
            $res = $this->addOrderAction($orderid, $user_id, '交易完成', $user_type);
            if (empty($res)) {
                $this->rollback();
                Log::write('this->addOrderAction 出错');
                return false;
            }
            // $this->calculateOrderGivePoint($orderid);
            $this->calculateOrderMansong($orderid);
            // 判断是否需要在本阶段赠送积分
            $this->giveGoodsOrderPoint($orderid, 1);
           $this->commit();
           // return 1;
            return true;
        } catch (\Exception $e) {
            
            $this->rollback();
            $this->error=$e->getMessage();
            return false;
        }
    }

    /**
     * 统计订单完成后赠送用户积分-ok
     */
    private function calculateOrderGivePoint($order_id)
    {
        $point = $this->order->getInfo([
            'id' => $order_id
        ], 'give_point,buyer_id');
        $member_account = new MemberAccountHandle();
      //  addMemberAccountData($account_type, $user_id, $sign, $number, $from_type, $data_id,$text)
        //$member_account->addMemberAccountData($point['shop_id'], 1, $point['buyer_id'], 1, $point['give_point'], 1, $order_id, '订单商品赠送积分');
        $member_account->addMemberAccountData( 1, $point['buyer_id'], 1, $point['give_point'], 1, $order_id, '订单商品赠送积分');

    }

    /**
     * 订单完成后统计满减送赠送--ok
     */
    private function calculateOrderMansong($order_id)
    {
        $order_info = $this->order->getInfo([
            'id' => $order_id
        ], 'buyer_id');
        $order_promotion_details = new OrderPromotionDetailsModel();
        // 查询满减送活动规则
        $list = $order_promotion_details->getConditionQuery([
            'order_id' => $order_id,
            'promotion_type_id' => 1
        ], '*', '');
        if (! empty($list)) {
            $promotion_mansong_rule = new PromotionMansongRuleModel();
            foreach ($list as $k => $v) {
                $mansong_data = $promotion_mansong_rule->getInfo([
                    'id' => $v['promotion_id']
                ], '*');
                if (! empty($mansong_data)) {
                    // 满减送赠送积分
                    if ($mansong_data['give_point'] != 0) {
                        $member_account = new MemberAccountHandle();
                     //   addMemberAccountData($account_type, $user_id, $sign, $number, $from_type, $data_id,$text)
                     //   $member_account->addMemberAccountData($order_info['shop_id'], 1, $order_info['buyer_id'], 1, $mansong_data['give_point'], 1, $order_id, '订单满减送赠送积分');
                        $member_account->addMemberAccountData( 1, $order_info['buyer_id'], 1, $mansong_data['give_point'], 1, $order_id, '订单满减送赠送积分');

                    }
                    // 满减送赠送优惠券--用户获取优惠券
                    if ($mansong_data['give_coupon_id'] != 0) {
                        $member_coupon = new MemberCouponHandle();
                       // userAchieveCoupon($user_id, $coupon_type_id, $get_type)
                        $member_coupon->UserAchieveCoupon($order_info['buyer_id'], $mansong_data['give_coupon_id'], 1);
                    }
                }
            }
        }
    }

    /**
     * 订单执行交易关闭-ok-ookk-2ok
     */
    public function orderClose($user_id, $user_type, $orderid)
    {
        $this->startTrans();
        try {
            $data_close = array(
                'order_status' => 5
            );
            $order_model = new OrderModel();
            $res = $order_model->save($data_close, [
                'id' => $orderid
            ]);
            if ($res === false) {
                $this->rollback();
                Log::write('order_model->save 出错');
                return false;
            }
            $order_info = $this->order->getInfo([
                'id' => $orderid
            ], 'pay_status,point, coupon_id, user_money, buyer_id,user_platform_money, coin_money');
            // 积分返还
            $account_flow = new MemberAccountHandle();
            if ($order_info['point'] > 0) {
              //  addMemberAccountData($account_type, $user_id, $sign, $number, $from_type, $data_id,$text)
              //  addMemberAccountData($account_type, $user_id, $sign, $number, $from_type, $data_id,$text,$operation_id=0)
                $res = $account_flow->addMemberAccountData( 1, $order_info['buyer_id'], 1, $order_info['point'], 2, $orderid, '订单关闭返还积分',7);
                if (empty($res)) {
                    $this->rollback();
                    $this->error = $account_flow->getError();
                    return false;
                }
            }
            /*
            if ($order_info['coin_money'] > 0) {
                $coin_convert_rate = $account_flow->getCoinConvertRate();
                $account_flow->addMemberAccountData( 3, $order_info['buyer_id'], 1, $order_info['coin_money'] / $coin_convert_rate, 2, $orderid, '订单关闭返还购物币',8);
            }
            */
            // 会员余额返还
            
            if ($order_info['user_money'] > 0) {
               $res =  $account_flow->addMemberAccountData( 2, $order_info['buyer_id'], 1, $order_info['user_money'], 2, $orderid, '订单关闭返还用户余额',9);
                if (empty($res)) {
                    $this->rollback();
                    $this->error = $account_flow->getError();
                    return false;
                }
            }
            // 平台余额返还
            
            if ($order_info['user_platform_money'] > 0) {
               $res =  $account_flow->addMemberAccountData( 2, $order_info['buyer_id'], 1, $order_info['user_platform_money'], 2, $orderid, '商城订单关闭返还平台余额',9);
                if (empty($res)) {
                    $this->rollback();
                    $this->error = $account_flow->getError();
                    return false;
                }
            }
            // 优惠券返还
            $coupon = new MemberCouponHandle();
            if ($order_info['coupon_id'] > 0) {
              //  userReturnCoupon($coupon_id)
               $res = $coupon->UserReturnCoupon($order_info['coupon_id']);
                if (empty($res)) {
                    $this->rollback();
                    $this->error = $coupon->getError();
                    return false;
                }
            }
          //  addMemberAccountData($account_type, $user_id, $sign, $number, $from_type, $data_id,$text,$operation_id=0)
            //减少有效订单
            $res =  $account_flow->addMemberAccountData( 4, $order_info['buyer_id'], 0, -1, 2, $orderid, '商城订单关闭减少有效订单数',14);
            if (empty($res)) {
                $this->rollback();
                $this->error = $account_flow->getError();
                return false;
            }

            // 退回库存
            $order_goods = new OrderGoodsModel();
            $order_goods_list = $order_goods->getConditionQuery([
                'id' => $orderid
            ], '*', '');
            foreach ($order_goods_list as $k => $v) {
                $return_stock = 0;
                $goods_model = new GoodsModel();
                $goods_info  = $goods_model->getInfo([
                    'id' => $v['goods_id']
                ], 'id, total');
                $stock = $goods_info['total'];

                if ($v['sku_id'] > 0) {
                    $goods_sku_model = new GoodsSkuModel();
                    $goods_sku_info = $goods_sku_model->getInfo([
                        'id' => $v['sku_id']
                    ], 'goods_id, stock');
                    $stock = $goods_sku_info['stock'];
                }

                if ($v['shipping_status'] != 1) {
                    // 卖家未发货
                    $return_stock = 1;
                } else {
                    // 卖家已发货,买家不退货
                    if ($v['refund_type'] == 1) {
                        $return_stock = 0;
                    } else {
                        $return_stock = 1;
                    }
                }
                // 退货返回库存
                if ($return_stock == 1) {
                    if ( $v['sku_id'] > 0) {
                        $data_goods_sku = array(
                            'stock' => $goods_sku_info['stock'] + $v['num']
                        );
                        $goods_sku_model->save($data_goods_sku, [
                            'id' => $v['sku_id']
                        ]);
                        $count = $goods_sku_model->getSum(['goods_id' => $goods_sku_info['goods_id']], 'stock');
                    } else {
                        $data_goods = array(
                            'total' => $goods_info['total'] + $v['num']
                        );
                        $goods_model->save($data_goods, [
                            'id' => $v['goods_id']
                        ]);
                        $count = $goods_model->getSum(['id' =>  $v['goods_id']], 'total');
                    }
                    //商品库存增加
                    $goods_model = new GoodsModel();
                    $goods_model->save(['total' => $count], ["id"=>$v['goods_id']]);
                }
            }
            $this->addOrderAction($orderid, $user_id, '交易关闭', $user_type);
           $this->commit();
           // return 1;
            return true;
        } catch (\Exception $e) {
            Log::write($e->getMessage());
            $this->rollback();
            $this->error =  $e->getMessage();
            return false;
        }
    }

    /**2ok
     * 订单状态变更-ok-2ok
     */
    public function orderGoodsRefundFinish($user_id, $user_type, $order_id)
    {
        $orderInfo = OrderModel::get($order_id);
        $this->startTrans();
        try {
            
            $total_count = OrderGoodsModel::where("order_id=$order_id")->count();
            $refunding_count = OrderGoodsModel::where("order_id=$order_id AND refund_status<>0 AND refund_status<>5 AND refund_status>0")->count();
            $refunded_count = OrderGoodsModel::where("order_id=$order_id AND refund_status=5")->count();
            
            $shipping_status = $orderInfo->shipping_status;
            $all_refund = 0;
            if ($refunding_count > 0) {
                
                $orderInfo->order_status = OrderStatusHandle::getOrderCommonStatus()[6]['status_id']; // 退款中
            } elseif ($refunded_count == $total_count) {
                
                $all_refund = 1;
            } elseif ($shipping_status == OrderStatusHandle::getShippingStatus()[0]['shipping_status']) {
                
                $orderInfo->order_status = OrderStatusHandle::getOrderCommonStatus()[1]['status_id']; // 待发货
            } elseif ($shipping_status == OrderStatusHandle::getShippingStatus()[1]['shipping_status']) {
                
                $orderInfo->order_status = OrderStatusHandle::getOrderCommonStatus()[2]['status_id']; // 已发货
            } elseif ($shipping_status == OrderStatusHandle::getShippingStatus()[2]['shipping_status']) {
                
                $orderInfo->order_status = OrderStatusHandle::getOrderCommonStatus()[3]['status_id']; // 已收货
            }
            
            // 订单恢复正常操作
            if ($all_refund == 0) {
                $retval = $orderInfo->save();
                if ($retval === false) {
                    $this->rollback();
                    Log::write('orderInfo->save 出错');
                    return false;
                }
            } else {
                // 全部退款订单转化为交易关闭
              //  orderClose($user_id, $user_type, $orderid)
                $retval = $this->orderClose($user_id, $user_type, $order_id);
                if (empty($retval)) {
                    $this->rollback();
                    Log::write('this->orderClose 出错');
                    return false;
                }
            }
            
            $this->commit();
            return true;
           // return $retval;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
           // return $e->getMessage();
        }
        
      //  return $retval;
    }

    /**
     * 获取订单详情--ok
     */
    public function getDetail($order_id)
    {
        // 查询主表
        $order_detail = $this->order->get($order_id);
        if(empty($order_detail)){
            return array();
        }
        // 发票信息
        $temp_array = array();
        if ($order_detail["buyer_invoice"] != "") {
            $temp_array = explode("$", $order_detail["buyer_invoice"]);
        }
        $order_detail["buyer_invoice_info"] = $temp_array;
        if (empty($order_detail)) {
            return '';
        }
        $order_detail['payment_type_name'] = OrderStatusHandle::getPayType($order_detail['payment_type']);
        $express_company_name="";
        if ($order_detail['shipping_type'] == 1) {
            $order_detail['shipping_type_name'] = '商家配送';
            $express_company=new OrderExpressCompanyModel();
            
            $express_obj=$express_company->getInfo(["id"=>$order_detail["shipping_company_id"]], "company_name");
            if(!empty($express_obj["company_name"])){
                $express_company_name=$express_obj["company_name"];
            }
        } elseif ($order_detail['shipping_type'] == 2) {
            $order_detail['shipping_type_name'] = '门店自提';
        } else {
            $order_detail['shipping_type_name'] = '';
        }
        $order_detail["shipping_company_name"]=$express_company_name;
        // 查询订单项表
        $order_detail['order_goods'] = $this->getOrderGoods($order_id);
        if ($order_detail['payment_type'] == 6 || $order_detail['shipping_type'] == 2) {
            $order_status = OrderStatusHandle::getSinceOrderStatus();
        } else {
            // 查询操作项
            $order_status = OrderStatusHandle::getOrderCommonStatus();
        }
        // 查询订单提货信息表
        if ($order_detail['shipping_type'] == 2) {
            $order_pickup_model = new OrderPickupModel();
            $order_pickup_info = $order_pickup_model->getInfo([
                'order_id' => $order_id
            ], '*');
            $address = new AddressHandle();
            $order_pickup_info['province_name'] = $address->getProvinceName($order_pickup_info['province_id']);
            $order_pickup_info['city_name'] = $address->getCityName($order_pickup_info['city_id']);
            $order_pickup_info['dictrict_name'] = $address->getDistrictName($order_pickup_info['district_id']);
            $order_detail['order_pickup'] = $order_pickup_info;
        } else {
            $order_detail['order_pickup'] = '';
        }
        // 查询订单操作
        foreach ($order_status as $k_status => $v_status) {
            
            if ($v_status['status_id'] == $order_detail['order_status']) {
                $order_detail['operation'] = $v_status['operation'];
                $order_detail['status_name'] = $v_status['status_name'];
            }
        }
        // 查询订单操作日志
        $order_action = new OrderActionModel();
        $order_action_log = $order_action->getConditionQuery([
            'order_id' => $order_id
        ], '*', 'action_time desc');
        $order_detail['order_action'] = $order_action_log;
        
        $address_service = new AddressHandle();
        $order_detail['address'] = $address_service->getAddress($order_detail['receiver_province'], $order_detail['receiver_city'], $order_detail['receiver_district']);
        $order_detail['address'] .= $order_detail["receiver_address"];
        return $order_detail;
    }

    /**
     * 查询订单的订单项列表-ok
     */
    public function getOrderGoods($order_id)
    {
        $order_goods = new OrderGoodsModel();
        $order_goods_list = $order_goods->all([
            'order_id' => $order_id
        ]);
        foreach ($order_goods_list as $k => $v) {
            $order_goods_list[$k]['express_info'] = $this->getOrderGoodsExpress($v['id']);
            $shipping_status_array = OrderStatusHandle::getShippingStatus();
            foreach ($shipping_status_array as $k_status => $v_status) {
                if ($v['shipping_status'] == $v_status['shipping_status']) {
                    $order_goods_list[$k]['shipping_status_name'] = $v_status['status_name'];
                }
            }
            // 商品图片
            $picture = new AlbumPictureModel();
            $picture_info = $picture->get($v['goods_picture']);
            $order_goods_list[$k]['picture_info'] = $picture_info;
            if ($v['refund_status'] != 0) {
                $order_refund_status = OrderStatusHandle::getRefundStatus();
                foreach ($order_refund_status as $k_status => $v_status) {
                    
                    if ($v_status['status_id'] == $v['refund_status']) {
                        $order_goods_list[$k]['refund_operation'] = $v_status['refund_operation'];
                        $order_goods_list[$k]['status_name'] = $v_status['status_name'];
                    }
                }
            } else {
                $order_goods_list[$k]['refund_operation'] = '';
                $order_goods_list[$k]['status_name'] = '';
            }
        }
        return $order_goods_list;
    }

    /**
     * 获取订单的物流信息-ok
     */
    public function getOrderExpress($order_id)
    {
        $order_goods_express = new OrderGoodsExpressModel();
        $order_express_list = $order_goods_express->all([
            'order_id' => $order_id
        ]);
        return $order_express_list;
    }

    /**
     * 获取订单项的物流信息-ok
    */
    private function getOrderGoodsExpress($order_goods_id)
    {
        $order_goods = new OrderGoodsModel();
        $order_goods_info = $order_goods->getInfo([
            'id' => $order_goods_id
        ], 'order_id,shipping_status');
        if ($order_goods_info['shipping_status'] == 0) {
            return array();
        } else {
            $order_express_list = $this->getOrderExpress($order_goods_info['order_id']);
            foreach ($order_express_list as $k => $v) {
                $order_goods_id_array = explode(",", $v['order_goods_id_array']);
                if (in_array($order_goods_id, $order_goods_id_array)) {
                    return $v;
                }
            }
            return array();
        }
    }

    /**
     * 订单价格调整-ok-2ok
     *
     * @param  $order_id            
     * @param  $goods_money
     *            调整后的商品总价
     * @param  $shipping_fee
     *            调整后的运费
     */
    public function orderAdjustMoney($user_id, $user_type, $order_id, $goods_money, $shipping_fee)
    {
        $this->startTrans();
        try {
            $order_model = new OrderModel();
            $order_info = $order_model->get($order_id);
            // 商品金额差额
            $goods_money_adjust = $goods_money - $order_info['goods_money'];
            $shipping_fee_adjust = $shipping_fee - $order_info['shipping_money'];
            $order_money = $order_info['order_money'] + $goods_money_adjust + $shipping_fee_adjust;
            $pay_money = $order_info['pay_money'] + $goods_money_adjust + $shipping_fee_adjust;
            $data = array(
                'goods_money' => $goods_money,
                'order_money' => $order_money,
                'shipping_money' => $shipping_fee,
                'pay_money' => $pay_money
            );
            $retval = $order_model->save($data, [
                'id' => $order_id
            ]);
            if ($retval === false) {
                $this->rollback();
                Log::write('order_model->save 出错');
                return false;
            }
            $res = $this->addOrderAction($order_id, $user_id, '调整金额',$user_type);
            if (empty($res)) {
                $this->rollback();
                Log::write('this->addOrderAction 出错');
                return false;
            }
           $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 获取订单整体商品金额(根据订单项)-ok
     */
    public function getOrderGoodsMoney($order_id)
    {
        $order_goods = new OrderGoodsModel();
        $money = $order_goods->where([
            'order_id' => $order_id
        ])->sum('goods_money');
        if (empty($money)) {
            $money = 0;
        }
        return $money;
    }

    /**
     * 获取订单赠品-ok
     */
    public function getOrderPromotionGift($order_id)
    {
        $gift_list = array();
        $order_promotion_details = new OrderPromotionDetailsModel();
        $promotion_list = $order_promotion_details->getConditionQuery([
            'order_id' => $order_id,
            'promotion_type_id' => 1
        ], 'promotion_id', '');
        if (! empty($promotion_list)) {
            foreach ($promotion_list as $k => $v) {
                $rule = new PromotionMansongRuleModel();
                $gift = $rule->getInfo([
                    'id' => $v['promotion_id']
                ], 'gift_id');
                $gift_list[] = $gift['gift_id'];
            }
        }
        return $gift_list;
    }

    /**
     * 获取具体订单项信息-ok
     *
     */
    public function getOrderGoodsInfo($order_goods_id)
    {
        $order_goods = new OrderGoodsModel();
        return $order_goods->getInfo([
            'id' => $order_goods_id
        ], 'goods_id,goods_name,goods_money,goods_picture,shop_id');
    }

    /**
     * 通过订单id 得到该订单的实际支付金额-ok
     */
    public function getOrderRealPayMoney($order_id)
    {
        $order_goods_model = new OrderGoodsModel();
        // 查询订单的所有的订单项
        $order_goods_list = $order_goods_model->getConditionQuery([
            "order_id" => $order_id
        ], "*", "");
        $order_real_money = 0;
        if (! empty($order_goods_list)) {
            $order_goods_promotion = new OrderGoodsPromotionDetailsModel();
            foreach ($order_goods_list as $k => $order_goods) {
                $promotion_money = $order_goods_promotion->where([
                    'order_id' => $order_id,
                    'goods_id' => $order_goods['goods_id'],
                    'sku_id' => $order_goods['sku_id']
                ])->sum('discount_money');
                if (empty($promotion_money)) {
                    $promotion_money = 0;
                }
                // 订单项的真实付款金额
                $order_goods_real_money = $order_goods['goods_money'] + $order_goods['adjust_money'] - $order_goods['refund_real_money'] - $promotion_money;
                // 订单付款金额
                $order_real_money = $order_real_money + $order_goods_real_money;
            }
        }
        return $order_real_money;
    }

    /**
     * 通过订单id 得到该订单的实际结算金额-ok
     */
    public function getOrderRealAccountMoney($order_id)
    {
        $order_goods_model = new OrderGoodsModel();
        // 查询订单的所有的订单项
        $order_goods_list = $order_goods_model->getConditionQuery([
            "order_id" => $order_id
        ], "*", "");
        $order_real_money = 0;
        if (! empty($order_goods_list)) {
            $order_goods_promotion = new OrderGoodsPromotionDetailsModel();
            foreach ($order_goods_list as $k => $order_goods) {
                $promotion_money = $order_goods_promotion->where([
                    'order_id' => $order_id,
                    'goods_id' => $order_goods['goods_id'],
                    'sku_id' => $order_goods['sku_id']
                ])->sum('discount_money');
                if (empty($promotion_money)) {
                    $promotion_money = 0;
                }
                // 订单项的真实付款金额
              //  $order_goods_real_money = $order_goods['goods_money'] + $order_goods['adjust_money'] - $order_goods['refund_real_money'] - $promotion_money;
                $order_goods_real_money = $order_goods['goods_money']  - $order_goods['refund_real_money'] - $promotion_money;

                // 订单付款金额
                $order_real_money = $order_real_money + $order_goods_real_money;
            }
        }
        $order_model = new OrderModel();
        $order = $order_model->get($order_id);
        $point_money =$order['point_money'];

        $coupon_money = $order['coupon_money'];
        $order_real_money =  $order_real_money - $point_money - $coupon_money;
        if ($order_real_money < 0) {
            $order_real_money = 0;
        }


        return $order_real_money;
    }

    /**
     * 订单提货-ok-2ok
     */
    public function pickupOrder($user_id, $user_type, $order_id, $buyer_name, $buyer_phone, $remark)
    {
        // 订单转为已收货状态
        $this->startTrans();
        try {
            $data_take_delivery = array(
                'shipping_status' => 2,
                'order_status' => 3,
                'sign_time' => time()
            );
            $order_model = new OrderModel();
            $res = $order_model->save($data_take_delivery, [
                'id' => $order_id
            ]);
            if ($res === false) {
                $this->rollback();
                Log::write('order_model->save 出错');
                return false;
            }

           $res =  $this->addOrderAction($order_id, $user_id, '订单提货' . '提货人：' . $buyer_name . ' ' . $buyer_phone, $user_type);
            if (empty($res)) {
                $this->rollback();
                Log::write('this->addOrderAction 出错');
                return false;
            }

            // 记录提货信息
            $order_pickup_model = new OrderPickupModel();
            $data_pickup = array(
                'buyer_name' => $buyer_name,
                'buyer_mobile' => $buyer_phone,
                'remark' => $remark
            );
            $res = $order_pickup_model->save($data_pickup, [
                'order_id' => $order_id
            ]);
            if ($res === false) {
                $this->rollback();
                Log::write('order_pickup_model->save 出错');
                return false;
            }

            $order_goods_model = new OrderGoodsModel();
            $res = $order_goods_model->save([
                'shipping_status' => 2
            ], [
                'order_id' => $order_id
            ]);
            if ($res === false) {
                $this->rollback();
                Log::write('order_goods_model->save 出错');
                return false;
            }
            $this->giveGoodsOrderPoint($order_id, 2);
           $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 订单发放 -ok-2ok
     */
    public function giveGoodsOrderPoint($order_id, $type)
    {
        // 判断是否需要在本阶段赠送积分
        $order_model = new OrderModel();
        $order_info = $order_model->getInfo([
            "id" => $order_id
        ], "*");
        if ($order_info["give_point_type"] == $type) {
            if ($order_info["give_point"] > 0) {
                $member_account = new MemberAccountHandle();
                $text = "";
                $operation_id = 0;
                if ($order_info["give_point_type"] == 1) {
                    $text = "商城订单完成赠送积分";
                    $operation_id = 18;
                } elseif ($order_info["give_point_type"] == 2) {
                    $text = "商城订单完成收货赠送积分";
                    $operation_id = 19;
                } elseif ($order_info["give_point_type"] == 3) {
                    $text = "商城订单完成支付赠送积分";
                    $operation_id = 17;
                }

                $member_account->addMemberAccountData( 1, $order_info['buyer_id'], 1, $order_info['give_point'], 1, $order_id, $text,$operation_id);
            }
        }
    }

    /**
     * ok-2ok
     * 添加订单退款账号记录
     */
    public function addOrderRefundAccountRecords($order_goods_id, $refund_trade_no, $refund_money, $refund_way, $buyer_id, $remark)
    {
        $model = new OrderRefundAccountRecordsModel();

        $data = array(
            'order_goods_id' => $order_goods_id,
            'refund_trade_no' => $refund_trade_no,
            'refund_money' => $refund_money,
            'refund_way' => $refund_way,
            'buyer_id' => $buyer_id,
            'refund_time' => time(),
            'remark' => $remark,
            'create_time'=>time()
        );
        $res = $model->save($data);

        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }


}