<?php
/**
 * OrderGoodsHandle.php
 * 处理订单商品--ok
 * @date : 2017.8.17
 * @version : v1.0.0.0
 */

namespace dao\handle\order;

use dao\handle\order\OrderStatusHandle;
use dao\handle\order\OrderHandle;
use dao\handle\PlatformUserHandle;
use dao\model\OrderGoods as OrderGoodsModel;
use dao\model\OrderRefund as OrderRefundModel;
use dao\model\Goods as GoodsModel;
use dao\model\GoodsSku as GoodsSkuModel;
use dao\handle\BaseHandle;
use dao\handle\promotion\GoodsPreferenceHandle;
use dao\model\MemberUser as MemberUserModel;
use dao\model\AlbumPicture as AlbumPictureModel;
use dao\model\OrderGoodsPromotionDetails as OrderGoodsPromotionDetailsModel;
use dao\handle\goodscalculate\GoodsCalculateHandle;
use dao\model\Order as OrderModel;
use think\Log;
use dao\handle\member\MemberAccountHandle;
// use think\Model;

class OrderGoodsHandle extends BaseHandle
{

    public $order_goods;
 // 订单主表
    function __construct()
    {
        parent::__construct();
        $this->order_goods = new OrderGoodsModel();
    }

    /**
     * 订单创建添加订单项 -ok
     * order_goods_id int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '订单项ID',
     * order_id int(11) NOT NULL COMMENT '订单ID',
     * goods_id int(11) NOT NULL COMMENT '商品ID',
     * goods_name varchar(50) NOT NULL COMMENT '商品名称',
     * sku_id int(11) NOT NULL COMMENT 'skuID',
     * sku_name varchar(50) NOT NULL COMMENT 'sku名称',
     * price decimal(19, 2) NOT NULL DEFAULT 0.00 COMMENT '商品价格',
     * num varchar(255) NOT NULL DEFAULT '0' COMMENT '购买数量',
     * adjust_money varchar(255) NOT NULL DEFAULT '0' COMMENT '调整金额',
     * goods_money varchar(255) NOT NULL DEFAULT '0' COMMENT '商品总价',
     * goods_picture int(11) NOT NULL DEFAULT 0 COMMENT '商品图片',
     * shop_id int(11) NOT NULL DEFAULT 1 COMMENT '店铺ID',
     * buyer_id int(11) NOT NULL DEFAULT 0 COMMENT '购买人ID',
     * goods_type varchar(255) NOT NULL DEFAULT '1' COMMENT '商品类型',
     * promotion_id int(11) NOT NULL DEFAULT 0 COMMENT '促销ID',
     * promotion_type_id int(11) NOT NULL DEFAULT 0 COMMENT '促销类型',
     * order_type int(11) NOT NULL DEFAULT 1 COMMENT '订单类型',
     * order_status int(2) NOT NULL DEFAULT 0 COMMENT '订单状态',
     * give_point int(2) NOT NULL DEFAULT 0 COMMENT '积分数量',
     * shipping_status int(2) NOT NULL DEFAULT 0 COMMENT '物流状态',
     * refund_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '退款时间',
     * refund_type int(11) NOT NULL DEFAULT 1 COMMENT '退款方式',
     * refund_require_money decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '退款金额',
     * refund_reason varchar(255) NOT NULL DEFAULT '' COMMENT '退款原因',
     * refund_shipping_code varchar(255) NOT NULL DEFAULT '' COMMENT '退款物流单号',
     * refund_shipping_company int(11) NOT NULL DEFAULT 0 COMMENT '退款物流公司名称',
     * refund_real_money decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '实际退款金额',
     * refund_status int(1) NOT NULL DEFAULT 0 COMMENT '退款状态',
     * memo varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
     * PRIMARY KEY (order_goods_id)
     * 
     * @param  $goods_sku_list
     *$goods_list = goods_id:sku_id:num
     */
    public function addOrderGoods($user_id,$order_id, $goods_list,  $adjust_money = 0) // $goods_sku_list
    {
        $this->startTrans();
        try {
            $err = 0;
          //  $goods_sku_list_array = explode(",", $goods_sku_list);
            $goods_list_array = explode(",", $goods_list);
            foreach ($goods_list_array as $k => $goods_array) {
                $goods_item = explode(':', $goods_array);
                $goods_id = $goods_item[0];
                $sku_id = $goods_item[1];
                $num = $goods_item[2];
                $goods_model = new GoodsModel();
                $goods_info = $goods_model->getInfo([
                    'id' => $goods_id // $goods_sku_info['goods_id']
                ], 'title,price,cost_price,promotion_price, type,thumb,promotion_type,promote_id,point_exchange_type,total, give_point');
                $sku_name = "";
                $price = $goods_info['promotion_price'];
                $cost_price = $goods_info['cost_price'];
                if ($sku_id > 0) {
                    $goods_sku_model = new GoodsSkuModel();
                    $goods_sku_info = $goods_sku_model->getInfo([
                        'id' => $sku_id   //$goods_sku[0]
                    ], 'id,goods_id,cost_price,stock,title');
                    $sku_name = $goods_sku_info['title'];
                    //这里的优惠针对sku,以后要改为针对goods
                    $goods_promote = new GoodsPreferenceHandle();
                   // getGoodsSkuPrice($user_id, $sku_id)
                    $sku_price = $goods_promote->getGoodsSkuPrice($user_id, $sku_id);
                    $price = $sku_price;
                    $cost_price = $goods_sku_info['cost_price'];
                    $goods_promote_info = $goods_promote->getGoodsPromote($goods_id);
                    if (empty($goods_promote_info)) {
                        $goods_info['promotion_type'] = 0;
                        $goods_info['promote_id'] = 0;
                    }
                    if ($goods_sku_info['stock'] < $num || $num <= 0) {
                        $this->rollback();
                        if ($num <=0) {
                            $this->error ="购买数量必须大于0";
                        } else {
                            $this->error = $goods_info['title'] . ":" . $goods_sku_info['title'] . ',库存量为 ' . $goods_sku_info['stock'] . ",库存低于需求量";
                        }
                        return false; //LOW_STOCKS;
                    }
                    // 库存减少销量增加
                    $goods_calculate = new GoodsCalculateHandle();
                    $goods_calculate->subGoodsStock($goods_sku_info['goods_id'], $sku_id, $num, '');
                    $goods_calculate->addGoodsSales($goods_sku_info['goods_id'], $sku_id, $num);
                } else {
                     //sku_id <= 0
                    //这里的优惠针对sku,以后要改为针对goods

                    $goods_promote = new GoodsPreferenceHandle();
                    $price = $goods_promote->getGoodsPrice($goods_id);
                    $goods_promote_info = $goods_promote->getGoodsPromote($goods_id);
                    if (empty($goods_promote_info)) {
                        $goods_info['promotion_type'] = 0;
                        $goods_info['promote_id'] = 0;
                    }

                    if ($goods_info['total'] < $num || $num <= 0) {
                        $this->rollback();
                        if ($num <=0) {
                            $this->error = "购买数量必须大于0";
                        } else {
                            $this->error = $goods_info['title'] . ":" . ',库存量为 ' . $goods_info['total'] . ",库存低于需求量";
                        }

                       // $this->error = "库存低于需求量";
                        return false; //LOW_STOCKS;
                    }
                    // 库存减少销量增加
                    /*
                    $goods_info['promotion_type'] = 0;
                    $goods_info['promote_id'] = 0;
                    */
                    $goods_calculate = new GoodsCalculateHandle();
                    $goods_calculate->subGoodsStock($goods_id, 0, $num, '');
                    $goods_calculate->addGoodsSales($goods_id, 0, $num);


                }


                $give_point = $num * $goods_info["give_point"];

                $data_order_sku = array(
                    'order_id' => $order_id,
                    'goods_id' => $goods_id,
                    'goods_name' => $goods_info['title'],
                    'sku_id' =>  $sku_id, // $goods_sku_info['sku_id'],
                    'sku_name' => $sku_name, //$goods_sku_info['sku_name'],
                    'price' => $price, //$sku_price,
                    'num' => $num,   //$goods_sku[1],
                    'adjust_money' => $adjust_money,
                    'cost_price' => $cost_price, //$goods_sku_info['cost_price'],
                    'goods_money' => $price * $num - $adjust_money, //$sku_price * $goods_sku[1] - $adjust_money,
                    'goods_picture' => $goods_info['thumb'],
                   // 'shop_id' => $this->instance_id,
                    'buyer_id' => $user_id,
                    'goods_type' => $goods_info['type'],
                    'promotion_id' => $goods_info['promote_id'],
                    'promotion_type_id' => $goods_info['promotion_type'],
                    'point_exchange_type' => $goods_info['point_exchange_type'],
                    'order_type' => 1, // 订单类型默认1
                    'give_point' => $give_point
                ) // 积分数量默认0

                ;
              //  if ($goods_sku[1] == 0) {
                if ($num <= 0) {
                    $err = 1;
                }
                $order_goods = new OrderGoodsModel();
                
                $order_goods->save($data_order_sku);
            }
            if ($err == 0) {
                $this->commit();
                return 1;
            } elseif ($err == 1) {
                $this->error = "所购商品数量为0";
                $this->rollback();
                return false;
              //  return ORDER_GOODS_ZERO;
            }
        } catch (\Exception $e) {
            $this->rollback();
            $this->error="操作出现异常：".$e->getMessage();
            return false;
           // return $e->getMessage();
        }
    }

