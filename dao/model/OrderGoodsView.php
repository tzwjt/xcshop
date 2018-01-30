<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-11-24
 * Time: 20:00
 */

namespace dao\model;

use dao\model\BaseModel;


class OrderGoodsView extends BaseModel
{
    /**
     * 订单商品表
     */
    protected $name = "order_goods";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;

    protected function getRefundTimeAttr($refund_time)
    {
        return date('Y-m-d H:i:s', $refund_time);
    }


    /**
     * ok-2ok
     * 获取列表返回数据格式
     * @param $page_index
     * @param $page_size
     * @param $condition
     * @param $order
     * @return multitype
     */
    public function getOrderGoodsViewList($page_index, $page_size, $condition, $order){

        $queryList = $this->getOrderGoodsViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getOrderGoodsViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }


    /**
     * ok-2ok
     * 获取列表
     * @param $page_index
     * @param $page_size
     * @param $condition
     * @param $order
     * @return multitype
     */
    public function getOrderGoodsViewQuery($page_index, $page_size, $condition, $order)
    {
        $viewObj = $this->alias('hog')
            ->join('xcshop_order ho','hog.order_id=ho.id','left')
            ->field('hog.goods_name, hog.sku_name, hog.num, ho.pay_time, ho.create_time, ho.user_name, ho.order_no');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }

    /**
     * ok-2ok
     * 获取列表数量
     * @param unknown $condition
     * @return \data\model\unknown
     */
    public function getOrderGoodsViewCount($condition)
    {
        $viewObj = $this->alias('hog')
            ->join('xcshop_order ho','hog.order_id=ho.id','left')
            ->field('hog.goods_name, hog.sku_name, hog.num, ho.pay_time');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }

    /*
    public function getShippingList($page_index, $page_size, $condition, $order){
        $viewObj = $this->alias("hog")
            ->join('xcshop_goods_sku hgs','hog.goods_id = hgns.goods_id and hog.sku_id = ngs.sku_id','left')
            ->field('hog.goods_name,hog.sku_id,hog.sku_name,SUM(nog.num) as num,ngs.code,ngs.stock')
            ->group('nog.sku_id');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
*/
}