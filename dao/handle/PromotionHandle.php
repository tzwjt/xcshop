<?php
/**
 * PromoteHandle.php
 * 促销（营销)处理-ok
 * @date : 2017.8.17
 * @version : v1.0
 */
namespace dao\handle;

use dao\model\AlbumPicture as AlbumPictureModel;
use dao\model\CouponGoods as CouponGoodsModel;
use dao\model\Coupon as CouponModel;
use dao\model\CouponType as CouponTypeModel;
use dao\model\Goods as GoodsModel;
use dao\model\GoodsSku as GoodsSkuModel;
use dao\model\PointConfig as PointConfigModel;
use dao\model\PromotionDiscountGoods as PromotionDiscountGoodsModel;
use dao\model\PromotionDiscount as PromotionDiscountModel;
use dao\model\PromotionFullMail as PromotionFullMailModel ;
use dao\model\PromotionGiftGoods as PromotionGiftGoodsModel;
use dao\model\PromotionGift as PromotionGiftModel;
use dao\model\PromotionMansongGoods as PromotionMansongGoodsModel;
use dao\model\PromotionMansong as PromotionMansongModel;
use dao\model\PromotionMansongRule as PromotionMansongRuleModel;
use dao\handle\BaseHandle as BaseHandle;
use dao\handle\promotion\GoodsDiscountHandle;
use dao\handle\promotion\GoodsMansongHandle;
use think\Log;

class PromotionHandle extends BaseHandle
{

    function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取优惠券类型列表 -okk
     * @param number $page_index
     * @param number $page_size
     * @param string $condition
     * @param string $order
     */
    public function getCouponTypeList($page_index = 1, $page_size = 0, $condition = '', $order = 'create_time desc')
    {
        $coupon_type = new CouponTypeModel();
        $coupon_type_list = $coupon_type->pageQuery($page_index, $page_size, $condition, $order, 'id, coupon_name, money, count, max_fetch, at_least, need_user_level, range_type, start_time, end_time, create_time, update_time,is_show');
        /*
         * if(!empty($coupon_type_list['data']))
         * foreach ($coupon_type_list['data'] as $k => $v)
         * {
         * if($v['range_type'] == 0) //部分产品
         * {
         * $coupon_goods = new NsCouponGoodsModel();
         * $goods_list = $coupon_goods->getCouponTypeGoodsList($v['coupon_type_id']);
         * $coupon_type_list['data'][$k]['goods_list'] = $goods_list;
         * }
         * }
         */
        //
        return $coupon_type_list;
    }

    public function getVilidCouponTypeList()
    {
        $coupon_type = new CouponTypeModel();
        $condition = array (
             'end_time'  => array('EGT', time()),
         );
        $page_index = 1;
        $page_size = 0;
        $order = 'create_time desc';

        $coupon_type_list = $coupon_type->pageQuery($page_index, $page_size, $condition, $order, 'id, coupon_name, money, count, max_fetch, at_least, need_user_level, range_type, start_time, end_time, create_time, update_time,is_show');
        /*
         * if(!empty($coupon_type_list['data']))
         * foreach ($coupon_type_list['data'] as $k => $v)
         * {
         * if($v['range_type'] == 0) //部分产品
         * {
         * $coupon_goods = new NsCouponGoodsModel();
         * $goods_list = $coupon_goods->getCouponTypeGoodsList($v['coupon_type_id']);
         * $coupon_type_list['data'][$k]['goods_list'] = $goods_list;
         * }
         * }
         */
        //
        return $coupon_type_list;
    }

    /**
     * 删除优惠券-ok
     * @param  $coupon_type_id
     */
    public function deleteCouponType($coupon_type_id)
    {
        $coupon = new CouponModel();
        $coupon_type = new CouponTypeModel();
        $this->startTrans();
        try {
            $condition['coupon_type_id'] = $coupon_type_id;
            $condition['status'] = 1;
            $coupon_count = $coupon->getcount($condition);
            if ($coupon_count > 0) {
                $this->rollback();
                $this->error = '已被领用不可删除';
                return false;
               // return - 1;
            }
            $coupon_type->destroy($coupon_type_id);
            $this->commit();
          //  return 1;
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
           // return $e->getMessage();
        }
    }

    /**
     * 获取优惠券类型详情-okk
     * @param  $coupon_type_id
     */
    public function getCouponTypeDetail($coupon_type_id)
    {
        $coupon_type = new CouponTypeModel();
        $data = $coupon_type->get($coupon_type_id);
        $coupon_goods = new CouponGoodsModel();
        $goods_list = $coupon_goods->getCouponTypeGoodsList($coupon_type_id);
        foreach ($goods_list as $k => $v) {
            $picture = new AlbumPictureModel();
            $pic_info = array();
            $pic_info['pic_cover'] = '';
            if (! empty($v['thumb'])) {
                $pic_info = $picture->get($v['thumb']);
            }
            $goods_list[$k]['picture_info'] = $pic_info;
        }
        $data['goods_list'] = $goods_list;
        return $data;
    }


