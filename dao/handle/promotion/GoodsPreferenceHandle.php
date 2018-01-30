<?php
/**
 * GoodsPreferenceHandle.php--ok
 * 商品优惠价格操作类(运费，商品优惠)(没有考虑订单优惠活动例如满减送)
 * @date : 2017.08.17
 * @version : v1.0
 */
namespace dao\handle\promotion;

use dao\handle\ConfigHandle;
use dao\model\Goods as GoodsModel;
use dao\model\GoodsSku as GoodsSkuModel;
use dao\model\Coupon as CouponModel;
use dao\handle\BaseHandle as BaseHandle;
use dao\handle\GoodsHandle as GoodsHandle;
use dao\handle\member\MemberCouponHandle;
use dao\model\CouponGoods as CouponGoodsModel;
use dao\model\CouponType as CouponTypeModel;
use dao\model\PromotionDiscount as PromotionDiscountModel;
use dao\model\PointConfig as PointConfigModel;
use dao\handle\PromotionHandle as PromotionHandle;
use dao\handle\MemberHandle as MemberHandle;
use dao\model\MemberUser as MemberUserModel;
use dao\model\MemberLevel as MemberLevelModel;
//use data\service\Config;
use think\Log;
/**
 * 商品优惠价格操作类(运费，商品优惠)(没有考虑订单优惠活动例如满减送)
 *
 */
class GoodsPreferenceHandle extends BaseHandle{
    function __construct(){
        parent::__construct();
    }
    /*****************************************************************************************订单商品管理开始***************************************************/
    /**
     * 获取商品sku列表价格
     * @param  $goods_sku_list skuid:1,skuid:2,skuid:3·
     * ok-2ok
     */
    public function getGoodsSkuListPrice($user_id, $goods_sku_list)
    {
        $price = 0;
        if(!empty($goods_sku_list))
        {

            $goods_sku_list_array = explode( ",", $goods_sku_list);
            foreach ($goods_sku_list_array as $k => $v)
            {
                $sku_data = explode(":", $v);
                $sku_id = $sku_data[0];
                $sku_count = $sku_data[1];
                $sku_price = $this->getGoodsSkuPrice($user_id, $sku_id);
                $price = $price + $sku_price * $sku_count;
            }
            return $price;
    
        }else{
            return $price;
        }
    
    }

    //2okkk
    public function getGoodsListPrice($user_id, $goods_list)
    {
        $price = 0;
        if(!empty($goods_list))
        {
            Log::write("getGoodsListPrice:".$goods_list);

            $goods_list_array = explode( ",", $goods_list);
            foreach ($goods_list_array as $k => $v) {
                $goods_data = explode(":", $v);
                $goods_id = $goods_data[0];
                $sku_id = $goods_data[1];
                $goods_count = $goods_data[2];
                $goods_price = 0;
                if ($sku_id > 0) {
                    $goods_price = $this->getGoodsSkuPrice($user_id, $sku_id);
                 } else {
                    $goods_price = $this->getGoodsPrice($user_id, $goods_id);
                }
                $price = $price + $goods_price * $goods_count;
            }
            return $price;

        }else{
            return $price;
        }

    }
    /**
     * 获取商品sku列表购买后可得积分-ok
     * @param  $goods_sku_list
     */
    public function getGoodsSkuListGivePoint($goods_sku_list)
    {
        $point = 0;
        if(!empty($goods_sku_list))
        {
            $goods_sku_list_array = explode( ",", $goods_sku_list);
            foreach ($goods_sku_list_array as $k => $v)
            {
                $sku_data = explode(":", $v);
                $sku_id = $sku_data[0];
                $sku_count = $sku_data[1];
                $goods = new GoodsHandle();
                $goods_id = $goods->getGoodsId($sku_id);
                $give_point = $goods->getGoodsGivePoint($goods_id);
                $point += $give_point*$sku_count;
                
            }
            return $point;
        
        }else{
            return $point;
        }
    }

