<?php
/**
 * OrderHandle.php
 * 处理订单
 * @date : 2017.8.17
 * @version : v1.0.0.0
 */
namespace dao\handle;

//use dao\extend\Kdniao; //快递查询接口
use dao\handle\AddressHandle;
use dao\handle\UnifyPayHandle;
use dao\model\AlbumPicture as AlbumPictureModel;
use dao\model\City as CityModel;
use dao\model\District as DistrictModel;
use dao\handle\member\MemberUserHandle;
use dao\model\Cart as CartModel;
use dao\model\GoodsEvaluate as GoodsEvaluateModel;
use dao\model\Goods as GoodsModel;
use dao\model\ExpressCompany as OrderExpressCompanyModel;
use dao\model\OrderGoodsExpress as OrderGoodsExpressModel;
use dao\model\OrderGoods as OrderGoodsModel;
use dao\model\GoodsSku as GoodsSkuModel;
use dao\model\Order as OrderModel;
use dao\model\OrderShopReturn as OrderShopReturnModel;
//use dao\model\OrderShopReturn as OrderShopReturnModel;
//use dao\model\NsShopModel;
use dao\model\Province as ProvinceModel;
use dao\model\GoodsComment as GoodsCommentModel;
use dao\handle\BaseHandle;
use dao\handle\goodscalculate\GoodsCalculateHandle;
//use dao\handle\NfxCommissionCalculate;
//use dao\handle\NfxUser;
//use dao\handle\niubusiness\NbsBusinessAssistantAccount;
use dao\handle\order\OrderHandle as OrderBusiness;
use dao\handle\order\OrderAccountHandle;
use dao\handle\order\OrderExpressHandle;
use dao\handle\order\OrderGoodsHandle;
use dao\handle\order\OrderStatusHandle;
use dao\handle\promotion\GoodsExpressHandle;
use dao\handle\promotion\GoodsPreferenceHandle;
//use dao\handle\shopaccount\ShopAccount;
use dao\handle\promotion\PromoteRewardRuleHandle;
use dao\handle\AgentHandle as AgentHandle;
use dao\handle\agentaccount\AgentAccountHandle as AgentAccountHandle;
use dao\handle\platformaccount\PlatformAccountHandle as PlatformAccountHandle;
use think\Log;
use dao\model\BaseModel;
use dao\model\Agent as AgentModel;
use dao\extend\Kdniao;
use dao\model\OrderGoodsView as OrderGoodsViewModel;
use dao\model\OrderPayment as OrderPaymentModel;
use dao\handle\pay\AliPay;
use dao\handle\pay\WeiXinPay;
use dao\model\OrderRefundAccountRecords as OrderRefundAccountRecordsModel;

class OrderHandle extends BaseHandle
{

    function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取订单详情--ok-2ok
     */
    public function getOrderDetail($order_id)
    {
        // 查询主表信息
        $order = new OrderBusiness();
        $detail = $order->getDetail($order_id);
        if(empty($detail)){
            return array();
        }
        $detail['pay_status_name'] = $this->getPayStatusInfo($detail['pay_status'])['status_name'];
        $detail['shipping_status_name'] = $this->getShippingInfo($detail['shipping_status'])['status_name'];
        
        $express_list = $this->getOrderGoodsExpressList($order_id);
        // 未发货的订单项
        $order_goods_list = array();
        // 已发货的订单项
        $order_goods_delive = array();
        // 没有配送信息的订单项
        $order_goods_exprss = array();
        foreach ($detail["order_goods"] as $order_goods_obj) {
            $shipping_status = $order_goods_obj["shipping_status"];
            if ($shipping_status == 0) {
                // 未发货
                $order_goods_list[] = $order_goods_obj;
            } else {
                $order_goods_delive[] = $order_goods_obj;
            }
        }
        $detail["order_goods_no_delive"] = $order_goods_list;
        // 没有配送信息的订单项
        if (! empty($order_goods_delive) && count($order_goods_delive) > 0) {
            foreach ($order_goods_delive as $goods_obj) {
                $is_have = false;
                $order_goods_id = $goods_obj["id"];
                foreach ($express_list as $express_obj) {
                    $order_goods_id_array = $express_obj["order_goods_id_array"];
                    $goods_id_str = explode(",", $order_goods_id_array);
                    if (in_array($order_goods_id, $goods_id_str)) {
                        $is_have = true;
                    }
                }
                if (! $is_have) {
                    $order_goods_exprss[] = $goods_obj;
                }
            }
        }
        $goods_packet_list = array();
        if (count($order_goods_exprss) > 0) {
            $packet_obj = array(
                "packet_name" => "无需物流",
                "express_name" => "",
                "express_code" => "",
                "express_id" => 0,
                "is_express" => 0,
                "order_goods_list" => $order_goods_exprss
            );
            $goods_packet_list[] = $packet_obj;
        }
        if (! empty($express_list) && count($express_list) > 0 && count($order_goods_delive) > 0) {
            $packet_num = 1;
            foreach ($express_list as $express_obj) {
                $packet_goods_list = array();
                $order_goods_id_array = $express_obj["order_goods_id_array"];
                $goods_id_str = explode(",", $order_goods_id_array);
                foreach ($order_goods_delive as $delive_obj) {
                    $order_goods_id = $delive_obj["id"];
                    if (in_array($order_goods_id, $goods_id_str)) {
                        $packet_goods_list[] = $delive_obj;
                    }
                }
                $packet_obj = array(
                    "packet_name" => "包裹  + " . $packet_num,
                    "express_name" => $express_obj["express_name"],
                    "express_code" => $express_obj["express_no"],
                    "express_id" => $express_obj["id"],
                    "is_express" => 1,
                    "order_goods_list" => $packet_goods_list
                );
                $packet_num = $packet_num + 1;
                $goods_packet_list[] = $packet_obj;
            }
        }
        $detail["goods_packet_list"] = $goods_packet_list;
        return $detail;
    }

    /**
     * 获取订单基础信息--ok
     */
    public function getOrderInfo($order_id)
    {
        $order_model = new OrderModel();
        $order_info = $order_model->get($order_id);
        return $order_info;
    }

    /**
     * 获取订单列表-ok
     */
    public function getOrderList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        $order_model = new OrderModel();
        $order_business = new OrderBusiness();
        // 查询主表

        $order_list = $order_model->pageQuery($page_index, $page_size, $condition, $order, '*');
        