    /**
     * 添加优惠券类型-okk
     * @param  $coupon_name
     * @param  $money
     * @param  $count
     * @param  $max_fetch
     * @param  $at_least
     * @param  $need_user_level
     * @param  $range_type
     * @param  $start_time
     * @param  $end_time
     * @param  $goods_list
     */
    public function addCouponType($coupon_name, $money, $count, $max_fetch, $at_least, $need_user_level, $range_type, $start_time, $end_time, $is_show, $goods_list)
    {
        $coupon_type = new CouponTypeModel();
        $error = 0;
        $this->startTrans();
        try {
            // 添加优惠券类型表
            /**
             * coupon_type_id int(11) NOT NULL AUTO_INCREMENT COMMENT '优惠券类型Id',
             * shop_id int(11) NOT NULL DEFAULT 1 COMMENT '店铺ID',
             * coupon_name varchar(50) NOT NULL DEFAULT '' COMMENT '优惠券名称',
             * money decimal(10, 2) NOT NULL COMMENT '发放面额',
             * count int(11) NOT NULL COMMENT '发放数量',
             * max_fetch int(11) NOT NULL DEFAULT 0 COMMENT '每人最大领取个数 0无限制',
             * at_least decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '满多少元使用 0代表无限制',
             * need_user_level tinyint(4) NOT NULL DEFAULT 0 COMMENT '领取人会员等级',
             * range_type tinyint(4) NOT NULL DEFAULT 1 COMMENT '使用范围0部分产品使用 1全场产品使用',
             * start_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '有效日期开始时间',
             * end_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '有效日期结束时间',
             * create_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
             */
            $data = array(
            //    'shop_id' => $this->instance_id,
                'coupon_name' => $coupon_name,
                'money' => $money,
                'count' => $count,
                'max_fetch' => $max_fetch,
                'at_least' => $at_least,
                'need_user_level' => $need_user_level,
                'range_type' => $range_type,
                'start_time' => getTimeTurnTimeStamp($start_time),
                'end_time' => getTimeTurnTimeStamp($end_time),
                'is_show' => $is_show
            );
            $res1 =  $coupon_type->save($data);
            if (empty($res1)) {
                $this->rollback();
                return false;
            }
            $coupon_type_id = $coupon_type->id;
            // 添加类型商品表
            if ($range_type == 0 && ! empty($goods_list)) {
                $goods_list_array = explode(',', $goods_list);
                foreach ($goods_list_array as $k => $v) {
                    $data_coupon_goods = array(
                        'coupon_type_id' => $coupon_type_id,
                        'goods_id' => $v
                    );
                    $coupon_goods = new CouponGoodsModel();
                    $retval = $coupon_goods->save($data_coupon_goods);
                    if (empty($retval)) {
                        $this->rollback();
                        return false;
                    }
                }
            }
            // 添加优惠券表
            if ($count > 0) {
                for ($i = 0; $i < $count; $i ++) {
                    /**
                     * coupon_id int(11) NOT NULL AUTO_INCREMENT COMMENT '优惠券id',
                     * coupon_type_id int(11) NOT NULL COMMENT '优惠券类型id',
                     * shop_id int(11) NOT NULL COMMENT '店铺Id',
                     * coupon_code varchar(255) NOT NULL DEFAULT '' COMMENT '优惠券编码',
                     * uid int(11) NOT NULL COMMENT '领用人',
                     * use_order_id int(11) NOT NULL COMMENT '优惠券使用订单id',
                     * create_order_id int(11) NOT NULL DEFAULT 0 COMMENT '创建订单id(优惠券只有是完成订单发放的优惠券时才有值)',
                     * money decimal(10, 2) NOT NULL COMMENT '面额',
                     * fetch_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '领取时间',
                     * use_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '使用时间',
                     * state tinyint(4) NOT NULL DEFAULT 0 COMMENT '优惠券状态 0未领用 1已领用（未使用） 2已使用 3已过期',
                     */
                    $data_coupon = array(
                        'coupon_type_id' => $coupon_type_id,
                      //  'shop_id' => $this->instance_id,
                        'coupon_code' => time() . rand(111, 999),
                        'user_id' => 0,
                        'create_order_id' => 0,
                        'money' => $money,
                        'status' => 0,
                        "start_time" => getTimeTurnTimeStamp($start_time),
                        "end_time" => getTimeTurnTimeStamp($end_time)
                    );
                    $coupon = new CouponModel();
                    $retval = $coupon->save($data_coupon);
                    if (empty($retval)) {
                        $this->rollback();
                        return false;
                    }
                }
            }
            $this->commit();
           // return 1;
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
           // return $e->getMessage();
        }
        return false;

    }