    /**
     * ok-2ok
     * 获取商品列表购买后可得积分-ok
     * @param  $goods_sku_list
     */
    public function getGoodsListGivePoint($goods_list)
    {
        $point = 0;
        if(!empty($goods_list))
        {
            $goods_list_array = explode( ",", $goods_list);
            foreach ($goods_list_array as $k => $v)
            {
                $goods_data = explode(":", $v);
                $goods_id = $goods_data[0];
                $sku_id = $goods_data[1];
                $goods_count = $goods_data[2];
                $goods = new GoodsHandle();
             //   $goods_id = $goods->getGoodsId($sku_id);
                $give_point = $goods->getGoodsGivePoint($goods_id);
                $point += $give_point * $goods_count;

            }
            return $point;

        }else{
            return $point;
        }
    }
    
    /**
     * 获取商品sku列表使用优惠券详情--ok
     * @param  $coupon_id
     * @param  $coupon_money
     * @param  $goods_sku_list
     */
    public function getGoodsCouponPromoteDetail($user_id, $coupon_id, $coupon_money, $goods_sku_list)
    {
        $promote_coupon_detail = array();
        //获取商品总价
        $all_goods_money = $this->getGoodsSkuListPrice($user_id, $goods_sku_list);
        //获取优惠券详情
        $coupon = new CouponModel();
        $coupon_type_id = $coupon->getInfo(['id' => $coupon_id],'coupon_type_id');
        $promote = new PromotionHandle();
        $coupon_type_detail = $promote->getCouponTypeDetail($coupon_type_id['coupon_type_id']);
        //拆分sku
        $goods_sku_list_array = explode( ",", $goods_sku_list);
        
        if($coupon_type_detail['range_type'] == 1)
        {
            
            //优惠券全场使用
            foreach ($goods_sku_list_array as $k => $v)
            {
                //获取sku总价
                $sku_data = explode(':', $v);
                $goods_money = $this->getGoodsSkuListPrice($user_id, $v);
                $sku_id = $sku_data[0];
                $promote_item = array(
                    'sku_id' => $sku_id,
                    'money'  => round($coupon_money*$goods_money/$all_goods_money,2)
                );
                $promote_coupon_detail[] = $promote_item;
            
            }
        }else{
            //优惠券部分商品使用
            $coupon_goods_money = 0;
            $goods_list = $coupon_type_detail['goods_list'];
            
            $list = array();//整理后的商品数组
            foreach($goods_list as $k_goods => $v_goods)
            {
              
                foreach ($goods_sku_list_array as $k => $v)
                {
                    
                    $sku_data = explode(':', $v);
                    $sku_id = $sku_data[0];
                    $goods = new GoodsHandle();
                    $goods_id = $goods->getGoodsId($sku_id);
                    if($goods_id == $v_goods['goods_id'])
                    {
                        $goods_money = $this->getGoodsSkuListPrice($user_id, $v);
                        $coupon_goods_money+=$goods_money;
                        $list[] = $v;
                    }
                    
                }
              
            }
            if($coupon_goods_money == 0)
            {
                $coupon_goods_money = $all_goods_money;
            }
            foreach ($list as $k => $v)
            {
                $goods_money = $this->getGoodsSkuListPrice($user_id, $v);
                $sku_data = explode(':', $v);
                $sku_id = $sku_data[0];
                $promote_item = array(
                    'sku_id' => $sku_id,
                    'money'  => round($coupon_money*$goods_money/$coupon_goods_money,2)
                );
                $promote_coupon_detail[] = $promote_item;
                
            }
        }
        return $promote_coupon_detail;
        
    }

