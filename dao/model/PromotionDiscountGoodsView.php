<?php
/**
 * 限时折扣商品表视图
 * @date : 2017.8.17
 * @version : v1.0
 */
namespace data\model;

use dao\model\BaseModel as BaseModel;
/**
 * 限时折扣商品表
 *  discount_goods_id int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  discount_id int(11) NOT NULL COMMENT '对应活动',
  start_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '开始时间',
  end_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '结束时间',
  goods_id int(11) NOT NULL COMMENT '商品ID',
  status tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态',
  discount tinyint(1) NOT NULL COMMENT '活动折扣或者减现信息',
  PRIMARY KEY (discount_goods_id)
 */
class NsPromotionDiscountGoodsViewModel extends BaseModel {

    protected $name = 'promotion_discount_goods';
    
    /**
     * 获取列表返回数据格式
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return unknown
     */
    public function getViewList($page_index, $page_size, $condition, $order){
    
        $queryList = $this->getViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    /**
     * 获取列表
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return \data\model\multitype:number
     */
    public function getViewQuery($page_index, $page_size, $condition, $order)
    {
        //设置查询视图
        $viewObj = $this->alias('pdg')
        ->join('xcshop_goods gs','gs.id = pdg.goods_id','inner')
        ->field('pdg.id,pdg.discount_id,pdg.start_time,pdg.end_time,pdg.goods_id,pdg.status,pdg.discount,pdg.goods_name,pdg.goods_picture,gs.pcate,gs.price,gs.promotion_price');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }

    /**
     * 获取列表数量
     * @param unknown $condition
     * @return \data\model\unknown
     */
    public function getViewCount($condition)
    {
        $viewObj = $this->alias('pdg')
        ->join('xcshop_goods gs','gs.id = pdg.goods_id','inner')
        ->field('pdg.id');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }

}