    /**
     * 修改优惠券类型 okk
     */
    public function updateCouponType($coupon_type_id, $coupon_name, $money, $count, $repair_count, $max_fetch, $at_least, $need_user_level, $range_type, $start_time, $end_time, $is_show, $goods_list)
    {
        $coupon_type = new CouponTypeModel();
        $error = 0;
        $this->startTrans();
        try {
            // 更新优惠券类型表
            /**
             * coupon_type_id int(11) NOT NULL AUTO_INCREMENT COMMENT '优惠券类型Id',
             * shop_id int(11) NOT NULL DEFAULT 1 COMMENT '店铺ID',
             * coupon_name varchar(50) NOT NULL DEFAULT '' COMMENT '优惠券名称',
             * money decimal(10, 2) NOT NULL COMMENT '发放面额',
             * count int(11) NOT NULL COMMENT '发放数量',
             * max_fetch int(11) NOT NULL DEFAULT 0 COMMENT '每人最大领取个数 0无限制',
             * at_least decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '满多少元使用 0代表无限制',
             * need_user_level tinyint(4) NOT NULL DEFAULT 0 COMMENT '领取人会员等级',
             * range_type tinyint(4) NOT NULL DEFAULT 1 COMMENT '使用范围0部分产品使用 1全场产品使用',
             * start_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '有效日期开始时间',
             * end_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '有效日期结束时间',
             * create_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
             */
            $data = array(
               // 'shop_id' => $this->instance_id,
                'coupon_name' => $coupon_name,
                'money' => $money,
                'count' => $count + $repair_count,
                'max_fetch' => $max_fetch,
                'at_least' => $at_least,
                'need_user_level' => $need_user_level,
                'range_type' => $range_type,
                'start_time' => getTimeTurnTimeStamp($start_time),
                'end_time' => getTimeTurnTimeStamp($end_time),
                'is_show' => $is_show
            );
           $res= $coupon_type->save($data, [
                'id' => $coupon_type_id
            ]);
            if (empty($res)) {
                $this->rollback();
                return false;
            }
            // 更新类型商品表
            $coupon_goods = new CouponGoodsModel();
            $coupon_goods->destroy([
                'coupon_type_id' => $coupon_type_id
            ]);
            if ($range_type == 0 && ! empty($goods_list)) {
                $goods_list_array = explode(',', $goods_list);
                foreach ($goods_list_array as $k => $v) {
                    $data_coupon_goods = array(
                        'coupon_type_id' => $coupon_type_id,
                        'goods_id' => $v
                    );
                    $coupon_goods = new CouponGoodsModel();
                    $retval = $coupon_goods->save($data_coupon_goods);
                    if (empty($retval)) {
                        $this->rollback();
                        return false;
                    }
                }
            }
            // 添加优惠券表
            if ($repair_count > 0) {
                for ($i = 0; $i < $repair_count; $i ++) {
                    $data_coupon = array(
                        'coupon_type_id' => $coupon_type_id,
                      //  'shop_id' => $this->instance_id,
                        'coupon_code' => time() . rand(111, 999),
                        'user_id' => 0,
                        'create_order_id' => 0,
                        'money' => $money,
                        'status' => 0,
                        'start_time' => getTimeTurnTimeStamp($start_time),
                        'end_time' => getTimeTurnTimeStamp($end_time)
                    );
                    $coupon = new CouponModel();
                    $retval = $coupon->save($data_coupon);
                    if (empty($retval)) {
                        $this->rollback();
                        return false;
                    }
                }
            }
            // 修改优惠券时，更新优惠券的使用状态
            $coupon = new CouponModel();
            $coupon_condition['status'] = array(
                'in',
                [
                    0,
                    3
                ]
            ); // 未领用或者已过期的优惠券
            $coupon_condition['coupon_type_id'] = $coupon_type_id;
           $retval =  $coupon->save([
                'end_time' => getTimeTurnTimeStamp($end_time),
                'start_time' => getTimeTurnTimeStamp($start_time),
                'status' => 0
            ], $coupon_condition);

            if (empty($retval)) {
                $this->rollback();
                return false;
            }

            $this->commit();
            return true;
           // return 1;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            //return 0;
            return false;
        }
      //  return 0;
        return false;
    }


    /**
     * 获取优惠券类型的优惠券列表-okk
     * @param  $coupon_type_id
     * @param  $get_type    0 标示全部
     * @param  $use_type    0 标示全部
     */
    public function getTypeCouponList($coupon_type_id, $get_type = 0, $use_type = 0)
    {
        $coupon = new CouponModel();
        $condition = array(
            'coupon_type_id' => $coupon_type_id,
            'status' => $use_type
        );
        $list = $coupon->pageQuery(1, 0, $condition, '', '*');
        return $list['data'];
    }