    /**
     * 获取商品sku列表使用优惠券详情--ok
     * @param  $coupon_id
     * @param  $coupon_money
     * @param  $goods_sku_list
     */
    public function getGoodsCouponPromoteDetailByGoodsList($user_id, $coupon_id, $coupon_money, $goods_list)
    {
        $promote_coupon_detail = array();
        //获取商品总价
      //  getGoodsListPrice($goods_list)
      //  $all_goods_money = $this->getGoodsSkuListPrice($goods_sku_list);
          $all_goods_money = $this->getGoodsListPrice($user_id, $goods_list);
        //获取优惠券详情
        $coupon = new CouponModel();
        $coupon_type_id = $coupon->getInfo(['id' => $coupon_id],'coupon_type_id');
        $promote = new PromotionHandle();
        $coupon_type_detail = $promote->getCouponTypeDetail($coupon_type_id['coupon_type_id']);
        //拆分sku
        $goods_list_array = explode( ",", $goods_list);

        if($coupon_type_detail['range_type'] == 1)
        {

            //优惠券全场使用
            foreach ($goods_list_array as $k => $v)
            {
                //获取sku总价
                $goods_data = explode(':', $v);
                $goods_money = $this->getGoodsListPrice($user_id, $v);
                $goods_id = $goods_data[0];
                $sku_id = $goods_data[1];
                $promote_item = array(
                    'goods_id' => $goods_id,
                    'sku_id' => $sku_id,
                    'money'  => round($coupon_money*$goods_money/$all_goods_money,2)
                );
                $promote_coupon_detail[] = $promote_item;

            }
        }else{
            //优惠券部分商品使用
            $coupon_goods_money = 0;
            $goods_list = $coupon_type_detail['goods_list'];

            $list = array();//整理后的商品数组
            foreach($goods_list as $k_goods => $v_goods)
            {

                foreach ($goods_list_array as $k => $v)
                {

                    $goods_data = explode(':', $v);
                    $goods_id = $goods_data[0];
                    $sku_id = $goods_data[1];
                   // $goods = new GoodsHandle();
                  //  $goods_id = $goods->getGoodsId($sku_id);
                    if($goods_id == $v_goods['goods_id'])
                    {
                        $goods_money = $this->getGoodsListPrice($user_id, $v);
                        $coupon_goods_money+=$goods_money;
                        $list[] = $v;
                    }

                }

            }
            if($coupon_goods_money == 0)
            {
                $coupon_goods_money = $all_goods_money;
            }
            foreach ($list as $k => $v)
            {
                $goods_money = $this->getGoodsListPrice($user_id, $v);
                $goods_data = explode(':', $v);
                $goods_id = $goods_data[0];
                $sku_id = $goods_data[1];
                $promote_item = array(
                    'goods_id' => $goods_id,
                    'sku_id' => $sku_id,
                    'money'  => round($coupon_money*$goods_money/$coupon_goods_money,2)
                );
                $promote_coupon_detail[] = $promote_item;

            }
        }
        return $promote_coupon_detail;

    }

    /**
     * 获取商品对应的价格-ok-2ok
     * @param  $sku_id
     */
    public function getGoodsSkuPrice($user_id, $sku_id)
    {
        $goods_sku = new GoodsSkuModel();
        $goods_sku_info = $goods_sku->getInfo(['id' => $sku_id], 'goods_id,price,promotion_price');
        if(!empty($user_id))
        {
            $member_price = $this->getGoodsSkuMemberPrice($sku_id, $user_id);
            if($member_price < $goods_sku_info['promotion_price'])
            {
                return $member_price;
            }else{
                return $goods_sku_info['promotion_price'];
            }
        }else{
            return $goods_sku_info['promotion_price'];
        }
     
    }

    /**
     * 获取商品对应的价格-ok-2ok
     * @param  $sku_id
     */
    public function getGoodsPrice($user_id, $goods_id)
    {
        $goods = new GoodsModel();
        $goods_info = $goods->getInfo(['id' => $goods_id], 'id,price,promotion_price');
        if(!empty($user_id))
        {
            $member_price = $this->getGoodsMemberPrice($goods_id, $user_id);
            if($member_price < $goods_info['promotion_price'])
            {
                return $member_price;
            }else{
                return $goods_info['promotion_price'];
            }
        }else{
            return $goods_info['promotion_price'];
        }

    }
    /**
     * 获取商品sku的积分兑换值--ok
     * @param  $sku_id
     * @return  <number, >
     */
    public function getGoodsSkuExchangePoint($sku_id)
    {
        $goods_sku = new GoodsSkuModel();
        $goods_sku_info = $goods_sku->getInfo(['id' => $sku_id], 'goods_id');
        $goods = new GoodsHandle();
        $point = $goods->getGoodsPointExchange($goods_sku_info['goods_id']);
        return $point;
    }

