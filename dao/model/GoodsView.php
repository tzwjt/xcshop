<?php
/**
 * GoodsView -- 商品表视图
 * @date : 2017.8.02
 * @version : v1.0.0.0
 */
namespace dao\model;

use dao\model\BaseModel;
//use data\model\NsGoodsGroupModel as NsGoodsGroupModel;
use dao\model\GoodsSku as GoodsSkuModel;
/**
 * 
 * @author Administrator
 *
 */
class GoodsView extends BaseModel {

    protected $name = "goods";
    
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    /**
     * 获取列表返回数据格式
     * @param unknown $page_index
     * @param unknown $page_size
     * @param unknown $condition
     * @param unknown $order
     * @return unknown
     */
    public function getGoodsViewList($page_index, $page_size, $condition, $order){
        
        $queryList = $this->getGoodsViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getGoodsrViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    
    
    
    /**
     * 查询商品的视图
     * @param unknown $condition
     * @param unknown $field
     * @param unknown $order
     * @return unknown
     */
    public function getGoodsViewQueryField($condition, $field, $order=""){
        $viewObj = $this->alias('gs')
            ->join('xcshop_goods_category gc','gs.category_id = gc.id','left')
          //  ->join('ns_goods_brand ngb','ng.brand_id = ngb.brand_id','left')
            ->join('xcshop_system_album_picture sap','gs.thumb = sap.id', 'left')
            ->field($field);
        $list = $viewObj->where($condition)
        ->order($order)
        ->select();
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
    public function getGoodsViewQuery($page_index, $page_size, $condition, $order)
    {
        //  ->join('ns_shop nss','ng.shop_id = nss.shop_id','left'),
        /*  gs.promotion_type促销类型,gs.promotion_price,  gs.point_exchange_type, gs.point_exchange, gs.give_point,
         gs.min_stock_alarm, gs.clicks,gs.collects,  gs.star, gs.evaluates,  gs.shares,
         
         */
        $viewObj = $this->alias('gs')
        ->join('xcshop_goods_category gc','gs.category_id = gc.id','left')
       // ->join('ns_goods_brand ngb','ng.brand_id = ngb.brand_id','left')
        ->join('xcshop_system_album_picture sap','gs.thumb = sap.id', 'left')
       
        ->field('gs.id, gs.title,gs.sub_title,gs.category_id, gs.promotion_type,
            gs.type,gs.market_price,gs.price,gs.promotion_price,gs.cost_price,gs.point_exchange_type,
            gs.point_exchange,gs.give_point,gs.is_member_discount,gs.is_send_free,gs.dispatch_price,
            gs.dispatch_id,gs.total, gs.max_buy,gs.min_buy,gs.min_stock_alarm, gs.view_count, gs.sales,
             gs.collects, gs.star, gs.evaluates,gs.shares, gs.province, gs.city, gs.thumb,
              gs.product_sn, gs.goods_sn, gs.show_total, gs.has_option, gs.is_hot, gs.is_recommend,
            gs.is_new, gs.is_presell, gs.invoice, gs.status, gs.sale_time, gs.create_time,
            gs.update_time, gs.sort, gs.sales_real,gc.category_name, sap.pic_cover_micro,sap.pic_cover_mid,sap.pic_cover_small');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        if(!empty($list))
        {
           // $goods_group_model = new NsGoodsGroupModel();
            $goods_sku = new GoodsSkuModel();
            foreach ($list as $k=>$v)
            {
               
                //获取group列表
          //      $group_name_query = $goods_group_model->all($v['group_id_array']);
               
            //    $list[$k]['group_query'] = $group_name_query;
                //获取sku列表
          //      $sku_list = $goods_sku->where(['goods_id'=>$v['id']])->select();
                
          //      $list[$k]['sku_list'] = $sku_list;
            }
        }
        return $list;
    }
    
    /**
     * 获取列表数量
     * @param unknown $condition
     */
    public function getGoodsrViewCount($condition)
    {
        $viewObj = $this->alias('gs')
        ->join('xcshop_goods_category gc','gs.category_id = gc.id','left')
       // ->join('ns_goods_brand ngb','ng.brand_id = ngb.brand_id','left')
        ->join('xcshop_system_album_picture sap','gs.thumb = sap.id', 'left')
        ->field('gs.id');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }
    
    
   
    




}