    /*
     * ok-2ok-3ok
     * 使用优惠券 -okk
     */
    public function useCoupon($user_id, $coupon_id, $order_id)
    {
        $coupon = new CouponModel();
        $data = array(
            'use_order_id' => $order_id,
            'status' => 2
        );
        $res = $coupon->save($data, [
            'coupon_id' => $coupon_id,
            'user_id'=>$user_id
        ]);
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 获取优惠券详情
     * @param  $coupon_id
     */
    public function getCouponDetail($coupon_id)
    {

    }

    /**
     * ok-2ok
     * 获取店铺积分配置信息 -okk
     */
    public function getPointConfig()
    {
        $point_model = new PointConfigModel();
        /*
        $count = $point_model->where([
            'shop_id' => $this->instance_id
        ])->count();
        */
        $count = $point_model->count();
        if ($count > 0) {
            /*
            $info = $point_model->get([
                'shop_id' => $this->instance_id
            ]);
            */
            $info = $point_model->getInfo();
        } else {
            $data = array(
              //  'shop_id' => $this->instance_id,
                'is_open' => 0,
                'desc' => '',
             //   'create_time' => time()
            );
            $point_model = new PointConfigModel();
            $res = $point_model->save($data);
            /*
            $info = $point_model->get([
                'shop_id' => $this->instance_id
            ]);
            */
            $info = $point_model->get($point_model->id);
        }

        return $info;
    }

    /**
     * 店铺积分设置 --okk
     * @param  $is_open
     * @param  $convert_rate
     * @param  $desc
     */
    public function setPointConfig($config_id, $convert_rate, $is_open, $desc)
    {
        $point_model = new PointConfigModel();
        $data = array(
            'convert_rate' => $convert_rate,
            'is_open' => $is_open,
            'desc' => $desc,
          //  'update_time' => time()
        );
        $retval = $point_model->save($data, [
            'id' => $config_id
        ]);
        return $retval;
    }



    /**
     * 赠品活动列表--okk
     * @param number $page_index
     * @param number $page_size
     * @param string $condition
     * @param string $order
     */
    public function getPromotionGiftList($page_index = 1, $page_size = 0, $condition = '', $order = 'create_time desc')
    {
        $promotion_gift = new PromotionGiftModel();
        $list = $promotion_gift->pageQuery($page_index, $page_size, $condition, $order, '*');
        if (! empty($list['data'])) {
            foreach ($list['data'] as $k => $v) {
                $start_time = $v['start_time'];
                $end_time = $v['end_time'];
                if ($end_time < time()) {
                    $list['data'][$k]['type'] = 2;
                    $list['data'][$k]['type_name'] = '已结束';
                } elseif ($start_time > time()) {
                    $list['data'][$k]['type'] = 0;
                    $list['data'][$k]['type_name'] = '未开始';
                } elseif ($start_time <= time() && time() <= $end_time) {
                    $list['data'][$k]['type'] = 1;
                    $list['data'][$k]['type_name'] = '进行中';
                }
            }
        }
        return $list;
    }

    /**
     * 添加赠品活动-okk
     * @param  $gift_name
     * @param  $start_time
     * @param  $end_time
     * @param  $days
     * @param  $max_num
     * @param  $goods_id_array
     */
    public function addPromotionGift($gift_name, $start_time, $end_time, $days, $max_num, $goods_id_array)
    {
        $promotion_gift = new PromotionGiftModel();
        $this->startTrans();
        try {
            $data_gift = array(
                'gift_name' => $gift_name,
               // 'shop_id' => $shop_id,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'days' => $days,
                'max_num' => $max_num,
               // 'create_time' => time()
            );
           $res =  $promotion_gift->save($data_gift);
            if (empty($res)) {
                $this->rollback();
                return false;

            }
            $gift_id = $promotion_gift->id;
            // 当前功能只能选择一种商品
            $promotion_gift_goods = new PromotionGiftGoodsModel();
            // 查询商品名称图片
            $goods = new GoodsModel();
            $goods_info = $goods->getInfo([
                'id' => $goods_id_array
            ], 'title,thumb');
            $data_goods = array(
                'gift_id' => $gift_id,
                'goods_id' => $goods_id_array,
                'goods_name' => $goods_info['title'],
                'goods_picture' => $goods_info['thumb']
            );
           $res = $promotion_gift_goods->save($data_goods);
            if (empty($res)) {
                $this->rollback();
                return false;
            }
            $this->commit();
          //  return $gift_id;
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
           // return $e->getMessage();
        }

    }

    /**
     * 修改赠品活动 -okk
     * @param  $gift_id
     * @param  $gift_name
     * @param  $start_time
     * @param  $end_time
     * @param  $days
     * @param  $max_num
     * @param  $goods_id_array
     */
    public function updatePromotionGift($gift_id,  $gift_name, $start_time, $end_time, $days, $max_num, $goods_id_array)
    {
        $promotion_gift = new PromotionGiftModel();
        $this->startTrans();
        try {
            $data_gift = array(
                'gift_name' => $gift_name,
              //  'shop_id' => $shop_id,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'days' => $days,
                'max_num' => $max_num
            );
            $res = $promotion_gift->save($data_gift, [
                'id' => $gift_id
            ]);
            if (empty($res)) {
                $this->rollback();
                return false;
            }
            // 当前功能只能选择一种商品
            $promotion_gift_goods = new PromotionGiftGoodsModel();
            $promotion_gift_goods->destroy([
                'gift_id' => $gift_id
            ]);
            // 查询商品名称图片
            $goods = new GoodsModel();
            $goods_info = $goods->getInfo([
                'id' => $goods_id_array
            ], 'title,thumb');
            $data_goods = array(
                'gift_id' => $gift_id,
                'goods_id' => $goods_id_array,
                'goods_name' => $goods_info['title'],
                'goods_picture' => $goods_info['thumb']
            );
            $promotion_gift_goods = new PromotionGiftGoodsModel();
            $res = $promotion_gift_goods->save($data_goods);
            if (empty($res)) {
                $this->rollback();
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
     * 获取 赠品详情-okk
     *
     * @param  $gift_id            
     */
    public function getPromotionGiftDetail($gift_id)
    {
        $promotion_gift = new PromotionGiftModel();
        $data = $promotion_gift->get($gift_id);
        $promotion_gift_goods = new PromotionGiftGoodsModel();
        $gift_goods = $promotion_gift_goods->get([
            'gift_id' => $gift_id
        ]);
        $picture = new AlbumPictureModel();
        $pic_info = array();
        $pic_info['pic_cover'] = '';
        if (! empty($gift_goods['goods_picture'])) {
            $pic_info = $picture->get($gift_goods['goods_picture']);
        }
        $gift_goods['picture'] = $pic_info;
        $data['gift_goods'] = $gift_goods;
        return $data;
    }

    /**
     * 获取满减送列表-okk
     * @param number $page_index
     * @param number $page_size
     * @param string $condition
     * @param string $order
     */
    public function getPromotionMansongList($page_index = 1, $page_size = 0, $condition = '', $order = 'create_time desc')
    {
        $promotion_mansong = new PromotionMansongModel();
        $list = $promotion_mansong->pageQuery($page_index, $page_size, $condition, $order, '*');
        if (! empty($list['data'])) {
            foreach ($list['data'] as $k => $v) {
                if ($v['status'] == 0) {
                    $list['data'][$k]['status_name'] = '未开始';
                }
                if ($v['status'] == 1) {
                    $list['data'][$k]['status_name'] = '进行中';
                }
                if ($v['status'] == 2) {
                    $list['data'][$k]['status_name'] = '已取消';
                }
                if ($v['status'] == 3) {
                    $list['data'][$k]['status_name'] = '已失效';
                }
                if ($v['status'] == 4) {
                    $list['data'][$k]['status_name'] = '已结束';
                }
            }
        }
        return $list;
    }

    /**
     * 添加满减送活动-okkk
     * @param  $mansong_name
     * @param  $start_time
     * @param  $end_time
     * @param  $remark
     * @param  $type
     * @param  $range_type
     * @param  $rule   ,discount,fee_shipping,give_point,give_coupon,gift_id;price,discount,fee_shipping,give_point,give_coupon,gift_id
     * @param  $goods_id_array
     */
    public function addPromotionMansong($mansong_name, $start_time, $end_time,  $remark, $type, $range_type, $rule, $goods_id_array)
    {

        $promot_mansong = new PromotionMansongModel();
        $goods_mansong = new GoodsMansongHandle();
        $this->startTrans();
        try {
            $err = 0;
            $count_quan = $goods_mansong->getQuanmansong($start_time, $end_time);
            if ($count_quan > 0 && $range_type == 1) {
                $err = 1;
                $this->rollback();
                $this->error = "此类活动已存在，不可重复进行!";
                return false;
            }
         //   $shop_name = $this->instance_name;
            $data = array(
                'mansong_name' => $mansong_name,
                'start_time' => getTimeTurnTimeStamp($start_time),
                'end_time' => getTimeTurnTimeStamp($end_time),
            //    'shop_id' => $shop_id,
            //    'shop_name' => $shop_name,
                'status' => 0, // 状态重新设置
                'remark' => $remark,
                'type' => $type,
                'range_type' => $range_type,
                'create_time' => time()
            );
           $res =  $promot_mansong->save($data);
            if (empty($res)) {
                $this->rollback();
                Log::write('promot_mansong->save(data) 此操作失败!');
                return false;
            }
            $mansong_id = $promot_mansong->id;
            // 添加活动规则表
            $rule_array = explode(';', $rule);
            foreach ($rule_array as $k => $v) {
                $get_rule = explode(',', $v);
                $data_rule = array(
                    'mansong_id' => $mansong_id,
                    'price' => $get_rule[0],
                    'discount' => $get_rule[1],
                    'free_shipping' => $get_rule[2],
                    'give_point' => $get_rule[3],
                    'give_coupon_id' => $get_rule[4],
                    'gift_id' => $get_rule[5]
                );
                $promot_mansong_rule = new PromotionMansongRuleModel();
                $res = $promot_mansong_rule->save($data_rule);

                if (empty($res)) {
                    $this->rollback();
                    Log::write('promot_mansong_rule->save(data_rule) 此操作失败!');
                    return false;
                }
            }

            // 满减送商品表
            if ($range_type == 0 && ! empty($goods_id_array)) {
                // 部分商品
                $goods_id_array = explode(',', $goods_id_array);
                foreach ($goods_id_array as $k => $v) {
                    $promotion_mansong_goods = new PromotionMansongGoodsModel();
                    // 查询商品名称图片
                    $goods = new GoodsModel();
                    $goods_info = $goods->getInfo([
                        'id' => $v
                    ], 'title,thumb');
                    $data_goods = array(
                        'mansong_id' => $mansong_id,
                        'goods_id' => $v,
                        'goods_name' => $goods_info['title'],
                        'goods_picture' => $goods_info['thumb'],
                        'status' => 0, // 状态重新设置
                        'start_time' => getTimeTurnTimeStamp($start_time),
                        'end_time' => getTimeTurnTimeStamp($end_time)
                    );
                    $count = $goods_mansong->getGoodsIsMansong($v, $start_time, $end_time);
                    if ($count > 0) {
                        $err = 1;
                        $this->rollback();
                        $this->error = "此类活动已存在，不可重复进行!";
                        return false;
                    }
                    $res = $promotion_mansong_goods->save($data_goods);
                    if (empty($res)) {
                        $this->rollback();
                        Log::write('promotion_mansong_goods->save(data_goods) 此操作失败!');
                        return false;
                     }
                }
            }
            if ($err > 0) {
                $this->rollback();
                $this->error = "此类活动已存在，不可重复进行!";
                return false;
            } else {
                $this->commit();
               // return $mansong_id;
                return true;
            }
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
           // return $e->getMessage();
        }

    }

    /**
     * 修改满减送活动-okkk
     * @param  $mansong_id
     * @param  $mansong_name
     * @param  $start_time
     * @param  $end_time
     * @param  $remark
     * @param  $type
     * @param  $range_type
     * @param  $rule ,give_coupon,gift_id;price,discount,fee_shipping,give_point,give_coupon,gift_id
     * @param  $goods_id_array
     */
    public function updatePromotionMansong($mansong_id, $mansong_name, $start_time, $end_time, $remark, $type, $range_type, $rule, $goods_id_array)
    {
        $promot_mansong = new PromotionMansongModel();
        $this->startTrans();
        try {
            $err = 0;
         //   $shop_name = $this->instance_name;
            $data = array(
                'mansong_name' => $mansong_name,
                'start_time' => getTimeTurnTimeStamp($start_time),
                'end_time' => getTimeTurnTimeStamp($end_time),
              //  'shop_id' => $this->instance_id,
             //   'shop_name' => $shop_name,
                'status' => 0, // 状态重新设置
                'remark' => $remark,
                'type' => $type,
                'range_type' => $range_type,
             //   'create_time' => time()
            );
           $res = $promot_mansong->save($data, [
                'id' => $mansong_id
            ]);
            if (empty($res)) {
                $this->rollback();
                Log::write('promot_mansong->save(data, [\'id\' => mansong_id
                        ]); 此操作失败!');
                return false;

            }
            // 添加活动规则表
            $promot_mansong_rule = new PromotionMansongRuleModel();
            $promot_mansong_rule->destroy([
                'mansong_id' => $mansong_id
            ]);
            $rule_array = explode(';', $rule);
            foreach ($rule_array as $k => $v) {
                $promot_mansong_rule = new PromotionMansongRuleModel();
                $get_rule = explode(',', $v);
                $data_rule = array(
                    'mansong_id' => $mansong_id,
                    'price' => $get_rule[0],
                    'discount' => $get_rule[1],
                    'free_shipping' => $get_rule[2],
                    'give_point' => $get_rule[3],
                    'give_coupon_id' => $get_rule[4],
                    'gift_id' => $get_rule[5]
                );
                $res = $promot_mansong_rule->save($data_rule);
                if (empty($res)) {
                    $this->rollback();
                    Log::write('promot_mansong_rule->save(data_rule) 此操作失败!');
                    return false;

                }
            }

            // 满减送商品表
            if ($range_type == 0 && ! empty($goods_id_array)) {
                // 部分商品
                $goods_id_array = explode(',', $goods_id_array);
                $promotion_mansong_goods = new PromotionMansongGoodsModel();
                $promotion_mansong_goods->destroy([
                    'mansong_id' => $mansong_id
                ]);
                foreach ($goods_id_array as $k => $v) {
                    // 查询商品名称图片
                    $goods_mansong = new GoodsMansongHandle();
                    $count = $goods_mansong->getGoodsIsMansong($v, $start_time, $end_time);
                    if ($count > 0) {
                        $err = 1;
                        $this->rollback();
                        $this->error = "此类活动已存在，不可重复进行!";
                        return false;
                    }
                    $promotion_mansong_goods = new PromotionMansongGoodsModel();
                    $goods = new GoodsModel();
                    $goods_info = $goods->getInfo([
                        'id' => $v
                    ], 'title,thumb');
                    $data_goods = array(
                        'mansong_id' => $mansong_id,
                        'goods_id' => $v,
                        'goods_name' => $goods_info['title'],
                        'goods_picture' => $goods_info['thumb'],
                        'status' => 0, // 状态重新设置
                        'start_time' => getTimeTurnTimeStamp($start_time),
                        'end_time' => getTimeTurnTimeStamp($end_time)
                    );
                   $res =  $promotion_mansong_goods->save($data_goods);
                    if (empty($res)) {
                        $this->rollback();
                        Log::write('promotion_mansong_goods->save 此操作失败!');
                        return false;

                    }
                }
            }
            if ($err > 0) {
                $this->rollback();
                $this->error = "此类活动已存在，不可重复进行!";
                return false;
            } else {

                $this->commit();
                return true;
             //   return 1;
            }
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;

           // return $e->getMessage();
        }
    }

    /**
     * 获取满减送详情-okkk
     * @param  $mansong_id
     */
    public function getPromotionMansongDetail($mansong_id)
    {
        $promotion_mansong = new PromotionMansongModel();
        $data = $promotion_mansong->get($mansong_id);
        if ($data['status'] == 0) {
            $data['status_name'] = '未发布';
        } else if ($data['status'] == 1) {
            $data['status_name'] = '进行中';
        } else if ($data['status'] == 2) {
            $data['status_name'] = '取消';
        } else if ($data['status'] == 3) {
            $data['status_name'] = '已关闭';
        } else if ($data['status'] == 4) {
            $data['status_name'] = '已结束';
        }
        $promot_mansong_rule = new PromotionMansongRuleModel();
        $rule_list = $promot_mansong_rule->pageQuery(1, 0, 'mansong_id = ' . $mansong_id, '', '*');
        foreach ($rule_list['data'] as $k => $v) {
            if ($v['free_shipping'] == 1) {
                $rule_list['data'][$k]['free_shipping_name'] = "是";
            } else {
                $rule_list['data'][$k]['free_shipping_name'] = "否";
            }
            if ($v['give_coupon_id'] == 0) {
                $rule_list['data'][$k]['coupon_name'] = '';
            } else {
                $coupon_type = new CouponTypeModel();
                $coupon_name = $coupon_type->getInfo([
                    'id' => $v['give_coupon_id']
                ], 'coupon_name');
                $rule_list['data'][$k]['coupon_name'] = $coupon_name['coupon_name'];
            }
            if ($v['gift_id'] == 0) {
                $rule_list['data'][$k]['gift_name'] = '';
            } else {
                $gift = new PromotionGiftModel();
                $gift_name = $gift->getInfo([
                    'id' => $v['gift_id']
                ], 'gift_name');
                $rule_list['data'][$k]['gift_name'] = $gift_name['gift_name'];
            }

        }
        $data['rule'] = $rule_list['data'];
        if ($data['range_type'] == 0) {
            $mansong_goods = new PromotionMansongGoodsModel();
            $list = $mansong_goods->getConditionQuery([
                'mansong_id' => $mansong_id
            ], '*', '');
            if (! empty($list)) {
                foreach ($list as $k => $v) {
                    $goods = new GoodsModel();
                    $goods_info = $goods->getInfo([
                        'id' => $v['goods_id']
                    ], 'price, total');
                    $picture = new AlbumPictureModel();
                    $pic_info = array();
                    $pic_info['pic_cover'] = '';
                    if (! empty($v['goods_picture'])) {
                        $pic_info = $picture->get($v['goods_picture']);
                    }
                    $v['picture_info'] = $pic_info;
                    $v['price'] = $goods_info['price'];
                    $v['stock'] = $goods_info['total'];
                }
            }
            $data['goods_list'] = $list;
            $goods_id_array = array();
            foreach ($list as $k => $v) {
                $goods_id_array[] = $v['goods_id'];
            }
            $data['goods_id_array'] = $goods_id_array;
        }
        return $data;
    }

    /**
     * 添加限时折扣-okkk
     * @param  $discount_name
     * @param  $start_time
     * @param  $end_time
     * @param  $remark
     * @param  $goods_id_array  ,goods_id:discount
     */
    public function addPromotionDiscount($discount_name, $start_time, $end_time, $remark, $goods_id_array)
    {
        $promotion_discount = new PromotionDiscountModel();
        $this->startTrans();
        try {

            $data = array(
                'discount_name' => $discount_name,
                'start_time' => getTimeTurnTimeStamp($start_time),
                'end_time' => getTimeTurnTimeStamp($end_time),
                'status' => 0,
                'remark' => $remark,
             //   'create_time' => time()
            );
            $res =  $promotion_discount->save($data);
            if (empty($res)) {
                $this->rollback();
                Log::write('promotion_discount->save(data) 操作出错!');
                return false;
            }
            $discount_id = $promotion_discount->id;
            $goods_id_array = explode(',', $goods_id_array);
            $promotion_discount_goods = new PromotionDiscountGoodsModel();
            $promotion_discount_goods->destroy([
                'discount_id' => $discount_id
            ]);
            foreach ($goods_id_array as $k => $v) {
                // 添加检测考虑商品在一个时间段内只能有一种活动

                $promotion_discount_goods = new PromotionDiscountGoodsModel();
                $discount_info = explode(':', $v);
                $goods_discount = new GoodsDiscountHandle();
                $count = $goods_discount->getGoodsIsDiscount($discount_info[0], $start_time, $end_time);
                // 查询商品名称图片
                if ($count > 0) {
                    $this->error = "有些商品的限时折扣活动已存在，不可重复!";
                    $this->rollback();
                   // return ACTIVE_REPRET;
                    return false;
                }
                $goods = new GoodsModel();
                $goods_info = $goods->getInfo([
                    'id' => $discount_info[0]
                ], 'title,thumb');
                $data_goods = array(
                    'discount_id' => $discount_id,
                    'goods_id' => $discount_info[0],
                    'discount' => $discount_info[1],
                    'status' => 0,
                    'start_time' => getTimeTurnTimeStamp($start_time),
                    'end_time' => getTimeTurnTimeStamp($end_time),
                    'goods_name' => $goods_info['title'],
                    'goods_picture' => $goods_info['thumb']
                );
                $res = $promotion_discount_goods->save($data_goods);
                if (empty($res)) {
                    $this->rollback();
                    Log::write('promotion_discount_goods->save(data_goods) 操作出错!');
                    return false;
                }
            }
            $this->commit();
           // return $discount_id;
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
         //   return $e->getMessage();
        }
    }
    /**
     * 修改限时折扣-ok
     */
    public function updatePromotionDiscount($discount_id, $discount_name, $start_time, $end_time, $remark, $goods_id_array)
    {
        $promotion_discount = new PromotionDiscountModel();
        $this->startTrans();
        try {

            $data = array(
                'discount_name' => $discount_name,
                'start_time' => getTimeTurnTimeStamp($start_time),
                'end_time' => getTimeTurnTimeStamp($end_time),
                'status' => 0,
                'remark' => $remark,
              //  'create_time' => time()
            );
            $res = $promotion_discount->save($data, [
                'id' => $discount_id
            ]);
            if (empty($res)) {
                $this->rollback();
                Log::write('promotion_discount->save(data, [ \'discount_id\' => discount_id
                            ]); 操作出错!');
                return false;
            }
            $goods_id_array = explode(',', $goods_id_array);
            $promotion_discount_goods = new PromotionDiscountGoodsModel();
            $promotion_discount_goods->destroy([
                'discount_id' => $discount_id
            ]);
            foreach ($goods_id_array as $k => $v) {
                $promotion_discount_goods = new PromotionDiscountGoodsModel();
                $discount_info = explode(':', $v);
                $goods_discount = new GoodsDiscountHandle();
                $count = $goods_discount->getGoodsIsDiscount($discount_info[0], $start_time, $end_time);
                // 查询商品名称图片
                if ($count > 0) {
                    $this->error = "有些商品的限时折扣活动已存在，不可重复!";
                    $this->rollback();
                    // return ACTIVE_REPRET;
                    return false;
                }
                // 查询商品名称图片
                $goods = new GoodsModel();
                $goods_info = $goods->getInfo([
                    'id' => $discount_info[0]
                ], 'title,thumb');
                $data_goods = array(
                    'discount_id' => $discount_id,
                    'goods_id' => $discount_info[0],
                    'discount' => $discount_info[1],
                    'status' => 0,
                    'start_time' => getTimeTurnTimeStamp($start_time),
                    'end_time' => getTimeTurnTimeStamp($end_time),
                    'goods_name' => $goods_info['title'],
                    'goods_picture' => $goods_info['thumb']
                );
               $res =  $promotion_discount_goods->save($data_goods);
                if (empty($res)) {
                    $this->rollback();
                    Log::write('promotion_discount_goods->save(data_goods) 操作出错!');
                    return false;
                }
            }
            $this->commit();
          //  return $discount_id;
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
           // return $e->getMessage();
            return false;
        }
    }

    /**
     * 关闭限时折扣活动--okkk
     * @param  $discount_id
     */
    public function closePromotionDiscount($discount_id)
    {
        $promotion_discount = new PromotionDiscountModel();
        $this->startTrans();
        try {
            $retval = $promotion_discount->save([
                'status' => 3
            ], [
                'id' => $discount_id
            ]);
            if ($retval == 1) {
                $goods = new GoodsModel();
                
                $data_goods = array(
                    'promotion_type' => 2,
                    'promote_id' => $discount_id
                );
                $goods_id_list = $goods->getConditionQuery($data_goods, 'id', '');
                if (! empty($goods_id_list)) {
                    
                    foreach ($goods_id_list as $k => $goods_id) {
                        $goods_info = $goods->getInfo([
                            'id' => $goods_id['id']
                        ], 'promotion_type,price');
                        $goods->save([
                            'promotion_price' => $goods_info['price']
                        ], [
                            'id' => $goods_id['id']
                        ]);
                        $goods_sku = new GoodsSkuModel();
                        $goods_sku_list = $goods_sku->getConditionQuery([
                            'goods_id' => $goods_id['id']
                        ], 'price,id', '');
                        foreach ($goods_sku_list as $k_sku => $sku) {
                            $goods_sku = new GoodsSkuModel();
                            $data_goods_sku = array(
                                'promotion_price' => $sku['price']
                            );
                            $goods_sku->save($data_goods_sku, [
                                'id' => $sku['id']
                            ]);
                        }
                    }
                }
                $goods->save([
                    'promotion_type' => 0,
                    'promote_id' => 0
                ], $data_goods);
                $promotion_discount_goods = new PromotionDiscountGoodsModel();
                $retval = $promotion_discount_goods->save([
                    'status' => 3
                ], [
                    'discount_id' => $discount_id
                ]);
            }
             $this->commit();
            return $retval;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
           // return $e->getMessage();
            return false;
        }
    }

    /**
     * 获取限时折扣列表-okkk
     * @param number $page_index
     * @param number $page_size
     * @param string $condition
     * @param string $order
     */
    public function getPromotionDiscountList($page_index = 1, $page_size = 0, $condition = '', $order = 'create_time desc')
    {
        $promotion_discount = new PromotionDiscountModel();
        $list = $promotion_discount->pageQuery($page_index, $page_size, $condition, $order, '*');
        return $list;
    }

    /**
     * 获取限时折扣详情-okkk
     * @param  $discount_id
     */
    public function getPromotionDiscountDetail($discount_id)
    {
        $promotion_discount = new PromotionDiscountModel();
        $promotion_detail = $promotion_discount->get($discount_id);
        $promotion_discount_goods = new PromotionDiscountGoodsModel();
        $promotion_goods_list = $promotion_discount_goods->getConditionQuery([
            'discount_id' => $discount_id
        ], '*', '');
        if (! empty($promotion_goods_list)) {
            foreach ($promotion_goods_list as $k => $v) {
                $goods = new GoodsModel();
                $goods_info = $goods->getInfo([
                    'id' => $v['goods_id']
                ], 'price, total');
                $picture = new AlbumPictureModel();
                $pic_info = array();
                $pic_info['pic_cover'] = '';
                if (! empty($v['goods_picture'])) {
                    $pic_info = $picture->get($v['goods_picture']);
                }
                $v['picture_info'] = $pic_info;
                $v['price'] = $goods_info['price'];
                $v['stock'] = $goods_info['total'];
            }
        }
        $promotion_detail['goods_list'] = $promotion_goods_list;
        return $promotion_detail;
    }

    /**
     * 删除限时折扣--okkk
     * @param  $discount_id
     */
    public function delPromotionDiscount($discount_id)
    {
        $promotion_discount = new PromotionDiscountModel();
        $promotion_discount_goods = new PromotionDiscountGoodsModel();
        $this->startTrans();
        try {
            $discount_id_array = explode(',', $discount_id);
            foreach ($discount_id_array as $k => $v) {
                $promotion_detail = $promotion_discount->get($discount_id);
                if ($promotion_detail['status'] == 1) {
                    $this->rollback();
                    $this->error = "活动进行中不可删除!";
                    //return - 1;
                    return false;
                }
                $promotion_discount->destroy($v);
                $promotion_discount_goods->destroy([
                    'discount_id' => $v
                ]);
            }
            $this->commit();
            return true;
           // return 1;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
           // return $e->getMessage();
            return false;
        }
    }

    /**
     * 关闭满减送活动-okkk
     * @param  $mansong_id
     */
    public function closePromotionMansong($mansong_id)
    {
        $promotion_mansong = new PromotionMansongModel();
        $retval = $promotion_mansong->save([
            'status' => 3
        ], [
            'id' => $mansong_id,
          //  'shop_id' => $this->instance_id
        ]);
        if ($retval == 1) {
            $promotion_mansong_goods = new PromotionMansongGoodsModel();

            $retval = $promotion_mansong_goods->save([
                'status' => 3
            ], [
                'mansong_id' => $mansong_id
            ]);
        }
        return $retval;
    }

    /**
     * 删除满减送活动-okkk
     * @param  $mansong_id
     */
    public function delPromotionMansong($mansong_id)
    {
        $promotion_mansong = new PromotionMansongModel();
        $promotion_mansong_goods = new PromotionMansongGoodsModel();
        $promot_mansong_rule = new PromotionMansongRuleModel();
        $this->startTrans();
        try {
            $mansong_id_array = explode(',', $mansong_id);
            foreach ($mansong_id_array as $k => $v) {
                $status = $promotion_mansong->getInfo([
                    'id' => $v
                ], 'status');
                if ($status['status'] == 1) {
                    $this->error ='进行中的活动不可删除!';
                    $this->rollback();
                  //  return - 1;
                    return false;
                }
                $promotion_mansong->destroy($v);
                $promotion_mansong_goods->destroy([
                    'mansong_id' => $v
                ]);
                $promot_mansong_rule->destroy([
                    'mansong_id' => $v
                ]);
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
     * 得到店铺的满额包邮信息-okkk-2okk
     */
    public function getPromotionFullMail()
    {
        $promotion_fullmail = new PromotionFullMailModel();
        /*
        $mail_count = $promotion_fullmail->getCount([
            "shop_id" => $shop_id
        ]);
        */
        $mail_count = $promotion_fullmail->getCount([
        ]);
        if ($mail_count == 0) {
            $data = array(
              //  'shop_id' => $shop_id,
                'is_open' => 0,
                'full_mail_money' => 0,
                'no_mail_province_id_array' => '',
                'no_mail_city_id_array' => '',
             //   'create_time' => time()
            );
            $promotion_fullmail->save($data);
        }
        /*
        $mail_obj = $promotion_fullmail->getInfo([
            "shop_id" => $shop_id
        ]);
        */
        $mail_obj = $promotion_fullmail->getInfo();
        return $mail_obj;
    }

    /**
     * 更新或添加满额包邮的信息-ok
     */
    public function updatePromotionFullMail( $is_open, $full_mail_money, $no_mail_province_id_array, $no_mail_city_id_array)
    {
        $full_mail_model = new PromotionFullMailModel();
        $data = array(
            'is_open' => $is_open,
            'full_mail_money' => $full_mail_money,
         //   'modify_time' => time(),
            'no_mail_province_id_array' => $no_mail_province_id_array,
            'no_mail_city_id_array' => $no_mail_city_id_array
        );
        $mail_obj = $full_mail_model->getInfo();
        $res = $full_mail_model->save($data, [
            "id" => $mail_obj['id']
        ]);
        return $res;
    }
}