<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-08
 * Time: 14:20
 */
/**
 * Order.php
 * 订单控制器
 * @date : 2017.9.1
 * @version : v1.0
 */

namespace app\shop\controller;
use app\shop\controller\BaseController;

use dao\handle\AgentHandle;
use dao\handle\ExpressHandle;
use dao\handle\MemberHandle;
use dao\handle\promotion\GoodsExpressHandle as GoodsExpressHandle;
use dao\handle\GoodsHandle;
use dao\handle\member\MemberUserHandle;
use dao\handle\order\OrderHandle as OrderOrderHandle;
use dao\handle\OrderHandle as OrderHandle;
use dao\handle\promotion\GoodsMansongHandle;
use dao\model\Cart as CartModel;
use dao\model\Goods as GoodsModel;
use dao\model\AlbumPicture as AlbumPictureModel;
use dao\handle\ConfigHandle;
use dao\handle\PromotionHandle;

use dao\handle\promotion\GoodsPreferenceHandle;
use think\Log;



class Order  extends BaseController
{
    /**
     * 待付款订单
     */
    public function paymentOrder()
    {
        $this->orderInfo();
      //  return view($this->style . '/Order/paymentOrder');
    }

    /**
     * ok-2ok
     * 生成订单需要的数据
     */
    public function orderInfo()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");
        if ($user_id == 0) {
            return $authRet;
        }
        $order_tag = request()->post('order_tag', 'cart'); //订单标志， cart表示由购物车生成， buy_now表示立即购买
        $goods_handle = new GoodsHandle();

        if ($order_tag == 'buy_now') {
            $goods_list = request()->post('goods_list');
            $cartlist = $goods_handle->getNowBuyGoodsInfo($goods_list, $user_id);
            if (empty($cartlist)) {
                if (empty($goods_handle->getError())) {
                    return json(resultArray(2, "没有获取到所购买的商品数据"));
                } else {
                    return json(resultArray(2, $goods_handle->getError()));
                }
            }
        } else {
            $cartlist = $goods_handle->getCartSelected($user_id);
            if (empty($cartlist)) {
                return json(resultArray(2, "没有获取到所需的购物车数据"));
            }
        }
        $goods_list = '';
        foreach ($cartlist['data'] as $cart_k => $cart_v) {
            if (!empty($cart_v['goods_id'])) {
                $goods_list = $goods_list . $cart_v['goods_id'] . ':' . $cart_v['sku_id'] . ':' . $cart_v['num'] . ',';
            }
        }
        if (!empty($goods_list) && substr($goods_list, -1, 1) == ',') {
            $goods_list = substr($goods_list, 0, strlen($goods_list) - 1);
        }

        Log::write("goods_list:".$goods_list);

        // $goods_list = request()->post('goods_list'); // 商品列表

        $member = new MemberHandle();
        $order = new OrderHandle();
        $goods_mansong = new GoodsMansongHandle();
        $goods_handle = new GoodsHandle();
        $Config = new ConfigHandle();
        $promotion = new PromotionHandle();
        $agent_handle = new AgentHandle();
       // getDefaultExpressAddress($user_id)

        $address = $member->getDefaultExpressAddress($user_id); // 获取默认收货地址
        $addresslist = $member->getMemberExpressAddressList($user_id);
        $express = 0;
        $express_company_list = array();
        $goods_express_handle = new GoodsExpressHandle();
        if (! empty($address)) {
            // 物流公司
         //   Log::write("before".$goods_list);
            $express_company_list = $goods_express_handle->getExpressCompanyByGoods($goods_list, $address['province'], $address['city'], $address['district'],$user_id);
          //  Log::write("after".$goods_list);
            if (! empty($express_company_list)) {
                foreach ($express_company_list as $v) {
                    $express = $v['express_fee']; // 取第一个运费，初始化加载运费
                    break;
                }
            } else {
                //获得固定运费
                $express = $goods_handle->getFixedShippingFeeByGoodsList($goods_list);
            }
            $address_is_have = 1;
         //   $this->assign("address_is_have", 1);
           //  $this->assign("address_is_have", $address_is_have);
        } else {
            $address_is_have = 0;
          //  $this->assign("address_is_have", 0);
          //  $this->assign("address_is_have", $address_is_have);
        }
        $count = $goods_express_handle->getExpressCompanyCount();
        $express_company_count = $count;
        //$this->assign("express_company_count", $count); // 物流公司数量
     //   if ($express <= 0) {
            //获得固定运费
           // $express = $goods_handle->getShippingFeeByGoodsList($goods_list);
     //   }