    /**okkk-2ok
     * 订单项发货--ok
     * 
     * @param  $order_goods_id_array
     *            ','隔开
     */
    public function orderGoodsDelivery($user_id, $user_type, $order_id, $order_goods_id_array)
    {
        $this->startTrans();
        try {
            $order_goods_id_array = explode(',', $order_goods_id_array);
            foreach ($order_goods_id_array as $k => $order_goods_id) {
                $order_goods_id = (int) $order_goods_id;
                $data = array(
                    'shipping_status' => 1
                );
                $order_goods = new OrderGoodsModel();
                $retval = $order_goods->save($data, [
                    'id' => $order_goods_id
                ]);

                if ($retval === false) {
                    $this->rollback();
                    Log::write('order_goods->save 出错');
                    return false;
                }
            }
            
            $order = new OrderHandle();

           // orderDoDelivery($user_id, $user_type, $orderid)
           // orderDoDelivery($user_id, $user_type, $orderid)
           // orderDoDelivery($user_id, $user_type, $orderid)
           // orderDoDelivery($user_id, $user_type, $orderid)
            $retval =  $order->orderDoDelivery($user_id, $user_type, $order_id);  //订单发货(整体发货)(不考虑订单项)
            if (empty($retval)) {
                $this->rollback();
                $this->error = $order->getError();
                return false;
            }
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
          //  return $e->getMessage();
            return false;
        }
        
      //  return $retval;
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
     * @param  $refund_reason
     */
    public function orderGoodsRefundAskfor($user_id, $user_type, $order_id, $order_goods_id, $refund_type, $refund_require_money, $refund_reason)
    {
        $this->startTrans();
        try {
            $status_id = OrderStatusHandle::getRefundStatus()[0]['status_id'];
            // 订单项退款操作
            $order_goods = new OrderGoodsModel();
            $order_goods_data = array(
                'refund_status' => $status_id,
                'refund_time' => time(),
                'refund_type' => $refund_type,
                'refund_require_money' => $refund_require_money,
                'refund_reason' => $refund_reason
            );
            $res = $order_goods->save($order_goods_data, [
                'id' => $order_goods_id
            ]);

            if ($res === false) {
                $this->rollback();
               Log::write('order_goods->save 出错');
                return false;
            }
            
            // 退款记录
        //    addOrderRefundAction($order_goods_id, $refund_status_id, $action_way, $user_id, $user_type)
           $res =  $this->addOrderRefundAction($order_goods_id, $status_id, 1, $user_id, $user_type);
            if (empty($res)) {
                $this->rollback();
                Log::write('this->addOrderRefundAction 出错');
                return false;
            }
            // 订单退款操作
            $order = new OrderHandle();
           // orderGoodsRefundFinish($user_id, $order_id)
         //   orderGoodsRefundFinish($user_id, $user_type, $order_id)
           // orderGoodsRefundFinish($user_id, $user_type, $order_id)
            $res = $order->orderGoodsRefundFinish($user_id,$user_type, $order_id);
            if (empty($res)) {
                $this->rollback();
                Log::write('order->orderGoodsRefundFinish 出错');
                return false;
            }
            
            $this->commit();
           // return 1;
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 买家取消退款--ok-2ok
     */
    public function orderGoodsCancel($user_id,$user_type, $order_id, $order_goods_id)
    {
        $this->startTrans();
        try {
            $status_id = OrderStatusHandle::getRefundStatus()[6]['status_id'];
            
            // 订单项退款操作
            $order_goods = new OrderGoodsModel();
            $order_goods_data = array(
                'refund_status' => $status_id
            );
            $res = $order_goods->save($order_goods_data, [
                'id' => $order_goods_id,
             //   'buyer_id' => $user_id
            ]);

            if ($res === false) {
                $this->rollback();
                Log::write('order_goods->save 出错');
                return false;
            }
            
            // 退款记录

            $res = $this->addOrderRefundAction($order_goods_id, $status_id, 1, $user_id, $user_type);
            if (empty($res)) {
                $this->rollback();
                return false;
            }
            // 订单退款操作
            $order = new OrderHandle();
            $res = $order->orderGoodsRefundFinish($user_id,$user_type, $order_id);
            if (empty($res)) {
                $this->rollback();
                return false;
            }
            
            $this->commit();
            return true;
           // return 1;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 买家退货-ok-2ok
     */
    public function orderGoodsReturnGoods($user_id, $user_type, $order_id, $order_goods_id, $refund_shipping_company, $refund_shipping_code)
    {
        $order_goods = OrderGoodsModel::get($order_goods_id);
        $this->startTrans();
        try {
            $status_id = OrderStatusHandle::getRefundStatus()[2]['status_id'];
            
            // 订单项退款操作
            $order_goods->refund_status = $status_id;
            $order_goods->refund_shipping_company = $refund_shipping_company;
            $order_goods->refund_shipping_code = $refund_shipping_code;
            $retval = $order_goods->save();

            if ($retval === false) {
                $this->rollback();
                Log::write('order_goods->save 出错');
                return false;
            }
            
            // 退款记录
            $res =  $this->addOrderRefundAction($order_goods_id, $status_id, 1, $user_id, $user_type);
            if (empty($res)) {
                $this->rollback();
                Log::write('this->addOrderRefundAction 出错');
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
     * 卖家同意买家退款申请-ok-2ok
     */
    public function orderGoodsRefundAgree($user_id, $user_type, $order_id, $order_goods_id)
    {
        $this->startTrans();
        try {
            
            // 退款信息
            $refund_status = OrderStatusHandle::getRefundStatus();
            $orderGoodsInfo = OrderGoodsModel::get($order_goods_id);
            $refund_type = $orderGoodsInfo->refund_type;
            if ($refund_type == 1) { // 仅退款
                $status_id = OrderStatusHandle::getRefundStatus()[3]['status_id'];
            } else { // 退货退款
                $status_id = OrderStatusHandle::getRefundStatus()[1]['status_id'];
            }
            
            // 订单项退款操作
            $order_goods = new OrderGoodsModel();
            $order_goods_data = array(
                'refund_status' => $status_id
            );
            $res = $order_goods->save($order_goods_data, [
                'id' => $order_goods_id
            ]);

            if ($res === false) {
                $this->rollback();
                Log::write('order_goods->save 出错');
                return false;
            }
            
            // 退款记录

           $res =  $this->addOrderRefundAction($order_goods_id, $status_id, 2, $user_id, $user_type);
            if (empty($res)) {
                $this->rollback();
                Log::write('this->addOrderRefundAction 出错');
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
     * 卖家永久拒绝本退款-ok
     */
    public function orderGoodsRefuseForever($user_id, $user_type, $order_id, $order_goods_id)
    {
        $this->startTrans();
        try {
            
            $status_id = OrderStatusHandle::getRefundStatus()[5]['status_id'];
            // 订单项退款操作
            $order_goods = new OrderGoodsModel();
            $order_goods_data = array(
                'refund_status' => $status_id
            );
            $res = $order_goods->save($order_goods_data, [
                'id' => $order_goods_id
            ]);

            if ($res === false) {
                $this->rollback();
                Log::write('order_goods->save 出错');
                return false;
            }
            
            // 退款记录
            $res = $this->addOrderRefundAction($order_goods_id, $status_id, 2, $user_id, $user_type);
            if (empty($res)) {
                $this->rollback();
                Log::write('this->addOrderRefundAction 出错');
                return false;
            }
            // 订单恢复正常操作
            $order = new OrderHandle();

            $res = $order->orderGoodsRefundFinish($user_id, $user_type, $order_id);
            if (empty($res)) {
                $this->rollback();
                $this->error = $order->getError();
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
     * 卖家拒绝本次退款-ok-2okkk
     */
    public function orderGoodsRefuseOnce($user_id, $user_type, $order_id, $order_goods_id)
    {
        $this->startTrans();
        try {
            $status_id = OrderStatusHandle::getRefundStatus()[7]['status_id'];
            
            // 订单项退款操作
            $order_goods = new OrderGoodsModel();
            $order_goods_data = array(
                'refund_status' => $status_id
            );
            $res = $order_goods->save($order_goods_data, [
                'id' => $order_goods_id
            ]);

            if ($res === false){
                $this->rollback();
                Log::write('order_goods->save 出错');
                return false;
            }

            // 退款日志
            $res = $this->addOrderRefundAction($order_goods_id, $status_id, 2, $user_id, $user_type);
            if (empty($res)) {
                $this->rollback();
                Log::write('this->addOrderRefundAction 出错');
                return false;
            }
            // 订单恢复正常操作
            $order = new OrderHandle();
            $res = $order->orderGoodsRefundFinish($user_id, $user_type,$order_id);
            if (empty($res)) {
                $this->rollback();
                $this->error = $order->getError();
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
     * 卖家确认收货(退货后收货)-ok-2okkk
     */
    public function orderGoodsConfirmRecieve($user_id, $user_type, $order_id, $order_goods_id, $storage_num, $isStorage, $goods_id, $sku_id)
    {
        $this->startTrans();
        try {
            $status_id = OrderStatusHandle::getRefundStatus()[3]['status_id'];
            
            // 订单项退款操作
            $order_goods = new OrderGoodsModel();
            $order_goods_data = array(
                'refund_status' => $status_id
            );
            $res = $order_goods->save($order_goods_data, [
                'id' => $order_goods_id
            ]);

            if ($res === false) {
                $this->rollback();
                Log::write('order_goods->save 出错');
                return false;
            }

            // 退款记录
            $res = $this->addOrderRefundAction($order_goods_id, $status_id, 2, $user_id, $user_type);
            if (empty($res)) {
                $this->rollback();
                Log::write('this->addOrderRefundAction 出错');
                return false;
            }
            if($isStorage > 0){

                $goods_sku_model = new GoodsSkuModel();
                $goods_sku_model->where(["goods_id"=>$goods_id,"id"=>$sku_id])->setInc('stock',$storage_num);

                $goods_model = new GoodsModel();
                $goods_model->where(["id"=>$goods_id])->setInc('total',$storage_num); //新增
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
     * 卖家确认退款-ok-2okk
     */
    public function orderGoodsConfirmRefund2($user_id, $user_type, $order_id, $order_goods_id, $refund_real_money)
    {
        $order_goods = OrderGoodsModel::get($order_goods_id);
        $this->startTrans();
        try {
            $status_id = OrderStatusHandle::getRefundStatus()[4]['status_id'];
            
            // 订单项退款操作
            $order_goods->refund_status = $status_id;
            $order_goods->refund_real_money = $refund_real_money;
            $res = $order_goods->save();
            if ($res === false) {
                $this->rollback();
                Log::write('order_goods->save 出错');
                return false;
            }
            // 退款记录
            $res = $this->addOrderRefundAction($order_goods_id, $status_id, 2, $user_id, $user_type);
            if (empty($res)) {
                $this->rollback();
                Log::write('this->addOrderRefundAction 出错');
                return false;
            }

            $order_model = new OrderModel();
            // 订单添加退款金额
            $order_info = $order_model->getInfo([
                'id' => $order_id
            ], '*');
            $res = $order_model->save([
                'refund_money' => $order_info['refund_money'] + $refund_real_money
            ], [
                'id' => $order_id
            ]);
            if ($res === false) {
                $this->rollback();
                Log::write('order_model->save 出错');
                return false;
            }

            // 订单恢复正常操作
            $order = new OrderHandle();
            //orderGoodsRefundFinish($user_id, $user_type, $order_id)
            $retval = $order->orderGoodsRefundFinish($user_id, $user_type,$order_id);
            if (empty($retval)) {
                $this->rollback();
                $this->error = $order->getError();
                return false;
            }

            // 退款时 扣除已发放的积分
            $give_point = $order_goods["give_point"];
            if ($order_info["give_point_type"] == 3) {
                $member_account = new MemberAccountHandle();
                $text = "退款成功,扣除已发放的积分";
                $operation_id=31; //订单退款-将送出的积分收回
                $member_account->addMemberAccountData( 1, $order_info['buyer_id'], 0, -$give_point, 1, $order_id, $text, $operation_id);
            }
            $total_point = $order_info["give_point"] - $give_point;
            $order_model->save([
                "give_point" => $total_point
            ], [
                'id' => $order_id
            ]);
             $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * ok-2ok
     * 卖家确认退款
     */
    public function orderGoodsConfirmRefund($user_id, $user_type,$order_id, $order_goods_id, $refund_real_money, $refund_balance_money, $refund_trade_no, $refund_way, $refund_remark)
    {
        $order_goods = OrderGoodsModel::get($order_goods_id);
        $this->startTrans();
        try {
            $status_id = OrderStatusHandle::getRefundStatus()[4]['status_id'];

            // 订单项退款操作
            $order_goods->refund_status = $status_id;
            $order_goods->refund_real_money = $refund_real_money; // 退款金额
            $order_goods->refund_balance_money = $refund_balance_money; // 退款余额
            $res = $order_goods->save();

            if ($res === false) {
                $this->rollback();
                Log::write('order_goods->save 出错');
                return false;
            }

            // 执行余额账户修正
            // 退款记录
            $res = $this->addOrderRefundAction($order_goods_id, $status_id, 2, $user_id, $user_type);
            if (empty($res)) {
                $this->rollback();
                Log::write('this->addOrderRefundAction 出错');
                return false;
            }

            $order_model = new OrderModel();

            // 订单添加退款金额、余额
            $order_info = $order_model->getInfo([
                'id' => $order_id
            ], '*');

            $order = new OrderHandle();
            // 添加退款帐户记录
            if (empty($refund_remark)) {
                $remark = "订单编号:" . $order_info['order_no'] . "，退款方式为:[" . OrderStatusHandle::getPayType($refund_way) . "]，退款金额:" . $refund_real_money . "元，退款余额：" . $refund_balance_money . "元";
            } else {
                $remark = $refund_remark;
            }
            $res =  $order->addOrderRefundAccountRecords($order_goods_id, $refund_trade_no, $refund_real_money, $refund_way, $order_info['buyer_id'], $remark);

            if (empty($res)) {
                $this->rollback();
                Log::write('order->addOrderRefundAccountRecords 出错');
                return false;
            }

            $res =$order_model->save([
                'refund_money' => $order_info['refund_money'] + $refund_real_money,
                'refund_balance_money' => $order_info['refund_balance_money'] + $refund_balance_money
            ], [
                'id' => $order_id
            ]);
            if ($res === false) {
                $this->rollback();
                Log::write('order_model->save 出错');
                return false;
            }

            $this->orderGoodsRefundExt($order_id, $order_goods_id, $refund_balance_money);

            // 订单恢复正常操作
          //  $retval = $order->orderGoodsRefundFinish($order_id);
            $retval = $order->orderGoodsRefundFinish($user_id, $user_type,$order_id);

            if (empty($retval)) {
                $this->rollback();
                $this->error = $order->getError();
                return false;
            }

            // 退款是 扣除已发放的积分
            $give_point = $order_goods["give_point"];
            if ($order_info["give_point_type"] == 3) {
                $member_account = new MemberAccountHandle();
                $text = "退款成功,扣除已发放的积分";
                $operation_id=31; //订单退款-将送出的积分收回
                $member_account->addMemberAccountData( 1, $order_info['buyer_id'], 0, -$give_point, 1, $order_id, $text, $operation_id);

            }

            $total_point = $order_info["give_point"] - $give_point;
            $order_model->save([
                "give_point" => $total_point
            ], [
                'id' => $order_id
            ]);

            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * ok-2ok
     * 订单项目退款处理
     */
    private function orderGoodsRefundExt($order_id, $order_goods_id, $refund_balance_money)
    {
        $order_model = new OrderModel();
        $order_info = $order_model->getInfo([
            'id' => $order_id
        ], '*');
        $member_account = new MemberAccountHandle();
        if ($refund_balance_money > 0) {
            $member_account->addMemberAccountData(2, $order_info['buyer_id'], 1, $refund_balance_money, 2, $order_id,'订单退款-退回支出的余额',41);
        }
    }

    /**
     * 添加订单退款日志--ok-2ok
     * 
     * @param  $order_goods_id            
     * @param  $refund_status            
     * @param  $action            
     * @param  $action_way            
     * @param  $uid            
     * @param  $user_name            
     */
    public function addOrderRefundAction($order_goods_id, $refund_status_id, $action_way, $user_id, $user_type)
    {
        $refund_status = OrderStatusHandle::getRefundStatus();
        foreach ($refund_status as $k => $v) {
            if ($v['status_id'] == $refund_status_id) {
                $refund_status_name = $v['status_name'];
            }
        }
        if ($user_type == 1) {
            $user = new MemberUserModel();
            $user_name = $user->getInfo([
                'id' => $user_id
            ], 'login_phone');
            $action_name = $user_name['login_phone'];
        } else if ($user_type == 2) {
            $action_name= PlatformUserHandle::loginUserName();    // "platform";
        } else if ($user_type == 3) {
            $action_name="agent";
        } else {
            $action_name = "";
        }
        $order_refund = new OrderRefundModel();
        $data_refund = array(
            'order_goods_id' => $order_goods_id,
            'refund_status' => $refund_status_id,
            'action' => $refund_status_name,
            'action_way' => $action_way,
            'action_userid' => $user_id,
            'action_usertype'=>$user_type,
            'action_username' =>  $action_name,  //$user_name['user_name'],
            'action_time' => time()
        );
        $retval = $order_refund->save($data_refund);

        if (empty($retval)) {
            return false;
        } else {
            return true;
        }
       // return $retval;
    }

    /**
     * 订单项商品价格调整--ok-2ok
     * 
     * @param  $order_goods_id_adjust_array
     *            订单项数列 order_goods_id,adjust_money;order_goods_id,adjust_money
     */
    public function orderGoodsAdjustMoney($order_goods_id_adjust_array)
    {
        $this->startTrans();
        try {
            $order_goods_id_adjust_array = explode(';', $order_goods_id_adjust_array);
            if (! empty($order_goods_id_adjust_array)) {
                foreach ($order_goods_id_adjust_array as $k => $order_goods_id_adjust) {
                    $order_goods_adjust_array = explode(',', $order_goods_id_adjust);
                    $order_goods_id = $order_goods_adjust_array[0];
                    $adjust_money = $order_goods_adjust_array[1];
                    $order_goods_info = $this->order_goods->get($order_goods_id);
                    // 调整金额
                    $adjust_money_adjust = $adjust_money - $order_goods_info['adjust_money'];
                    $data = array(
                        'adjust_money' => $adjust_money,
                        'goods_money' => $order_goods_info['goods_money'] + $adjust_money_adjust
                    );
                    $order_goods = new OrderGoodsModel();
                    $res = $order_goods->save($data, [
                        'id' => $order_goods_id
                    ]);
                    if ($res === false) {
                        $this->rollback();
                        Log::write('order_goods->save 出错');
                        return false;
                    }
                }
            }
            
            $this->commit();
            return true;
           // return 1;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->rollback();
            return false;
        }
    }

    /**
     * 获取订单项实际可退款金额--ok-2ok
     * 为什么没有将抵用券减去
     * @param  $order_goods_id            
     */
    public function orderGoodsRefundMoney($order_goods_id)
    {
        $order_goods = new OrderGoodsModel();
        $order_goods_info = $order_goods->getInfo([
            'id' => $order_goods_id
        ], 'order_id,goods_id, sku_id,goods_money');
        $order_goods_promotion = new OrderGoodsPromotionDetailsModel();
        $promotion_money = $order_goods_promotion->where([
            'order_id' => $order_goods_info['order_id'],
            'goods_id' => $order_goods_info['goods_id'],
            'sku_id' => $order_goods_info['sku_id']
        ])->sum('discount_money');
        if (empty($promotion_money)) {
            $promotion_money = 0;
        }
        $money = $order_goods_info['goods_money'] - $promotion_money;
        // 计算其他方式支付金额
        $order = new OrderModel();
        $order_other_pay_money = $order->getInfo([
            'id' => $order_goods_info['order_id']
        ], 'order_money,point_money,user_money,coin_money,user_platform_money,tax_money,shipping_money');
        $all_other_pay_money = $order_other_pay_money['point_money'] + $order_other_pay_money['user_money'] + $order_other_pay_money['coin_money'] + $order_other_pay_money['user_platform_money']-$order_other_pay_money['tax_money'];
        if ($all_other_pay_money != 0) {
            $other_pay = $money / ($order_other_pay_money['order_money']-$order_other_pay_money['shipping_money']-$order_other_pay_money['tax_money'])*$all_other_pay_money;
            $money = $money - round($other_pay,2);
        }
        if ($money < 0) {
            $money = 0;
        }
        return $money;
    }

    /**
     * ok-2ok
     * 获取订单项实际可退款余额
     */
    public function orderGoodsRefundBalance($order_goods_id)
    {
        $order_goods = new OrderGoodsModel();
        $order_goods_info = $order_goods->getInfo([
            'id' => $order_goods_id
        ], 'order_id,goods_id, sku_id,goods_money');


        $order_goods_promotion = new OrderGoodsPromotionDetailsModel();
        $promotion_money = $order_goods_promotion->where([
            'order_id' => $order_goods_info['order_id'],
            'goods_id' => $order_goods_info['goods_id'],
            'sku_id' => $order_goods_info['sku_id']
        ])->sum('discount_money');
        if (empty($promotion_money)) {
            $promotion_money = 0;
        }

        $money = $order_goods_info['goods_money'] - $promotion_money;
        // 计算其他方式支付金额
        $order = new OrderModel();
        $order_other_pay_money = $order->getInfo([
            'id' => $order_goods_info['order_id']
        ], 'order_money,point_money,user_money,coin_money,user_platform_money,tax_money,shipping_money');
        $order_goods_real_money = $order_other_pay_money['order_money'] - $order_other_pay_money['shipping_money'] - $order_other_pay_money['tax_money'];

        if ($order_goods_real_money != 0) {
            $refund_balance = $money / $order_goods_real_money * $order_other_pay_money['user_platform_money'];
            if ($refund_balance < 0) {
                $refund_balance = 0;
            }
        } else {
            $refund_balance = 0;
        }
        return $refund_balance;
    }

    /**
     * 查询订单项退款--ok-2ok
     * 
     * @param  $order_goods_id            
     */
    public function getOrderGoodsRefundDetail($order_goods_id)
    {
        // 查询基础信息
        $order_goods_info = $this->order_goods->get($order_goods_id);
        // 商品图片
        $picture = new AlbumPictureModel();
        $picture_info = $picture->get($order_goods_info['goods_picture']);
        $order_goods_info['picture_info'] = $picture_info;
        if ($order_goods_info['refund_status'] != 0) {
            $order_refund_status = OrderStatusHandle::getRefundStatus();
            foreach ($order_refund_status as $k_status => $v_status) {
                
                if ($v_status['status_id'] == $order_goods_info['refund_status']) {
                    $order_goods_info['refund_operation'] = $v_status['refund_operation'];
                    $order_goods_info['status_name'] = $v_status['status_name'];
                }
            }
            // 查询订单项的操作日志
            $order_refund = new OrderRefundModel();
            $refund_info = $order_refund->all([
                'order_goods_id' => $order_goods_id
            ]);
            $order_goods_info['refund_info'] = $refund_info;
        } else {
            $order_goods_info['refund_operation'] = '';
            $order_goods_info['status_name'] = '';
            $order_goods_info['refund_info'] = '';
        }
        return $order_goods_info;
    }
}