    /**
     * 获取商品的积分兑换值--ok
     * @param  $goods_id
     * @return  <number, >
     */
    public function getGoodsExchangePoint($goods_id)
    {
      //  $goods = new GoodsModel();
      //  $goods_sku_info = $goods->getInfo(['id' => $goods_id], 'goods_id');
        $goods = new GoodsHandle();
        $point = $goods->getGoodsPointExchange($goods_id);
        return $point;
    }

    /**
     * 获取商品列表总积分-ok
     * @param  $goods_sku_list
     * @return  <\data\service\promotion\, number, >
     */
    public function getGoodsListExchangePoint($goods_sku_list)
    {
        $goods_sku_list_array = explode( ",", $goods_sku_list);
        $point = 0;
        foreach ($goods_sku_list_array as $k => $v)
        {
            //获取sku总价
            $sku_data = explode(':', $v);
            $sku_id = $sku_data[0];
            $sku_point = $this->getGoodsSkuExchangePoint($sku_id);
            $point += $sku_point*$sku_data[1];
          
        }
        return $point;
    }

    /**
     * 获取商品列表总积分-ok
     * @param  $goods_sku_list
     * @return  <\data\service\promotion\, number, >
     */
    public function getGoodsListExchangePointByGoodsId($goods_list)
    {
        $goods_list_array = explode( ",", $goods_list);
        $point = 0;
        foreach ($goods_list_array as $k => $v)
        {
            //获取sku总价
            $goods_data = explode(':', $v);
            $goods_id = $goods_data[0];
            $sku_id = $goods_data[1];
            $num = $goods_data[2];
            $goods_point = 0;
            if ($sku_id > 0) {
                $goods_point = $this->getGoodsSkuExchangePoint($sku_id);
            } else {
                $goods_point = $this->getGoodsExchangePoint($goods_id);
            }
            $point += $goods_point * $num; //$sku_data[1];

        }
        return $point;
    }
    /**
     * 获取积分对应金额
     * @param  $point
     * @param  $shop_id
     */
  /*   public function getPointMoney($point, $shop_id)
    {
        $point_config = new NsPointConfigModel();
        $config = $point_config->getInfo(['shop_id'=> $shop_id], 'is_open, convert_rate');
        if(!empty($config))
        {
            $money = $point*$config['convert_rate'];
        }else{
            $money = 0;
        }
        return $money;
    } */

    /**
     * 获取商品当前单品优惠活动--ok
     * @param  $goods_id
     */
    public function getGoodsPromote($goods_id)
    {
        $goods = new GoodsModel();
        $promote_info = $goods->getInfo(['id'=>$goods_id], 'promotion_type,promote_id');
        if($promote_info['promotion_type'] == 0)
        {
            //无促销活动
            return '';
        }else if($promote_info['promotion_type'] == 1){
            //团购(注意查询活动时间)
            return '团购';
        }else if($promote_info['promotion_type'] == 2)
        {
            //限时折扣(注意查询活动时间)
            return  '限时折扣';
        }
    }
    /**
     * 获取商品sku列表的商品列表形式--ok
     * @param  $goods_sku_list array(array(goods_id,skuid,num))
     */
    public function getGoodsSkuListGoods($goods_sku_list){
        $array = array();
        if(!empty($goods_sku_list)){
            $goods_sku_list_array = explode(',', $goods_sku_list);
            foreach ($goods_sku_list_array as $k => $v)
            {
                $sku_item = explode(":", $v);
                //获取商品goods_id
                $goods = new GoodsHandle();
                $goods_id = $goods->getGoodsId($sku_item[0]);
                $array[] = array($goods_id, $sku_item[0], $sku_item[1]);
            }
       
        }
        return $array;
       
    }

