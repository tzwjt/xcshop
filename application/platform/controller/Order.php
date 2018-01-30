<?php
/**
 *
 * 平台订单控制器
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-29
 * Time: 15:05
 */

namespace app\platform\controller;

use app\platform\controller\BaseController;
use dao\handle\AddressHandle;
//use dao\handle\ExpressHandle;
use dao\handle\AgentHandle;
use dao\handle\ConfigHandle;
use dao\handle\ExpressHandle;
use dao\handle\member\MemberUserHandle;
use dao\handle\order\OrderGoodsHandle;
use dao\handle\order\OrderStatusHandle;
use dao\handle\OrderHandle;
use dao\handle\PlatformUserHandle;
use think\Log;


class Order extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 订单列表
     */
    public function orderList()
    {

        $page_index = request()->post('page_index', 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $start_date = request()->post('start_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('start_date'));
        $end_date = request()->post('end_date') == "" ? 0 : getTimeTurnTimeStamp(request()->post('end_date'));
        $user_name = request()->post('login_phone', '');
        $receiver_name = request()->post('receiver_name', '');
        $order_no = request()->post('order_no', '');
        $order_status = request()->post('order_status', '');
        $receiver_mobile = request()->post('receiver_mobile', '');
        $payment_type = request()->post('payment_type', 0);
        $condition['is_deleted'] = 0; // 未删除订单
    //    Log::write('start_date:'.$start_date.', end_date: '.$end_date);
        if ($start_date != 0 && $end_date != 0) {
            $condition["create_time"] = [
                    [
                        ">",
                        $start_date
                    ],
                    [
                        "<",
                        $end_date
                    ]
            ];
        } elseif ($start_date != 0 && $end_date == 0) {
            $condition["create_time"] = [
                        ">",
                        $start_date
            ];
        } elseif ($start_date == 0 && $end_date != 0) {
            $condition["create_time"] = [
                        "<",
                        $end_date
            ];
        }
        if ($order_status != '') {
            // $order_status 1 待发货
            if ($order_status == 1) {
                // 订单状态为待发货实际为已经支付未完成还未发货的订单
                $condition['shipping_status'] = 0; // 0 待发货
                $condition['pay_status'] = 2; // 2 已支付
                $condition['order_status'] = array(
                        'neq',
                        4
                    ); // 4 已完成
                $condition['order_status'] = array(
                        'neq',
                        5
                    ); // 5 关闭订单
            } else
                $condition['order_status'] = $order_status;
        }
        if (! empty($payment_type)) {
            $condition['payment_type'] = $payment_type;
        }
        if (! empty($user_name)) {
            $condition['user_name'] = $user_name;
        }
        if (! empty($receiver_name)) {
            $condition['receiver_name'] = $receiver_name;
        }
        if (! empty($order_no)) {
            $condition['order_no'] = ['like', '%'.$order_no.'%'];
        }
        if (! empty($receiver_mobile)) {
            $condition['receiver_mobile'] = $receiver_mobile;
        }
          //  $condition['shop_id'] = $this->instance_id;
        $order_handle = new OrderHandle();
       // getOrderList($page_index = 1, $page_size = 0, $condition = '', $order = '')
      //  $condition ="";
        $list = $order_handle->getOrderList($page_index, $page_size, $condition, 'create_time desc');
        return json(resultArray(0,"操作成功", $list));
    }

    public function getOrderCommonStatus() {
        $all_status = OrderStatusHandle::getOrderCommonStatus();
         return json(resultArray(0,"操作成功", $all_status));
    }

    public function getOrderStatusMenu() {
        $status = request()->get('status', '');
      //  $this->assign();
        $all_status = OrderStatusHandle::getOrderCommonStatus();
        $child_menu_list = array();
        $child_menu_list[] = array(
            'url' => "order/orderList",
            'menu_name' => '全部',
            "active" => $status == '' ? 1 : 0
        );
        foreach ($all_status as $k => $v) {
            // 针对发货与提货状态名称进行特殊修改
            /*
             * if($v['status_id'] == 1)
             * {
             * $status_name = '待发货/待提货';
             * }elseif($v['status_id'] == 3){
             * $status_name = '已收货/已提货';
             * }else{
             * $status_name = $v['status_name'];
             * }
             */
            $child_menu_list[] = array(
                'url' => "order/orderlist?status=" . $v['status_id'],
                'menu_name' => $v['status_name'],
                "active" => $status == $v['status_id'] ? 1 : 0
            );
        }


       // 获取物流公司
       $express = new ExpressHandle();
       $where = 1;
       $expressList = $express->expressCompanyQuery();

        $data = array(
            'status' => $status,
            'child_menu_list'=> $child_menu_list,
            'expressList' => $expressList   //新增
        );

        return json(resultArray(0,"操作成功", $data));


    }



    /**
     * ok-2ok
     * 订单详情
     */
    public function orderDetail()
    {
      //  $order_id = request()->get('order_id', 0);
        $order_id = isset($this->param['order_id']) ? $this->param['order_id'] : 0;
        if ($order_id == 0) {
            return json(resultArray(2,"没有获取到订单信息"));
        }
        $order_handle = new OrderHandle();
       // getOrderDetail($order_id)
        $detail = $order_handle->getOrderDetail($order_id);
        if (empty($detail)) {
            return json(resultArray(2,"没有获取到订单信息"));
        }
        if (! empty($detail['operation'])) {
            $operation_array = $detail['operation'];
            foreach ($operation_array as $k => $v) {
                if ($v["no"] == 'logistics') {
                    unset($operation_array[$k]);
                }
            }
            $detail['operation'] = $operation_array;
        }
      //  $this->assign("order", $detail);
      //  return view($this->style . "Order/orderDetail");
        return json(resultArray(0,"操作成功", $detail));
    }


    /**ok-2ok
     * 线下支付
     */
    public function orderOffLinePay()
    {
        $order_handle = new OrderHandle();
        $order_id = request()->post('order_id');
        $order_info = $order_handle->getOrderInfo($order_id);
      //  ($user_id, $user_type,$order_id, $pay_type, $status)
        $user_id = PlatformUserHandle::loginUserId();
        $user_type = 2;
        if ($order_info['payment_type'] == 6) {
            //6到店支付
            $res = $order_handle->orderOffLinePay($user_id, $user_type, $order_id, 6, 0);
        } else {
            //10线下支付
            $res = $order_handle->orderOffLinePay($user_id, $user_type, $order_id, 10, 0);
        }

        if (empty($res)) {
            return json(resultArray(2,"操作失败 ".$order_handle->getError()));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * ok-2ok
     * 交易完成
     */
    public function orderComplete()
    {
        $order_handle = new OrderHandle();
       // $order_id = request()->post('order_id');
        $order_id = $this->param['order_id'];

        $user_id = PlatformUserHandle::loginUserId();
        $user_type = 2;

        $res = $order_handle->orderComplete($user_id, $user_type, $order_id);
        if (empty($res)) {
            return json(resultArray(2,"操作失败 ".$order_handle->getError()));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * 交易关闭ok-2ok
     */
    public function orderClose()
    {
        $order_handle = new OrderHandle();
        $order_id = request()->post('order_id');
       // orderClose($user_id, $user_type,$order_id)
        $user_id = PlatformUserHandle::loginUserId();
        $user_type = 2;
        $res = $order_handle->orderClose($user_id, $user_type, $order_id);
        if (empty($res)) {
            return json(resultArray(2,"操作失败 ".$order_handle->getError()));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * ok
     * 订单发货 所需数据
     */
    public function orderDeliveryData()
    {
        $order_handle = new OrderHandle();
        $express_handle = new ExpressHandle();
        $address_handle = new AddressHandle();
        $order_id = request()->post('order_id');
        $order_info = $order_handle->getOrderDetail($order_id);
        $order_info['address'] = $address_handle->getAddress($order_info['receiver_province'], $order_info['receiver_city'], $order_info['receiver_district']);
      //  $shopId = $this->instance_id;
        // 快递公司列表
       // $express_company_list = $express_handle->expressCompanyQuery('shop_id = ' . $shopId, "*");

         $express_company_list = $express_handle->expressCompanyQuery('is_enabled = 1', "*");
        // 订单商品项
        $order_goods_list = $order_handle->getOrderGoods($order_id);
        $data['order_info'] = $order_info;
        $data['express_company_list'] = $express_company_list;
        $data['order_goods_list'] = $order_goods_list;
        return json(resultArray(0,"操作成功",$data));
    }

    /**
     * 订单发货操作
     */
    public function orderDelivery()
    {
        $order_handle = new OrderHandle();
        $order_id = request()->post('order_id');
        $order_goods_id_array = request()->post('order_goods_id_array');
        $express_name = request()->post('express_name', '');
        $shipping_type = request()->post('shipping_type', '');
        $express_company_id = request()->post('express_company_id', '');
        $express_no = request()->post('express_no', '');
        $user_id =PlatformUserHandle::loginUserId();
        $user_type = 2;
        if ($shipping_type == 1) {
           // orderDelivery($user_id, $user_type,$order_id, $order_goods_id_array, $express_name, $shipping_type, $express_company_id, $express_no)
            $res = $order_handle->orderDelivery($user_id, $user_type,$order_id, $order_goods_id_array, $express_name, $shipping_type, $express_company_id, $express_no);

          //  orderDelivery($order_id, $order_goods_id_array, $express_name, $shipping_type, $express_company_id, $express_no);
        } else {
          //  orderGoodsDelivery($user_id, $user_type,$order_id, $order_goods_id_array)
            $res = $order_handle->orderGoodsDelivery($user_id, $user_type,$order_id, $order_goods_id_array);

          //  orderGoodsDelivery($order_id, $order_goods_id_array);
        }

        if (empty($res)) {
            return json(resultArray(2,"操作失败 ".$order_handle->getError()));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * 获取订单中的订单项
     */
    public function getOrderGoods()
    {
        $order_id = request()->post('order_id');
        $order_handle = new OrderHandle();
        $order_goods_list = $order_handle->getOrderGoods($order_id);
        $order_info = $order_handle->getOrderInfo($order_id);
        $list[0] = $order_goods_list;
        $list[1] = $order_info;
        return json(resultArray(0,"操作成功", $list));
    }

    /**
     * ok-2ok
     * 订单价格调整
     */
    public function orderAdjustMoney()
    {
        $order_id = request()->post('order_id');
        $order_goods_id_adjust_array = request()->post('order_goods_id_adjust_array', '');
        $shipping_fee = request()->post('shipping_fee', '');
        $order_handle = new OrderHandle();
        $user_id = PlatformUserHandle::loginUserId();
        $user_type = 2;
        $res = $order_handle->orderMoneyAdjust($user_id, $user_type, $order_id, $order_goods_id_adjust_array, $shipping_fee);
        if (empty($res)) {
            return json(resultArray(2,"操作失败 ".$order_handle->getError()));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }


    /**
     * 添加备注-2ok
     */
    public function addMemo()
    {
        $order_handle = new OrderHandle();
        $order_id = request()->post('order_id');
        $memo = request()->post('memo');
        $res = $order_handle->addOrderSellerMemo($order_id, $memo);
        if (empty($res)) {
            return json(resultArray(2,"操作失败 ".$order_handle->getError()));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**-2ok
     * 获取订单备注信息
     */
    public function getOrderSellerMemo()
    {
        $order_handle = new OrderHandle();
        $order_id = request()->post('order_id');
        $res = $order_handle->getOrderSellerMemo($order_id);
        return json(resultArray(0,"操作成功", $res));
    }


    /**
     * ok-2ok
     * 功能说明：获取打印订单项预览信息
     */
    public function getOrderExpressPreview()
    {
        // $shop_id = $this->instance_id;
        // 获取值
        $orderIdArray = isset($this->param['order_ids']) ? $this->param['order_ids'] :'';
          //  request()->get('ids', '');
        // 操作
        $order_handle = new OrderHandle();
        $goods_express_list = $order_handle->getOrderGoodsExpressDetail($orderIdArray);
        // 返回信息
        return json(resultArray(0,"操作成功", $goods_express_list));
    }

    /**
     * -ok-2ok
     * 功能说明：打印预览 发货单
     */
    public function printDeliveryPreview()
    {
        // 获取值
        $order_handle = new OrderHandle();
        $order_ids = isset($this->param['order_ids']) ? $this->param['order_ids'] :'';
        $ShopName =isset($this->param['ShopName']) ? $this->param['ShopName'] :'';
     //   $shop_id = $this->instance_id;
        $order_str = explode(",", $order_ids);
        $order_array = array();
        foreach ($order_str as $order_id) {
            $detail = array();
            $detail = $order_handle->getOrderDetail($order_id);
            if (empty($detail)) {
                return json(resultArray(2,"没有获取到订单信息"));
            }
            $order_array[] = $detail;
        }
        $receive_address = $order_handle->getShopReturnSet(0);
        $data=array(
            'order_print'=> $order_array,
            'ShopName'=>$ShopName,
            'receive_address'=> $receive_address
        );
        return json(resultArray(0,"操作成功", $data));

      //  return view($this->style . 'Order/printDeliveryPreview');
    }

    /**
     * ok-2ok
     * 打印快递单
     */
    public function printExpressPreview()
    {
        $order_handle = new OrderHandle();
        $address_handle = new AddressHandle();

        $order_ids = isset($this->param['order_ids']) ? $this->param['order_ids'] :'';
        $ShopName = isset($this->param['ShopName']) ? $this->param['ShopName'] :'';
        $co_id = isset($this->param['co_id']) ? $this->param['co_id'] :'';
        $order_str = explode(",", $order_ids);
        $order_array = array();
        foreach ($order_str as $order_id) {
            $detail = array();
            $detail = $order_handle->getOrderDetail($order_id);
            if (empty($detail)) {
                return json(resultArray(2,"没有获取到订单信息"));
            }
            // $detail['address'] = $address_service->getAddress($detail['receiver_province'], $detail['receiver_city'], $detail['receiver_district']);
            $order_array[] = $detail;
        }
        $express_handle = new ExpressHandle();
        // 物流模板信息
        $express_shipping = $express_handle->getExpressShipping($co_id);
        // 物流打印信息
        $express_shipping_item = $express_handle->getExpressShippingItems($express_shipping["sid"]);
        $receive_address = $order_handle->getShopReturnSet(0);

        $data = array(
            "order_print" => $order_array,
            "ShopName"=> $ShopName,
            "express_ship"=>$express_shipping,
            "express_item_list"=> $express_shipping_item,
            "receive_address"=>$receive_address
        );
        return json(resultArray(0,"操作成功", $data));

      //  return view($this->style . 'Order/printExpressPreview');
    }

    /**
     * ok-2ok
     * 订单退款详情
     */
    public function orderRefundDetail()
    {
        $order_goods_id = isset($this->param['item_id']) ? $this->param['item_id'] :0;
        if ($order_goods_id == 0) {
            return json(resultArray(2,"没有获取到退款信息"));
        }
        $order_handle = new OrderHandle();
        $info = $order_handle->getOrderGoodsRefundInfo($order_goods_id);
        $data = array(
            'order_goods'=> $info
        );
        return json(resultArray(0,"操作成功", $data));
    }

    /**
     * 买家申请退款
     */
    public function orderGoodsRefundAskfor()
    {
        $order_id = request()->post('order_id', '');
        $order_goods_id = request()->post('order_goods_id', '');
        $refund_type = request()->post('refund_type', '');
        $refund_require_money = request()->post('refund_require_money', 0);
        $refund_reason = request()->post('refund_reason', '');
        if (empty($order_id) || empty($order_goods_id) || empty($refund_type) || empty($refund_require_money) || empty($refund_reason)) {
            return json(resultArray(2,"操作失败，缺少必需参数"));

        }
        $order_handle = new OrderHandle();

        $user_id = PlatformUserHandle::loginUserId();
        $user_type = 2;
        $retval = $order_handle->orderGoodsRefundAskfor($user_id, $user_type,$order_id, $order_goods_id, $refund_type, $refund_require_money, $refund_reason);

        if (empty($retval)) {
            return json(resultArray(2,"操作失败 ").$order_handle->getError());
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * 买家取消退款-2ok
     */
    public function orderGoodsCancel()
    {
        $order_id = request()->post('order_id', '');
        $order_goods_id = request()->post('order_goods_id', '');
        if (empty($order_id) || empty($order_goods_id)) {
            return json(resultArray(2,"操作失败，缺少必需参数"));
        }
        $order_handle = new OrderHandle();
        $user_id = PlatformUserHandle::loginUserId();
        $user_type = 2;

        $retval = $order_handle->orderGoodsCancel($user_id,$user_type,$order_id, $order_goods_id);

        if (empty($retval)) {
            return json(resultArray(2,"操作失败 ").$order_handle->getError());
        } else {
            return json(resultArray(0,"操作成功"));
        }

    }

    /**
     * 买家退货
     */
    public function orderGoodsReturnGoods()
    {
        $order_id = request()->post('order_id', '');
        $order_goods_id = request()->post('order_goods_id', '');
        if (empty($order_id) || empty($order_goods_id)) {
            return json(resultArray(2,"操作失败，缺少必需参数"));
        }
        $refund_shipping_company = request()->post('refund_shipping_company', '');
        $refund_shipping_code = request()->post('refund_shipping_code', '');
        $order_handle = new OrderHandle();
        $user_id = PlatformUserHandle::loginUserId();
        $user_type = 2;
        $retval = $order_handle->orderGoodsReturnGoods($user_id, $user_type,$order_id, $order_goods_id, $refund_shipping_company, $refund_shipping_code);

      //  orderGoodsReturnGoods($order_id, $order_goods_id, $refund_shipping_company, $refund_shipping_code);
         if (empty($retval)) {
             return json(resultArray(2,"操作失败 ").$order_handle->getError());
         } else {
             return json(resultArray(0,"操作成功"));
         }
    }

    /**
     * 卖家同意买家退款申请-2ok
     */
    public function orderGoodsRefundAgree()
    {
        $order_id = request()->post('order_id','');
        $order_goods_id = request()->post('order_goods_id', '');
        if (empty($order_id) || empty($order_goods_id)) {
            return json(resultArray(2,"操作失败，缺少必需参数"));
        }
        $order_handle = new OrderHandle();

        $user_id = PlatformUserHandle::loginUserId();
        $user_type = 2;
        $retval = $order_handle->orderGoodsRefundAgree($user_id, $user_type, $order_id, $order_goods_id);

        //orderGoodsRefundAgree($order_id, $order_goods_id);
        if (empty($retval)) {
            return json(resultArray(2,"操作失败 ").$order_handle->getError());
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * ok
     * 卖家永久拒绝本次退款
     */
    public function orderGoodsRefuseForever()
    {
        $order_id = request()->post('order_id', '');
        $order_goods_id = request()->post('order_goods_id', '');
        if (empty($order_id) || empty($order_goods_id)) {
            return json(resultArray(2,"操作失败，缺少必需参数"));
        }
        $order_handle = new OrderHandle();
        $user_id = PlatformUserHandle::loginUserId();
        $user_type = 2;

        $retval = $order_handle->orderGoodsRefuseForever($user_id, $user_type,$order_id, $order_goods_id);

        //orderGoodsRefuseForever($order_id, $order_goods_id);
        if (empty($retval)) {
            return json(resultArray(2,"操作失败 ").$order_handle->getError());
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * ok
     * 卖家拒绝本次退款
     */
    public function orderGoodsRefuseOnce()
    {
        $order_id = request()->post('order_id', '');
        $order_goods_id = request()->post('order_goods_id', '');
        if (empty($order_id) || empty($order_goods_id)) {
            return json(resultArray(2,"操作失败，缺少必需参数"));
        }
        $order_handle = new OrderHandle();
        $user_id = PlatformUserHandle::loginUserId();
        $user_type = 2;
        $retval = $order_handle->orderGoodsRefuseOnce($user_id, $user_type, $order_id, $order_goods_id);
        if (empty($retval)) {
            return json(resultArray(2,"操作失败 ").$order_handle->getError());
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * 卖家确认收货-ok
     */
    public function orderGoodsConfirmRecieve()
    {
        $order_id = request()->post('order_id','');
        $order_goods_id = request()->post('order_goods_id', '');
        if (empty($order_id) || empty($order_goods_id)) {
            return json(resultArray(2,"操作失败，缺少必需参数"));
        }
        $storage_num = request()->post("storage_num", "");
        $isStorage = request()->post("isStorage", "");
        $goods_id = request()->post("goods_id", '');
        $sku_id = request()->post('sku_id', 0);
        $order_handle = new OrderHandle();

        $user_id = PlatformUserHandle::loginUserId();
        $user_type = 2;

        $retval = $order_handle->orderGoodsConfirmRecieve($user_id, $user_type,$order_id, $order_goods_id, $storage_num, $isStorage, $goods_id, $sku_id);

        if (empty($retval)) {
            return json(resultArray(2,"操作失败 ").$order_handle->getError());
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * ok-2ok
     * 确认退款时，查询买家实际退款金额
     */
    public function orderGoodsRefundMoney()
    {
        $order_handle = new OrderHandle();
        $order_goods_id = request()->post('order_goods_id', 0);
        if (empty($order_goods_id)) {
            return json(resultArray(2,"没有获取到相关信息"));
        }
        $res = 0;
        if ($order_goods_id != '') {
            $res = $order_handle->orderGoodsRefundMoney($order_goods_id);
        }
        return json(resultArray(0,"操作成功", $res));
    }

    /**
     * 卖家确认退款
     */
    public function orderGoodsConfirmRefund2()
    {
        $order_id = request()->post('order_id', '');
        $order_goods_id = request()->post('order_goods_id', '');
        $refund_real_money = request()->post('refund_real_money', 0);
        if (empty($order_id) || empty($order_goods_id) || $refund_real_money === '') {
            return json(resultArray(2,"操作失败，缺少必需参数"));
        }
        $order_handle = new OrderHandle();

        $user_id = PlatformUserHandle::loginUserId();
        $user_type = 2;
        $retval = $order_handle->orderGoodsConfirmRefund($user_id, $user_type,$order_id, $order_goods_id, $refund_real_money);

      //  orderGoodsConfirmRefund($order_id, $order_goods_id, $refund_real_money);

         if (empty($retval)) {
             return json(resultArray(2,"操作失败 ").$order_handle->getError());
         } else {
             return json(resultArray(0,"操作成功"));
         }
    }

    /**
     * ok-2ok
     * 卖家确认退款
     */
    public function orderGoodsConfirmRefund()
    {
        $order_id = request()->post('order_id', '');
        $order_goods_id = request()->post('order_goods_id', '');
        $refund_real_money = request()->post('refund_real_money', 0); // 退款金额
        $refund_balance_money = request()->post("refund_balance_money", 0); // 退款余额
        $refund_way = request()->post("refund_way", ""); // 退款方式
        $refund_remark = request()->post("refund_remark", ""); // 退款备注
        if (empty($order_id) || empty($order_goods_id) || $refund_real_money === '' || empty($refund_way)) {
           // $this->error('缺少必需参数');
            return json(resultArray(2,"操作失败，缺少必需参数"));
        }
        $order_handle = new OrderHandle();
        $user_id = PlatformUserHandle::loginUserId();
        $user_type = 2;
        $retval = $order_handle->orderGoodsConfirmRefund($user_id, $user_type,$order_id, $order_goods_id, $refund_real_money, $refund_balance_money, $refund_way, $refund_remark);

        if (empty($retval)) {
          //  return json(resultArray(2,"操作失败 ").$order_handle->getError());
            if (empty($order_handle->getError())) {
                return json(resultArray(2, "操作失败 "));
            } else if (is_array($order_handle->getError())) {
                $err1 = json_encode($order_handle->getError(), JSON_UNESCAPED_UNICODE);
                return json(resultArray(2, "操作失败 ".$err1));
            } else {
                $err1 = json_encode($order_handle->getError(),JSON_UNESCAPED_UNICODE);
                return json(resultArray(2, "操作失败 ".$err1));
                //return json(resultArray(2, "操作失败 ").$order_handle->getError());
            }
        } else {
            return json(resultArray(0,"操作成功"));
        }
        /**
        if (is_numeric($retval)) {
            return AjaxReturn($retval);
        } else {
            return array(
                "code" => 0,
                "message" => $retval
            );
        }
         * **/
    }

    /**
     * 确认退款时，查询买家实际付款金额
     */
    /*
    public function orderGoodsRefundMoney()
    {
        $order_handle = new OrderHandle();
        $order_goods_id = request()->post('order_goods_id', '');
        $res = 0;
        if ($order_goods_id != '') {
            $res = $order_handle->orderGoodsRefundMoney($order_goods_id);
        }
        return $res;
    }
*/
    /**
     * 获取订单销售统计
     */
    public function getOrderAccount()
    {
        $order_handle = new OrderHandle();
        // 获取日销售统计
        $account = $order_service->getShopOrderAccountDetail($this->instance_id);
        var_dump($account);
    }

    /**
     * 获取修改收货地址的信息
     */
    public function getOrderUpdateAddress()
    {
        $order_handle = new OrderHandle();
        $order_id = request()->post('order_id');
        $res = $order_handle->getOrderReceiveDetail($order_id);
        return json(resultArray(0,"操作成功", $res));
    }

    /**
     * 修改收货地址的信息
     *
     * @return string
     */
    public function updateOrderAddress()
    {
        $order_handle = new OrderHandle();
        $order_id = request()->post('order_id');
        $receiver_name = request()->post('receiver_name');
        $receiver_mobile = request()->post('receiver_mobile');
        $receiver_zip = request()->post('receiver_zip');
        $receiver_province = request()->post('receiver_province');
        $receiver_city = request()->post('receiver_city');
        $receiver_district = request()->post('receiver_district');
        $receiver_address = request()->post('address_detail');
        $res = $order_handle->updateOrderReceiveDetail($order_id, $receiver_mobile, $receiver_province, $receiver_city, $receiver_district, $receiver_address, $receiver_zip, $receiver_name);
        if (empty($res)) {
            return json(resultArray(2,"操作失败 "));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * 收货
     */
    public function orderTakeDelivery()
    {
        $order_handle = new OrderHandle();
        $order_id = request()->post('order_id');
        $user_id = PlatformUserHandle::loginUserId();
        $user_type = 2;
        $res = $order_handle->orderTakeDelivery($user_id,$user_type,$order_id);

        if (empty($res)) {
            return json(resultArray(2,"操作失败 ".$order_handle->getError()));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * 退货设置
     */
    public function saveReturnSetting()
    {

        $order_handle = new OrderHandle();
        $address = request()->post('shop_address');
        $real_name = request()->post('seller_name');
        $mobile = request()->post('seller_mobile');
        $zipcode = request()->post('seller_zipcode');
        $retval = $order_handle->updateShopReturnSet(0, $address, $real_name, $mobile, $zipcode);
        if (empty($retval)) {
            return json(resultArray(2,"操作失败 ".$order_handle->getError()));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * 进入退货设置
     */
    public function returnSetting() {
        $child_menu_list = array(
            array(
                'url' => "express/expresscompany",
                'menu_name' => "物流公司",
                "active" => 0
            ),
            array(
                'url' => "order/returnsetting",
                'menu_name' => "商家退货地址",
                "active" => 1
            ),
            array(
                'url' => "agent/pickuppointlist",
                'menu_name' => "自提点管理",
                "active" => 0
            ),
            array(
                'url' => "agent/pickuppointfreight",
                'menu_name' => "自提点运费菜单",
                "active" => 0
            )
        );

        $order_handle = new OrderHandle();
        $info = $order_handle->getShopReturnSet(0);
        $data = array(
            'child_menu_list'=> $child_menu_list,
            'setting_info' => $info
        );
        return json(resultArray(0,"操作成功", $data));
    }

    /**
     * ok-2ok
     * 提货
     */
    public function pickupOrder()
    {
        $order_id = request()->post('order_id');
        if (empty($order_id)) {
            return json(resultArray(2,"缺少必需参数"));
        }
        $buyer_name = request()->post('buyer_name');
        $buyer_phone = request()->post('buyer_phone');
        $remark = request()->post('remark', '');
        $order_handle = new OrderHandle();
        $user_id = PlatformUserHandle::loginUserId();
        $user_type = 2;
        $retval = $order_handle->pickupOrder($user_id, $user_type,$order_id, $buyer_name, $buyer_phone, $remark);
        if (empty($retval)) {
            return json(resultArray(2,"操作失败 ".$order_handle->getError()));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * 获取物流跟踪信息
     */
    public function getExpressInfo()
    {
        $order_handle = new OrderHandle();
        $express_id = request()->post('express_id');//887152079571115699
        $expressinfo = $order_handle->getOrderGoodsExpressMessage($express_id);
        return json(resultArray(0,"操作成功", $expressinfo));
    }

    /**
     * 获取物流跟踪信息
     */
    /**
    public function getExpressInfo2()
    {
        $order_handle = new OrderHandle();
        $express_id = request()->post('express_id');//887152079571115699
        $expressinfo = $order_handle->getOrderGoodsExpressMessage2222($express_id);
        return json(resultArray(0,"操作成功".$order_handle->getError(), $expressinfo));
    }
**/
    /**
     * 订单数据excel导出
     */
    public function orderDataExcel()
    {
        $xlsName = "订单数据列表";
        $xlsCell = array(
            array(
                'order_no',
                '订单编号'
            ),
            array(
                'create_date',
                '日期'
            ),
            array(
                'receiver_info',
                '收货人信息'
            ),
            array(
                'order_money',
                '订单金额'
            ),
            array(
                'pay_money',
                '实际支付'
            ),
            array(
                'pay_type_name',
                '支付方式'
            ),
            array(
                'shipping_type_name',
                '配送方式'
            ),
            array(
                'pay_status_name',
                '支付状态'
            ),
            array(
                'status_name',
                '发货状态'
            ),
            array(
                'goods_info',
                '商品信息'
            )
        );
        $start_date = isset($this->param['start_date']) ? $this->param['start_date'] : "";
        $start_date = $start_date == "" ? 0 : getTimeTurnTimeStamp($start_date );
        $end_date = isset($this->param['end_date']) ? $this->param['end_date'] : "";
        $end_date = $end_date == "" ? 0 : getTimeTurnTimeStamp($end_date);

        $user_name = request()->get('login_phone', '');
        $order_no = request()->get('order_no', '');
        $order_status = request()->get('order_status', '');
        $receiver_mobile = request()->get('receiver_mobile', '');
        $receiver_name = request()->get('receiver_name', '');
        $payment_type = request()->get('payment_type', '');
        $condition = array();
        if ($start_date != 0 && $end_date != 0) {
            $condition["create_time"] = [
                [
                    ">",
                    $start_date
                ],
                [
                    "<",
                    $end_date
                ]
            ];
        } elseif ($start_date != 0 && $end_date == 0) {
            $condition["create_time"] = [
                [
                    ">",
                    $start_date
                ]
            ];
        } elseif ($start_date == 0 && $end_date != 0) {
            $condition["create_time"] = [
                [
                    "<",
                    $end_date
                ]
            ];
        }
        if ($order_status != '') {
            // $order_status 1 待发货
            if ($order_status == 1) {
                // 订单状态为待发货实际为已经支付未完成还未发货的订单
                $condition['shipping_status'] = 0; // 0 待发货
                $condition['pay_status'] = 2; // 2 已支付
                $condition['order_status'] = array(
                    'neq',
                    4
                ); // 4 已完成
                $condition['order_status'] = array(
                    'neq',
                    5
                ); // 5 关闭订单
            } else
                $condition['order_status'] = $order_status;
        }
        if (! empty($payment_type)) {
            $condition['payment_type'] = $payment_type;
        }
        if (! empty($user_name)) {
            $condition['user_name'] = $user_name;
        }
        if (! empty($order_no)) {
            $condition['order_no'] = $order_no;
        }
        if (! empty($receiver_mobile)) {
            $condition['receiver_mobile'] = $receiver_mobile;
        }

        if (! empty($receiver_name)) {
            $condition['receiver_name'] = $receiver_name;
        }
      //  $condition['shop_id'] = $this->instance_id;
        $order_handle = new OrderHandle();
        $list = $order_handle->getOrderList(1, 0, $condition, 'create_time desc');
      //  getOrderList($page_index = 1, $page_size = 0, $condition = '', $order = '')
        $list = $list["data"];
        foreach ($list as $k => $v) {
            $list[$k]["create_date"] = $v["create_time"]; // getTimeStampTurnTime($v["create_time"]); // 创建时间
            $list[$k]["receiver_info"] = $v["receiver_name"] . "  " . $v["receiver_mobile"] . "  " . $v["receiver_province_name"] . $v["receiver_city_name"] . $v["receiver_district_name"] . $v["receiver_address"] . "  " . $v["receiver_zip"]; // 创建时间
            if ($v['shipping_type'] == 1) {
                $list[$k]["shipping_type_name"] = '商家配送';
            } elseif ($v['shipping_type'] == 2) {
                $list[$k]["shipping_type_name"] = '门店自提';
            } else {
                $list[$k]["shipping_type_name"] = '';
            }
            if ($v['pay_status'] == 0) {
                $list[$k]["pay_status_name"] = '待付款';
            } elseif ($v['pay_status'] == 2) {
                $list[$k]["pay_status_name"] = '已付款';
            } elseif ($v['pay_status'] == 1) {
                $list[$k]["pay_status_name"] = '支付中';
            }
            $goods_info = "";
            foreach ($v["order_item_list"] as $t => $m) {
                $goods_info .= "商品名称:" . $m["goods_name"] . "  规格:" . $m["sku_name"] . "  商品价格:" . $m["price"] . "  购买数量:" . $m["num"] . "  ";
            }
            $list[$k]["goods_info"] = $goods_info;
        }
        dataExcel($xlsName, $xlsCell, $list);
    }

    /**
     * 得到订单项的退款信息
     * @return \think\response\Json
     */
    public function getOrderGoodsRefundDetail()
    {
        $order_goods_id = request()->post("order_goods_id", 0);
        if (empty($order_goods_id)) {
            return json(resultArray(2, "没有获取到相关信息 "));
        }
        $order_goods = new OrderGoodsHandle();

        $res = $order_goods->getOrderGoodsRefundDetail($order_goods_id);
        $order_id = $res['order_id'];
        $order = new OrderHandle();
        $order_info = $order->getOrderInfo($order_id);
        $data = array (
            'order_info'=>$order_info,
            'refund_detail'=>$res
        );
        return json(resultArray(0, "操作成功", $data));
    }

    /**
     * ok-2ok
     * 删除订单
     */
    public function deleteOrder()
    {
        $order_handle = new OrderHandle();
        $order_id = request()->post("order_id");
        if (empty($order_id)) {
            return json(resultArray(2, "未指定订单"));
        }

        $user_id = $this->userId;
        $res = $order_handle->deleteOrder($order_id, 1, $user_id);

        if (empty($res)) {
            return json(resultArray(2, "删除失败"));
        } else {
            return json(resultArray(0, "删除成功"));
        }
    }


    /**********************支付相关**************************/
    /**
     * ok-2ok
     * 检测支付配置是否开启，支付配置和原路退款配置都要开启才行（配置信息也要填写）
     */
    public function checkPayConfigEnabled()
    {
        $type = request()->post("type", ""); //type:alipay, wechat
        if (empty($type)) {
            return json(resultArray(2, '支付类型不可为空'));
        }

        $config = new ConfigHandle();
        $enabled = $config->checkPayConfigEnabled(0, $type);
        if (empty($enabled)) {
            return json(resultArray(2, $config->getError(), 0));
        } else {
            return json(resultArray(0, "已正确开启", 1));
        }
    }

    /**
     * ok-2ok
     * 查询当前订单的付款方式，用于进行退款操作时，选择退款方式
     */
    public function getOrderTermsOfPayment()
    {
        $order_id = request()->post("order_id", "");
        if (empty($order_id)) {
            return json(resultArray(2, "未指定订单id"));
        }

        $order = new OrderHandle();
        $payment_type = $order->getTermsOfPaymentByOrderId($order_id);
        $type = OrderStatusHandle::getPayType($payment_type);
        $json = array();
        if ($type == "微信支付") {
            $temp['type_id'] = 1;
            $temp['type_name'] = "微信";
            array_push($json, $temp);
            $temp['type_id'] = 10;
            $temp['type_name'] = "线下";
            array_push($json, $temp);
        } elseif ($type == "支付宝") {
            $temp['type_id'] = 2;
            $temp['type_name'] = "支付宝";
            array_push($json, $temp);
            $temp['type_id'] = 10;
            $temp['type_name'] = "线下";
            array_push($json, $temp);
        } else {
            $temp['type_id'] = 10;
            $temp['type_name'] = "线下";
            array_push($json, $temp);
        }

        return json(resultArray(0, "操作成功", $json));
    }

    /**
     * ok-2ok
     * 查询订单项实际可退款余额
     */
    public function getOrderGoodsRefundBalance()
    {
        $order_goods_id = request()->post("order_goods_id", "");
        if (empty($order_goods_id)) {
            return json(resultArray(2, "未指定订单项id"));
        }

        $order_goods = new OrderGoodsHandle();
        $refund_balance = $order_goods->orderGoodsRefundBalance($order_goods_id);
        return json(resultArray(0, "操作成功", $refund_balance));
    }


    /**
     * ok-2ok
     * 订单数统计
     */
    public function getOrderCount()
    {
        $order = new OrderHandle();
        $order_count_array = array();
        $order_count_array['daifukuan'] = $order->getOrderCount([
            'order_status' => 0
        ]); // 代付款
        $order_count_array['daifahuo'] = $order->getOrderCount([
            'order_status' => 1
        ]); // 代发货
        $order_count_array['yifahuo'] = $order->getOrderCount([
            'order_status' => 2
        ]); // 已发货
        $order_count_array['yishouhuo'] = $order->getOrderCount([
            'order_status' => 3
        ]); // 已收货
        $order_count_array['yiwancheng'] = $order->getOrderCount([
            'order_status' => 4
        ]); // 已完成
        $order_count_array['yiguanbi'] = $order->getOrderCount([
            'order_status' => 5,
            'is_deleted' => 0
        ]); // 已关闭
        $order_count_array['tuikuanzhong'] = $order->getOrderCount([
            'order_status' => - 1
        ]); // 退款中
        /*
        $order_count_array['yituikuan'] = $order->getOrderCount([
            'order_status' => - 2
        ]);
        */// 已退款

        $order_count_array['all'] = $order->getOrderCount([
            'is_deleted' => 0
        ]);
        // 全部订单数量，排除已删除的

       // $order_count_array['all'] = $order->getOrderCount([]); // 全部订单数量
        $agent_handle = new AgentHandle();
        $first_order_count =  $order->getOrderCount([
            'agent_id'=> $agent_handle->getPlatformAgentId(),
            'is_deleted' => 0
        ]);
        $second_order_count =  $order->getOrderCount([
            'agent_id'=>['<>', $agent_handle->getPlatformAgentId()],
                'is_deleted' => 0

        ]);
        $order_count_array['platform_order_count']=$first_order_count;
        $order_count_array['agent_order_count']=$second_order_count;

        return json(resultArray(0,"操作成功",$order_count_array));
    }
}