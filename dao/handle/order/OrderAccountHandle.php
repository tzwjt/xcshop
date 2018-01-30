<?php
/**
  * OrderAccountHandle
 * 处理订单帐户-ok
 * @date : 2017.8.17
 * @version : v1.0
 */
namespace dao\handle\order;

use dao\handle\BaseHandle as BaseHandle;
use dao\model\Order as OrderModel;
use dao\model\OrderGoods as OrderGoodsModel;

class OrderAccountHandle extends BaseHandle
{
    /**
     * 获取一段时间之内店铺订单支付统计-ok
     */
    public function getShopOrderSum( $start_time, $end_time)
    {
        $order_model = new OrderModel();
          $condition["create_time"] = [
               [
                   ">=",
                   $this->getTimeTurnTimeStamp($start_time)
               ],
               [
                   "<=",
                   $this->getTimeTurnTimeStamp($end_time)
               ]
           ];
          $condition['order_status']= array('NEQ', 0); //待付款
          $condition['order_status']= array('NEQ', 5); //已关闭
        /*
          if($shop_id != 0)
          {
              $condition['shop_id']= array('NEQ', 0);
          }
        */
          $order_sum = $order_model->where($condition)->sum('pay_money');
          if(!empty($order_sum))
          {
              return $order_sum;
          }else{
              return 0;
          }
    }
    /**
     * 获取在一段时间之内订单收入明细表
     */
    public function getShopOrderSumList( $start_time, $end_time, $page_index, $page_size){
        $order_model = new OrderModel();
        $condition["create_time"] = [
            [
                ">=",
                $this->getTimeTurnTimeStamp($start_time)
            ],
            [
                "<=",
                $this->getTimeTurnTimeStamp($end_time)
            ]
        ];
        $condition['order_status']= array('NEQ', 0);   //待付款
        $condition['order_status']= array('NEQ', 5);   //已关闭
        /*
        if($shop_id != 0)
        {
            $condition['shop_id']= array('NEQ', 0);
        }
        */
        $list = $order_model->pageQuery($page_index, $page_size, $condition, 'create_time desc', '*');
        return $list;
        
    }
    /**
     * 获取店铺在一段时间之内退款统计-ok
     */
    public function getShopOrderSumRefund( $start_time, $end_time)
    {
        $order_model = new OrderModel();
        $condition["create_time"] = [
            [
                ">=",
                $this->getTimeTurnTimeStamp($start_time)
            ],
            [
                "<=",
                $this->getTimeTurnTimeStamp($end_time)
            ]
        ];
        $condition['order_status']= array('not in', '0,5');
        /*
        if($shop_id != 0)
        {
            $condition['shop_id']= array('NEQ', 0);
        }
        */
        $order_sum = $order_model->where($condition)->sum('refund_money');
        return $order_sum;
        
    }
    /**
     * 获取订单在一段时间之内退款列表-ok
     */
    public function getShopOrderRefundList($start_time, $end_time, $page_index, $page_size)
    {
        $order_model = new OrderModel();
        $condition["create_time"] = [
            [
                ">=",
                $this->getTimeTurnTimeStamp($start_time)
            ],
            [
                "<=",
                $this->getTimeTurnTimeStamp($end_time)
            ]
        ];
        $condition['order_status']= array('NEQ', 0);
        $condition['order_status']= array('NEQ', 5);
        $condition['refund_money'] = array('GT', 0);
        /*
        if($shop_id != 0)
        {
            $condition['shop_id']= array('NEQ', 0);
        }
        */
         $list = $order_model->pageQuery($page_index, $page_size, $condition, 'create_time desc', '*');
        return $list;
    }

    /**
     * ok-2ok
     * 查询一段时间下单量-ok
     */
    public function getShopSaleSum($condition){
        $order_model = new OrderModel();
        $order_sum = $order_model->where($condition)->sum('pay_money');
        if(!empty($order_sum))
        {
            return $order_sum;
        }else{
            return 0;
        }
    }
    /**
     * ok-2ok
     * 查询一点时间下单用户-ok
     */
    public function getShopSaleUserSum($condition){
        
        $order_model = new OrderModel();
        $order_sum = $order_model->distinct(true)->field('buyer_id')->where($condition)->select();
        if(!empty($order_sum))
        {
            return count($order_sum);
        }else{
            return 0;
        }
    }
    /**
     * ok-2ok
     * 查询一段时间下单量-ok
     */
    public function getShopSaleNumSum($condition){
        $order_model = new OrderModel();
        $order_sum = $order_model->where($condition)->count("id");
        if(!empty($order_sum))
        {
            return $order_sum;
        }else{
            return 0;
        }
    }
    /**
     * ok-2ok
     * 查询一段时间内下单商品数-ok
     */
    public function getShopSaleGoodsNumSum($condition){
        $order_model = new OrderModel();
        $order_list = $order_model->where($condition)->select();
        $order_string = "";
        $goods_num = 0;
        foreach($order_list as $k=>$v){
            $order_id =  $v["id"];
            $order_string = $order_string.",".$order_id;
        }
        
        if($order_string != ''){
            $order_string = substr($order_string,1);
            $order_goods_model = new OrderGoodsModel();
            $goods_num = $order_goods_model->where(" order_id in ({$order_string})")->sum("num");
        }
        if(!empty($goods_num))
        {
            return $goods_num;
        }else{
            return 0;
        }
    }
    
}