    /**
     * 获取商品sku列表的商品列表形式--ok
     * @param  $goods_sku_list array(array(goods_id,skuid,num))
     */
    public function getGoodsListGoods($goods_list){
        $array = array();
        if(!empty($goods_list)){
            $goods_list_array = explode(',', $goods_list);
            foreach ($goods_list_array as $k => $v)
            {
                $goods_item = explode(":", $v);
                //获取商品goods_id
                $goods_id = $goods_item[0];
                $sku_id = $goods_item[1];
                $count = $goods_item[2];
               // $goods = new GoodsHandle();
               // $goods_id = $goods->getGoodsId($sku_item[0]);
                $array[] = array($goods_id, $sku_id, $count);
            }

        }
        return $array;

    }


    /**
     * 获取商品列表所属店铺(只针对单店)-ok
     * @param  $goods_sku_list
     */
    public function getGoodsSkuListShop($goods_sku_list)
    {
        return 0;
        /*
        if(!empty($goods_sku_list)){
            $goods_sku_list_array = explode(',', $goods_sku_list);
                $v = $goods_sku_list_array[0];
                $sku_item = explode(":", $v);
                //获取商品goods_id
                $goods = new Goods();
                $goods_id = $goods->getGoodsId($sku_item[0]);
                $shop_id = $goods->getGoodsShopid($goods_id);
                return $shop_id;
               // $array[] = array($goods_id, $sku_item[0], $sku_item[1]);
          
        }else{
            return 0;
        }
        */
    }
    /**
     * 获取当前会员可用优惠券--ok
     * @param  $goods_sku_list
     *   $array[] = array($goods_id, $sku_item[0], $sku_item[1]);
     */
    public function getMemberCouponList($user_id,$goods_sku_list)
    {
        //1.获取当前会员所有优惠券
        $coupon_list = array();
        $goods_sku_list_array = $this->getGoodsSkuListGoods($goods_sku_list);
        $member_coupon = new MemberCouponHandle();
       // getUserCouponList($user_id, $type = '')
        $member_coupon_list = $member_coupon->getUserCouponList($user_id,1);
        //2.获取当前优惠券是否可用
        if(!empty($member_coupon_list)){
            foreach ($member_coupon_list as $k => $coupon)
            {
                //查询优惠券类型的情况
                $coupon_type = new CouponTypeModel();
                $type_info = $coupon_type->getInfo(['id' => $coupon['coupon_type_id']], 'range_type,at_least');
                if($type_info['range_type'] == 1)
                {
                    //全场商品使用的优惠券
                    $price = $this->getGoodsSkuListPrice($user_id, $goods_sku_list);
                }else{
                    //部分商品使用的优惠券
                    $coupon_goods = new CouponGoodsModel();
                    $coupon_goods_list = $coupon_goods->getConditionQuery(['coupon_type_id' => $coupon['coupon_type_id']], '*', '');
                    $new_sku_list = '';
                    foreach ($coupon_goods_list as $k_coupon => $goods)
                    {
                        foreach ($goods_sku_list_array as $k_goods_sku => $v_goods_sku)
                        {
                            if($goods['goods_id'] == $v_goods_sku[0])
                            {
                                $new_sku_list = $new_sku_list.$v_goods_sku[1].':'.$v_goods_sku[2].',';
                            }
                        }
                    }
                   
                    
                    if(!empty($new_sku_list))
                    {
                        $new_sku_list = substr($new_sku_list, 0, strlen($new_sku_list)-1);
                    }
                    $price = $this->getGoodsSkuListPrice($user_id, $new_sku_list);
                }
                if($type_info['at_least'] <= $price)
                {
                    $coupon_list[] = $coupon;
                }
            }
        }
        return $coupon_list;
    }

