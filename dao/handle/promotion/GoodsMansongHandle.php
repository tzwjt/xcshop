<?php
/**
 * GoodsMansong.php
 * 商品满减送活动操作-ok
 * @date : 2017.08.17
 * @version : v1.0
 */
namespace dao\handle\promotion;
use dao\model\PromotionMansong as PromotionMansongModel;
use dao\model\PromotionMansongGoods as PromotionMansongGoodsModel;
use dao\handle\BaseHandle;
use dao\handle\GoodsHandle;
use dao\model\PromotionMansongRule as PromotionMansongRuleModel;
use dao\model\Coupon as CouponModel;
use dao\model\PromotionGift as PromotionGiftModel;


class GoodsMansongHandle extends BaseHandle{
    function __construct(){
        parent::__construct();
    }

    /**
     * 查询商品在某一时间段是否有满送活动-okkk
     */
    public function getGoodsIsMansong($goods_id, $start_time, $end_time)
    {
        $mansong_goods = new PromotionMansongGoodsModel();
        $mansong_model = new PromotionMansongModel();
        $condition_1 = array(
            'start_time'=> array('ELT', $end_time),
            'end_time'  => array('EGT', $end_time),
            'status'     => array('NEQ', 3),
            'goods_id'  => $goods_id
        );
        $condition_1_1 = array(
            'start_time'=> array('ELT', $end_time),
            'end_time'  => array('EGT', $end_time),
            'status'     => array('NEQ', 3),
            'range_type' => 1
        );
        $condition_2 = array(
            'start_time'=> array('ELT', $start_time),
            'end_time'  => array('EGT', $start_time),
            'status'     => array('NEQ', 3),
            'goods_id'  => $goods_id
        );
        $condition_2_1 = array(
            'start_time'=> array('ELT', $start_time),
            'end_time'  => array('EGT', $start_time),
            'status'     => array('NEQ', 3),
            'range_type' => 1
        );
        $condition_3 = array(
            'start_time'=> array('EGT', $start_time),
            'end_time'  => array('ELT', $end_time),
            'status'     => array('NEQ', 3),
            'goods_id'  => $goods_id
        );
        $condition_3_1 = array(
            'start_time'=> array('ELT', $start_time),
            'end_time'  => array('EGT', $end_time),
            'status'     => array('NEQ', 3),
            'range_type' => 1
        );

        $count_1 = $mansong_goods->where($condition_1)->count();
        $count_1_1 = $mansong_model->where($condition_1_1)->count();
        $count_2 = $mansong_goods->where($condition_2)->count();
        $count_2_1 = $mansong_model->where($condition_2_1)->count();
        $count_3 = $mansong_goods->where($condition_3)->count();
        $count_3_1 = $mansong_model->where($condition_3_1)->count();
        $count = $count_1 + $count_2 + $count_3+$count_1_1+$count_2_1+$count_3_1;
        return $count;
    }