        $express = sprintf("%.2f", $express);
       // $this->assign("express", sprintf("%.2f", $express)); // 运费
      //  $this->assign("express_company_list", $express_company_list); // 物流公司

        $discount_money = $goods_mansong->getGoodsMansongMoneyByGoodsList($user_id, $goods_list);

       // getGoodsMansongMoney($goods_sku_list);
        $discount_money = sprintf("%.2f", $discount_money);
       // $this->assign("discount_money", sprintf("%.2f", $discount_money)); // 总优惠

        $count_money = $order->getGoodsListPrice($user_id, $goods_list);

       // getGoodsSkuListPrice($goods_sku_list);
      //  $this->assign("count_money", sprintf("%.2f", $count_money)); // 商品金额
        $pick_up_money = $order->getPickupMoney($count_money);

       // $this->assign("pick_up_money", $pick_up_money);
        $count_point_exchange = 0;
        /*
        foreach ($goods_list as $k => $v) {
            $list[$k]['price'] = sprintf("%.2f", $goods_list[$k]['price']);
            $list[$k]['subtotal'] = sprintf("%.2f", $list[$k]['price'] * $list[$k]['num']);
            if ($v["point_exchange_type"] == 1) {
                if ($v["point_exchange"] > 0) {
                    $count_point_exchange += $v["point_exchange"] * $v["num"];
                }
            }
        }
        $this->assign("count_point_exchange", $count_point_exchange); // 总积分
        $this->assign("itemlist", $list);

*/
        $shop_config = $Config->getShopConfig();
        $order_invoice_content = explode(",", $shop_config['order_invoice_content']);
        $shop_config['order_invoice_content_list'] = array();
        foreach ($order_invoice_content as $v) {
            if (! empty($v)) {
                array_push($shop_config['order_invoice_content_list'], $v);
            }
        }
      //  $this->assign("shop_config", $shop_config); // 后台配置

        $member_account = $member->getMemberAccount($user_id);

        if ($member_account['balance'] == '' || $member_account['balance'] == 0) {
            $member_account['balance'] = '0.00';
        }
       // $this->assign("member_account", $member_account); // 用户余额

        $coupon_list = $order->getMemberCouponList($user_id, $goods_list);

       // $this->assign("coupon_list", $coupon_list); // 获取优惠券

        $promotion_full_mail = $promotion->getPromotionFullMail();

        if (! empty($address)) {
            $no_mail = checkIdIsinIdArr($address['city'], $promotion_full_mail['no_mail_city_id_array']);
            if ($no_mail) {
                $promotion_full_mail['is_open'] = 0;
            }
        }
       // $this->assign("promotion_full_mail", $promotion_full_mail); // 满额包邮
        $member_user_handle = new MemberUserHandle();
        $member_user = $member_user_handle->getUserInfoById($user_id);
        $agent_id = $member_user['agent_id'];

        $where = ['agent_id'=>$agent_id];

        $pickup_point_list = $agent_handle->getPickupPointList(1,  0, $where, 'id desc');

        $promotion = new PromotionHandle();
        $point_config = $promotion->getPointConfig();
        $point_convert_rate = $point_config['convert_rate'];



      //  $this->assign("pickup_point_list", $pickup_point_list); // 自提地址列表