    /**
     * 获取当前会员可用优惠券--ok-2okk
     * @param  $goods_sku_list
     */
    public function getMemberCouponListByGoodsList($user_id,$goods_list)
    {
        //1.获取当前会员所有优惠券
        $coupon_list = array();

       // $goods_sku_list_array =   $this->getGoodsSkuListGoods($goods_sku_list);
        $goods_list_array = $this->getGoodsListGoods($goods_list);

        $member_coupon = new MemberCouponHandle();
        // getUserCouponList($user_id, $type = '')
        $member_coupon_list = $member_coupon->getUserCouponList($user_id,1);
        //2.获取当前优惠券是否可用
        if(!empty($member_coupon_list)){
            foreach ($member_coupon_list as $k => $coupon)
            {
                //查询优惠券类型的情况
                $coupon_type = new CouponTypeModel();
                $type_info = $coupon_type->getInfo(['id' => $coupon['coupon_type_id']], 'range_type,at_least');
                if($type_info['range_type'] == 1)
                {
                    //全场商品使用的优惠券
                    $price = $this->getGoodsListPrice($user_id, $goods_list);
                }else{
                    //部分商品使用的优惠券
                    $coupon_goods = new CouponGoodsModel();
                    $coupon_goods_list = $coupon_goods->getConditionQuery(['coupon_type_id' => $coupon['coupon_type_id']], '*', '');
                    $new_goods_list = '';
                    foreach ($coupon_goods_list as $k_coupon => $goods)
                    {
                        foreach ($goods_list_array as $k_goods => $v_goods)
                        {
                            if($goods['goods_id'] == $v_goods[0])
                            {
                                $new_goods_list = $new_goods_list.$v_goods[0].':'.$v_goods[1].':'.$v_goods[2].',';
                            }
                        }
                    }


                    if(!empty($new_goods_list))
                    {
                        $new_goods_list = substr($new_goods_list, 0, strlen($new_goods_list)-1);
                    }
                    $price = $this->getGoodsListPrice($user_id, $new_goods_list);
                }
                if($type_info['at_least'] <= $price)
                {
                    $coupon_list[] = $coupon;
                }
            }
        }
        return $coupon_list;
    }

    /**
     * 查询会员等级折扣--ok-2ok
     * @param  $uid
     */
    public function getMemberLevelDiscount($user_id)
    {
        //查询会员等级
        $member = new MemberUserModel();
        $member_info = $member->getInfo(['id' => $user_id], 'member_level');
        if(!empty($member_info))
        {
           $member_level = new MemberLevelModel();
           $level_info = $member_level->getInfo(['id' => $member_info['member_level']],'goods_discount');
           if(!empty($level_info))
           {
               return $level_info['goods_discount'];
           }else{
               return 1;
           }
        }else{
            return 1;
        }
    }
    /**
     * 获取商品sku会员价-ok--2ok
     * @param  $goods_sku_id
     * @param  $uid
     */
    public function getGoodsSkuMemberPrice($goods_sku_id, $user_id){
        //查询sku相关信息
        $goods_sku = new GoodsSkuModel();
        $sku_info = $goods_sku->getInfo(['id'=> $goods_sku_id], 'price');
        $member_level_discount = $this->getMemberLevelDiscount($user_id);
        return $sku_info['price']*$member_level_discount;
    }

    /**
     * 获取商品会员价-ok-2ok
     * @param  $goods_sku_id
     * @param  $uid
     */
    public function getGoodsMemberPrice($goods_id, $user_id){
        //查询goods相关信息
        $goods = new GoodsModel();
        $goods_info = $goods->getInfo(['id'=> $goods_id], 'price');
        $member_level_discount = $this->getMemberLevelDiscount($user_id);
        return $goods_info['price']*$member_level_discount;
    }

    /**
     * 获取自提点运费 -ok-2okk
     * @param  $goods_sku_list
     */
    public function getPickupMoney($goods_sku_list_price)
    {
        $config_handle = new ConfigHandle();

        $config_info = $config_handle->getConfig(0, 'PICKUPPOINT_FREIGHT');
        if(!empty($config_info))
        {
            $pick_up_info = json_decode($config_info['value'], true);
            if($pick_up_info['is_enable'] == 1 && $goods_sku_list_price <= $pick_up_info['manjian_freight'])
            {
                $pick_money = $pick_up_info['pickup_freight'];
            }else{
                $pick_money = 0;
            }
        }else{
            $pick_money = 0;
        }
        return $pick_money;
    }
  
    /*****************************************************************************************订单商品管理结束***************************************************/
    
}