    /**
     * 获取在一段时间之内是否存在全场满减(全场活动检测同时存在部分商品活动)-okkk
     */
    public function getQuanmansong($start_time, $end_time)
    {
        $mansong_model = new PromotionMansongModel();
        $condition_1_1 = array(
            'start_time'=> array('ELT', $end_time),
            'end_time'  => array('EGT', $end_time),
            'status'     => array('NEQ', 3)
        );
        $condition_2_1 = array(
            'start_time'=> array('ELT', $start_time),
            'end_time'  => array('EGT', $start_time),
            'status'     => array('NEQ', 3)
        );
        $condition_3_1 = array(
            'start_time'=> array('ELT', $start_time),
            'end_time'  => array('EGT', $end_time),
            'status'     => array('NEQ', 3)
        );
        $count_1_1 = $mansong_model->where($condition_1_1)->count();
        $count_2_1 = $mansong_model->where($condition_2_1)->count();
        $count_3_1 = $mansong_model->where($condition_3_1)->count();
        $count = $count_1_1+$count_2_1+$count_3_1;
        return $count;

    }
    /**
     * 获取商品满减送优惠(核心函数)
     * -ok-2ok
     * @param  $goods_sku_list
     */
    public function getGoodsSkuListMansong($user_id,$goods_sku_list)
    {
        
        $discount_info = array();
        $goods_preference = new GoodsPreferenceHandle();
        $goods_sku_list_price = $goods_preference->getGoodsSkuListPrice($user_id, $goods_sku_list);
        
        if(!empty($goods_sku_list))
        {
            $time = date("Y-m-d H:i:s", time());
            //检测店铺是否存在正在进行的全场满减送活动
            $condition = array(
                'status'     => 1,
                'range_type' => 1,
               // 'shop_id'    => $this->instance_id
            );
            $promotion_mansong = new PromotionMansongModel();
            $list_quan = $promotion_mansong -> getConditionQuery($condition, '*', 'create_time desc');
            if(!empty($list_quan[0]))
            {
                //存在全场满减送
                $goods_sku_list_array = explode( ",", $goods_sku_list);
                $rule_list = $this->getMansongRule($list_quan[0]['id']);//得到满减规则
                $discount = array();
                    //获取订单项减现金额
                    foreach ($rule_list as $k_rule=>$rule)
                    {
                        if($rule['price'] <= $goods_sku_list_price){
                            foreach ($goods_sku_list_array as $k_goods_sku => $v_goods_sku)
                            {
                                $sku_data_goods = explode(":", $v_goods_sku);
                                $sku_id_goods = $sku_data_goods[0];
                                $sku_count_goods = $sku_data_goods[1];
                                $goods_preference = new GoodsPreferenceHandle();
                                $goods_sku_price = $goods_preference->getGoodsSkuListPrice($user_id,$v_goods_sku);
                                $goods_sku_promote_price = $rule['discount'] * $goods_sku_price/$goods_sku_list_price;
                                $discount[] = array($rule, $sku_id_goods.":".$goods_sku_promote_price);
                            
                            }
                        }
                    }
                  
                $discount_info[0] = array(
                    'rule' => $list_quan[0],
                    'discount_detail' => $discount
                );
            }else{
                //存在部分商品满减送活动(只可能存在部分商品满减送)
                //1.查询商品列表可能的满减送活动列表
                $mansong_list = $this->getGoodsSkuMansongList($goods_sku_list);
                
                if(!empty($mansong_list))
                {
                    //循环满减送活动
                    foreach($mansong_list as $k => $v)
                    {
                        $discount_info_detail = $this->getMansongGoodsSkuListPromotion($user_id, $v, $goods_sku_list);
                        $discount_info[] = $discount_info_detail;
                    }
                    
                }
                
                
            }
           
        }
        return $discount_info;
    
    }

    /**
     * wjt
     * 获取商品满减送优惠(核心函数)
     * ok-2ok
     * @param  $goods_sku_list
     */
    public function getGoodsListMansong($user_id, $goods_list)
    {

        $discount_info = array();
        $goods_preference = new GoodsPreferenceHandle();
      //  getGoodsListPrice($goods_list)
       // $goods_sku_list_price = $goods_preference->getGoodsSkuListPrice($user_id, $goods_sku_list);
       // getGoodsListPrice($user_id, $goods_list);
        $goods_list_price = $goods_preference->getGoodsListPrice($user_id,$goods_list);

        if(!empty($goods_list))
        {
            $time = date("Y-m-d H:i:s", time());
            //检测店铺是否存在正在进行的全场满减送活动
            $condition = array(
                'status'     => 1,
                'range_type' => 1,
                // 'shop_id'    => $this->instance_id
            );
            $promotion_mansong = new PromotionMansongModel();
            $list_quan = $promotion_mansong ->getConditionQuery($condition, '*', 'create_time desc');
            if(!empty($list_quan[0]))
            {
                //存在全场满减送
                $goods_list_array = explode( ",", $goods_list);
                $rule_list = $this->getMansongRule($list_quan[0]['id']);//得到满减规则
                $discount = array();
                //获取订单项减现金额
                foreach ($rule_list as $k_rule=>$rule)
                {
                    if($rule['price'] <= $goods_list_price){
                        foreach ($goods_list_array as $k_goods => $v_goods)
                        {
                            $data_goods = explode(":", $v_goods);
                            $goods_id = $data_goods[0];
                            $sku_id = $data_goods[1];
                            $goods_count = $data_goods[2];
                            $goods_preference = new GoodsPreferenceHandle();
                        //    $goods_sku_price = $goods_preference->getGoodsSkuListPrice($user_id,$v_goods_sku);
                            $goods_price = $goods_preference->getGoodsListPrice($user_id, $v_goods);
                            //getGoodsListPrice($goods_list)
                            $goods_promote_price = $rule['discount'] * $goods_price/$goods_list_price;
                            $discount[] = array($rule, $goods_id.":".$sku_id.":".$goods_promote_price);

                        }
                    }
                }

                $discount_info[0] = array(
                    'rule' => $list_quan[0],
                    'discount_detail' => $discount
                );
            }else{
                //存在部分商品满减送活动(只可能存在部分商品满减送)
                //1.查询商品列表可能的满减送活动列表

               // $mansong_list = $this->getGoodsSkuMansongList($goods_sku_list);
                $mansong_list = $this->getGoodsMansongList($goods_list);

                if(!empty($mansong_list))
                {
                    //循环满减送活动
                    foreach($mansong_list as $k => $v)
                    {
                       // $discount_info_detail = $this->getMansongGoodsSkuListPromotion($user_id, $v, $goods_sku_list);
                        $discount_info_detail =$this->getMansongGoodsListPromotion($user_id, $v, $goods_list);
                   //     getMansongGoodsListPromotion($mansong_obj, $goods_list)
                        $discount_info[] = $discount_info_detail;
                    }

                }


            }

        }
        return $discount_info;

    }