        if (! empty($order_list['data'])) {
            foreach ($order_list['data'] as $k => $v) {
                //实际结算金额
                $order_list['data'][$k]['real_account_money'] = $order_business->getOrderRealAccountMoney($v['id']);
                // 查询订单项表
                $order_item = new OrderGoodsModel();
                $order_item_list = $order_item->where([
                    'order_id' => $v['id']
                ])->select();
                /*
                //通过sku_id查询ns_goods_sku中code
                foreach($order_item_list as $key=>$val){
                    //查询商品sku表开始


                    $goods_sku = new GoodsSkuModel();
                    $goods_sku_info = $goods_sku->getInfo(['sku_id'=>$val['sku_id']],'code');
                    $order_item_list[$key]['code'] = $goods_sku_info['code'];
                    //查询商品sku结束
                }
                */
                $province_name = "";
                $city_name = "";
                $district_name = "";
                
                $province = new ProvinceModel();
                $province_info = $province->getInfo(array(
                    "id" => $v["receiver_province"]
                ), "*");
                if (count($province_info) > 0) {
                    $province_name = $province_info["province_name"];
                }
                $order_list['data'][$k]['receiver_province_name'] = $province_name;
                $city = new CityModel();
                $city_info = $city->getInfo(array(
                    "id" => $v["receiver_city"]
                ), "*");
                if (count($city_info) > 0) {
                    $city_name = $city_info["city_name"];
                }
                $order_list['data'][$k]['receiver_city_name'] = $city_name;
                $district = new DistrictModel();
                $district_info = $district->getInfo(array(
                    "id" => $v["receiver_district"]
                ), "*");
                if (count($district_info) > 0) {
                    $district_name = $district_info["district_name"];
                }
                $order_list['data'][$k]['receiver_district_name'] = $district_name;
                foreach ($order_item_list as $key_item => $v_item) {
                    
                    
                    $picture = new AlbumPictureModel();
                    // $order_item_list[$key_item]['picture'] = $picture->get($v_item['goods_picture']);
                    $goods_picture = $picture->get($v_item['goods_picture']);
                    if (empty($goods_picture)) {
                        $goods_picture = array(
                            'pic_cover' => '',
                            'pic_cover_big' => '',
                            'pic_cover_mid' => '',
                            'pic_cover_small' => '',
                            'pic_cover_micro' => '',
                            "upload_type"=>1,
                            "domain"=>""
                        );
                    }
                    $order_item_list[$key_item]['picture'] = $goods_picture;
                    if ($v_item['refund_status'] != 0) {
                        $order_refund_status = OrderStatusHandle::getRefundStatus();
                        foreach ($order_refund_status as $k_status => $v_status) {
                            
                            if ($v_status['status_id'] == $v_item['refund_status']) {
                                $order_item_list[$key_item]['refund_operation'] = $v_status['refund_operation'];
                                $order_item_list[$key_item]['status_name'] = $v_status['status_name'];
                            }
                        }
                    } else {
                        $order_item_list[$key_item]['refund_operation'] = '';
                        $order_item_list[$key_item]['status_name'] = '';
                    }
                }
                $order_list['data'][$k]['order_item_list'] = $order_item_list;
                $order_list['data'][$k]['operation'] = '';
                // 订单来源名称
                $order_list['data'][$k]['order_from_name'] = OrderStatusHandle::getOrderFrom($v['order_from']);
                $order_list['data'][$k]['pay_type_name'] = OrderStatusHandle::getPayType($v['payment_type']);
                // 根据订单类型判断订单相关操作
                if ($order_list['data'][$k]['payment_type'] == 6 || $order_list['data'][$k]['shipping_type'] == 2) {
                    $order_status = OrderStatusHandle::getSinceOrderStatus();
                } else {
                    $order_status = OrderStatusHandle::getOrderCommonStatus();
                }
                
                // 查询订单操作
                foreach ($order_status as $k_status => $v_status) {
                    
                    if ($v_status['status_id'] == $v['order_status']) {
                        $order_list['data'][$k]['operation'] = $v_status['operation'];
                        $order_list['data'][$k]['member_operation'] = $v_status['member_operation'];
                        $order_list['data'][$k]['status_name'] = $v_status['status_name'];
                        $order_list['data'][$k]['is_refund'] = $v_status['is_refund'];
                    }
                }
            }
        }
        return $order_list;
    }

    /*
     订单创建--ok
     */
    public function orderCreate($user_id, $user_type=1,$order_type, $out_trade_no, $pay_type, $shipping_type, $order_from, $buyer_ip, $buyer_message, $buyer_invoice, $shipping_time, $receiver_mobile, $receiver_province, $receiver_city, $receiver_district, $receiver_address, $receiver_zip, $receiver_name, $point, $coupon_id, $user_money, $goods_list, $platform_money, $pick_up_id, $shipping_company_id, $coin = 0)
    {
        $order = new OrderBusiness();
        if ($pay_type == 4) {
            // 如果是货到付款 判断当前地址是否符合货到付款的地址
            $address = new AddressHandle();
           // getDistributionAreaIsUser( $province_id, $city_id, $district_id)
            $result = $address->getDistributionAreaIsUser( $receiver_province, $receiver_city, $receiver_district);
            if (! $result) {
                $this->error="当前地址不在货到付款范围";
                return false;
             //   return ORDER_CASH_DELIVERY;
            }
        }
      //  orderCreate($user_id, $user_type, $order_type, $out_trade_no, $pay_type, $shipping_type, $order_from, $buyer_ip, $buyer_message, $buyer_invoice, $shipping_time, $receiver_mobile, $receiver_province, $receiver_city, $receiver_district, $receiver_address, $receiver_zip, $receiver_name, $point, $coupon_id, $user_money, $goods_list, $platform_money, $pick_up_id, $shipping_company_id, $coin)

        $retval = $order->orderCreate($user_id, $user_type,$order_type, $out_trade_no, $pay_type, $shipping_type, $order_from, $buyer_ip, $buyer_message, $buyer_invoice, $shipping_time, $receiver_mobile, $receiver_province, $receiver_city, $receiver_district, $receiver_address, $receiver_zip, $receiver_name, $point, $coupon_id, $user_money, $goods_list, $platform_money, $pick_up_id, $shipping_company_id, $coin);
      /**执行通知
        runhook("Notify", "orderCreate", array(
            "order_id" => $retval
        ));
       * */
        //针对特殊订单执行支付处理
        if (empty($retval)) {
            $this->error = $order->getError();
            return false;
        }
        if($retval > 0)
        {
          //  hook('orderCreateSuccess', ['order_id' => $retval]);
            //货到付款
            if($pay_type == 4)
            {
                $this->orderOnLinePay($user_id, $user_type,$out_trade_no, 4);

               // orderOnLinePay($out_trade_no, 4);
            }else{
                $order_model = new OrderModel();
                $order_info = $order_model->getInfo(['id' => $retval], '*');
                if(!empty($order_info))
                {
                    if($order_info['user_platform_money'] != 0)
                    {
                        if($order_info['pay_money'] == 0)
                        {
                            $this->orderOnLinePay($user_id, $user_type,$out_trade_no, 5);
                           // $this->orderOnLinePay($out_trade_no, 5);
                           // $this->

                        }
                    }else{
                    
                        if($order_info['pay_money'] == 0)
                        {
                            $this->orderOnLinePay($user_id, $user_type,$out_trade_no, 1);
                          //  $this->orderOnLinePay($out_trade_no, 1);//默认微信支付
                        }
                    }
                }
               
            }
        
        }
        
        return $retval;

    }




    /**
     * 获取支付编号--ok
     */
    public function getOrderTradeNo()
    {
        $order = new OrderBusiness();
        $no = $order->createOutTradeNo();
        return $no;
    }

    /**
     * 订单物流发货--ok
     *
     * @param  $order_id
     * @param  $order_goods_id_array
     *            //订单项ID列 ','隔开
     * @param  $express_name
     *            //物流公司名称
     * @param  $shipping_type
     *            //物流方式
     * @param  $express_company_id
     *            //物流公司ID
     * @param  $express_no
     *            //运单编号
     */
    public function orderDelivery($user_id, $user_type,$order_id, $order_goods_id_array, $express_name, $shipping_type, $express_company_id, $express_no)
    {
        $order_express = new OrderExpressHandle();
       // delivey($user_id, $user_type,$order_id, $order_goods_id_array, $express_name, $shipping_type, $express_company_id, $express_no)
      //  delivey($user_id, $user_type,$order_id, $order_goods_id_array, $express_name, $shipping_type, $express_company_id, $express_no)
        $retval = $order_express->delivey($user_id, $user_type,$order_id, $order_goods_id_array, $express_name, $shipping_type, $express_company_id, $express_no);
        if (empty($retval)) {
            $this->error = $order_express->getError();
            return false;
        }
       /*
        runhook("Notify", "orderDelivery", array(
            "order_goods_ids" => $order_goods_id_array
        ));
        */
        if($retval){
            $params = [
                'order_id' => $order_id,
                'order_goods_id_array' => $order_goods_id_array,
                'express_name' => $express_name,
                'shipping_type' => $shipping_type,
                'express_company_id' => $express_company_id,
                'express_no' => $express_no,
            ];

           // hook('orderDeliverySuccess', $params);
        }
        return $retval;
    }

    /**2-ok
     * 订单不执行物流发货-ok
     */
    public function orderGoodsDelivery($user_id, $user_type,$order_id, $order_goods_id_array)
    {
        $order_goods = new OrderGoodsHandle();
       //orderGoodsDelivery($user_id, $user_type, $order_id, $order_goods_id_array)
       // orderGoodsDelivery($user_id, $user_type, $order_id, $order_goods_id_array)
        $retval = $order_goods->orderGoodsDelivery($user_id, $user_type,$order_id, $order_goods_id_array);
        if (empty($retval)) {
            $this->error = $order_goods->getError();
            return false;
        }
        if($retval){
            $params = [
                'order_id' => $order_id,
                'order_goods_id_array' => $order_goods_id_array,
            ];
           // hook('orderDeliverySuccess', $params);
        }
        return $retval;
    }

    /**
     * 订单执行交易关闭-ok-ookk-2ok
     */
    public function orderClose($user_id, $user_type,$order_id)
    {
        $order = new OrderBusiness();
        $retval = $order->orderClose($user_id, $user_type, $order_id);
        if (empty($retval)) {
            $this->error = $order->getError();
            return false;
        }
        if($retval){
           // hook("orderCloseSuccess", ['order_id' => $order_id]);
        }
        return $retval;
    }

    /*
     * 订单完成的函数-ok
     */
    public function orderComplete($user_id, $user_type,$orderid)
    {
        $order = new OrderBusiness();
       // orderComplete($user_id, $user_type, $orderid)
        $retval = $order->orderComplete($user_id, $user_type,$orderid);
        if (empty($retval)) {
            $this->error = $order->getError();
            return false;
        }
        try {
            // 结算订单的分销佣金
          //  $this->updateOrderCommission($orderid);
            // 处理店铺的账户资金
          //  $this->dealShopAccount_OrderComplete("", $orderid);
            //处理代理商的帐户中的佣金
            $this->dealAgentAccountCommissionOnOrderComplete("", $orderid);
            //处理平台帐户中的佣金
            $this->dealPlatformAccountCommissionOnOrderComplete("", $orderid);
            // 处理平台的账户资金
          //  $this->updateAccountOrderComplete($orderid);

            //更新会员的等级
            $user_service=new MemberUserHandle();
            $order_model=new OrderModel();
            $order_detail=$order_model->getInfo(["id"=>$orderid], "buyer_id");
            $user_service->updateUserLevel( $order_detail["buyer_id"]);
            
          //runhook("Notify", "orderComplete", array(
            //    "order_id" => $orderid
            //));
        } catch (\Exception $e) {
            Log::write($e->getMessage());
        }
        if($retval){
          //  hook("orderComplateSuccess", ['order_id' => $orderid]);
        }
        return $retval;
    }

    /*
     * 订单在线支付-ok-2ok
     * -ookk
     */
    public function orderOnLinePay($user_id, $user_type,$order_pay_no, $pay_type)
    {
        $order = new OrderBusiness();
      //  orderPay($user_id, $user_type, $order_pay_no, $pay_type, $status)
        $retval = $order->orderPay($user_id, $user_type,$order_pay_no, $pay_type, 0);
        if (empty($retval)) {
            $this->error = $order->getError();
            return false;
        }
        try {

            //计算代理商的帐户数据
            $this->dealAgentAccountOnOrderPay($order_pay_no);
            //计算平台的订单数据
            $this->dealPlatformAccountOnOrderPay($order_pay_no);
                // 计算店铺内部的分销佣金
             //   $this->orderCommissionCalculate($order_pay_no);
                // 处理店铺的账户资金
            //    $this->dealShopAccount_OrderPay($order_pay_no);
                // 处理平台的资金账户
           //     $this->dealPlatformAccountOrderPay($order_pay_no);
            
            $order_model = new OrderModel();
            $condition = " out_trade_no=" . $order_pay_no;
            $order_list = $order_model->getConditionQuery($condition, "id", "");
            foreach ($order_list as $k => $v) {
                   // runhook("Notify", "orderPay", array(
                     //   "order_id" => $v["id"]
                    //));
                    // 判断是否需要在本阶段赠送积分
                $order = new OrderBusiness();
                  //  giveGoodsOrderPoint($order_id, $type)
                $res = $order->giveGoodsOrderPoint($v["id"], 3);
            }
        } catch (\Exception $e) {
            Log::write($e->getMessage());
        }
        if($retval){
            $pay_type_name = OrderStatusHandle::getPayType($pay_type);
           // hook('orderOnLinePaySuccess', ['order_pay_no' => $order_pay_no]);
           // hook('orderOnLinePaySuccess', ['order_pay_no' => $order_pay_no]);
            runhook('Notify', 'orderRemindBusiness', [
                "out_trade_no" => $order_pay_no,
                "shop_id" => 0
            ]); // 订单提醒
        }
        return $retval;
    }

    /*
     * 订单线下支付--ok-2ok
     * -ookk
     */
    public function orderOffLinePay($user_id, $user_type,$order_id, $pay_type, $status)
    {
        $order = new OrderBusiness();
        
        $new_no = $this->getOrderNewOutTradeNo($order_id);
        if ($new_no) {
           // orderPay($user_id, $user_type, $order_pay_no, $pay_type, $status)
            //orderPay($user_id, $user_type, $order_pay_no, $pay_type, $status)
            $retval = $order->orderPay($user_id, $user_type,$new_no, $pay_type, $status);
            if (empty($retval)) {
                $this->error = $order->getError();
                return false;
            }

            $pay = new UnifyPayHandle();
            $pay->offLinePay($new_no, $pay_type);

                //计算代理商的帐户数据
            $this->dealAgentAccountOnOrderPay('', $order_id);
                //计算平台的订单数据
            $this->dealPlatformAccountOnOrderPay('', $order_id);
                // 计算店铺的佣金情况
              //  $this->orderCommissionCalculate('', $order_id);
                // 处理店铺的账户资金
               // $this->dealShopAccount_OrderPay('', $order_id);
                // 处理平台的资金账户
              //  $this->dealPlatformAccountOrderPay('', $order_id);
                // 判断是否需要在本阶段赠送积分
            $order = new OrderBusiness();
               // giveGoodsOrderPoint($order_id, $type)
            $res = $order->giveGoodsOrderPoint($order_id, 3);
            $pay_type_name = OrderStatusHandle::getPayType($pay_type);
              //  hook('orderOffLinePaySuccess', ['order_id' => $order_id]);

            runhook('Notify', 'orderRemindBusiness', [
                "out_trade_no" => $new_no,
                "shop_id" => 0
            ]); // 订单提醒


            return true;
           // return $retval;
        } else {
            return false;
        }
    }

    /**
     * 获取订单新的支付流水号-ok
     */
    public function getOrderNewOutTradeNo($order_id)
    {
        $order_model = new OrderModel();
        $out_trade_no = $order_model->getInfo([
            'id' => $order_id
        ], 'out_trade_no');
        $order = new OrderBusiness();
        //createNewOutTradeNo($orderid)
        $new_no = $order->createNewOutTradeNo($order_id);
        $pay = new UnifyPayHandle();
        $pay->modifyNo($out_trade_no['out_trade_no'], $new_no);
        return $new_no;
    }

    /**
     * 订单金额调整ok-2ok
     *
     * @param  $order_id
     * @param  $order_goods_id_adjust_array
     *            订单项数列 order_goods_id,adjust_money;order_goods_id,adjust_money
     * @param  $shipping_fee
     */
    public function orderMoneyAdjust($user_id, $user_type,$order_id, $order_goods_id_adjust_array, $shipping_fee)
    {
        // 调整订单
        $order_goods = new OrderGoodsHandle();
      //  orderGoodsAdjustMoney($order_goods_id_adjust_array)
        $retval = $order_goods->orderGoodsAdjustMoney($order_goods_id_adjust_array);

        if (empty($retval)) {
            $this->error = $order_goods->getError();
            return false;
        }
        
       // if ($retval >= 0) {
        // 计算整体商品调整金额
        $new_no = $this->getOrderNewOutTradeNo($order_id);
        $order = new OrderBusiness();
          //  getOrderGoodsMoney($order_id)
        $order_goods_money = $order->getOrderGoodsMoney($order_id);
          //  orderAdjustMoney($user_id, $user_type, $order_id, $goods_money, $shipping_fee)
        $retval_order = $order->orderAdjustMoney($user_id, $user_type,$order_id, $order_goods_money, $shipping_fee);

        if (empty($retval_order)) {
            $this->error = $order->getError();
            return false;
        }
        $order_model = new OrderModel();
        $order_money = $order_model->getInfo([
                'id' => $order_id
        ], 'pay_money');
        $pay = new UnifyPayHandle();
        $pay->modifyPayMoney($new_no, $order_money['pay_money']);
          //  hook("orderMoneyAdjustSuccess", ['order_id' => $order_id, 'order_goods_id_adjust_array' => $order_goods_id_adjust_array, 'shipping_fee' => $shipping_fee]);
        return $retval_order;
     //   } else {
     //       return $retval;
      //  }
    }

    /**
     * 查询订单-ok
     */
    public function orderQuery($where = "", $field = "*")
    {
        $order = new OrderModel();
        return $order->where($where)
            ->field($field)
            ->select();
    }

    /**
     * 查询订单项退款信息-ok-2ok
     */
    public function getOrderGoodsRefundInfo($order_goods_id)
    {
        $order_goods = new OrderGoodsHandle();
        $order_goods_info = $order_goods->getOrderGoodsRefundDetail($order_goods_id);
        return $order_goods_info;
    }


    /**
     * 查询订单的订单项列表-ok
     *
     * @param  $order_id            
     */
    public function getOrderGoods($order_id)
    {
        $order = new OrderBusiness();
      //  getOrderGoods($order_id)
        return $order->getOrderGoods($order_id);
    }

    /**
     * 查询订单的订单项列表-ok
     */
    public function getOrderGoodsInfo($order_goods_id)
    {
        $order = new OrderBusiness();
        $picture = new AlbumPictureModel();
      //  getOrderGoodsInfo($order_goods_id)
        $order_goods_info = $order->getOrderGoodsInfo($order_goods_id);
        $order_goods_info['goods_picture'] = $picture->get($order_goods_info['goods_picture'])['pic_cover'];
        return $order_goods_info;
    }

    /*
     */
    public function addOrder($data)
    {

    }

    /**
     * 买家退款申请-ok-2ok
     *
     * @param  $order_id
     *            订单ID
     * @param  $order_goods_id_array
     *            订单项ID (','隔开)
     * @param  $refund_type
     * @param  $refund_require_money
     *            //需要退款金额
     * @param  $refund_reason
     *            //退款原因
     * @return number <number, \think\false>
     */
    public function orderGoodsRefundAskfor($user_id, $user_type,$order_id, $order_goods_id, $refund_type, $refund_require_money, $refund_reason)
    {
        $order_goods = new OrderGoodsHandle();

        $retval = $order_goods->orderGoodsRefundAskfor($user_id, $user_type,$order_id, $order_goods_id, $refund_type, $refund_require_money, $refund_reason);
        if (empty($retval)) {
            $this->error = $order_goods->getError();
            return false;
        }
        if($retval){
            $params = [
                'order_id' => $order_id,
                'order_goods_id' => $order_goods_id,
                'refund_type' => $refund_type,
                'refund_require_money' => $refund_require_money,
                'refund_reason' => $refund_reason,
            ];

            runhook("Notify", "orderRefoundBusiness", [
                "shop_id" => 0,
                "order_id" => $order_id
            ]); // 商家退款提醒
        //    hook('orderGoodsRefundAskforSuccess', $params);
        }
        return $retval;
    }

    /*
     * 买家取消退款-ok-2ok
     */
    public function orderGoodsCancel($user_id,$user_type,$order_id, $order_goods_id)
    {
        $order_goods = new OrderGoodsHandle();
        $retval = $order_goods->orderGoodsCancel($user_id,$user_type,$order_id, $order_goods_id);

        if (empty($retval)) {
            $this->error = $order_goods->getError();
            return false;
        }
        if($retval){
          //  hook("orderGoodsCancelSuccess", ['order_id' => $order_id, 'order_goods_id' => $order_goods_id]);
        }
        return $retval;
    }

    /**
     * 买家退货-ok-2ok
     *
     * @param  $order_id
     * @param  $order_goods_id
     * @param  $refund_shipping_company
     *            //退货物流公司名称
     * @param  $refund_shipping_code
     *            //退货物流运单号
     */
    public function orderGoodsReturnGoods($user_id, $user_type,$order_id, $order_goods_id, $refund_shipping_company, $refund_shipping_code)
    {
        $order_goods = new OrderGoodsHandle();

        $retval = $order_goods->orderGoodsReturnGoods($user_id, $user_type,$order_id, $order_goods_id, $refund_shipping_company, $refund_shipping_code);
        if (empty($retval)) {
            $this->error = $order_goods->getError();
            return false;
        }

        if($retval){
            $params = [
                'order_id' => $order_id,
                'order_goods_id' => $order_goods_id,
                'refund_shipping_company' => $refund_shipping_company,
                'refund_shipping_code' => $refund_shipping_code,
            ];
          //  hook("orderGoodsReturnGoodsSuccess", $params);
        }
        return $retval;
    }

    /*
     * 卖家同意买家退款申请-ok
     */
    public function orderGoodsRefundAgree($user_id, $user_type,$order_id, $order_goods_id)
    {
        $order_goods = new OrderGoodsHandle();
       // orderGoodsRefundAgree($user_id, $user_type, $order_id, $order_goods_id)
        $retval = $order_goods->orderGoodsRefundAgree($user_id, $user_type,$order_id, $order_goods_id);
        if($retval){
           // hook("orderGoodsRefundAgreeSuccess", ['order_id' => $order_id, 'order_goods_id' => $order_goods_id]);
        }
        return $retval;
    }

    /*
     * 卖家永久决绝退款-ok-2ok
     */
    public function orderGoodsRefuseForever($user_id, $user_type,$order_id, $order_goods_id)
    {
        $order_goods = new OrderGoodsHandle();

        $retval = $order_goods->orderGoodsRefuseForever($user_id, $user_type,$order_id, $order_goods_id);

        if (empty($retval)) {
            $this->error = $order_goods->getError();
            return false;
        }
        if($retval){
          //  hook("orderGoodsRefuseForeverSuccess", ['order_id' => $order_id, 'order_goods_id' => $order_goods_id]);
        }
        return $retval;
    }

    /*
     * 卖家拒绝本次退款-ok
     */
    public function orderGoodsRefuseOnce($user_id, $user_type,$order_id, $order_goods_id)
    {
        $order_goods = new OrderGoodsHandle();
       // orderGoodsRefuseOnce($user_id, $user_type, $order_id, $order_goods_id)
        $retval = $order_goods->orderGoodsRefuseOnce($user_id, $user_type,$order_id, $order_goods_id);

        if (empty($retval)) {
            $this->error = $order_goods->getError();
            return false;
        }
        if($retval){
           // hook("orderGoodsRefuseOnceSuccess", ['order_id' => $order_id, 'order_goods_id' => $order_goods_id]);
        }
        return $retval;
    }

    /*
     * 卖家确认收货-ok-2okk
     */
    public function orderGoodsConfirmRecieve($user_id, $user_type,$order_id, $order_goods_id, $storage_num, $isStorage, $goods_id, $sku_id)
    {
        $order_goods = new OrderGoodsHandle();
        $retval = $order_goods->orderGoodsConfirmRecieve($user_id, $user_type,$order_id, $order_goods_id, $storage_num, $isStorage, $goods_id, $sku_id);

        if (empty($retval)) {
            $this->error = $order_goods->getError();
            return false;
        }

        if($retval){
          //  hook("orderGoodsConfirmRecieveSuccess", ['order_id' => $order_id, 'order_goods_id' => $order_goods_id]);
        }
        return $retval;
    }

    /*
     * 再分析一下
     * 卖家确认退款-ok
     */
    public function orderGoodsConfirmRefund1($user_id, $user_type,$order_id, $order_goods_id, $refund_real_money)
    {
        $order_goods = new OrderGoodsHandle();
        $retval = $order_goods->orderGoodsConfirmRefund($user_id, $user_type,$order_id, $order_goods_id, $refund_real_money);

        if (empty($retval)) {
            $this->error = $order_goods->getError();
            return false;
        }


        //处理平台的帐户数据
        $this->dealPlatformAccountOnRefund($order_id, $order_goods_id,$refund_real_money);
        //处理平台的代理商数据
        $this->dealAgentAccountOnRefund($order_id, $order_goods_id,$refund_real_money);
        //处理平台的佣金
        $this->dealPlatformCommissionOnRefund($order_id, $order_goods_id,$refund_real_money );
        //处理代理商的佣金
        $this->dealAgentCommisionOnRefund($order_id, $order_goods_id,$refund_real_money);




/*
        //计算代理商的帐户数据
        $this->dealAgentAccountOnOrderPay('', $order_id);

        // 重新计算订单的佣金情况
        $this->updateCommissionMoney($order_id, $order_goods_id);
        
        // 计算店铺的账户
        $this->updateShopAccount_OrderRefund($order_goods_id);
        $this->updateShopAccount_OrderComplete($order_id);
        // 计算平台的账户
        $this->updateAccountOrderRefund($order_goods_id);
        $this->updateAccountOrderComplete($order_id);

        */

        if($retval){
           // hook("orderGoodsConfirmRefundSuccess", ['order_id' => $order_id, 'order_goods_id' => $order_goods_id, 'refund_real_money' => $refund_real_money]);
        }
        return $retval;
    }

    /**
     * ok-2ok
     * 卖家确认退款
    */
    public function orderGoodsConfirmRefund($user_id, $user_type,$order_id, $order_goods_id, $refund_real_money, $refund_balance_money, $refund_way, $refund_remark)
    {
        $order_model = new OrderModel();
        $order_info = $order_model->getInfo([
            "id" => $order_id
        ], "pay_money,refund_money");

        $order_refund_chai = ($order_info['pay_money'] - $order_info['refund_money']) * 100;
        $order_refund_chai = $order_refund_chai + 1000;
        $refund_real_money_ext = $refund_real_money * 100;
        $refund_real_money_ext = $refund_real_money_ext + 1000;
        if ($order_refund_chai < $refund_real_money_ext) {
            $this->error = "实际退款超过订单支付金额，退款失败";
            return false;
        } else {

            $refund_trade_no = date("YmdHis", time()) . rand(100000, 999999);
            // 在线原路退款（微信/支付宝）
            $refund = $this->onlineOriginalRoadRefund($order_id, $refund_real_money, $refund_way, $refund_trade_no, $order_info['pay_money']);

            if ($refund['is_success'] == 1) {

                $order_goods = new OrderGoodsHandle();
                $retval = $order_goods->orderGoodsConfirmRefund($user_id, $user_type,$order_id, $order_goods_id, $refund_real_money, $refund_balance_money, $refund_trade_no, $refund_way, $refund_remark);

                if (empty($retval)) {
                    $this->error = $order_goods->getError();
                    return false;
                }

                //处理平台的帐户数据
                $this->dealPlatformAccountOnRefund($order_id, $order_goods_id,$refund_real_money);
                //处理平台的代理商数据
                $this->dealAgentAccountOnRefund($order_id, $order_goods_id,$refund_real_money);
                //处理平台的佣金
                $this->dealPlatformCommissionOnRefund($order_id, $order_goods_id,$refund_real_money );
                //处理代理商的佣金
                $this->dealAgentCommisionOnRefund($order_id, $order_goods_id,$refund_real_money);
/**


                // 重新计算订单的佣金情况
                $this->updateCommissionMoney($order_id, $order_goods_id);

                // 计算店铺的账户
                $this->updateShopAccount_OrderRefund($order_goods_id);
                $this->updateShopAccount_OrderComplete($order_id);

                // 计算平台的账户
                $this->updateAccountOrderRefund($order_goods_id);
                $this->updateAccountOrderComplete($order_id);
**/
                if ($retval) {
                    /*
                    hook("orderGoodsConfirmRefundSuccess", [
                        'order_id' => $order_id,
                        'order_goods_id' => $order_goods_id,
                        'refund_real_money' => $refund_real_money
                    ]);
                    */
                }
                return $retval;
            } else {
                $this->error =$refund['msg'];
              //  Log::write("refund:".$refund['msg']);
                return false;
            }
        }
    }

    /**
     * ok-2ok
     * 在线原路退款（微信、支付宝）
     */
    private function onlineOriginalRoadRefund($order_id, $refund_fee, $refund_way, $refund_trade_no, $total_fee)
    {
        // 1.根据订单id查询外部交易号
        $order_model = new OrderModel();
        $out_trade_no = $order_model->getInfo([
            'id' => $order_id
        ], "out_trade_no");

        // 2.根据外部交易号查询trade_no（交易号）支付宝支付会返回一个交易号，微信传空
        $order_payment_model = new OrderPaymentModel();
        $trade_no = $order_payment_model->getInfo([
            "out_trade_no" => $out_trade_no['out_trade_no']
        ], 'trade_no');

        // 3.根据用户选择的退款方式，进行不同的原路退款操作
        if ($refund_way == 1) {

            // 微信退款
            $weixin_pay = new WeiXinPay();
            $retval = $weixin_pay->setWeiXinRefund($refund_trade_no, $out_trade_no['out_trade_no'], $refund_fee * 100, $total_fee * 100);
        } elseif ($refund_way == 2) {

            // 支付宝退款
            $ali_pay = new AliPay();
            $retval = $ali_pay->aliPayRefund($refund_trade_no, $trade_no['trade_no'], $refund_fee);
        } else {

            // 线下操作，直接通过
            $retval = array(
                "is_success" => 1,
                'msg' => ""
            );
        }

        return $retval;
    }


    /**
     * 获取对应sku列表价格-ok
     */
    public function getGoodsSkuListPrice($goods_sku_list)
    {
        $goods_preference = new GoodsPreferenceHandle();
       // getGoodsSkuListPrice($goods_sku_list)
        $money = $goods_preference->getGoodsSkuListPrice($goods_sku_list);
        return $money;
    }

    /**
     * 获取对应商品列表价格-ok-2okokok
     * $goods_list= $goods_id:$sku_id:$count
     */
    public function getGoodsListPrice($user_id, $goods_list)
    {
        $goods_preference = new GoodsPreferenceHandle();
        // getGoodsSkuListPrice($goods_sku_list)
        //getGoodsListPrice($goods_list)
        $money = $goods_preference->getGoodsListPrice($user_id, $goods_list);
        return $money;
    }

    /**
     * 获取邮费
     *
     * @param  $goods_sku_list            
     * @param  $province            
     * @param  $city
     */
  //  public function getExpressFee($goods_sku_list, $express_company_id, $province, $city, $district)
 //   {
        /*
        $goods_express = new GoodsExpress();
        $fee = $goods_express->getSkuListExpressFee($goods_sku_list, $express_company_id, $province, $city, $district);
        return $fee;
        */
       // 暂返回0
    //    $goods = new GoodsModel();

    //    return 0;
   // }

    public function getExpressFee($goods_id)
    {

        $goods_model = new GoodsModel();
     //   is_send_free
       // `dispatch_type` '运费类型，0运费模板，1统一邮费', `dispatch_price`  '运费，统一邮费',
        $goods_ship = $goods_model->getInfo(["id"=>$goods_id], "is_send_free,dispatch_type,dispatch_price ");
        if ( $goods_ship['is_send_free'] == 1) {
            return 0;
        } else {
           return  $goods_ship['dispatch_price'];
        }
    }

    /**
     * 订单实际退款金额--ok-2ok
     *
     * @param  $order_goods_id
     *            //订单商品ID（订单项）
     */
    public function orderGoodsRefundMoney($order_goods_id)
    {
        $order_goods = new OrderGoodsHandle();
       // orderGoodsRefundMoney($order_goods_id)
        $money = $order_goods->orderGoodsRefundMoney($order_goods_id);
        return $money;
    }

    /**
     * 获取用户可使用优惠券--ok-2okk
     *
     * @param  $goods_sku_list
     *goods_list: goods_id:sku_id:count
     */
    public function getMemberCouponList($user_id, $goods_list)
    {
        $goods_preference = new GoodsPreferenceHandle();
      //  getMemberCouponListByGoodsList($user_id,$goods_list)
      //  $coupon_list = $goods_preference->getMemberCouponList($goods_sku_list);
       // getMemberCouponListByGoodsList($user_id,$goods_list)

        $coupon_list = $goods_preference-> getMemberCouponListByGoodsList($user_id,$goods_list);
        return $coupon_list;
    }

    /**
     * 查询商品列表可用积分数--改为getGoodsListUsePoint
     *
     * @param  $goods_sku_list            
     */
    public function getGoodsSkuListUsePoint($goods_sku_list)
    {
        $point = 0;
        $goods_sku_list_array = explode(",", $goods_sku_list);
        foreach ($goods_sku_list_array as $k => $v) {
            
            $sku_data = explode(':', $v);
            $sku_id = $sku_data[0];
            $goods = new GoodsHandle();
            $goods_id = $goods->getGoodsId($sku_id);
            $goods_model = new GoodsModel();
            $point_use = $goods_model->getInfo([
                'goods_id' => $goods_id
            ], 'point_exchange_type,point_exchange');
            if ($point_use['point_exchange_type'] == 1) {
                $point += $point_use['point_exchange'];
            }
        }
        return $point;
    }

    /**
     * 查询商品列表可用积分数--ok
     *
     * @param  $goods_sku_list
     */
    public function getGoodsListUsePoint($goods_list)
    {
        $point = 0;
        $goods_list_array = explode(",", $goods_list);
        foreach ($goods_list_array as $k => $v) {

            $goods_data = explode(':', $v);
            $goods_id = $goods_data[0];
          //  $goods = new Goods();
           // $goods_id = $goods->getGoodsId($sku_id);
            $goods_model = new GoodsModel();
            $point_use = $goods_model->getInfo([
                'id' => $goods_id
            ], 'point_exchange_type,point_exchange');
            if ($point_use['point_exchange_type'] == 1) {
                $point += $point_use['point_exchange'];
            }
        }
        return $point;
    }

    /**
     *
     * 订单收货-ok-2ok
     */
    public function orderTakeDelivery($user_id,$user_type,$order_id)
    {
        $order = new OrderBusiness();
        $res = $order->orderTakeDelivery($user_id,$user_type,$order_id);
        if (empty($res)) {
            $this->error = $order->getError();
            return false;
        }
        if($res){
          //  hook("orderTakeDeliverySuccess", ['order_id' => $order_id]);
        }
        return $res;
    }

    /**
     * 删除购物车中的数据--ok
     * 首先要查询当前商品在购物车中的数量，如果商品数量等于1则删除，如果商品数量大于1个，则减少该商品的数量
     */
    public function deleteCart($goods_list, $user_id)
    {
        $cart = new CartModel();
        $goods_list_array = explode(",", $goods_list);
        foreach ($goods_list_array as $k => $v) {
            $goods_data = explode(':', $v);
            $goods_id = $goods_data[0];
            $sku_id = $goods_data[1];
            $info = $cart->getInfo([
                'buyer_id' => $user_id,
                'goods_id' => $goods_id,
                'sku_id' => $sku_id
            ], "num,id");
//             $num = $info['num'];
            $cart_id = $info['id'];
            $cart->destroy([
                'buyer_id' => $user_id,
                'goods_id' => $goods_id,
                'sku_id' => $sku_id
            ]);
//             if ($num == 1) {
//                 // 购物车中该商品数量为1的话就删除
//             } else {
//                 // 修改商品数量
//                 $data["num"] = $num - 1;
//                 $cart->update($data, [
//                     'cart_id' => $cart_id
//                 ]);
//             }
        }
        $_SESSION["user_cart"] = '';
    }

    /**
     * ok-2ok
     * 获取某种条件下订单数量-ok
     */
    public function getOrderCount($condition)
    {
        $order = new OrderModel();
        $count = $order->where($condition)->count();
        return $count;
    }

    /**
     * 获取某种条件 订单总金额（元）-ok
     */
    public function getPayMoneySum($condition)
    {
        $order_model = new OrderModel();
        $money_sum = $order_model->where($condition)->sum('pay_money');
        return $money_sum;
    }

    /**
     * 获取某种条件 订单量（件）--ok
     */
    public function getGoodsNumSum($condition)
    {
        $order_model = new OrderModel();
        $order_list = $order_model->where($condition)
            ->select();
        $goods_sum = 0;
        foreach ($order_list as $k => $v) {
            $order_goods = new OrderGoodsModel();
            $goods_sum += $order_goods->where([
                'order_id' => $v['id']
            ])->sum('num');
        }
        return $goods_sum;
    }

    /**
     * 获取具体配送状态信息--ok
     */
    public static function getShippingInfo($shipping_status_id)
    {
        $shipping_status = OrderStatusHandle::getShippingStatus();
        $info = null;
        foreach ($shipping_status as $shipping_info) {
            if ($shipping_status_id == $shipping_info['shipping_status']) {
                $info = $shipping_info;
                break;
            }
        }
        return $info;
    }

    /**
     * 获取具体支付状态信息-ok
     *
     * @param  $pay_status_id            
     * @return :multitype:string |string
     */
    public static function getPayStatusInfo($pay_status_id)
    {
        $pay_status = OrderStatusHandle::getPayStatus();
        $info = null;
        foreach ($pay_status as $pay_info) {
            if ($pay_status_id == $pay_info['pay_status']) {
                $info = $pay_info;
                break;
            }
        }
        return $info;
    }

    /**
     * 获取订单各状态数量-ok
     */
    public static function getOrderStatusNum($condition = '')
    {
        $order = new OrderModel();
        $orderStatusNum['all'] = $order->where($condition)->count(); // 全部
        $condition['order_status'] = 0; // 待付款
        $orderStatusNum['wait_pay'] = $order->where($condition)->count();
        $condition['order_status'] = 1; // 待发货
        $orderStatusNum['wait_delivery'] = $order->where($condition)->count();
        $condition['order_status'] = 2; // 待收货
        $orderStatusNum['wait_recieved'] = $order->where($condition)->count();
        $condition['order_status'] = 3; // 已收货
        $orderStatusNum['recieved'] = $order->where($condition)->count();
        $condition['order_status'] = 4; // 交易成功
        $orderStatusNum['success'] = $order->where($condition)->count();
        $condition['order_status'] = 5; // 已关闭
        $orderStatusNum['closed'] = $order->where($condition)->count();
        $condition['order_status'] = - 1; // 退款中
        $orderStatusNum['refunding'] = $order->where($condition)->count();
        $condition['order_status'] = - 2; // 已退款
        $orderStatusNum['refunded'] = $order->where($condition)->count();
        $condition['order_status'] = array(
            'in',
            '3,4'
        ); // 已收货
        $condition['is_evaluate'] = 0; // 未评价
        $orderStatusNum['wait_evaluate'] = $order->where($condition)->count(); // 待评价
        
        return $orderStatusNum;
    }

    /**
     * 商品评价-添加--ok
     *
     * @param  $dataList
     *            评价内容的 数组
     */
    public function addGoodsEvaluate($dataArr, $order_id)
    {
        $goodsEvaluate = new GoodsEvaluateModel();
        $goods = new GoodsModel();
        $res = $goodsEvaluate->saveAll($dataArr);
        $result = false;
        
        if ($res != false) {
            // 修改订单评价状态
            $order = new OrderModel();
            $data = array(
                'is_evaluate' => 1
            );
            $result = $order->save($data, [
                'id' => $order_id
            ]);
            
            $this->commentPoint($order_id);

        }
        foreach ($dataArr as $item) {
            $good_info = $goods->get($item['goods_id']);
            $evaluates = $good_info['evaluates'] + 1;
            $star = $good_info['star'] + $item['scores'];
            $match_point = $star / $evaluates;
            $match_ratio = $match_point / 5 * 100 + '%';
            $data = array(
                'evaluates' => $evaluates,
                'star' => $star,
               // 'match_point' => $match_point,
              //  'match_ratio' => $match_ratio
            );
            $goods->update($data, [
                'id' => $item['goods_id']
            ]);
        }
     //   hook("goodsEvaluateSuccess", ['order_id' => $order_id, 'data' => $dataArr]);
        return $result;
    }

    /**
     * 商品评价-回复-ok
     *
     * @param  $explain_first
     *            评价内容
     * @param  $ordergoodsid
     *            订单项ID
     */
    public function addGoodsEvaluateExplain($explain_first, $order_goods_id)
    {
        $goodsEvaluate = new GoodsEvaluateModel();
        $data = array(
            'explain_first' => $explain_first
        );
        $res = $goodsEvaluate->save($data, [
            'order_goods_id' => $order_goods_id
        ]);
       // hook("goodsEvaluateExplainSuccess", ['order_goods_id' => $order_goods_id, 'explain_first' => $explain_first]);
        return $res;
    }

    /**
     * 商品评价-追评-ok
     *
     * @param  $again_content
     *            追评内容
     * @param  $againImageList
     *            传入追评图片的 数组
     * @param  $ordergoodsid
     *            订单项ID
     */
    public function addGoodsEvaluateAgain($again_content, $againImageList, $order_goods_id)
    {
        $goodsEvaluate = new GoodsEvaluateModel();
        $data = array(
            'again_content' => $again_content,
            'again_addtime' => time(),
            'again_image' => $againImageList
        );
        $res = $goodsEvaluate->save($data, [
            'order_goods_id' => $order_goods_id
        ]);
      //  hook("goodsEvaluateAgainSuccess", ['again_content' => $again_content, 'againImageList' => $againImageList, 'order_goods_id' => $order_goods_id]);
        return $res;
    }

    /**
     * 商品评价-追评回复-ok
     *
     * @param  $again_explain
     *            追评的 回复内容
     * @param  $ordergoodsid
     *            订单项ID
     */
    public function addGoodsEvaluateAgainExplain($again_explain, $order_goods_id)
    {
        $goodsEvaluate = new GoodsEvaluateModel();
        $data = array(
            'again_explain' => $again_explain
        );
        $res = $goodsEvaluate->save($data, [
            'order_goods_id' => $order_goods_id
        ]);
     //   hook("goodsEvaluateAgainExplainSuccess", ['order_goods_id' => $order_goods_id, 'again_explain' => $again_explain]);
        return $res;
    }

    /**
     * 获取指定订单的评价信息--ok
     *
     * @param  $orderid
     *            订单ID
     */
    public function getOrderEvaluateByOrder($order_id)
    {
        $goodsEvaluate = new GoodsEvaluateModel();
        $condition['order_id'] = $order_id;
        $field = 'order_id, order_no, order_goods_id, goods_id, goods_name, goods_price, goods_image,   content, addtime, image, explain_first, member_name, user_id, is_anonymous, scores, again_content, again_addtime, again_image, again_explain';
        return $goodsEvaluate->getConditionQuery($condition, $field, 'order_goods_id ASC');
    }

    /**
     * 获取指定会员的评价信息-ok
     *
     * @param  $uid
     *            会员ID
     */
    public function getOrderEvaluateByMember($user_id)
    {
        $goodsEvaluate = new GoodsEvaluateModel();
        $condition['user_id'] = $user_id;
        $field = 'order_id, order_no, order_goods_id, goods_id, goods_name, goods_price, goods_image,  content, addtime, image, explain_first, member_name, user_id, is_anonymous, scores, again_content, again_addtime, again_image, again_explain';
        return $goodsEvaluate->getConditionQuery($condition, $field, 'order_goods_id ASC');
    }

    /**
     * 评价信息 分页-ok
     */
    public function getOrderEvaluateDataList($page_index, $page_size, $condition, $order)
    {
        $goodsEvaluate = new GoodsEvaluateModel();
        return $goodsEvaluate->pageQuery($page_index, $page_size, $condition, $order, "*");
    }

    /**
     * 获取评价列表-ok
     *
     * @param  $page_index
     *            页码
     * @param  $page_size
     *            页大小
     * @param  $condition
     *            条件
     * @param  $order
     *            排序
     * @return :number
     */
    public function getOrderEvaluateList($page_index, $page_size, $condition, $order)
    {
        $goodsEvaluate = new GoodsEvaluateModel();
        $field = 'order_id, order_no, order_goods_id, goods_id, goods_name, goods_price, goods_image,  content, addtime, image, explain_first, member_name, uid, is_anonymous, scores, again_content, again_addtime, again_image, again_explain';
        return $goodsEvaluate->pageQuery($page_index, $page_size, $condition, $order, $field);
    }

    /**
     * 修改订单数据
     *
     * @param  $order_id            
     * @param  $data            
     */
    public function modifyOrderInfo($data, $order_id)
    {
        $order = new OrderModel();
        return $order->save($data, [
            'id' => $order_id
        ]);
    }

    /**
     * ok-2ok
     * 删除订单
     */
    public function deleteOrder($order_id, $operator_type, $operator_id)
    {
        $order_model = new OrderModel();
        $data = array(
            "is_deleted" => 1,
            "operator_type" => $operator_type,
            "operator_id" => $operator_id
        );
        $order_id_array = explode(',', $order_id);
        if ($operator_type == 1) {
            // 商家删除 目前之针对已关闭订单
            $res = $order_model->save($data, [
                "order_status" => 5,
                "id" => [
                    "in",
                    $order_id_array
                ],
               // "shop_id" => $operator_id
            ]);
            if ($res === false) {
                return false;
            }
        } elseif ($operator_type == 2) {
            // 用户删除
            $res = $order_model->save($data, [
                "order_status" => 5,
                "id" => [
                    "in",
                    $order_id_array
                ],
                "buyer_id" => $operator_id
            ]);
            if ($res === false) {
                return false;
            }
        }

        return true;
       // return 1;
    }

    /**
     * 判断店铺类型
     */
    private function getShopTypeDetail($shop_id)
    {
        /*
        $shop_model = new NsShopModel();
        $shop_detail = $shop_model->get($shop_id);
        if (empty($shop_detail)) {
            return 0;
        } else {
            return $shop_detail["shop_type"];
        }
        */
    }

    /**
     * 获取店铺在一段时间之内账户列表-ok
     */
    public function getShopOrderAccountList($shop_id=0, $start_time, $end_time, $page_index, $page_size)
    {
        $order_account = new OrderAccountHandle();
       // getShopOrderSumList( $start_time, $end_time, $page_index, $page_size)
        $list = $order_account->getShopOrderSumList($start_time, $end_time, $page_index, $page_size);
        return $list;
    }

    /**
     * 获取店铺在一段时间之内订单退款列表-ok
     */
    public function getShopOrderRefundList($shop_id=0, $start_time, $end_time, $page_index, $page_size)
    {
        $order_account = new OrderAccountHandle();
      //  getShopOrderRefundList($start_time, $end_time, $page_index, $page_size)
        $list = $order_account->getShopOrderRefundList( $start_time, $end_time, $page_index, $page_size);
        return $list;
    }

    /**
     * 获取店铺订单销售统计（统计店铺订单账户）-ok
     */
    public function getShopOrderStatics($shop_id=0, $start_time, $end_time)
    {
        $order_account = new OrderAccountHandle();
      //  getShopOrderSum( $start_time, $end_time)
        $order_sum = $order_account->getShopOrderSum( $start_time, $end_time);
      //  getShopOrderSumRefund( $start_time, $end_time)
        $order_refund_sum = $order_account->getShopOrderSumRefund($start_time, $end_time);
        $order_sum_account = $order_sum - $order_refund_sum;
        $array = array(
            'order_sum' => $order_sum,
            'order_refund_sum' => $order_refund_sum,
            'order_account' => $order_sum_account
        );
        return $array;
    }

    /**
     * 获取店铺订单账户详情
     */
    public function getShopOrderAccountDetail($shop_id=0)
    {
        // 获取总销售统计
        $account_all = $this->getShopOrderStatics($shop_id, '2015-1-1', '3050-1-1');
        // 获取今日销售统计
        $date_day_start = date("Y-m-d", time());
        $date_day_end = date("Y-m-d H:i:s", time());
        $account_day = $this->getShopOrderStatics($shop_id, $date_day_start, $date_day_end);
        // 获取周销售统计（7天）
        $date_week_start = date('Y-m-d', strtotime('-7 days'));
        $date_week_end = $date_day_end;
        $account_week = $this->getShopOrderStatics($shop_id, $date_week_start, $date_week_end);
        // 获取月销售统计(30天)
        $date_month_start = date('Y-m-d', strtotime('-30 days'));
        $date_month_end = $date_day_end;
        $account_month = $this->getShopOrderStatics($shop_id, $date_month_start, $date_month_end);
        $array = array(
            'day' => $account_day,
            'week' => $account_week,
            'month' => $account_month,
            'all' => $account_all
        );
        return $array;
    }

    /*
     * ok-2ok
     * 订单销售概况--ok
     */
    public function getShopAccountCountInfo($shop_id=0)
    {
        // 本月第一天
        $date_month_start = $this->getTimeTurnTimeStamp(date('Y-m-d', strtotime('-30 days')));
        $date_month_end = $this->getTimeTurnTimeStamp(date("Y-m-d H:i:s", time()));
        // 下单金额
        $order_account = new OrderAccountHandle();
        $condition["create_time"] = [
            [
                ">=",
                $date_month_start
            ],
            [
                "<=",
                $date_month_end
            ]
        ];
        $condition['order_status'] = array(
            'NEQ',
            0
        );
        $condition['order_status'] = array(
            'NEQ',
            5
        );
        /*
        if ($shop_id != 0) {
            $condition['shop_id'] = array(
                'NEQ',
                0
            );
        }
        */
        $order_money = $order_account->getShopSaleSum($condition);
        // var_dump($order_money);
        // 下单会员
        $order_user_num = $order_account->getShopSaleUserSum($condition);
        // 下单量
        $order_num = $order_account->getShopSaleNumSum($condition);
        // 下单商品数
        $order_goods_num = $order_account->getShopSaleGoodsNumSum($condition);
        // 平均客单价
        if ($order_user_num > 0) {
            $user_money_average = $order_money / $order_user_num;
        } else {
            $user_money_average = 0;
        }
        // 平均价格
        if ($order_goods_num > 0) {
            $goods_money_average = $order_money / $order_goods_num;
        } else {
            $goods_money_average = 0;
        }
        $array = array(
            "order_money" => sprintf('%.2f', $order_money),
            "order_user_num" => $order_user_num,
            "order_num" => $order_num,
            "order_goods_num" => $order_goods_num,
            "user_money_average" => sprintf('%.2f', $user_money_average),
            "goods_money_average" => sprintf('%.2f', $goods_money_average)
        );
        return $array;
    }

    /*
     * ok-2ok
     * 商品销售列表--ok
     */
    public function getShopGoodsSalesList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        // $goods_calculate = new GoodsCalculate();
        // $goods_sales_list = $goods_calculate->getGoodsSalesInfoList($page_index, $page_size , $condition , $order );
        // return $goods_sales_list;
        $goods_model = new GoodsModel();
        $tmp_array = $condition;
        if(!empty($condition["order_status"])){
            $order_condition["order_status"] = $condition["order_status"];
            unset($tmp_array["order_status"]);           
        }
        $goods_list = $goods_model->pageQuery($page_index, $page_size, $tmp_array, $order, '*');
        // 条件
        $start_date = $this->getTimeTurnTimeStamp(date('Y-m-d', strtotime('-30 days')));
        $end_date = $this->getTimeTurnTimeStamp(date("Y-m-d H:i:s", time()));
        $order_condition['create_time'] = [
            'between',
            [
                $start_date,
                $end_date
            ]
        ];
        
        //$order_condition["shop_id"] = $condition["shop_id"];
        $goods_calculate = new GoodsCalculateHandle();
        // 得到条件内的订单项
        $order_goods_list = $goods_calculate->getOrderGoodsSelect($order_condition);
        // 遍历商品
        foreach ($goods_list["data"] as $k => $v) {
            $data = array();
            $goods_sales_num = $goods_calculate->getGoodsSalesNum($order_goods_list, $v["id"]);
            $goods_sales_money = $goods_calculate->getGoodsSalesMoney($order_goods_list, $v["id"]);
            $data["sales_num"] = $goods_sales_num;
            $data["sales_money"] = $goods_sales_money;
            $goods_list["data"][$k]["sales_info"] = $data;
        }
        return $goods_list;
    }

    /*
     * ok-2ok
     * 所有商品销售情况-ok
     */
    public function getShopGoodsSalesQuery($shop_id=0, $start_date, $end_date, $condition)
    {
        // TODO Auto-generated method stub
        // 商品
        $goods_model = new GoodsModel();
        $goods_list = $goods_model->getConditionQuery($condition, "*", '');
        // 订单项
        $condition['create_time'] = [
            'between',
            [
                $start_date,
                $end_date
            ]
        ];
        $order_condition["create_time"] = [
            [
                ">=",
                $start_date
            ],
            [
                "<=",
                $end_date
            ]
        ];
        $order_condition['order_status'] = array(
            'NEQ',
            0
        );
        $order_condition['order_status'] = array(
            'NEQ',
            5
        );
        /*
        if ($shop_id != '') {
            $order_condition["shop_id"] = $shop_id;
        }
        */
        $goods_calculate = new GoodsCalculateHandle();
        $order_goods_list = $goods_calculate->getOrderGoodsSelect($order_condition);
        // 遍历商品
        foreach ($goods_list as $k => $v) {
            $data = array();
            $goods_sales_num = $goods_calculate->getGoodsSalesNum($order_goods_list, $v["id"]);
            $goods_sales_money = $goods_calculate->getGoodsSalesMoney($order_goods_list, $v["id"]);
            $goods_list[$k]["sales_num"] = $goods_sales_num;
            $goods_list[$k]["sales_money"] = $goods_sales_money;
        }
        return $goods_list;
    }

    /**
     * ok-2ok
     * 查询一段时间内的店铺下单金额 -ok
     *
     * @param  $shop_id            
     * @param  $start_date            
     * @param  $end_date
     */
    public function getShopSaleSum($condition)
    {
        $order_account = new OrderAccountHandle();
        $sales_num = $order_account->getShopSaleSum($condition);
        return $sales_num;
    }

    /**
     * ok-2ok
     * 查询一段时间内的店铺下单量--ok
     */
    public function getShopSaleNumSum($condition)
    {
        $order_account = new OrderAccountHandle();
        $sales_num = $order_account->getShopSaleNumSum($condition);
        return $sales_num;
    }

    /**
     * ***********************************************店铺账户--Start******************************************************
     */
    /**
     * 订单支付的时候 调整店铺账户
     *
     * @param string $order_out_trade_no            
     * @param number $order_id            
     */
    private function dealShopAccount_OrderPay($order_out_trade_no = "", $order_id = 0)
    {}

    /**
     * 订单完成的时候调整账户金额
     *
     * @param string $order_out_trade_no            
     * @param number $order_id            
     */
    private function dealShopAccount_OrderComplete($order_out_trade_no = "", $order_id = 0)
    {}

    /**
     * 订单支付
     *
     * @param  $order_id            
     */
    private function updateShopAccount_OrderPay($order_id)
    {
        /*
        $order_model = new NsOrderModel();
        $shop_account = new ShopAccount();
        $order = new OrderBusiness();
        $this->startTrans();
        try {
            $order_obj = $order_model->get($order_id);
            // 订单的实际付款金额
            $pay_money = $order->getOrderRealPayMoney($order_id);
            // 订单的支付方式
            $payment_type = $order_obj["payment_type"];
            // 店铺id
            $shop_id = $order_obj["shop_id"];
            // 订单号
            $order_no = $order_obj["order_no"];
            // 处理订单的营业总额
            $shop_account->addShopAccountProfitRecords(getSerialNo(), $shop_id, $pay_money, 1, $order_id, "店铺订单支付金额" . $pay_money . "元, 订单号为：" . $order_no . ", 支付方式【线下支付】。");
            if ($payment_type != ORDER_REFUND_STATUS) {
                // 在线支付 处理店铺的入账总额
                $shop_account->addShopAccountMoneyRecords(getSerialNo(), $shop_id, $pay_money, 1, $order_id, "店铺订单支付金额" . $pay_money . "元, 订单号为：" . $order_no . ", 支付方式【在线支付】, 已入店铺账户。");
            }
            // 处理平台的利润分成
            $this->addShopOrderAccountRecords($order_id, $order_no, $shop_id, $pay_money);
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
        }
        */
    }

    /**
     * 订单项退款
     *
     * @param  $order_goods_id            
     */
    private function updateShopAccount_OrderRefund($order_goods_id)
    {}

    /**
     * 订单完成
     *
     * @param  $order_id            
     */
    private function updateShopAccount_OrderComplete($order_id)
    {}

    /**
     * ***********************************************店铺账户--End******************************************************
     */

    /**
     * ***********************************************代理商账户--Start******************************************************
     */

    /**
     * 在支付时处理代理商的帐户数据
     * @param string $order_out_trade_no
     * @param int $order_id
     */
    public function dealAgentAccountOnOrderPay($order_out_trade_no = "", $order_id = 0) {
        $agent_handle = new AgentHandle();
        $order_model = new OrderModel();
        if ($order_out_trade_no != "" && $order_id == 0) {

            $condition = " out_trade_no=" . $order_out_trade_no;
          //  getConditionQuery($condition, $field, $order)
            $order_list = $order_model->getConditionQuery($condition, "id, agent_id", "");
            foreach ($order_list as $k => $v) {
              //  if (!$agent_handle->isPlatformAgentById($v['agent_id'])){ //非平台代理商
                     $this->updateAgentAccountOnOrderPay($v["id"]);
               // }
            }
        } else {
            if ($order_out_trade_no == "" && $order_id != 0) {
              //  getInfo($condition = '', $field = '*')
                $order = $order_model->get($order_id);
              //  if (!$agent_handle->isPlatformAgentById($order['agent_id'])) { //非平台代理商
                    $this->updateAgentAccountOnOrderPay($order_id);
              //  }
            }
        }

    }

    /**
     * 在订单支付时对代理商的帐户的调整
     *
     * @param  $order_id
     */
    private function updateAgentAccountOnOrderPay($order_id)
    {
        $order_model = new OrderModel();
        $agent_account = new AgentAccountHandle();
        $order = new OrderBusiness();
        $this->startTrans();
        try {
            $order_obj = $order_model->get($order_id);

            $order_money=$order_obj['order_money'];
            $order_goods_money=$order_obj['goods_money'];
            $order_promotion_money = $order_obj['promotion_money'];
            $order_coupon_money=$order_obj['coupon_money'];
            $order_point_money=$order_obj['point_money'];
            $order_pay_money = $order_obj['pay_money'];
            $pay_type = $order_obj['payment_type'];
            $pay_time = $order_obj['pay_time'];
            // 订单的实际付款金额
            $pay_money = $order->getOrderRealPayMoney($order_id);

            // 订单的支付方式
            $payment_type = $order_obj["payment_type"];
            // 代理商id
            $agent_id = $order_obj["agent_id"];
            $buyer_id = $order_obj["buyer_id"];
            // 订单号
            $order_no = $order_obj["order_no"];
            $agent_handle = new AgentHandle();
            $agent = $agent_handle->getAgentById($agent_id);
            $p_agnet_id = 0;
          //  $from_level = 1;

            //处理代理商帐户中的订单的数据
            /*
            addAgentAccountOrderRecords($serial_no,$operation_id, $agent_id, $order_id, $from_level,
                $order_money, $order_goods_money,$order_promotion_money,
                $order_coupon_money, $order_point_money,$order_pay_money,
                $account_type,  $remark)*/
            $from_level = 1;
            $account_type = 1;
            $remark = '订单支付-代理商直接订单数据';
            $res = $agent_account->addAgentAccountOrderRecords(getSerialNo(),15, $agent_id, $order_id,$buyer_id, $from_level,
                $order_money, $order_goods_money,$order_promotion_money,
                $order_coupon_money, $order_point_money,$order_pay_money,$pay_type, $pay_time,
                $account_type,  $remark);



/*
            addAgentAccountOrderRecords(getSerialNo(),15, $agent_id, $order_id, $from_level,
                $order_money, $order_goods_money,$order_promotion_money,
                $order_coupon_money, $order_point_money,$order_pay_money,
                $account_type,  $remark);
            */
            if (empty($res)) {
                $this->rollback();
                $this->error = $agent_account->getError();
                return false;
            }



            if ($agent['agent_type'] == 2) {
                $p_agent_id = $agent['p_agent_id'];
                $from_level = 2;
                $account_type = 1;
                $remark = '订单支付-代理商间接订单数据';
                $res = $agent_account->addAgentAccountOrderRecords(getSerialNo(),16, $p_agent_id, $order_id,$buyer_id, $from_level,
                    $order_money, $order_goods_money,$order_promotion_money,
                    $order_coupon_money, $order_point_money,$order_pay_money,$pay_type, $pay_time,
                    $account_type,  $remark);
/*
                addAgentAccountOrderRecords(getSerialNo(),15, $agent_id, $order_id,$buyer_id, $from_level,
                    $order_money, $order_goods_money,$order_promotion_money,
                    $order_coupon_money, $order_point_money,$order_pay_money,$pay_type, $pay_time,
                    $account_type,  $remark);
*/



                if (empty($res)) {
                    $this->rollback();
                    $this->error = $agent_account->getError();
                    return false;
                }
            }
            // 处理订单的营业总额
          //  $shop_account->addShopAccountProfitRecords(getSerialNo(), $shop_id, $pay_money, 1, $order_id, "店铺订单支付金额" . $pay_money . "元, 订单号为：" . $order_no . ", 支付方式【线下支付】。");
         //   if ($payment_type != ORDER_REFUND_STATUS) {
                // 在线支付 处理店铺的入账总额
              //  $shop_account->addShopAccountMoneyRecords(getSerialNo(), $shop_id, $pay_money, 1, $order_id, "店铺订单支付金额" . $pay_money . "元, 订单号为：" . $order_no . ", 支付方式【在线支付】, 已入店铺账户。");
        //    }
            // 处理平台的利润分成
         //   $this->addShopOrderAccountRecords($order_id, $order_no, $shop_id, $pay_money);
            $this->commit();
            return true;
        } catch (\Exception $e) {
            Log::write("updateAgentAccountOnOrderPay".$e->getMessage());
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }


    /**
     * 订单完成的时候处理代理商的佣金
     *
     * @param string $order_out_trade_no
     * @param number $order_id
     */
    private function dealAgentAccountCommissionOnOrderComplete($order_out_trade_no = "", $order_id = 0)
    {
        $agent_handle = new AgentHandle();
        $order_model = new OrderModel();
        if ($order_out_trade_no != "" && $order_id == 0) {
            $condition = " out_trade_no=" . $order_out_trade_no;
            $order_list = $order_model->getConditionQuery($condition, "id, agent_id", "");
            foreach ($order_list as $k => $v) {
                if (!$agent_handle->isPlatformAgentById($v['agent_id'])) { //非平台代理商
                    $this->updateAgentAccountCommissionOnOrderComplete($v["id"]);
                }
            }
        } else {
            if ($order_out_trade_no == "" && $order_id != 0) {
                $order = $order_model->get($order_id);
                if (!$agent_handle->isPlatformAgentById($order['agent_id'])) { //非平台代理商
                    $this->updateAgentAccountCommissionOnOrderComplete($order_id);
                }
            }
        }

    }

    /**
     * 在订单确认(完成)时对代理商的帐户的佣金处理
     *
     * @param  $order_id
     */
    private function updateAgentAccountCommissionOnOrderComplete($order_id)
    {
        $order_model = new OrderModel();
        $agent_account = new AgentAccountHandle();
        $order = new OrderBusiness();
        $this->startTrans();
        try {
            $order_obj = $order_model->get($order_id);

          //  $order_money=$order_obj['order_money'];
            $order_goods_money=$order_obj['goods_money'];
            $order_promotion_money = $order_obj['promotion_money'];
            $order_coupon_money=$order_obj['coupon_money'];
            $order_point_money=$order_obj['point_money'];
            $order_refund_money = $order_obj['refund_money'];
           // $order_pay_money = $order_obj['pay_money'];
            // 订单的实际付款金额
           // $pay_money = $order->getOrderRealPayMoney($order_id);

            // 订单的支付方式
          //  $payment_type = $order_obj["payment_type"];
            // 代理商id
            $agent_id = $order_obj["agent_id"];
            // 订单号
           // $order_no = $order_obj["order_no"];
            $agent_handle = new AgentHandle();
            $agent = $agent_handle->getAgentById($agent_id);
            $p_agnet_id = 0;
            //  $from_level = 1;


            /*
            $account_money = $order_goods_money - $order_promotion_money
                                -  $order_coupon_money - $order_point_money -  $order_refund_money;
            */
            $order_business = new OrderBusiness();
            $account_money = $order_business->getOrderRealAccountMoney($order_id);
            if ($account_money < 0) {
                $account_money = 0;
            }
            $from_level = 1;
            $account_type = 1;
            $remark = '订单完成-代理商帐户中的直接佣金';
            $res = $agent_account->addAgentAccountCommissionRecordsOnAdd(21, getSerialNo(), $agent_id, $order_id, $from_level,
                $account_money, 1, 1, $remark);
            if (empty($res)) {
                $this->rollback();
                $this->error = $agent_account->getError();
                return false;
            }



            if ($agent['agent_type'] == 2) {
                $p_agent_id = $agent['p_agent_id'];
                $from_level = 2;
                $account_type = 1;
                $remark = '订单完成-代理商帐户中的间接佣金';
                $res = $agent_account->addAgentAccountCommissionRecordsOnAdd(22, getSerialNo(),  $p_agent_id, $order_id, $from_level,
                    $account_money, 1, 1, $remark);
                if (empty($res)) {
                    $this->rollback();
                    $this->error = $agent_account->getError();
                    return false;
                }
            }

            $this->commit();
            return true;
        } catch (\Exception $e) {
            Log::write("updateAgentAccountCommissionOnOrderComplete".$e->getMessage());
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 退款时处理代理商帐户
     * @param $order_id
     * @param $order_goods_id
     * @param $refund_real_money
     * @return bool
     */
    private function dealAgentAccountOnRefund($order_id, $order_goods_id,$refund_real_money) {
        $order_model = new OrderModel();
        $order_goods_model = new OrderGoodsModel();
        $agent_account = new AgentAccountHandle();
        $agent_model = new AgentModel();
        $this->startTrans();
        try {
            $operation_id = 33;
            $remark = '订单退款-增加代理商直接退款记录';
            $order = $order_model->get($order_id);
            $agent_id = $order['agent_id'];
            $order_goods = $order_goods_model->get($order_goods_id);
            $goods_money = $order_goods['goods_money'];
            $refund_money = $refund_real_money;
            $account_type = 1;

            $agent = $agent_model->get($agent_id);
            $agent_type = $agent['agent_type'];
            $from_level = 1;
            $res = $agent_account->addAgentAccountRefundRecords($operation_id,$order_id,$order_goods_id,$agent_id, $from_level,$goods_money,
                $refund_money,$account_type,$remark);
            if (empty($res)) {
                $this->rollback();
                Log::write('agent_account->addAgentAccountRefundRecords 出错');
                $this->error = $agent_account->getError();
                return false;
            }

            if ($agent_type == 2) {
                $operation_id = 34;
                $remark = '订单退款-增加代理商间接退款记录';
                $p_agent_id = $agent['p_agent_id'];
                $from_level = 2;
                $res = $agent_account->addAgentAccountRefundRecords($operation_id,$order_id,$order_goods_id,$p_agent_id, $from_level,$goods_money,
                    $refund_money,$account_type,$remark);
                if (empty($res)) {
                    $this->rollback();
                    Log::write('agent_account->addAgentAccountRefundRecords 出错');
                    $this->error = $agent_account->getError();
                    return false;
                }

            }

            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            Log::write('dealAgentAccountOnRefund 出现异常 '.$e->getMessage());
            $this->error = $e->getMessage();
            return false;
        }
    }


    /**
     * 退款时处理代理商佣金
     * @param $order_id
     * @param $order_goods_id
     * @param $refund_real_money
     * @return bool
     */
    private function dealAgentCommisionOnRefund($order_id, $order_goods_id,$refund_real_money) {
        $order_model = new OrderModel();
        $agent_account = new AgentAccountHandle();
        $agent_model = new AgentModel();
        $order = $order_model->get($order_id);
        $agent_id = $order['agent_id'];
        $agent_handle = new AgentHandle();
        if ($agent_handle->isPlatformAgentById($agent_id)) { //平台代理商不计算佣金
            return true;
        }

        $this->startTrans();
        try {
            $operation_id = 35;
            $remark = '订单退款-增加代理商直接退款退佣金记录';

            $refund_money = $refund_real_money;

            $agent = $agent_model->get($agent_id);
            $agent_type = $agent['agent_type'];
            $from_level = 1;
            $res = $agent_account->addAgentRefundCommissonReconds($operation_id,$order_id,$order_goods_id,$agent_id, $from_level,
                $refund_money,$remark );

            if (empty($res)) {
                $this->rollback();
                Log::write('agent_account->addAgentRefundCommissonReconds 出错');
                $this->error = $agent_account->getError();
                return false;
            }

            if ($agent_type == 2) {
                $operation_id = 36;
                $remark = '订单退款-增加代理商间接退款退佣金记录';
                $p_agent_id = $agent['p_agent_id'];
                $from_level = 2;
                if (!$agent_handle->isPlatformAgentById($p_agent_id)) { //平台代理商不计算佣金
                    $res = $agent_account->addAgentRefundCommissonReconds($operation_id, $order_id, $order_goods_id, $p_agent_id, $from_level,
                        $refund_money, $remark);
                    if (empty($res)) {
                        $this->rollback();
                        Log::write('agent_account->addAgentRefundCommissonReconds 出错');
                        $this->error = $agent_account->getError();
                        return false;
                    }
                }
            }

            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            Log::write('dealAgentCommisionOnRefund 出现异常 '.$e->getMessage());
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * ***********************************************代理商账户--End******************************************************
     */
    
    /**
     * ***********************************************平台账户计算--Start******************************************************
     */


    /**
     * 订单支付时处理 平台的账户
     *
     * @param string $order_out_trade_no
     * @param number $order_id
     */
    private function dealPlatformAccountOnOrderPay($order_out_trade_no = "", $order_id = 0)
    {
        if ($order_out_trade_no != "" && $order_id == 0) {
            $order_model = new OrderModel();
            $condition = " out_trade_no=" . $order_out_trade_no;
         //   getConditionQuery($condition, $field, $order)
            $order_list = $order_model->getConditionQuery($condition, "id", "");
            foreach ($order_list as $k => $v) {
                $this->updatePlatformAccountOnOrderPay($v["id"]);
            }
        } else
            if ($order_out_trade_no == "" && $order_id != 0) {
                $this->updatePlatformAccountOnOrderPay($order_id);
            }
    }

    /**
     * 订单支付成功后处理 平台账户
     */
    private function updatePlatformAccountOnOrderPay($order_id)
    {
        $order_model = new OrderModel();
        $platfrom_account = new PlatformAccountHandle();
        $order = new OrderBusiness();
        $this->startTrans();


/*
        addAccountOrderRecords($operation_id, $agent_id, $order_id, $order_money, $order_goods_money,
            $order_shipping_money,$order_tax_money,$order_promotion_money,
            $order_coupon_money,$order_point_money, $order_platform_balance_money,
            $order_user_balance_money, $order_coin_money,$order_pay_money,
            $order_pay_money_weixin,$order_pay_money_ali,$order_pay_money_offline,
            $back_point, $back_coupon, $account_type,$remark)
*/
        try {
            $order_obj = $order_model->get($order_id);
            // 订单的实际付款金额
            $pay_money = $order->getOrderRealPayMoney($order_id);
            // 订单的支付方式
            $payment_type = $order_obj["payment_type"];
            // 店铺id
            $shop_id = $order_obj["shop_id"];
            // 订单号
            $order_no = $order_obj["order_no"];

            $operation_id = 1;
            $agent_id = $order_obj['agent_id'];
            $buyer_id = $order_obj['buyer_id'];
            $order_money = $order_obj['order_money'];
            $order_goods_money = $order_obj['goods_money'];
            $order_shipping_money = $order_obj['shipping_money'];
            $order_tax_money = $order_obj['tax_money'];
            $order_promotion_money = $order_obj['promotion_money'];
            $order_coupon_money = $order_obj['coupon_money'];
            $order_point_money = $order_obj['point_money'];
            $order_platform_balance_money = $order_obj['user_platform_money'];
            $order_user_balance_money = $order_obj['user_money'];
            $order_coin_money = $order_obj['coin_money'];
            $order_pay_money = $order_obj['pay_money'];


            $order_pay_money_weixin = 0;
            $order_pay_money_ali = 0;
            $order_pay_money_offline = 0;
            $remark = '订单支付-处理平台订单数据';
            if ($payment_type == 1) {
                $order_pay_money_weixin = $order_obj['pay_money'];
                $remark = $remark.'- 微信支付';
            } else if  ($payment_type == 2) {
                $order_pay_money_ali = $order_obj['pay_money'];
                $remark = $remark.'- 支付宝支付';
            } else if  ($payment_type == 10 || $payment_type == 6) {
                $order_pay_money_offline = $order_obj['pay_money'];
                if ($payment_type == 10) {
                    $remark = $remark.'- 线下支付';
                } else {
                    $remark = $remark.'- 到店支付';
                }
            }

            $pay_type = $order_obj['payment_type'];

            $pay_time = $order_obj['pay_time'];


            $back_point = $order_obj['point'];
            $back_coupon_id = $order_obj['coupon_id'];
            $back_coupon_money = $order_obj['coupon_money'];
            $account_type = 1;







           $res = $platfrom_account->addAccountOrderRecords($operation_id, $agent_id, $order_id,$buyer_id, $order_money, $order_goods_money,
               $order_shipping_money,$order_tax_money,$order_promotion_money,
               $order_coupon_money,$order_point_money, $order_platform_balance_money,
               $order_user_balance_money, $order_coin_money,$order_pay_money,
               $order_pay_money_weixin,$order_pay_money_ali,$order_pay_money_offline,$pay_type,$pay_time,
               $back_point, $back_coupon_id,$back_coupon_money, $account_type,$remark);



           /*
           addAccountOrderRecords($operation_id, $agent_id, $order_id, $order_money, $order_goods_money,
                $order_shipping_money,$order_tax_money,$order_promotion_money,
                $order_coupon_money,$order_point_money, $order_platform_balance_money,
                $order_user_balance_money, $order_coin_money,$order_pay_money,
                $order_pay_money_weixin,$order_pay_money_ali,$order_pay_money_offline,
                $back_point, $back_coupon_id,$back_coupon_money, $account_type,$remark);
            */

            if (empty($res)) {
                $this->rollback();
                $this->error = $platfrom_account->getError();
                return false;

            }


           // if ($payment_type != ORDER_REFUND_STATUS) {
                // 在线支付 处理平台的资金账户
             //   $shop_account->addAccountOrderRecords($shop_id, $pay_money, 1, $order_id, "店铺订单支付金额" . $pay_money . "元, 订单号为：" . $order_no . ", 支付方式【在线支付】。");
          //  }
            $this->commit();
            return true;
        } catch (\Exception $e) {
            Log::write("updatePlatformAccountOnOrderPay:".$e->getMessage());
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 订单完成的时候处理平台帐户中的代理商的佣金
     *
     * @param string $order_out_trade_no
     * @param number $order_id
     */
    private function dealPlatformAccountCommissionOnOrderComplete($order_out_trade_no = "", $order_id = 0)
    {
        $agent_handle = new AgentHandle();
        $order_model = new OrderModel();
        if ($order_out_trade_no != "" && $order_id == 0) {
            $condition = " out_trade_no=" . $order_out_trade_no;
            $order_list = $order_model->getConditionQuery($condition, "id, agent_id", "");
            foreach ($order_list as $k => $v) {
                if (!$agent_handle->isPlatformAgentById($v['agent_id'])) { //非平台代理商
                    $this->updatePlatformAccountCommissionOnOrderComplete($v["id"]);
                }
            }
        } else {
            if ($order_out_trade_no == "" && $order_id != 0) {
                $order = $order_model->get($order_id);
                if (!$agent_handle->isPlatformAgentById($order['agent_id'])) { //非平台代理商
                    $this->updatePlatformAccountCommissionOnOrderComplete($order_id);
                }
            }
        }

    }

    /**
     * 在订单完成时对平台帐户中的代理商的佣金处理
     *
     * @param  $order_id
     */
    private function updatePlatformAccountCommissionOnOrderComplete($order_id)
    {
        $order_model = new OrderModel();
        $platform_account = new PlatformAccountHandle();
        $this->startTrans();
        try {
            $order_obj = $order_model->get($order_id);

            //  $order_money=$order_obj['order_money'];
            $order_goods_money=$order_obj['goods_money'];
            $order_promotion_money = $order_obj['promotion_money'];
            $order_coupon_money=$order_obj['coupon_money'];
            $order_point_money=$order_obj['point_money'];
            $order_refund_money = $order_obj['refund_money'];
            // $order_pay_money = $order_obj['pay_money'];
            // 订单的实际付款金额
            // $pay_money = $order->getOrderRealPayMoney($order_id);

            // 订单的支付方式
            //  $payment_type = $order_obj["payment_type"];
            // 代理商id
            $agent_id = $order_obj["agent_id"];
            // 订单号
            // $order_no = $order_obj["order_no"];
            $agent_handle = new AgentHandle();
            $agent = $agent_handle->getAgentById($agent_id);
            $p_agnet_id = 0;
            //  $from_level = 1;


            /*
            $account_money = $order_goods_money - $order_promotion_money
                -  $order_coupon_money - $order_point_money -  $order_refund_money;
            */

            $order_business = new OrderBusiness();
            $account_money = $order_business->getOrderRealAccountMoney($order_id);
            if ($account_money < 0) {
                $account_money = 0;
            }
            $from_level = 1;
            $account_type = 1;
            $remark = '订单完成-平台帐户中的直接佣金';
        //    addPlatformAccountCommissionRecordsOnAdd($operation_id, $serial_no, $agent_id, $order_id, $from_level,
        //        $account_money, $sign, $account_type, $remark)
            $res = $platform_account->addPlatformAccountCommissionRecordsOnAdd(23, getSerialNo(), $agent_id, $order_id, $from_level,
                $account_money, 1, $account_type, $remark);

            if (empty($res)) {
                $this->rollback();
                $this->error = $platform_account->getError();
                return false;
            }



            if ($agent['agent_type'] == 2) {
                $p_agent_id = $agent['p_agent_id'];
                $from_level = 2;
                $account_type = 1;
                $remark = '订单完成-平台帐户中的间接佣金';
                /*
                addPlatformAccountCommissionRecordsOnAdd($operation_id, $serial_no, $agent_id, $order_id, $from_level,
                    $account_money, $sign, $account_type, $remark)
                */

                $res = $platform_account->addPlatformAccountCommissionRecordsOnAdd(24, getSerialNo(), $p_agent_id, $order_id, $from_level,
                    $account_money, 1, $account_type, $remark);



                if (empty($res)) {
                    $this->rollback();
                    $this->error = $platform_account->getError();
                    return false;
                }
            }

            $this->commit();
            return true;
        } catch (\Exception $e) {
            Log::write("updatePlatformAccountCommissionOnOrderComplete".$e->getMessage());
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 在退款时处理平台帐户
     * @param $order_id
     * @param $order_goods_id
     * @param $refund_real_money
     * @return bool
     */
   private function dealPlatformAccountOnRefund($order_id, $order_goods_id,$refund_real_money ){
       $order_model = new OrderModel();
       $order_goods_model = new OrderGoodsModel();
       $platform_account = new PlatformAccountHandle();
       $this->startTrans();
       try {
           $operation_id = 32;
           $remark = '订单退款-增加平台退款记录';
           $order = $order_model->get($order_id);
           $agent_id = $order['agent_id'];
           $order_goods = $order_goods_model->get($order_goods_id);
           $goods_money = $order_goods['goods_money'];
           $refund_money = $refund_real_money;
           $account_type = 1;


           $res = $platform_account->addAccountRefundRecords($operation_id, $order_id, $order_goods_id, $agent_id, $goods_money,
               $refund_money, $account_type, $remark);

           if (empty($res)) {
               $this->rollback();
               Log::write('platform_account->addAccountRefundRecords 出错');
               $this->error = $platform_account->getError();
               return false;
           }
           $this->commit();
           return true;
       } catch (\Exception $e) {
           $this->rollback();
           Log::write('dealPlatformAccountOnRefund 出现异常 '.$e->getMessage());
           $this->error = $e->getMessage();
           return false;
       }
   }

    /**
     * 在退款时处理平台佣金
     * @param $order_id
     * @param $order_goods_id
     * @param $refund_real_money
     * @return bool
     */
    private function dealPlatformCommissionOnRefund($order_id, $order_goods_id,$refund_real_money ){
        $order_model = new OrderModel();
        $platform_account = new PlatformAccountHandle();

        $order = $order_model->get($order_id);
        $agent_id = $order['agent_id'];
        $agent_handle = new AgentHandle();
        if ($agent_handle->isPlatformAgentById($agent_id)) { //平台代理商不计算佣金
            return true;
        }
        $this->startTrans();
        try {

            $operation_id = 37;
            $remark = '订单退款-平台增加代理商退直接佣金记录';
            $refund_money = $refund_real_money;
            $from_level =1;
            $res = $platform_account->addPlatformRefundCommissonReconds($operation_id,$order_id,$order_goods_id,$agent_id, $from_level,
                $refund_money,$remark );
            if (empty($res)) {
                $this->rollback();
                Log::write('platform_account->addPlatformRefundCommissonReconds 出错');
                $this->error = $platform_account->getError();
                return false;
            }
            $agent = $agent_handle->getAgentById($agent_id);
            $agent_type = $agent['agent_type'];
            if ($agent_type == 2) {
                $operation_id = 38;
                $remark = '订单退款-平台增加代理商退间接佣金记录';
                $refund_money = $refund_real_money;
                $from_level =2;
                $p_agent_id = $agent['p_agent_id'];
                if (!$agent_handle->isPlatformAgentById($p_agent_id)) {
                    $res = $platform_account->addPlatformRefundCommissonReconds($operation_id, $order_id, $order_goods_id, $p_agent_id, $from_level,
                        $refund_money, $remark);
                    if (empty($res)) {
                        $this->rollback();
                        Log::write('platform_account->addPlatformRefundCommissonReconds 出错');
                        $this->error = $platform_account->getError();
                        return false;
                    }
                }

            }
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            Log::write('dealPlatformCommissionOnRefund 出现异常 '.$e->getMessage());
            $this->error = $e->getMessage();
            return false;
        }
    }


    /**
     * 订单支付成功后处理 平台账户
     *
     * @param  $orderid            
     */
    private function updateAccountOrderPay($order_id)
    {
        /*
        $order_model = new NsOrderModel();
        $shop_account = new ShopAccount();
        $order = new OrderBusiness();
        $this->startTrans();
        try {
            $order_obj = $order_model->get($order_id);
            // 订单的实际付款金额
            $pay_money = $order->getOrderRealPayMoney($order_id);
            // 订单的支付方式
            $payment_type = $order_obj["payment_type"];
            // 店铺id
            $shop_id = $order_obj["shop_id"];
            // 订单号
            $order_no = $order_obj["order_no"];
            if ($payment_type != ORDER_REFUND_STATUS) {
                // 在线支付 处理平台的资金账户
                $shop_account->addAccountOrderRecords($shop_id, $pay_money, 1, $order_id, "店铺订单支付金额" . $pay_money . "元, 订单号为：" . $order_no . ", 支付方式【在线支付】。");
            }
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
        }
        */
    }

    /**
     * 订单完成时 处理平台的利润抽成
     *
     * @param  $order_id            
     */
    private function updateAccountOrderComplete($order_id)
    {}

    /**
     * 订单退款 更细平台的订单支付金额
     *
     * @param  $order_goods_id            
     */
    private function updateAccountOrderRefund($order_goods_id)
    {}

    /**
     * ***********************************************平台账户计算--End******************************************************
     */
    
    /**
     * ***********************************************订单的佣金计算--Start******************************************************
     */

    /**
     * 订单完成后佣金操作
     *
     * @param  $order_out_trade_no
     * @param  $order_id
     */
    private function orderCommissionCalculate($order_out_trade_no = "", $order_id = 0)
    {


    }

    /**
     * 处理单个 订单佣金计算
     *
     * @param  $order_id
     */
    private function oneOrderCommissionCalculate($order_id)
    {





    }



    /**
     * 订单退款成功后需要重新计算订单的佣金
     *
     * @param  $order_id            
     * @param  $order_goods_id            
     */
    public function updateCommissionMoney($order_id, $order_goods_id)
    {
        // 单店基础版不进行计算
        /*
        if (NS_VERSION != NS_VER_B2C) {
            $commissionCalculate = new NfxCommissionCalculate($order_id, $order_goods_id);
            // 重新计算分销佣金
            $commissionCalculate->updateOrderDistributionCommission();
            // 重新计算股东分红
            $commissionCalculate->updateOrderPartnerCommission();
            // 重新计算区域代理佣金
            $commissionCalculate->updateOrderRegionAgentCommission();
            // 订单退款成功后 发放佣金
            $this->updateOrderCommission($order_id);
        }
        */
    }

    /**
     * 订单完成交易进行 佣金结算
     *
     * @param  $order_id            
     */
    private function updateOrderCommission($order_id)
    {
        /*
        if (NS_VERSION != NS_VER_B2C) {
            $order_model = new NsOrderModel();
            $this->startTrans();
            try {
                $shop_obj = $order_model->get($order_id);
                $order_sataus = $shop_obj["order_status"];
                // 判断当前订单的状态是否 已经交易完成 或者 已退款的状态
                if ($order_sataus == ORDER_COMPLETE_SUCCESS || $order_sataus == ORDER_COMPLETE_REFUND || $order_sataus == ORDER_COMPLETE_SHUTDOWN) {
                    // 得到订单的店铺id
                    $shop_id = $shop_obj["shop_id"];
                    // 得到订单用户id
                    $uid = $shop_obj["buyer_id"];
                    $user_service = new NfxUser();
                    // 发放订单的三级分销佣金
                    $user_service->updateCommissionDistributionIssue($order_id);
                    // 更新当前用户的分销商等级
                    $user_service->updatePromoterLevel($uid, $shop_id);
                    // /发放订单的区域代理佣金
                    $user_service->updateCommissionRegionAgentIssue($order_id);
                    // 发放订单的股东分红佣金
                    $user_service->updateCommissionPartnerIssue($order_id);
                    // 更新用户的股东等级
                    $user_service->updatePartnerLevel($uid, $shop_id);
                }
                $this->commit();
            } catch (\Exception $e) {
                $this->rollback();
            }
        }
        */
    }

    /**
     * ***********************************************订单的佣金计算--End******************************************************
     */
    
    /**
     * ***********************************************招商员的账户计算--Start******************************************************
     */
    /**
     * 招商员的订单佣金计算
     *
     * @param string $order_out_trade_no            
     * @param number $order_id            
     */
    private function AssistantOrderCommissionCalculate($order_out_trade_no = "", $order_id = 0)
    {}

    /**
     * 订单退款 更新佣金金额
     *
     * @param  $order_id            
     */
    private function UpdateAssistantOrderCommissionRefund($order_id)
    {
        /*
        $Assistant_account_service = new NbsBusinessAssistantAccount();
        $Assistant_account_service->updateOrderBusinessAssistant($order_id);
        */
    }

    /**
     * 订单交易完成发放订单的佣金
     *
     * @param  $order_id            
     */
    private function UpdateAssistantOrderCommission($order_id)
    {}

    /**
     * ***********************************************招商员的账户计算--End******************************************************
     */
    /**
     * 查询店铺的退货设置-ok-2ok
     */
    public function getShopReturnSet($shop_id=0)
    {
        $shop_return = new OrderShopReturnModel();
        $shop_return_obj = $shop_return->get(['shop_id'=>$shop_id]);
        if (empty($shop_return_obj)) {
            $data = array(
                "shop_id" => $shop_id,
                "create_time" => time()
            );
            $shop_return->save($data);
            $shop_return_obj = $shop_return->get(['shop_id'=>$shop_id]);
        }
        return $shop_return_obj;
    }

    /**
     *
     * 更新店铺的退货信息-ok-2ok
     */
    public function updateShopReturnSet($shop_id=0, $address, $real_name, $mobile, $zipcode)
    {
        $shop_return = new OrderShopReturnModel();
        $data = array(
            "shop_address" => $address,
            "seller_name" => $real_name,
            "seller_mobile" => $mobile,
            "seller_zipcode" => $zipcode,
            "update_time" => time()
        );
        $result = $shop_return->save($data, [
            "shop_id" => $shop_id
        ]);
        if ($result === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 得到订单的发货信息-ok-2ok
     */
    public function getOrderGoodsExpressDetail($order_ids)
    {
        $order_goods_model = new OrderGoodsModel();
        $order_model = new OrderModel();
        $order_goods_express = new OrderGoodsExpressModel();
        // 查询订单的订单项的商品信息
        $order_goods_list = $order_goods_model->where(" order_id in ($order_ids)")->select();
        
        for ($i = 0; $i < count($order_goods_list); $i ++) {
            $order_id = $order_goods_list[$i]["order_id"];
            $order_goods_id = $order_goods_list[$i]["id"];
            $order_obj = $order_model->get($order_id);
            $order_goods_list[$i]["order_no"] = $order_obj["order_no"];
            $goods_express_obj = $order_goods_express->where("FIND_IN_SET($order_goods_id,order_goods_id_array)")->select();
            if (! empty($goods_express_obj)) {
                $order_goods_list[$i]["express_company"] = $goods_express_obj[0]["express_company"];
                $order_goods_list[$i]["express_no"] = $goods_express_obj[0]["express_no"];
            } else {
                $order_goods_list[$i]["express_company"] = "";
                $order_goods_list[$i]["express_no"] = "";
            }
        }
        return $order_goods_list;
    }

    /**
     * 通过订单id 得到 该订单的发货物流-ok
     */
    public function getOrderGoodsExpressList($order_id)
    {
        $order_goods_express_model = new OrderGoodsExpressModel();
        $express_list = $order_goods_express_model->getConditionQuery([
            "order_id" => $order_id
        ], "*", "");
        return $express_list;
    }

    /**
     * 订单提货-ok
     */
    public function pickupOrder($user_id, $user_type,$order_id, $buyer_name, $buyer_phone, $remark)
    {
        $order = new OrderBusiness();
        $retval = $order->pickupOrder($user_id, $user_type,$order_id, $buyer_name, $buyer_phone, $remark);
        if (empty($retval)) {
            $this->error = $order->getError();
            return false;
        }
        return $retval;
    }

    /**
     * 查询订单项的物流信息-ok-2ok
     *
     * @param  $order_goods_id            
     */
    public function getOrderGoodsExpressMessage($express_id)
    {
        try {
            $order_express_model = new OrderGoodsExpressModel();
            $express_obj = $order_express_model->get($express_id);
            if (! empty($express_obj)) {
                $order_id = $express_obj["order_id"];
                $order_model = new OrderModel();
                // 订单编号
                $order_obj = $order_model->get($order_id);
                $order_no = $order_obj["order_no"];
              //  $shop_id = $order_obj["shop_id"];
                // 物流公司信息
                $express_company_id = $express_obj["express_company_id"];
                $express_company_model = new OrderExpressCompanyModel();
                $express_company_obj = $express_company_model->get($express_company_id);
                // 快递公司编号
                $express_no = $express_company_obj["express_no"];

                // 物流编号
                $send_no =   $express_obj["express_no"];
                Log::write("OrderCode:".$order_no);
                Log::write("订单物流 物流编号:".$send_no);
                Log::write("ShipperCode:".$express_no);
                Log::write("LogisticCode:".$send_no);
              //  $shop_id = 0;
                $kdniao = new Kdniao(); //$shop_id); //快递待实现
                $data = array(
                    "OrderCode" => $order_no,
                    "ShipperCode" => $express_no,
                    "LogisticCode" => $send_no
                );
                $result = $kdniao->getOrderTracesByJson(json_encode($data));
                Log::write("订单物流:".$result);
                return json_decode($result, true);
            } else {
                $this->error = "订单物流信息有误!";
                return false;
                /*
                return array(
                    "Success" => false,
                    "Reason" => "订单物流信息有误!"
                );
                */
            }
        } catch (\Exception $e) {
            $this->error = "订单物流信息有误!".$e->getMessage();
            return false;
            /*
            return array(
                "Success" => false,
                "Reason" => "订单物流信息有误!"
            );
            */
        }
    }


    /**
     * 查询订单项的物流信息-ok-2ok
     *
     * @param  $order_goods_id
     */
    /**
    public function getOrderGoodsExpressMessage2222($express_id)
    {
        try {


                // 物流编号
                $send_no =  '468280227737';  // $express_obj["express_no"];
                Log::write("订单物流 物流编号:".$send_no);
                //  $shop_id = 0;
                $kdniao = new Kdniao(); //$shop_id); //快递待实现
                $data = array(
                    "OrderCode" => '11111',//$order_no,
                    "ShipperCode" => 'ZTO',// $express_no,
                    "LogisticCode" => $send_no
                );
                $result = $kdniao->getOrderTracesByJson(json_encode($data));
                Log::write("订单物流:".$result);
                return json_decode($result, true);

        } catch (\Exception $e) {
            $this->error = "订单物流信息有误!".$e->getMessage();
            return false;
            /*
            return array(
                "Success" => false,
                "Reason" => "订单物流信息有误!"
            );
            */
    /**
        }
    }
     * **/

    /**
     * 添加卖家对订单的备注-ok-2ok
     */
    public function addOrderSellerMemo($order_id, $memo)
    {
        $order = new OrderModel();
        $data = array(
            'seller_memo' => $memo
        );
        $retval = $order->save($data, [
            'id' => $order_id
        ]);
        if ($retval === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 获取订单备注信息-ok
     */
    public function getOrderSellerMemo($order_id)
    {
        $order = new OrderModel();
        $res = $order->getConditionQuery([
            'id' => $order_id
        ], "seller_memo", '');
        $seller_memo = "";
        if (! empty($res[0]['seller_memo'])) {
            $seller_memo = $res[0]['seller_memo'];
        }
        return $seller_memo;
    }

    /**
     * 得到订单的收货地址--ok-2ok
     */
    public function getOrderReceiveDetail($order_id){
        $order = new OrderModel();
        $res = $order->getInfo([
            'id' => $order_id
        ], "id,receiver_mobile,receiver_province,receiver_city,receiver_district,receiver_address,receiver_zip,receiver_name");
        return $res;
    }

    /**
     * 更新订单的收货地址--ok-2ok
     */
    public function updateOrderReceiveDetail($order_id, $receiver_mobile, $receiver_province, $receiver_city, $receiver_district, $receiver_address, $receiver_zip, $receiver_name){
        $order = new OrderModel();
        $data = array(
            'receiver_mobile' => $receiver_mobile,
            'receiver_province' => $receiver_province,
            'receiver_city' => $receiver_city,
            'receiver_district' => $receiver_district,
            'receiver_address' => $receiver_address,
            'receiver_zip' => $receiver_zip,
            'receiver_name' => $receiver_name
        );
        $retval = $order->save($data, [
            'id' => $order_id
        ]);

        if ($retval === false) {
            return false;
        } else {
            return true;
        }
    }
    /**
     * ok-2ok
     * 获取自提点运费
     * @param  $goods_sku_list
     */
    public function getPickupMoney($goods_sku_list_price)
    {
        $goods_preference = new GoodsPreferenceHandle();
        $pick_money = $goods_preference->getPickupMoney($goods_sku_list_price);
         return $pick_money;
    
    }
    
    public function getOrderNumByOrderStatu($condition){
        $order = new OrderModel();
        return $order->getCount($condition);
    }
    
    /**
     * 评论送积分--ok
     */
    public function commentPoint($user_id, $order_id){
        //给记录表添加记录
        $goods_comment = new GoodsCommentModel();
        $rewardRule = new PromoteRewardRuleHandle();
        //查询评论赠送积分数量，然后叠加
        $shop_id = 0; //$this->instance_id;
        $info = $rewardRule->getRewardRuleDetail($shop_id);
        $data = array(
            'shop_id'     =>   $shop_id,
            'user_id'         =>   $user_id,
            'order_id'    =>   $order_id,
            'status'      =>   1,
            'number'      =>   $info['comment_point'],
            'create_time' =>   time()
        );
        $retval = $goods_comment->save($data);
        if($retval>0){
            //给总记录表加记录
            $result = $rewardRule->addMemberPointData($shop_id, $user_id, $info['comment_point'], 20, '评论赠送积分');
        
        }
    }
    /**
     * 
     * 查询会员的某个订单的条数--ok
     */
    public function getUserOrderDetailCount($user_id, $order_id){
        $order_count=0;
        $orderModel=new OrderModel();
        $condition=array(
          "buyer_id"=>$user_id,
          "id"=>$order_id
        );
        $order_count=$orderModel->getCount($condition);
        return $order_count;
    }
    
    /**
     * 查询会员某个条件的订单的条数--ok
     */
    public function getUserOrderCountByCondition($condition){
        $order_count=0;
        $orderModel=new OrderModel();
        $order_count=$orderModel->getCount($condition);
        return $order_count;
    }
    /**
     * 查询会员某个条件下的订单商品数量--ok
     */
    public function getUserOrderGoodsCountByCondition($condition){
        $order_goods = new OrderGoodsModel();
        $order_count = $order_goods -> getCount($condition);
        return $order_count;
    }

    /**
     * ok-2ok
     * 订单统计列表
     * @param $page_index
     * @param int $page_size
     * @param string $condition
     * @param string $order
     * @return \dao\model\multitype
     */
    public function getOrderAccountRecordsList($page_index=1, $page_size = 0, $condition = '', $order = '')
    {
        $order_goods = new OrderGoodsViewModel();
        $return = $order_goods->getOrderGoodsViewList($page_index, $page_size, $condition, $order);
        return $return;
    }

    /**
     * ok-2ok
     * 根据外部交易号查询订单状态
     */
    public function getOrderStatusByOutTradeNo($out_trade_no)
    {
        if (! empty($out_trade_no)) {
            $order_model = new OrderModel();
            $order_status = $order_model->getInfo([
                'out_trade_no' => $out_trade_no
            ], 'order_status');
            return $order_status;
        }
        return 0;
    }

    /**
     * ok-2ok
     * 根据外部交易号查询订单编号，为了兼容多店版。所以返回一个数组
     */
    public function getOrderNoByOutTradeNo($out_trade_no)
    {
        if (! empty($out_trade_no)) {
            $order_model = new OrderModel();
            $list = $order_model->getConditionQuery([
                'out_trade_no' => $out_trade_no
            ], 'order_no', '');

            return $list;
        }
        return [];
    }

    /**
     * ok-2ok
     * 根据订单查询付款方式，，用于进行退款操作时，选择退款方式
     */
    public function getTermsOfPaymentByOrderId($order_id)
    {
        if (! empty($order_id)) {
            $order_model = new OrderModel();
            $order_info = $order_model->getInfo([
                'id' => $order_id
            ], "out_trade_no,pay_money");

            // 如果订单实际支付金额为0，则只能进行线下
            if ($order_info['pay_money'] == 0) {
                return 10; // 线下退款id为10
            }
            // 准确的查询出付款方式
            $order_payment_model = new OrderPaymentModel();
            $pay_type = $order_payment_model->getInfo([
                "out_trade_no" => $order_info['out_trade_no']
            ], 'pay_type');

            return $pay_type['pay_type'];
        }
        return 0;
    }

    /**
     * ok-2ok
     * 根据订单项id查询订单退款账户记录
     */
    public function getOrderRefundAccountRecordsByOrderGoodsId($order_goods_id)
    {
        $model = new OrderRefundAccountRecordsModel();
        $info = $model->getInfo([
            "order_goods_id" => $order_goods_id
        ], "*");
        return $info;
    }

}