<?php
/**
 * 任务
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-11-08
 * Time: 10:58
 */

namespace dao\handle;

use dao\model\PromotionMansong as PromotionMansongModel;
use dao\handle\OrderHandle;
use dao\model\Order as OrderModel;
use dao\model\PromotionMansongGoods as PromotionMansongGoodsModel;
use dao\model\PromotionDiscount as PromotionDiscountModel;
use dao\model\PromotionDiscountGoods as PromotionDiscountGoodsModel;
use dao\model\GoodsSku as GoodsSkuModel;
use dao\model\Goods as GoodsModel;
use dao\model\Coupon as CouponModel;
use dao\model\CouponGoods as CouponGoodsModel;
use think\Log;


class EventsHandle extends BaseHandle
{
    function __construct()
    {
        parent::__construct();
    }

    /**
     * 暂未实现
     * 赠品超过有效期限自动取消
     */
    public function giftClose(){

    }

    /**
     * ok-2ok
     * 满减送超过期限自动关闭, 进入时间自动开始
     */
    public function mansongOperation(){
        $mansong = new PromotionMansongModel();
        $this->startTrans();
        try{
            $time = time();
            $condition_close = array(
                'end_time' => array('LT', $time),
                'status'   => array('NEQ', 3)
            );
            $condition_start = array(
                'start_time' => array('ELT', $time),
                'status'   => 0
            );
            $mansong->save(['status' => 4], $condition_close);
            $mansong->save(['status' => 1], $condition_start);
            $mansong_goods = new PromotionMansongGoodsModel();
            $mansong_goods->save(['status' => 4], $condition_close);
            $mansong_goods->save(['status' => 1], $condition_start);
            $this->commit();
            return true;
        }catch (\Exception $e)
        {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * ok-2ok
     * 订单长时间未付款自动交易关闭
     */
    public function ordersClose(){
        $order_model = new OrderModel();

        try{
            $config = new ConfigHandle();
            $config_info = $config->getConfig(0, 'ORDER_BUY_CLOSE_TIME');
            if(!empty($config_info['value']))
            {
                $close_time = $config_info['value'];
            }else{
                $close_time = 1440; // 60;//默认原为1小时,现改为24小时
            }
            $time = time() - $close_time * 60;//订单自动关闭
            $condition = array(
                'order_status' => 0,
                'create_time'  => array('LT', $time),
                'payment_type' => array('neq', 6)
            );
            $order_list = $order_model->getConditionQuery($condition, 'id', '');
            if(!empty($order_list))
            {
                $order = new OrderHandle();
                foreach ($order_list as $k => $v)
                {
                    if(!empty($v['id']))
                    {
                        $user_id = 0;
                        $user_type = 4;
                        $order->orderClose($user_id, $user_type,$v['id']);
                    }
                }

            }
            return true;
        }catch (\Exception $e)
        {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * ok-2ok
     * 订单收货后7天自动交易完成
     */
    public function ordersComplete(){
        $order_model = new OrderModel();
        try{
            $config = new ConfigHandle();
            $config_info = $config->getConfig(0, 'ORDER_DELIVERY_COMPLETE_TIME');
            if($config_info['value'] != '')
            {
                $complete_time = $config_info['value'];
            }else{
                $complete_time = 7;//7天
            }
            $time = time() - 3600 * 24 * $complete_time;//订单自动完成

            $condition = array(
                'order_status' => 3,  //已收货
                'sign_time'  => array('LT', $time)
            );
            $order_list = $order_model->getConditionQuery($condition, 'id', '');
            if(!empty($order_list))
            {
                $order = new OrderHandle();
                foreach ($order_list as $k => $v)
                {
                    if(!empty($v['id']))
                    {
                        $user_id = 0;
                        $user_type = 4;
                        $order->orderComplete($user_id, $user_type,$v['id']);
                    }
                }
            }

            return true;
        }catch (\Exception $e)
        {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * ok-2ok
     * 限时折扣自动开始以及自动关闭
     */
    public function discountOperation(){
        $discount = new PromotionDiscountModel();
        $this->startTrans();
        try{
            $time = time();
            $discount_goods = new PromotionDiscountGoodsModel();
            /************************************************************结束活动**************************************************************/
            $condition_close = array(
                'end_time' => array('LT', $time),
                'status'   => array('NEQ', 3)
            );
            $discount->save(['status' => 4], $condition_close);
            $discount_close_goods_list = $discount_goods->getConditionQuery($condition_close, '*', '');

            if(!empty($discount_close_goods_list))
            {
                foreach ( $discount_close_goods_list as $k => $discount_goods_item)
                {
                    $goods = new GoodsModel();

                    $data_goods = array(
                        'promotion_type' => 2,
                        'promote_id'     => $discount_goods_item['discount_id']
                    );
                    $goods_id_list = $goods->getConditionQuery($data_goods, 'id', '');
                    if(!empty($goods_id_list))
                    {
                        foreach($goods_id_list as $k => $goods_id)
                        {
                            $goods_info = $goods->getInfo(['id' => $goods_id['id']], 'promotion_type,price');
                            $goods->save(['promotion_price' => $goods_info['price']], ['id'=> $goods_id['id'] ]);
                            $goods_sku = new GoodsSkuModel();
                            $goods_sku_list = $goods_sku->getConditionQuery(['goods_id'=> $goods_id['id'] ], 'price,id', '');
                            foreach ($goods_sku_list as $k_sku => $sku)
                            {
                                $goods_sku = new GoodsSkuModel();
                                $data_goods_sku = array(
                                    'promotion_price' => $sku['price']
                                );
                                $goods_sku->save($data_goods_sku, ['id' => $sku['id']]);
                            }

                        }

                    }
                    $goods->save(['promotion_type' => 0, 'promote_id' => 0], $data_goods);

                }
            }
            $discount_goods->save(['status' => 4], $condition_close);
            /************************************************************结束活动**************************************************************/
            /************************************************************开始活动**************************************************************/
            $condition_start = array(
                'start_time' => array('ELT', $time),
                'status'   => 0
            );
            //查询待开始活动列表
            $discount_goods_list = $discount_goods->getConditionQuery($condition_start, '*', '');
            if(!empty($discount_goods_list))
            {
                foreach ( $discount_goods_list as $k => $discount_goods_item)
                {
                    $goods = new GoodsModel();
                    $goods_info = $goods->getInfo(['id' => $discount_goods_item['goods_id']],'promotion_type,price');
                    $data_goods = array(
                        'promotion_type' => 2,
                        'promote_id'     => $discount_goods_item['discount_id'],
                        'promotion_price'  => $goods_info['price'] *$discount_goods_item['discount']/10
                    );
                    $goods->save($data_goods,['id' => $discount_goods_item['goods_id']]);
                    $goods_sku = new GoodsSkuModel();
                    $goods_sku_list = $goods_sku->getConditionQuery(['goods_id'=> $discount_goods_item['goods_id'] ], 'price,id', '');
                    foreach ($goods_sku_list as $k_sku => $sku)
                    {
                        $goods_sku = new GoodsSkuModel();
                        $data_goods_sku = array(
                            'promotion_price' => $sku['price']*$discount_goods_item['discount']/10
                        );
                        $goods_sku->save($data_goods_sku, ['id' => $sku['id']]);
                    }
                }
            }
            $discount_goods->save(['status' => 1], $condition_start);
            $discount->save(['status' => 1], $condition_start);
            /************************************************************开始活动**************************************************************/
            $this->commit();
            return true;
        }catch (\Exception $e)
        {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * ok-2ok
     * 自动收货
     */
    public function autoDeilvery(){
        $order_model = new OrderModel();

        try{
            $config = new ConfigHandle();
            $config_info = $config->getConfig(0, 'ORDER_AUTO_DELIVERY');
            if(!empty($config_info['value']))
            {
                $delivery_time = $config_info['value'];
            }else{
                $delivery_time = 7;//默认7天自动收货
            }
            $time = time() - 3600 * 24 * $delivery_time;//订单自动完成

            $condition = array(
                'order_status' => 2,
                'consign_time'  => array('LT', $time)
            );
            $order_list = $order_model->getConditionQuery($condition, 'id', '');
            if(!empty($order_list))
            {
                $order = new \dao\handle\order\OrderHandle();
                foreach ($order_list as $k => $v)
                {
                    if(!empty($v['id']))
                    {
                        $order->orderAutoDelivery($v['id']);
                    }

                }

            }

            return true;
        }catch (\Exception $e)
        {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * ok-2ok
     * 优惠券自动过期
     */
    public function autoCouponClose(){
        $coupon_model = new CouponModel();
        $this->startTrans();
        try{
            $condition['end_time'] = array('LT',time());
            $condition['status'] = array('NEQ',2);//排除已使用的优惠券
            $condition['status'] = array('NEQ',3); //排除已关闭的优惠券
            $count = $coupon_model->getCount($condition);
          //  $res = true;
            $res = -1;
            if($count){
                $res = $coupon_model->save(['status'=>3],$condition);
            }
            $this->commit();
            return $res;
            /*
            if ($res === false) {
                return false;
            }
            return true;
            */
        }catch (\Exception $e)
        {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }

}