    /**
     * 获取免邮商品列表(由于满减送产生)
     * @param  $goods_sku_list
     * ok-2ok
     */
    public function getFreeExpressGoodsSkuList($user_id, $goods_sku_list)
    {
        $goods_sku_array = array();
        $mansong_array = $this->getGoodsSkuListMansong($user_id,$goods_sku_list);
        if(!empty($mansong_array))
        {
            foreach ($mansong_array as $k_mansong => $v_mansong)
            {
               
                    //存在免邮活动
                    foreach($v_mansong['discount_detail'] as $k_rule => $v_rule)
                    {
                        $mansong_rule = $v_rule[0];
                        if($mansong_rule['free_shipping'] == 1)
                        {
                            $rule = $v_rule[1];
                            $discount_money_detail = explode(':',$rule);
                            $goods_sku_array[] = $discount_money_detail[0];
                        }
                      
                    
                    }
               
            }
        }
        return $goods_sku_array;
    }

    /**
     * 获取免邮商品列表(由于满减送产生)
     * @param  $goods_sku_list
     * ok-2ok
     */
    public function getFreeExpressGoodsList($user_id, $goods_list)
    {
        $goods_array = array();
        $mansong_array = $this->getGoodsListMansong($user_id,$goods_list);

           // $this->getGoodsSkuListMansong($user_id,$goods_sku_list);
        if(!empty($mansong_array))
        {
            foreach ($mansong_array as $k_mansong => $v_mansong)
            {

                //存在免邮活动
                foreach($v_mansong['discount_detail'] as $k_rule => $v_rule)
                {
                    $mansong_rule = $v_rule[0];
                    if($mansong_rule['free_shipping'] == 1)
                    {
                        $rule = $v_rule[1];
                        $discount_money_detail = explode(':',$rule);
                        $goods_array[] = $discount_money_detail[0];
                    }


                }

            }
        }
        return $goods_array;
    }

    /**
     * 获取满减送金额
     * ok
     * @param  $goods_sku_list
     */
    public function getGoodsMansongMoney($user_id, $goods_sku_list)
    {
        $mansong_array = $this->getGoodsSkuListMansong($user_id, $goods_sku_list);
        $promotion_money = 0;
        if(!empty($mansong_array))
        {
            foreach ($mansong_array as $k_mansong => $v_mansong)
            {
                foreach($v_mansong['discount_detail'] as $k_rule => $v_rule)
                {
                    $rule = $v_rule[1];
                    $discount_money_detail = explode(':',$rule);
                    $promotion_money += round($discount_money_detail[1],2);
              
                }
            }
        }
        return $promotion_money;
    }

    /**
     * 根据goods_list获取满减送金额
     * ok-2ok
     * @param  $goods_sku_list
     */
    public function getGoodsMansongMoneyByGoodsList($user_id, $goods_list)
    {
        $mansong_array = $this->getGoodsListMansong($user_id, $goods_list);
        $promotion_money = 0;
        if(!empty($mansong_array))
        {
            foreach ($mansong_array as $k_mansong => $v_mansong)
            {
                foreach($v_mansong['discount_detail'] as $k_rule => $v_rule)
                {
                    $rule = $v_rule[1];
                    $discount_money_detail = explode(':',$rule);
                    $promotion_money += round($discount_money_detail[2],2);

                }
            }
        }
        return $promotion_money;
    }


