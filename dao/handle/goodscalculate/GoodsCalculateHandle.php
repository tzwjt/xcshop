<?php
/**
 * GoodsCalculateHandle.php
 * 商品购销存
 * @date : 2017.8.17
 * @version : v1.0
 */
namespace dao\handle\goodscalculate;

/**
 * 商品购销存
 */
use dao\handle\BaseHandle as BaseHandle;
use dao\model\Goods as GoodsModel;
use dao\model\GoodsSku as GoodsSkuModel;
use dao\model\Order as OrderModel;
use dao\model\OrderGoods as OrderGoodsModel;

class GoodsCalculateHandle extends BaseHandle
{
    /**
     * 添加商品库存(购销存使用)
     * @param  $sku_id
     * @param  $num
     * @param  $cost_price
     */
    public function addGoodsStock($goods_id, $sku_id, $num, $cost_price)
    {
        
    }
    /**
     * 减少商品库存(购买使用)
     * @param  $sku_id  //商品属性
     * @param  $num     //商品数量
     * @param  $cost_price  //减少成本价  通过加权统计
     */
    public function subGoodsStock($goods_id, $sku_id, $num, $cost_price)
    {
        $goods_model = new GoodsModel();
        $stock = $goods_model->getInfo(['id' => $goods_id], 'total');
        if($stock['total'] < $num)
        {
            $this->error = "库存低于出货数";
            return false; //LOW_STOCKS;
            exit();
        }
        if ($sku_id > 0) {
            $goods_sku_model = new GoodsSkuModel();
            $sku_stock = $goods_sku_model->getInfo(['id' => $sku_id], 'stock');
            if ($sku_stock['stock'] < $num) {
                $this->error = "库存低于出货数";
                return false; //LOW_STOCKS;
                //  return LOW_STOCKS;
                exit();
            }
        }
       $retval =  $goods_model->save(['total' => $stock['total']-$num], ['id' => $goods_id]);
        if ($sku_id > 0) {
            $retval = $goods_sku_model->save(['stock' => $sku_stock['stock'] - $num], ['id' => $sku_id]);
        }
        return $retval;
        
    }
    /**
     * 获取商品属性库存
     * @param  $sku_id
     */
    public function getGoodsSkuStock($sku_id){
        $goods_sku_model = new GoodsSkuModel();
        $sku_stock = $goods_sku_model->getInfo(['id' => $sku_id], 'stock');
        return $sku_stock['stock'];
    }
    /**
     * 添加商品销售(销售商品使用)
     * @param  $goods_id
     * @param  $sku_id
     * @param  $num
     */
    public function addGoodsSales($goods_id, $sku_id, $num)
    {
        $goods_model = new GoodsModel();
        $goods_sales = $goods_model->getInfo(['id' => $goods_id], 'sales, sales_real');
        $retval = $goods_model->save(['sales' => $goods_sales['sales'] + $num, 'sales_real' => $goods_sales['sales_real'] + $num], ['id' => $goods_id]);
        return $retval;
    }
    /**
     * 减少商品销售（订单关闭，冲账）
     * @param  $goods_id
     * @param  $sku_id
     * @param  $num
     */
    public function subGoodsSales($goods_id, $sku_id, $num)
    {
        $goods_model = new GoodsModel();
        $goods_sales = $goods_model->getInfo(['id' => $goods_id], 'sales, sales_real');
        $retval = $goods_model->save(['sales' => $goods_sales['sales'] - $num, 'sales_real' => $goods_sales['sales_real'] - $num], ['id' => $goods_id]);
        return $retval;
    }
    /**
     * 获取一段时间内的商品销售详情
     */
    public function getGoodsSalesInfoList($page_index = 1, $page_size = 0, $condition = '', $order = ''){
        $goods_model = new GoodsModel();
        $goods_list = $goods_model->pageQuery($page_index, $page_size, $condition, $order, '*');
        //得到条件内的订单项
        $start_date = strtotime(date('Y-m-d', strtotime('-30 days')));
        $end_date = strtotime(date("Y-m-d H:i:s", time()));
        $order_condition["create_time"] = [[">=",$start_date ],["<=",$end_date ]];
       // $order_condition["shop_id"] = $condition["shop_id"];
        $order_goods_list = $this->getOrderGoodsSelect($order_condition);
        //遍历商品
        foreach($goods_list["data"] as $k=>$v){
            $data= array();
            $goods_sales_num = $this->getGoodsSalesNum($order_goods_list, $v["id"]);
            $goods_sales_money = $this->getGoodsSalesMoney($order_goods_list, $v["id"]);
            $data["sales_num"] =  $goods_sales_num;
            $data["sales_money"] =  $goods_sales_money;
            $goods_list["data"][$k]["sales_info"] = $data;
        }
        return $goods_list;
    }

    /**
     * ok-2ok
     * 一段时间内的商品销售量
     * @param  $condition
     */
    public function getGoodsSalesNum($order_goods_list, $goods_id){
        $sales_num = 0;
        foreach( $order_goods_list as $k=>$v){
            if($v["goods_id"] ==$goods_id ){
                $sales_num = $sales_num + $v["num"];
            }
        }
        return $sales_num;
    }
    /**
     * ok-2ok
     * 一段时间内的商品下单金额
     * @param  $condition
     */
    public function getGoodsSalesMoney($order_goods_list, $goods_id){
        $sales_money = 0;
        foreach( $order_goods_list as $k=>$v){
            if($v["goods_id"] ==$goods_id ){
                $sales_money = $sales_money + ($v["goods_money"] - $v["adjust_money"]);
            }
        }
        return $sales_money;
    }


    /**
     * ok-2ok
     * 一段时间内的订单项
     */
    public function getOrderGoodsSelect($order_condition){
        $order_model = new OrderModel();
        $order_array = $order_model->where($order_condition)->select();
        $order_goods_list =array();
        foreach($order_array as $t=>$b ){
            $order_item = new OrderGoodsModel();
            $item_array = $order_item->where(['order_id' => $b['id']])->select();
            $order_goods_list = array_merge($order_goods_list,$item_array);
        }
        return $order_goods_list;
    }
}