      //  $this->assign("address_default", $address);
        $data = array (
            'order_tag'=>$order_tag,
            'select_goods_list' => $cartlist,
            'goods_list'=>$goods_list,
            'address_is_have' => $address_is_have, //是否有收货地址
            'address_list'=>$addresslist,
            "address_default"=>$address,
            'express_company_count'=>$express_company_count,   //物流公司数量
            'express' => $express,  // 运费
            'express_company_list' =>$express_company_list, // 物流公司列表
            'discount_money'=>$discount_money, //总优惠
            'count_money'=>$count_money,  // 商品金额
            "pick_up_money"=>$pick_up_money,//自提费用
            "shop_config"=>$shop_config, //相关设置
            "member_account"=> $member_account,// 用户积分，余额
            'point_convert_rate'=>$point_convert_rate,//积分兑换率
            "coupon_list"=>$coupon_list, // 获取优惠券
            "promotion_full_mail"=>$promotion_full_mail,
            "pickup_point_list"=> $pickup_point_list, //自提地址列表
           // "promotion_full_mail", $promotion_full_mail,//满额包邮
        );
        return json(resultArray(0,"操作成功",$data));
    }


    /**
     * ok-2ok
     * 创建订单
     */
    public function orderCreate()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");
        if ($user_id == 0) {
            return $authRet;
        }
        $order = new OrderHandle();
        $order_tag = request()->post('order_tag', 'cart');//订单标志
        // 获取支付编号
        $out_trade_no = $order->getOrderTradeNo();
        $use_coupon = request()->post('use_coupon', 0); // 优惠券
        $integral = request()->post('integral', 0); // 积分
        $goods_list = request()->post('goods_list', ''); // 商品列表
        $leavemessage = request()->post('leavemessage', ''); // 留言
        $user_money = request()->post("account_balance", 0); // 使用余额
        $pay_type = request()->post("pay_type", 1); // 支付方式
        $buyer_invoice = request()->post("buyer_invoice", ""); // 发票
        $pick_up_id = request()->post("pick_up_id", 0); // 自提点
        $shipping_company_id = request()->post("shipping_company_id", 0); // 物流公司

        $shipping_type = 1; // 配送方式，1：物流，2：自提
        if ($pick_up_id != 0) {
            $shipping_type = 2;
        }
        $member = new MemberHandle();
        //  getDefaultExpressAddress($user_id)
        $address = $member->getDefaultExpressAddress($user_id);
        $shipping_time = date("Y-m-d H::i:s", time());
       // orderCreate($user_id, $user_type, $order_type, $out_trade_no, $pay_type, $shipping_type, $order_from, $buyer_ip, $buyer_message, $buyer_invoice, $shipping_time, $receiver_mobile, $receiver_province, $receiver_city, $receiver_district, $receiver_address, $receiver_zip, $receiver_name, $point, $coupon_id, $user_money, $goods_list, $platform_money, $pick_up_id, $shipping_company_id, $coin)

        $order_id = $order->orderCreate($user_id, 1,'1', $out_trade_no, $pay_type, $shipping_type, '1', 1, $leavemessage, $buyer_invoice, $shipping_time, $address['mobile'],
            $address['province'], $address['city'], $address['district'], $address['address'], $address['zip_code'], $address['consigner'], $integral, $use_coupon, 0, $goods_list, $user_money, $pick_up_id, $shipping_company_id);
        Log::write($order_id);
        if ($order_id === false) {
            return json(resultArray(2,"操作失败: ".$order->getError()));
        }
        if ($order_id > 0) {
            //  deleteCart($goods_list, $user_id)
            if ($order_tag == 'cart') { //如果是通过购物车生成的订单，则去除购物车中的相应的项
                $order->deleteCart($goods_list, $user_id);
            }
           // $_SESSION['order_tag'] = ""; // 生成订单后，清除购物车
            $data = array(

                'order_id'=> $order_id,
              //  'order_no' =>
                'out_trade_no'=>$out_trade_no
            );
            return json(resultArray(0,"操作成功",$data));
            // return AjaxReturn($out_trade_no);
        } else {
            return json(resultArray(2,"操作失败: ".$order_id.$order->getError()));
            //  return AjaxReturn($order_id);
        }
    }

    /**
     * 获取当前会员的订单列表-ok
     */
    public function myOrderList()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");
        if ($user_id == 0) {
            return $authRet;
        }
       // $status = isset($this->param['status']) ? $this->param['status'] : 'all';
        //  if (request()->isAjax()) {
        //$status = isset($_POST['status']) ? $_POST['status'] : 'all';
        $status = isset($this->param['status']) ? $this->param['status'] : 'all';
        $condition['buyer_id'] = $user_id;
        $condition['is_deleted'] = 0;
        /*
            if (! empty($this->shop_id)) {
                $condition['shop_id'] = $this->shop_id;
            }
           */
        if ($status != 'all') {
            switch ($status) {
                case 0:
                    $condition['order_status'] = 0;
                    break;
                case 1:
                    $condition['order_status'] = 1;
                    break;
                case 2:
                    $condition['order_status'] = 2;
                    break;
                case 3:
                    $condition['order_status'] = array(
                        'in',
                        '3,4'
                    );
                    break;
                case 4:
                    $condition['order_status'] = array(
                        'in',
                        [
                            - 1,
                            - 2
                        ]
                    );
                    break;
                case 5:
                    $condition['order_status'] = array(
                        'in',
                        '3,4'
                    );
                    $condition['is_evaluate'] = array(
                        'in',
                        '0,1'
                    );
                    break;
                case 6:
                    $condition['order_status'] = 5;
                    break;
                default:
                    break;
            }
        }
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", PAGESIZE);
        // 还要考虑状态逻辑

        $order = new OrderHandle();
        // getOrderList($page_index = 1, $page_size = 0, $condition = '', $order = '')
        $order_list = $order->getOrderList($page_index, $page_size, $condition, 'create_time desc');
        // return $order_list['data'];
        return json(resultArray(0, "操作成功",  $order_list));

        // $this->assign("status", $status);
        // return view($this->style . '/Order/myOrderList');

    }

    /**
     * 订单详情-ok
     */
    public function orderDetail()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");
        if ($user_id == 0) {
            return $authRet;
        }
        $order_id = isset($this->param['order_id']) ? $this->param['order_id'] : 0;
        if ($order_id == 0) {
            return json(resultArray(2, "没有获取到订单信息"));
            // $this->error("没有获取到订单信息");
        }
        $order_handle = new OrderHandle();
        // getOrderDetail($order_id)
        $detail = $order_handle->getOrderDetail($order_id);
        if (empty($detail)) {
            //  $this->error("没有获取到订单信息");
            return json(resultArray(2, "没有获取到订单信息"));
        }
        //通过order_id判断该订单是否属于当前用户
        $condition['id'] = $order_id;
        $condition['buyer_id'] = $user_id;
        $order_count = $order_handle->getOrderCount($condition);
        if($order_count == 0){
            //  $this->error("没有获取到订单信息");
            return json(resultArray(2, "没有获取到订单信息"));
        }

        $count = 0; // 计算包裹数量（不包括无需物流）
        $express_count = count($detail['goods_packet_list']);
        $express_name = "";
        $express_code = "";
        if ($express_count) {
            foreach ($detail['goods_packet_list'] as $v) {
                if ($v['is_express']) {
                    $count ++;
                    if (! $express_name) {
                        $express_name = $v['express_name'];
                        $express_code = $v['express_code'];
                    }
                }
            }
            // $this->assign('express_name', $express_name);
            // $this->assign('express_code', $express_code);
        }
        //  $this->assign('express_count', $express_count);
        //  $this->assign('is_show_express_code', $count); // 是否显示运单号（无需物流不显示）
        $data = array(
            "order_details" => $detail,
            "express_name" => $express_name,
            "express_code" => $express_code,
            "express_count" => $express_count,
            "is_show_express_code" => $count // 是否显示运单号（无需物流不显示）


        );
        return json(resultArray(0, "操作成功", $data));


        //  $this->assign("order", $detail);
        //  return view($this->style . '/Order/orderDetail');
    }

    /**
     * 订单后期支付页面-ok
     */
    public function orderPay()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");
        if ($user_id == 0) {
            return $authRet;
        }
        $order_id = isset($_GET['id']) ? $_GET['id'] : 0;
        $out_trade_no = isset($_GET['out_trade_no']) ? $_GET['out_trade_no'] : 0;
        $order_handle = new OrderHandle();
        if ($order_id != 0) {
            // 更新支付流水号
            // getOrderNewOutTradeNo($order_id)
            $new_out_trade_no = $order_handle->getOrderNewOutTradeNo($order_id);
            //  $url = __URL(__URL__ . '/wap/pay/getpayvalue?out_trade_no=' . $new_out_trade_no);
            //  header("Location: " . $url);
            //  exit();
            $data = array(
                "out_trade_no"=>$new_out_trade_no
            );
            return json(resultArray(0,"操作成功",$data));
        } else {
            // 待结算订单处理
            if ($out_trade_no != 0) {
                // $url = __URL(__URL__ . '/wap/pay/getpayvalue?out_trade_no=' . $out_trade_no);
                $data = array(
                    "out_trade_no"=>$out_trade_no
                );
                return json(resultArray(0,"操作成功",$data));
                //  exit();
            } else {
                return json(resultArray(2,"没有获取到支付信息"));
                //  $this->error("没有获取到支付信息");
            }
        }
    }

    public function getOrderStatusNum() {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");
        if ($user_id == 0) {
            return $authRet;
        }
        $order_handle = new OrderHandle();
        $condition = array(
            "buyer_id" => $user_id
        );
        $data = $order_handle->getOrderStatusNum($condition);
        return json(resultArray(0,"操作成功",$data));
    }

    /***************************************************20171101************************************************/
    /**
     * 订单项退款详情
     */
    public function refundDetail()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");
        if ($user_id == 0) {
            return $authRet;
        }
        $order_goods_id =  isset($this->param['order_goods_id']) ? $this->param['order_goods_id'] : 0;
           // request()->get('order_goods_id', 0)
        if (empty($order_goods_id)) {
            return json(resultArray(2,"没有获取到退款信息"));
        }
        if (! is_numeric($order_goods_id)) {
            return json(resultArray(2,"没有获取到退款信息"));
        }
        $order_handle = new OrderHandle();
        $detail = $order_handle->getOrderGoodsRefundInfo($order_goods_id);
        $refund_money = $order_handle->orderGoodsRefundMoney($order_goods_id);
        // 查询店铺默认物流地址
        $express = new ExpressHandle();
        $address = $express->getDefaultPlatformExpressAddress();
        // 查询商家地址
        $shop_info = $order_handle->getShopReturnSet();
       // return view($this->style . '/Order/refundDetail');
        $data = array(
            "order_refund"=>$detail,
           // "detail"=> $detail,
            'refund_money'=> $refund_money,
            "shop_info"=> $shop_info,
            "address_info"=> $address
        );
        return json(resultArray(0,"操作成功",$data));
    }

    /**
     * 申请退款
     */
    public function orderGoodsRefundAskfor()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");
        if ($user_id == 0) {
            return $authRet;
        }
        $order_id = request()->post('order_id');
        $order_goods_id = request()->post('order_goods_id');
        $refund_type = request()->post('refund_type', 1);
        $refund_require_money = request()->post('refund_require_money', 0);
        $refund_reason = request()->post('refund_reason', '');
        $order_handle = new OrderHandle();

        $retval = $order_handle->orderGoodsRefundAskfor($user_id, 1,$order_id, $order_goods_id, $refund_type, $refund_require_money, $refund_reason);
        //orderGoodsRefundAskfor($order_id, $order_goods_id, $refund_type, $refund_require_money, $refund_reason);
        if (empty($retval)) {
            return json(resultArray(2,"操作失败 ".$order_handle->getError()));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**ok-2ok
     * 买家取消退款-2ok
     */
    public function orderGoodsCancel()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");
        if ($user_id == 0) {
            return $authRet;
        }
        $order_id = request()->post('order_id');
        $order_goods_id = request()->post('order_goods_id');
        if (empty($order_id) || empty($order_goods_id)) {
            return json(resultArray(2,"操作失败，缺少必需参数"));
        }
        $order_handle = new OrderHandle();
        $user_type = 1;
        $retval = $order_handle->orderGoodsCancel($user_id,$user_type,$order_id, $order_goods_id);

        if (empty($retval)) {
            return json(resultArray(2,"操作失败 ").$order_handle->getError());
        } else {
            return json(resultArray(0,"操作成功"));
        }

    }

    /**
     * 买家退货-2ok
     */
    public function orderGoodsRefundExpress()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");
        if ($user_id == 0) {
            return $authRet;
        }
        $order_id = request()->post('order_id');
        $order_goods_id = request()->post('order_goods_id');
        $refund_express_company = request()->post('refund_express_company', '');
        $refund_shipping_no = request()->post('refund_shipping_no', 0);
        $refund_reason = request()->post('refund_reason', '');
        $order_handle = new OrderHandle();
        $retval = $order_handle->orderGoodsReturnGoods($user_id, 1,$order_id, $order_goods_id, $refund_express_company, $refund_shipping_no);

        if (empty($retval)) {
            return json(resultArray(2,"操作失败 ".$order_handle->getError()));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * 交易关闭-2ok
     */
    public function orderClose()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");
        if ($user_id == 0) {
            return $authRet;
        }
        $order_handle = new OrderHandle();
        $order_id = request()->post('order_id');
        $res = $order_handle->orderClose($user_id, 1,$order_id);
       // orderClose($order_id);
        if (empty($res)) {
            return json(resultArray(2,"操作失败 ".$order_handle->getError()));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }


    /**
     * 收货-ok-2ok
     */
    public function orderTakeDelivery()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");
        if ($user_id == 0) {
            return $authRet;
        }
        $order_handle = new OrderHandle();
        $order_id = request()->post('order_id');
        $res = $order_handle->orderTakeDelivery($user_id, 1, $order_id);
       // orderTakeDelivery($order_id);
        if (empty($res)) {
            return json(resultArray(2,"操作失败 ".$order_handle->getError()));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**ok-2ok
     * 删除订单
     */
    public function deleteOrder()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");
        if ($user_id == 0) {
            return $authRet;
        }
        $order_handle = new OrderHandle();
        $order_id = request()->post("order_id");
        $operator_type = 2;
        $operator_id = $user_id;
        $res = $order_handle->deleteOrder($order_id, $operator_type, $operator_id);
            //deleteOrder($order_id, 2, $this->uid)
        if (empty($res)) {
            return json(resultArray(2,"删除失败 ".$order_handle->getError()));
        } else {
            return json(resultArray(0,"删除成功"));
        }

    }

    /**
     * 物流详情页ok-2ok
     */
    public function orderExpress()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");
        if ($user_id == 0) {
            return $authRet;
        }
        $order_id = isset($this->param['order_id']) ? $this->param['order_id'] :0;
        if (empty($order_id)) {
            return json(resultArray(2,"没有获取到订单信息"));
        }

        if (! is_numeric($order_id)) {
            return json(resultArray(2,"没有获取到订单信息"));
        }
        $order_handle = new OrderHandle();
        $detail = $order_handle->getOrderDetail($order_id);
        if (empty($detail)) {
            return json(resultArray(2,"没有获取到订单信息"));
        }
        // 获取物流跟踪信息
      //  $order_service = new OrderService();
        $data = array(
            'order' => $detail
        );
        return json(resultArray(0,"操作成功", $data));
       // $this->assign("order", $detail);
      //  return view($this->style . '/Order/orderExpress');
    }

    /**
     * 查询包裹物流信息 ok-2ok
     */
    public function getOrderGoodsExpressMessage()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");
        if ($user_id == 0) {
            return $authRet;
        }
        $express_id = request()->post("express_id", 0); // 物流包裹id
        if (empty($express_id)) {
            return json(resultArray(2,"没有获取到物流信息"));
        }

        if (! is_numeric($express_id)) {
            return json(resultArray(2,"没有获取到物流信息"));
        }

        $order_handle = new OrderHandle();
        $res = $order_handle->getOrderGoodsExpressMessage($express_id);
        if (empty($res)) {
            return json(resultArray(2,"没有获取到物流信息".$order_handle->getError()));
        }
        $res = array_reverse($res);
        return json(resultArray(0,"操作成功", $res));
    }


    /************************* 商品评价 **********************************************/
    /**
     * 我要评价
     *
     * @return \think\response\View
     */
    public function reviewCommodity()
    {
        // 先考虑显示的样式
        if (request()->isGet()) {
            $order_id = request()->get('orderId', '');
            // 判断该订单是否是属于该用户的
            $order_service = new OrderService();
            $condition['order_id'] = $order_id;
            $condition['buyer_id'] = $this->uid;
            $condition['review_status'] = 0;
            $condition['order_status'] = array(
                'in',
                '3,4'
            );
            $order_count = $order_service->getUserOrderCountByCondition($condition);
            if ($order_count == 0) {
                $this->error("对不起,您无权进行此操作");
            }
            $order = new OrderOrderService();
            $list = $order->getOrderGoods($order_id);
            $orderDetail = $order->getDetail($order_id);
            $this->assign("order_no", $orderDetail['order_no']);
            $this->assign("order_id", $order_id);
            $this->assign("list", $list);
            // var_dump($order_id);
            // var_dump($list);die;
            return view($this->style . '/Order/reviewCommodity');
            if (($orderDetail['order_status'] == 3 || $orderDetail['order_status'] == 4) && $orderDetail['is_evaluate'] == 0) {} else {
                $redirect = __URL(__URL__ . "/member/index");
                $this->redirect($redirect);
            }
        } else {
            return view($this->style . "Order/myOrderList");
        }
    }

    /**
     * 商品评价提交
     * 创建：李吉
     * 创建时间：2017-02-16 15:22:59
     */
    public function addGoodsEvaluate()
    {
        $order = new OrderService();
        $order_id = request()->post('order_id', '');
        $order_no = request()->post('order_no', '');
        $order_id = intval($order_id);
        $order_no = intval($order_no);
        $goods = request()->post('goodsEvaluate', '');
        $goodsEvaluateArray = json_decode($goods);
        $dataArr = array();
        foreach ($goodsEvaluateArray as $key => $goodsEvaluate) {
            $orderGoods = $order->getOrderGoodsInfo($goodsEvaluate->order_goods_id);
            $data = array(

                'order_id' => $order_id,
                'order_no' => $order_no,
                'order_goods_id' => intval($goodsEvaluate->order_goods_id),

                'goods_id' => $orderGoods['goods_id'],
                'goods_name' => $orderGoods['goods_name'],
                'goods_price' => $orderGoods['goods_money'],
                'goods_image' => $orderGoods['goods_picture'],
                'shop_id' => $orderGoods['shop_id'],
                'shop_name' => "默认",
                'content' => $goodsEvaluate->content,
                'addtime' => time(),
                'image' => $goodsEvaluate->imgs,
                // 'explain_first' => $goodsEvaluate->explain_first,
                'member_name' => $this->user->getMemberDetail()['member_name'],
                'explain_type' => $goodsEvaluate->explain_type,
                'uid' => $this->uid,
                'is_anonymous' => $goodsEvaluate->is_anonymous,
                'scores' => intval($goodsEvaluate->scores)
            );
            $dataArr[] = $data;
        }

        return $order->addGoodsEvaluate($dataArr, $order_id);
    }

    /**
     * 追评
     * 李吉
     * 2017-02-17 14:12:15
     */
    public function reviewAgain()
    {
        // 先考虑显示的样式
        if (request()->isGet()) {
            $order_id = request()->get('orderId', '');
            // 判断该订单是否是属于该用户的
            $order_service = new OrderService();
            $condition['order_id'] = $order_id;
            $condition['buyer_id'] = $this->uid;
            $condition['is_evaluate'] = 1;
            $order_count = $order_service->getUserOrderCountByCondition($condition);
            if ($order_count == 0) {
                $this->error("对不起,您无权进行此操作");
            }

            $order = new OrderOrderService();
            $list = $order->getOrderGoods($order_id);
            $orderDetail = $order->getDetail($order_id);
            $this->assign("order_no", $orderDetail['order_no']);
            $this->assign("order_id", $order_id);
            $this->assign("list", $list);
            if (($orderDetail['order_status'] == 3 || $orderDetail['order_status'] == 4) && $orderDetail['is_evaluate'] == 1) {
                return view($this->style . 'Order/reviewAgain');
            } else {

                $redirect = __URL(__URL__ . "/member/index");
                $this->redirect($redirect);
            }
        } else {
            return view($this->style . "Order/myOrderList");
        }
    }

    /**
     * 增加商品评价
     */
    public function modityCommodity()
    {
        return 1;
    }

    /**
     * 商品-追加评价提交数据
     * 创建：李吉
     * 创建时间：2017-02-16 15:22:59
     */
    public function addGoodsEvaluateAgain()
    {
        $order = new OrderService();
        $order_id = request()->post('order_id', '');
        $order_no = request()->post('order_no', '');
        $order_id = intval($order_id);
        $order_no = intval($order_no);
        $goods = request()->post('goodsEvaluate', '');
        $goodsEvaluateArray = json_decode($goods);

        $result = 1;
        foreach ($goodsEvaluateArray as $key => $goodsEvaluate) {
            $res = $order->addGoodsEvaluateAgain($goodsEvaluate->content, $goodsEvaluate->imgs, $goodsEvaluate->order_goods_id);
            if ($res == false) {
                $result = false;
                break;
            }
        }
        if ($result == 1) {
            $data = array(
                'is_evaluate' => 2
            );
            $result = $order->modifyOrderInfo($data, $order_id);
        }

        return $result;
    }


}