    /**
     * 获取当前商品满减送活动(只查询部分商品的满减送活动)
     * ok-2ok
     * @param  $goods_id
     */
    public function getGoodsMansongPromotion($goods_id)
    {
        $time = date("Y-m-d H:i:s", time());

            //查询当前部分商品活动
            $condition = array(
                'status'     => 1,
                'range_type' => 0,
               // 'shop_id'    => $this->instance_id
    
            );
            $promotion_mansong = new PromotionMansongModel();
            $list = $promotion_mansong ->getConditionQuery($condition, '*', 'create_time desc');
            foreach($list as $k => $v)
            {
                //检测当前满减送或送是否与此商品有关
                $promotion_mansong_goods = new PromotionMansongGoodsModel();
                $info = $promotion_mansong_goods->getInfo(['mansong_id' => $v['id'],'goods_id' => $goods_id], '*');
                if(!empty($info))
                {
                    return $v;
                }
    
            }
            return '';
    }

    /**
     * 获取商品sku的满减送活动列表
     * ok-2ok
     * @param  $goods_sku_list
     */
    public function getGoodsSkuMansongList($goods_sku_list)
    {
        $promotion_array = array();
        if(!empty($goods_sku_list))
        {
            $goods_sku_list_array = explode( ",", $goods_sku_list);
            foreach ($goods_sku_list_array as $k => $v)
            {
                $sku_data = explode(":", $v);
                $sku_id = $sku_data[0];
                $sku_count = $sku_data[1];
                //查询商品的goodsid
                $goods_handle = new GoodsHandle();
                $goods_id = $goods_handle->getGoodsId($sku_id);
                $promotion = $this->getGoodsMansongPromotion($goods_id);
                if(!empty($promotion))
                {
                    $promotion_array[] = $promotion;
                }
            }
                
        }
        
       /*   if(!empty($promotion_array))
        {
            foreach ($promotion_array as $k => $v)
            {
                
            }
        }  */
        $array = array_unique($promotion_array);
        return $array;
        
    }

    /**
     * 获取商品的满减送活动列表
     * ok-2ok
     * @param  $goods_sku_list
     */
    public function getGoodsMansongList($goods_list)
    {
        $promotion_array = array();
        if(!empty($goods_list))
        {
            $goods_list_array = explode( ",", $goods_list);
            foreach ($goods_list_array as $k => $v)
            {
                $goods_data = explode(":", $v);
                $goods_id = $goods_data[0];
                $sku_id = $goods_data[1];
                $goods_count = $goods_data[2];
                //查询商品的goodsid
              //  $goods_handle = new GoodsHandle();
               // $goods_id = $goods_handle->getGoodsId($sku_id);
                $promotion = $this->getGoodsMansongPromotion($goods_id);
                if(!empty($promotion))
                {
                    $promotion_array[] = $promotion;
                }
            }

        }

        /*   if(!empty($promotion_array))
         {
             foreach ($promotion_array as $k => $v)
             {

             }
         }  */
        $array = array_unique($promotion_array);
        return $array;

    }

    /**
     * 获取满减送规则
     * ok-2ok
     * @param  $mansong_id
     */
    public function getMansongRule($mansong_id)
    {
        $mansong_rule = new PromotionMansongRuleModel();
        $rule_list = $mansong_rule->getConditionQuery(['mansong_id' => $mansong_id], '*','price desc');
        return $rule_list;
    }

    /**
     * 查询满减送商品列表
     * ok-2ok
     * @param  $mansong_id
     */
    public function getMansongGoods($mansong_id)
    {
        $mansong_goods = new PromotionMansongGoodsModel();
        $list = $mansong_goods->getConditionQuery(['mansong_id' => $mansong_id], '*', '');
        return $list;
    }

    /**
     * 查询商品的满减送详情(应用商品详情)
     * ok-2ok
     * @param  $goods_id
     */
    public function getGoodsMansongDetail($goods_id)
    {
        //查询全场满减送活动
        //检测店铺是否存在正在进行的全场满减送活动
        $condition = array(
            'status'     => 1,
            'range_type' => 1,
           // 'shop_id'    => $this->instance_id
        );
        $promotion_mansong = new PromotionMansongModel();
        $list_quan = $promotion_mansong -> getConditionQuery($condition, '*', 'create_time desc');
        if(!empty($list_quan[0]))
        {
            $mansong_promotion = $list_quan[0];
        }
        //1. 查询商品满减送活动
        if(empty($mansong_promotion))
        {
            $mansong_promotion = $this->getGoodsMansongPromotion($goods_id);
        }
      
        if(!empty($mansong_promotion))
        {
            $rule = $this->getMansongRule($mansong_promotion['id']);
            $mansong_promotion['rule'] = $rule;
        
        }
        return $mansong_promotion;
    }

