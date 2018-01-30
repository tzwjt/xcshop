<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-02
 * Time: 1:26
 */

namespace dao\model;

use dao\model\BaseModel;

class  CouponGoods extends BaseModel
{

    /*
     * 优惠券使用商品表
     */
    protected $name = "coupon_goods";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;

    /**
     * 获取对应优惠券类型的相关商品列表
     * @param unknown $coupon_type_id
     */
    public function getCouponTypeGoodsList($coupon_type_id)
    {
        $list = $this->alias('cg')
            ->join('xcshop_goods gs','cg.goods_id = gs.id','left')
            ->field(' cg.coupon_type_id, cg.goods_id, gs.title, gs.total, gs.thumb,  gs.price')
            ->where(['coupon_type_id'=>$coupon_type_id])->select();
        return $list;
    }
}