    /**
     * 查询商品满减送活动名称-ok
     * @param  $goods_id
     */
    public function getGoodsMansongName($goods_id)
    {
        //查询满减送活动详情
        $mansong_detail = $this->getGoodsMansongDetail($goods_id);
        $mansong_name = '';
        if(!empty($mansong_detail))
        {
            foreach ($mansong_detail['rule'] as $k => $v)
            {
                $mansong_name .= '满'.$v['price'].'元 减'.$v['discount'].'元  ';
                if($v['free_shipping'] == 1)
                {
                    $mansong_name.='免邮'.' ';
                }
                if($v['give_point'] != 0)
                {
                    $mansong_name.='赠送'.$v['give_point'].'积分'.' ';
                }
                if($v['give_coupon_id'] != 0)
                {
                    $coupon = new CouponModel();
                    $coupon_name = $coupon->getInfo(['coupon_type_id' => $v['give_coupon_id']], 'money');
                    $mansong_name.='赠送'.$coupon_name['money'].'元优惠券'.' ';
                }
                if($v['gift_id'] != 0)
                {
                    $gift = new PromotionGiftModel();
                    $gift_name = $gift->getInfo(['id' => $v['gift_id']], 'gift_name');
                    $mansong_name.='赠送'.$gift_name['gift_name'];
                }
                $mansong_name.='; ';
            }
            if (!empty($mansong_name)) {
                $mansong_name = trim($mansong_name);
                if (!empty($mansong_name)) {
                    $mansong_name = substr($mansong_name, 0, strlen($mansong_name) - 1);
                }
            }
        }
        return $mansong_name;
    }

    /**-2ok
     * 查询对应满减送活动的商品列表的优惠情况-ok
     * @param  $mansong_obj(只针对部分商品满减)
     * @param  $goods_sku_list
     */
    public function getMansongGoodsSkuListPromotion($user_id, $mansong_obj, $goods_sku_list)
    {
        $new_sku_list = '';
        $new_sku_list_array = array();
        $goods_sku_list_array = $this->getGoodsSkuListGoods($goods_sku_list);
        //查询组装新的sku列表
        $mansong_goods = $this->getMansongGoods($mansong_obj['id']);
        foreach ($goods_sku_list_array as $k => $v)
        {
            foreach ($mansong_goods as $k_mansong => $v_mansong)
            {
                if($v[2] == $v_mansong['goods_id'])
                {
                    $new_sku_list = $new_sku_list.$v[0].':'.$v[1].',';
                    $new_sku_list_array[] = $v;
                }
            }
        }
        if(!empty($new_sku_list))
        {
            $new_sku_list = substr($new_sku_list, 0, strlen($new_sku_list)-1);
            //获取总价
            $goods_preference = new GoodsPreferenceHandle();
            $new_sku_list_price = $goods_preference->getGoodsSkuListPrice($user_id,$new_sku_list);
            $rule_list = $this->getMansongRule($mansong_obj['id']);//得到满减规则
            $discount = array();
            //获取订单项减现金额
            foreach ($rule_list as $k_rule=>$rule)
            {
                if($rule['price'] <= $new_sku_list_price){
                    foreach ($new_sku_list_array as $k_goods_sku => $v_goods_sku)
                    {
                       
                        $sku_id_goods = $v_goods_sku[0];
                        $sku_count_goods = $v_goods_sku[1];
                        $goods_preference = new GoodsPreferenceHandle();
                        $goods_sku_price = $goods_preference->getGoodsSkuListPrice($user_id,$sku_id_goods.':'.$sku_count_goods);
                        $goods_sku_promote_price = $rule['discount'] * $goods_sku_price/$new_sku_list_price;
                        $discount[] = array($rule, $sku_id_goods.":".$goods_sku_promote_price);
            
                    }
                    break;
                }
            }
            
            return array(
                'rule' => $mansong_obj,
                'discount_detail' => $discount
            );
        }
        else 
            return array();
        
        
    }


    /**2ok
     * 查询对应满减送活动的商品列表的优惠情况-ok
     * @param  $mansong_obj(只针对部分商品满减)
     * @param  $goods_sku_list
     */
    public function getMansongGoodsListPromotion($user_id, $mansong_obj, $goods_list)
    {
        $new_goods_list = '';
        $new_goods_list_array = array();
       // getGoodsListGoods($goods_list)
        //$goods_list_array = $this->getGoodsSkuListGoods($goods_sku_list);
        $goods_list_array = $this->getGoodsListGoods($goods_list);
        //查询组装新的sku列表
        $mansong_goods = $this->getMansongGoods($mansong_obj['id']);
        foreach ($goods_list_array as $k => $v)
        {
            foreach ($mansong_goods as $k_mansong => $v_mansong)
            {
                if($v[0] == $v_mansong['goods_id'])
                {
                    $new_goods_list = $new_goods_list.$v[0].':'.$v[1].':'.$v[2].',';
                    $new_goods_list_array[] = $v;
                }
            }
        }
        if(!empty($new_goods_list))
        {
            $new_goods_list = substr($new_goods_list, 0, strlen($new_goods_list)-1);
            //获取总价
            $goods_preference = new GoodsPreferenceHandle();

            //getGoodsListPrice($goods_list)
            //$new_sku_list_price = $goods_preference->getGoodsSkuListPrice($user_id, $new_sku_list);
             $new_goods_list_price = $goods_preference->getGoodsListPrice($user_id, $new_goods_list);
            $rule_list = $this->getMansongRule($mansong_obj['id']);//得到满减规则
            $discount = array();
            //获取订单项减现金额
            foreach ($rule_list as $k_rule=>$rule)
            {
                if($rule['price'] <= $new_goods_list_price){
                    foreach ($new_goods_list_array as $k_goods => $v_goods)
                    {

                      //  $sku_id_goods = $v_goods_sku[0];
                       // $sku_count_goods = $v_goods_sku[1];
                        $goods_id = $v_goods[0];
                        $sku_id = $v_goods[1];
                        $goods_count = $v_goods[2];
                        $goods_preference = new GoodsPreferenceHandle();
                        //getGoodsListPrice($goods_list)

                        //$goods_sku_price = $goods_preference->getGoodsSkuListPrice($usesr_id, $sku_id_goods.':'.$sku_count_goods);
                        $goods_price = $goods_preference->getGoodsListPrice($user_id, $goods_id.':'.$sku_id.':'.$goods_count);
                        $goods_promote_price = $rule['discount'] * $goods_price/$new_goods_list_price;
                       // $discount[] = array($rule, $sku_id_goods.":".$goods_sku_promote_price);
                        $discount[] = array($rule, $goods_id . ':' . $sku_id.":".$goods_promote_price);

                    }
                    break;
                }
            }

            return array(
                'rule' => $mansong_obj,
                'discount_detail' => $discount
            );
        }
        else
            return array();


    }

    /**
     * 查询商品水库列表的商品列表情况(返回数组)
     * ok-2ok
     * @param  $goods_sku_list
     */
    public function getGoodsSkuListGoods($goods_sku_list)
    {
        $list = array();
        $goods_sku_list_array = explode( ",", $goods_sku_list);
        foreach ($goods_sku_list_array as $k => $v)
        {
            $sku_data = explode(":", $v);
            $sku_id = $sku_data[0];
            $sku_count = $sku_data[1];
            //查询商品的goodsid
            $goods = new GoodsHandle();
            $goods_id = $goods->getGoodsId($sku_id);
            $sku_data[2] = $goods_id;
            $list[] = $sku_data;
        }
        return $list;
    }

    /**
     * 查询商品水库列表的商品列表情况(返回数组)
     * ok-2ok
     * @param  $goods_sku_list
     */
    public function getGoodsListGoods($goods_list)
    {
        $list = array();
        $goods_list_array = explode( ",", $goods_list);
        foreach ($goods_list_array as $k => $v)
        {
            $goods_data = explode(":", $v);
            $goods_id = $goods_data[0];
            $sku_id = $goods_data[1];
            $goods_count = $goods_data[2];
            //查询商品的goodsid
          //  $goods = new GoodsHandle();
         //   $goods_id = $goods->getGoodsId($sku_id);
          //  $sku_data[2] = $goods_id;
            $list[] = $goods_data;
        }
        return $list;
    }
}