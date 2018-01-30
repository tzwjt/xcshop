<?php

/**
 * GoodsHandle.php
 * @date : 2017.8.09
 * @version : v1.0
 */

namespace dao\handle;

use dao\handle\promotion\GoodsDiscountHandle;
use dao\handle\promotion\GoodsExpressHandle;
use dao\handle\promotion\GoodsMansongHandle;
use dao\handle\promotion\GoodsPreferenceHandle;
use dao\model\CombinationGoods;
use dao\model\GoodsCategory as GoodsCategoryModel;
use dao\model\Goods as GoodsModel;
use dao\model\GoodsView as GoodsViewModel;
use dao\model\GoodsSku as GoodsSkuModel;
use dao\model\GoodsSpec as GoodsSpecModel;
use dao\model\GoodsParam as GoodsParamModel;
use dao\model\GoodsSpecItem as GoodsSpecItemModel;
use dao\model\AlbumPicture as AlbumPictureModel;
use dao\model\AlbumClass as AlbumClassModel;
use dao\model\Cart as CartModel;
use dao\model\CombinationGoods as CombinationGoodsModel;
use dao\model\PromotionDiscount as PromotionDiscountModel;
use dao\model\OrderGoods as OrderGoodsModel;

use dao\model\CouponGoods as CouponGoodsModel;
use dao\model\CouponType as CouponTypeModel;
use dao\model\Coupon as CouponModel;

use dao\handle\GoodsCategoryHandle as GoodsCategoryHandle;
use dao\extend\DikaUtil;
use think\Log;



class GoodsHandle extends BaseHandle
{
    
    /**
     * 根据当前分类ID查询商品分类的三级分类ID
     *
     * @param  $category_id
     */
    private function getGoodsCategoryId($category_id)
    {
        // 获取分类层级
        $goods_category = new GoodsCategoryModel();
        $info = $goods_category->get($category_id);
        if ($info['level'] == 1) {
            return array(
                $category_id,
                0,
                0
            );
        }
        if ($info['level'] == 2) {
            // 获取父级
            return array(
                $info['pid'],
                $category_id,
                0
            );
            
        }
        if ($info['level'] == 3) {
            $info_parent = $goods_category->get($info['pid']);
            // 获取父级
            return array(
                $info_parent['pid'],
                $info['pid'],
                $category_id
            );
            
        }
    }

    /**
     * 根据当前商品分类组装分类名称
     *
     * @param  $category_id_1
     * @param  $category_id_2
     * @param  $category_id_3
     */
    private function getGoodsCategoryName($category_id_1, $category_id_2, $category_id_3)
    {
        $name = '';
        $goods_category = new GoodsCategoryModel();
        $info_1 = $goods_category->getInfo([
            'id' => $category_id_1
        ], 'category_name');
        $info_2 = $goods_category->getInfo([
            'id' => $category_id_2
        ], 'category_name');
        $info_3 = $goods_category->getInfo([
            'id' => $category_id_3
        ], 'category_name');
        if (! empty($info_1['category_name'])) {
            $name = $info_1['category_name'] . ' > ';
        }
        if (! empty($info_2['category_name'])) {
            $name = $name . '' . $info_2['category_name'] . ' > ';
        }
        if (! empty($info_3['category_name'])) {
            $name = $name . '' . $info_3['category_name'];
        }
        return $name;
    }
    
    
    public function addOrEditGoods($goodsParams)
    /*
     $goods_id,$category_id, $ex_category_ids,$sort,$title,$sub_title,
     $short_title,$keywords,$unit,$status,$type,$thumb,$thumb_url,$thumb_first,
     $product_price, $market_price, $cost_price, $sales, $is_new,
     $is_hot, $is_recommand, $is_send_free, $is_no_discount, $cash, $dispatch_type,
     $dispatch_id, $dispatch_price, $invoice, $quality, $repair, $seven, $province,
     $city, $groups_type, $auto_receive, $can_no_trefund, $goods_sn, $product_sn,
     $weight, $show_total, $total_cnf, $has_option,$content,
     $spec_array, $goods_sku_array, $goods_param_array
     */
    
    {
        $goodsModel = new GoodsModel();





        
        $resGoods = true;
        $resSpec = true;
        $resSpecItem = true;
        $resSku = true;
        $resParm = true;
        $resComb = true;
        
        
        $this->error = "";
        $title = "";

        if (isset($goodsParams['title'])) {
            $title = $goodsParams['title'];
        }
        
        if (empty($title)) {
            $this->error ="商品名称不能这空!";
            $resSpec1 = false;
            return false;
            
        }
        $category_id = 0;

        if (isset($goodsParams['category_id'])) {
            $category_id = $goodsParams['category_id'];
        }

        $category_list = "";

        if ($category_id > 0) {
            $category_list = $this->getGoodsCategoryId($category_id);
        }
        #1级扩展分类
        $extend_category_id_1s="";
        #2级扩展分类
        $extend_category_id_2s="";
        #3级扩展分类
        $extend_category_id_3s="";
        $ex_category_ids = '';
        if (isset($goodsParams['ex_category_ids'])) {
            $ex_category_ids = $goodsParams['ex_category_ids'];
        }

        if(!empty($ex_category_ids)){
            $extend_category_id_str=explode(",", $ex_category_ids);
            foreach ($extend_category_id_str as $extend_id){
                $extend_category_list = $this->getGoodsCategoryId($extend_id);
                
                if($extend_category_id_1s===""){
                    $extend_category_id_1s=$extend_category_list[0];
                }else{
                    $extend_category_id_1s=$extend_category_id_1s.",".$extend_category_list[0];
                }
                if($extend_category_id_2s===""){
                    $extend_category_id_2s=$extend_category_list[1];
                }else{
                    $extend_category_id_2s=$extend_category_id_2s.",".$extend_category_list[1];
                }
                if($extend_category_id_3s===""){
                    $extend_category_id_3s=$extend_category_list[2];
                }else{
                    $extend_category_id_3s=$extend_category_id_3s.",".$extend_category_list[2];
                }
            }
        }
        /*
        $cates = '';
        if (empty($ex_category_id)) {
            $cates = $category_id;
        } else {
            $cates = $category_id.','.$extend_category_id;
        }
        */

        if (!empty($category_list)) {
            $goodsParams['category_id'] = $category_id;
            $goodsParams['pcate'] = $category_list[0];
            $goodsParams['ccate'] = $category_list[1];
            $goodsParams['tcate'] = $category_list[2];
        }
        $goodsParams['pcates'] = $extend_category_id_1s;
        $goodsParams['ccates'] = $extend_category_id_2s;
        $goodsParams['tcates'] = $extend_category_id_3s;
        $goodsParams['ex_category_ids'] =  $ex_category_ids;


        if (isset( $goodsParams['total']) && $goodsParams['total'] === -1) {
            $goodsParams['total'] = 0;
            $goodsParams['total_cnf'] = 2;
        }


        if (isset($goodsParams['content'])) {
            $goodsParams['content'] = htmlspecialchars_decode($goodsParams['content']);
        }

        if (isset($goodsParams['type']) && $goodsParams['type'] == 4) {
            $goodsParams['is_comb'] = 1;
        } else {
            $goodsParams['is_comb'] = 0;
        }
        $goodsParams['promotion_price'] = $goodsParams['price'];

        $allowFields = ['category_id','pcate', 'ccate', 'tcate', 'pcates','ccates', 'tcates', 'ex_category_ids',
                        'brand_id',  'sort', 'status',  'title', 'unit', 'sub_title', 'short_title', 'keywords',
                        'type','is_main','is_comb','is_new','is_hot','is_recommend','is_send_free','is_member_discount','product_price',
                        'market_price','cost_price','price', 'promotion_price', 'original_price','thumb','thumb_url','thumb_first','province',
                       'city', 'dispatch_type','dispatch_id','dispatch_price','shipping_fee_type','cash', 'cashier', 'invoice', 'repair',
                       'seven','quality','groups_type','can_no_trefund','auto_receive','is_presell','presell_price',
                       'presell_over','presell_over_time', 'presell_start','presell_time_start','presell_end',
                       'presell_time_end', 'presell_send_type', 'presell_send_start_time', 'presell_send_time',
                       'goods_sn','product_sn','total','total_cnf','show_total','min_stock_alarm','content',
                       'virtual', 'virtual_send','virtual_send_content', 'weight', 'volume', 'has_option',
                      'max_buy','user_max_buy', 'min_buy', 'point_exchange_type','point_exchange', 'give_point','sale_time', 'create_time', 'update_time'];
        
        $this->startTrans();
        try {
            /*
            $data_goods = array(
             //   'category_id' => $category_id,
                //分类

                //    'extend_category_id' => $extend_category_id,
                

                
                //基本信息
                
                'sort' => $goodsParams['sort'],
                'title'=> $goodsParams['title'],
                'sub_title'=>$goodsParams['sub_title'],
                'short_title' => $goodsParams['short_title'],
                'keywords'=>$goodsParams['keywords'],
                'unit'=>$goodsParams['unit'],
                'status' => '', // $goodsParams['status'],
                'type' => $goodsParams['type'],
                'thumb' => $goodsParams['thumb'],
                'thumb_url' => $goodsParams['thumb_url'],
                'thumb_first'=>$goodsParams['thumb_first'],
                'product_price' => $goodsParams['product_price'],
                'market_price'=>$goodsParams['market_price'],
                'cost_price' => $goodsParams['cost_price'],
                'sales' => $goodsParams['sales'],
                'is_new' => $goodsParams['is_new'],
                'is_hot' => $goodsParams['is_hot'],
                'is_recommand' => $goodsParams['is_recommand'],
                'is_send_free' => $goodsParams['is_send_free'],
                'is_no_discount' => $goodsParams['is_no_discount'],
                'cash' => $goodsParams['cash'],
                'dispatch_type' => $goodsParams['dispatch_type'],
                'dispatch_id' => $goodsParams['dispatch_id'],
                'dispatch_price' => $goodsParams['dispatch_price'],
                'invoice' => $goodsParams['invoice'],
                'quality' => $goodsParams['quality'],
                'repair' => $goodsParams['repair'],
                'seven' => $goodsParams['seven'],
                'province' => $goodsParams['province'],
                'city' => $goodsParams['city'],
                'groups_type' => $goodsParams['groups_type'],
                'auto_receive' => $goodsParams['auto_receive'],
                'can_no_trefund' => $goodsParams['can_no_trefund'],
                
                //库存
                'goods_sn' => $goodsParams['goods_sn'],
                'product_sn' => $goodsParams['product_sn'],
                'weight' => $goodsParams['weight'],
                'total' => $goodsParams['total'],
                'show_total' => $goodsParams['show_total'],
                'total_cnf' => $goodsParams['total_cnf'],
                'has_option' => $goodsParams['has_option'],
                
                //详情
                'content' => htmlspecialchars_decode($goodsParams['content'])
                
            );
            */
            /*
            if ($data_goods['total'] === -1) {
                $data_goods['total'] = 0;
                $data_goods['total_cnf'] = 2;
            }
            */
            //商品基本信息保存
            $goods_id = 0;
            if (isset($goodsParams['goods_id'])) {
                $goods_id = $goodsParams['goods_id'];
            }
            if ($goods_id == 0) { //新增商品基本信息
               // $goodsParams['promotion_price'] = $goodsParams['price'];
                $goodsParams['sale_time'] = time();
              //  array_push($allowFields,'promotion_price' );
                $result = $goodsModel->allowField($allowFields)->save($goodsParams);
                if ($result > 0) {
                    $resGoods = true;
                } else {
                    $this->rollback();
                    $this->error ="新增商品基本信息时出出错误,操作失败!";
                    $resGoods = false;
                    return false;
                }
                
            } else { //更新操作
               // $goods1 = $goodsModel->get(goods_id);
                $goods_info1 = $goodsModel->getInfo([
                    'id' => $goods_id
                ], 'status');
               // $status = $goodsParams['status'];
                if ($goods_info1['status'] != $goodsParams['status']) {
                    $goodsParams['sale_time'] = time();
                }
                $result = $goodsModel->allowField($allowFields)->save($goodsParams, [
                    'id' => $goods_id
                ]);
                
                if ($result !== false) {
                    $resGoods = true;
                } else {  //更新商品基本信息
                    $this->rollback();
                    $this->error ="更新商品基本信息时出出错误,操作失败!";
                    $resGoods = false;
                    return false;
                }
            }
            //处量规格
            if (empty($goods_id)) {
                $goods_id = $goodsModel->id;
            }
            // 规格
            $specItems = array();
            $specIds = array();
            $spec_array = array();
            if (isset( $goodsParams['spec_array'])) {
                $spec_array = $goodsParams['spec_array'];
            }
            if (! empty($spec_array)) {
                
                foreach ($spec_array as $spec) {
                    $goodsSpecModel = new GoodsSpecModel();
                    $spec_id = 0;
                    
                    if (isset($spec['spec_id'])) {
                        $spec_id = $spec['spec_id'];
                    }
                    
                    if (empty($spec_id) || $spec_id < 1) {
                        $spec_id = 0;
                    }
                    
                    $data_spec = array(
                        'goods_id' => $goods_id,
                        'title' => $spec['title'],
                        'sort' => $spec['sort']
                    );
                    
                    if ($spec_id == 0) { //新增规格
                        $result = $goodsSpecModel->save($data_spec);
                        
                        if ($result > 0) {
                            $resSpec = true;
                        } else {
                            $this->rollback();
                            $this->error ="新增商品规格时出出错误,操作失败!";
                            $resSpec = false;
                            return false;
                        }
                    } else { //更新规格
                        $result = $goodsSpecModel->save($data_spec, [
                            'id' => $spec_id
                        ]);
                        
                        if ($result !== false) {
                            $resSpec = true;
                        } else {
                            $this->rollback();
                            $this->error ="更新商品规格时出出错误,操作失败!";
                            $resSpec = false;
                            return false;
                        }
                    }
                    if (empty($goods_id)) {
                        $goods_id = $goodsModel->id;
                    }
                    if (empty($spec_id)) {
                        $spec_id =  $goodsSpecModel->id;
                    }
                    array_push($specIds,$spec_id);
                    
                    $spec_item_array = array();
                    if (!empty($spec['spec_item_array'])) {
                        $spec_item_array =$spec['spec_item_array'];
                    }
                    
                    // 规格项
                    $ids=array();
                    if (! empty($spec_item_array)) {
                        foreach ($spec_item_array as $spec_item) {
                            $goodsSpecItemModel = new GoodsSpecItemModel();
                            $spec_item_id = 0;
                            if (isset($spec_item['spec_item_id'])) {
                                $spec_item_id = $spec_item['spec_item_id'];
                            }
                            
                            if (empty($spec_item_id) || $spec_item_id < 1) {
                                $spec_item_id = 0;
                            }
                            
                            $item_id = $spec_item['item_id'];
                            $data_spec_item = array(
                                'spec_id' => $spec_id,
                                'title' => $spec_item['title'],
                                'thumb' => $spec_item['thumb'],
                                //   'is_show' => $v['is_show'],
                                'sort' => $spec_item['sort']
                            );
                            
                            if ($spec_item_id == 0) { //新增规格项
                                $result = $goodsSpecItemModel->save($data_spec_item);
                                
                                if ($result > 0) {
                                    $resSpecItem = true;
                                } else {
                                    $this->rollback();
                                    $this->error ="新增规格项时出现错误，操作失败!";
                                    $resSpecItem = false;
                                    return false;
                                }
                            } else { //更新规格项
                                $result = $goodsSpecItemModel->save($data_spec_item, [
                                    'id' => $spec_item_id
                                ]);
                                
                                if ($result !== false) {
                                    $resSpecItem = true;
                                } else {
                                    $this->rollback();
                                    $this->error ="更新规格项时出现错误, 操作失败!";
                                    $resSpecItem = false;
                                    return false;
                                }
                            }
                            
                            if (empty($spec_item_id)) {
                                $spec_item_id = $goodsSpecItemModel->id;
                            }
                            array_push($ids, $spec_item_id);
                            
                            array_push($specItems, array("item_id"=>$item_id, "spec_item_id"=>$spec_item_id));//
                            
                        }
                        $content = serialize($ids);
                        $result1 = $goodsSpecModel->save(['content'=> $content], [
                            'id' => $spec_id
                        ]);
                        
                        
                        if (($result1 !== false)) {
                            $resSpec1 = true;
                        } else {
                            $this->rollback();
                            $this->error ="更新规格项时出现错误, 操作失败!";
                            $resSpec1 = false;
                            return false;
                        }
                    }  //删除不需要的规格项
                    $goodsSpecItemModel = new GoodsSpecItemModel();
                    if (!empty($ids)) {

                        $idsStr = implode(",", $ids);
                        $condition = array(
                          'spec_id' => $spec_id,
                           'id' => array('not in', $idsStr)
                        );
                        Log::log("idsStr:".$idsStr);
                         $result2 = $goodsSpecItemModel->where($condition)->delete();
                      //  $result2 = $goodsSpecItemModel->where('id', 'not in', $idsStr)->delete();
                    } else {
                        $result2 = $goodsSpecItemModel->where('spec_id', '=', $spec_id)->delete();
                    }
                    
                    if (($result2 !== false)) {
                        $resSpecItem1 = true;
                    } else {
                        $this->rollback();
                        $this->error ="删除规格项时出现错误, 操作失败!";
                        $resSpecItem1 = false;
                        return false;
                    }
                }
                
                // 商品sku
                $skuIds = array();
                if (isset($goodsParams['goods_sku_array'])) {
                    $goods_sku_array = $goodsParams['goods_sku_array'];
                }
                $totalstocks = 0;
                if (! empty($goods_sku_array)) {
                    if (empty($goods_id)) {
                        $goods_id = $goodsModel->id;
                    }
                    foreach ($goods_sku_array as $goods_sku) {
                        $goodsSkuModel = new GoodsSkuModel();
                        $sku_id = 0;
                        if (isset($goods_sku['sku_id'])) {
                            $sku_id = $goods_sku['sku_id'];
                        }
                        
                        if (empty($sku_id) || $sku_id < 1) {
                            $sku_id = 0;
                        }
                        
                        $sku_item_id = $goods_sku['sku_item_id'];  //
                        $sku_item_id_array = explode(",", $sku_item_id);
                        $specs = '';
                        foreach ($sku_item_id_array as $v1) {
                            foreach ($specItems as $v2) {
                                if ($v1 == $v2["item_id"]) {
                                    if (empty($specs)) {
                                        $specs = $specs.$v2["spec_item_id"];
                                    } else {
                                        $specs = $specs.'_'.$v2["spec_item_id"];
                                    }
                                }
                                
                            }
                            
                        }
                        
                        $data_sku = array(
                            'goods_id' => $goods_id,
                            'title' => $goods_sku['title'],
                            'thumb' => $goods_sku['thumb'],
                            'presell_price' => $goods_sku['presell_price'],
                           // 'product_price' => $goods_sku['product_price'],
                            'market_price' => $goods_sku['market_price'],
                             'price' => $goods_sku['price'],
                            'cost_price' => $goods_sku['cost_price'],
                             'promotion_price' => $goods_sku['price'],
                            'stock' => $goods_sku['stock'],
                            'weight' => $goods_sku['weight'],
                            'volume' => $goods_sku['volume'],
                            //   'is_show' => $goods_sku['is_show'],
                            'sort' => $goods_sku['sort'], //暂时
                            'specs' => $specs,
                            'goods_sn' => $goods_sku['goods_sn'],
                            'product_sn' => $goods_sku['product_sn'],
                            //  'virtual' => $goods_sku['virtual'],
                            
                        );
                        
                        if(!empty($goodsParams['has_option'])){
                            $totalstocks+=$data_sku['stock'];
                        }
                        
                        if ($sku_id == 0) { //新增商品SKU
                            
                            $result = $goodsSkuModel->save($data_sku);
                            
                            if ($result > 0) {
                                $resSku = true;
                            } else {
                                $this->rollback();
                                $this->error ="新增商品sku时出现错误，操作失败!";
                                $resSku = false;
                                return false;
                            }
                        } else { //更新规商品SKU
                            $result = $goodsSkuModel->save($data_sku, [
                                'id' => $sku_id
                            ]);
                            
                            if ($result !== false) {
                                $resSku = true;
                            } else {  //
                                $this->rollback();
                                $this->error ="更新商品sku时出现错误，操作失败!";
                                $resSku = false;
                                return false;
                            }
                        }
                        if (empty($sku_id)) {
                            $sku_id = $goodsSkuModel->id;
                        }
                        array_push($skuIds, $sku_id);
                    }
                    
                    //总库存
                    if ( ($totalstocks > 0) && ($goodsParams['total_cnf'] != 2) ) {
                        $result = $goodsModel->save(['total'=> $totalstocks], [
                            'id' => $spec_id
                        ]);
                        
                        if ($result !== false) {
                            $resGoods2 = true;
                        } else {  //
                            $this->rollback();
                            $this->error ="更新商品总库存时出现错误，操作失败!";
                            $resGoods2 = false;
                            return false;
                        }
                    }
                }
                
                //删除不需要的sku项
                $goodsSkuModel = new GoodsSkuModel();
                if (!empty($skuIds)) {
                    $idsStr = implode(",", $skuIds);
                    $condition = array(
                        'goods_id' => $goods_id,
                        'id' => array('not in', $idsStr)
                    );
                 //   $result = $goodsSkuModel->where('id', 'not in', $idsStr)->delete();
                    $result = $goodsSkuModel->where($condition)->delete();
                } else {
                    $result = $goodsSkuModel->where('goods_id', '=', $goods_id)->delete();
                }
                
                if ($result !== false) {
                    $resSku2 = true;
                } else {
                    $this->rollback();
                    $this->error ="删除不需要的商品sku时出现错误, 操作失败!";
                    $resSku2 = false;
                    return false;
                }
            }
            
            //删除不需要的规格
            $goodsSpecModel = new GoodsSpecModel();
            if (!empty($specIds)) {
                $idsStr = implode(",", $specIds);
                $condition = array(
                    'goods_id' => $goods_id,
                    'id' => array('not in', $idsStr)
                );
                $result = $goodsSpecModel->where($condition)->delete();
              //  $result = $goodsSpecModel->where('id', 'not in', $idsStr)->delete();
            } else {
                $result = $goodsSpecModel->where('goods_id', '=', $goods_id)->delete();
            }
            
            if ($result !== false) {
                $resSpec3 = true;
            } else {
                $this->rollback();
                $this->error ="删除不需要的规格时出现错误, 操作失败!";
                $resSpec3 = false;
                return false;
            }
            
            if (empty($goods_id)) {
                $goods_id = $goodsModel->id;
            }
            
            // 商品参数
            $paramIds = array();
            $goods_param_array = array();
            if (isset($goodsParams['goods_param_array'])) {
                $goods_param_array = $goodsParams['goods_param_array'];
            }
            if (! empty($goods_param_array)) {
                
                foreach ($goods_param_array as $goods_param) {
                    $goodsParamModel = new GoodsParamModel();
                    $param_id = 0;
                    if (isset($goods_param['param_id'])) {
                        $param_id = $goods_param['param_id'];
                    }
                    
                    if (empty($param_id) || $param_id < 1) {
                        $param_id = 0;
                    }
                    
                    $data_param = array(
                        'goods_id' => $goods_id,
                        'title' => $goods_param['title'],
                        'value' => $goods_param['value'],
                        'sort' => $goods_param['sort'],
                        
                    );
                    
                    if ($param_id == 0) { //新增商品属性
                        
                        $result = $goodsParamModel->save($data_param);
                        
                        if ($result > 0) {
                            $resParam = true;
                        } else {
                            $this->rollback();
                            $this->error ="新增商品参数时出现错误，操作失败!";
                            $resParam = false;
                            return false;
                        }
                    } else { //更新规商品参数
                        $result = $goodsParamModel->save($data_param, [
                            'id' => $param_id
                        ]);
                        
                        if ($result !== false) {
                            $resParam = true;
                        } else {
                            $this->rollback();
                            $this->error ="更新商品参数时出现错误，操作失败!";
                            $resParam = false;
                            return false;
                        }
                    }
                    if (empty($param_id)) {
                        $param_id = $goodsParamModel->id;
                    }
                    array_push($paramIds,  $param_id);
                }
            }
            
            //删除不需要的参数
            $goodsParamModel = new GoodsParamModel();
            if (!empty($paramIds)) {
                $idsStr = implode(",", $paramIds);
                $condition = array(
                    'goods_id' => $goods_id,
                    'id' => array('not in', $idsStr)
                );
                $result = $goodsParamModel->where($condition)->delete();
                //$result = $goodsParamModel->where('id', 'not in', $idsStr)->delete();
            } else {
                $result = $goodsParamModel->where('goods_id', '=', $goods_id)->delete();
            }
            
            if ($result !== false) {
                $resParam1 = true;
            } else {
                $this->rollback();
                $this->error ="删除不需要的参数出现错误, 操作失败!";
                $resParam1 = false;
                return false;
            }

            // 处理组合商品
            $combIds = array();
            $comb_goods_array = array();
            if (isset($goodsParams['comb_goods_array'])) {
                $comb_goods_array = $goodsParams['comb_goods_array'];
            }
            if (! empty($comb_goods_array)) {

                foreach ($comb_goods_array as $comb_goods) {
                    $combGoodsModel = new CombinationGoodsModel();
                    $comb_id = 0;
                    if (isset($comb_goods['comb_id'])) {
                        $comb_id = $comb_goods['comb_id'];
                    }

                    if (empty($comb_id) || $comb_id < 1) {
                        $comb_id = 0;
                    }

                    $data_comb = array(
                        'goods_id' => $goods_id,
                        'comb_goods_id' => $comb_goods['comb_goods_id'],
                      //  'title' => $goods_param['title'],
                        'sort' => $comb_goods['sort'],
                     //   'thumb' => $goods_param['thumb'],
                      //  'price' => $goods_param['price'],
                      //  'unit' => $goods_param['unit'],
                        'num' => $comb_goods['num'],
                     //   'market_price' => $goods_param['market_price'],
                     //   'combination_price' => $goods_param['combination_price'],

                    );

                    if ($comb_id == 0) { //新增组合商品

                        $result = $combGoodsModel->save($data_comb);

                        if ($result > 0) {
                            $resComb = true;
                        } else {
                            $this->rollback();
                            $this->error ="新增组合商品时出现错误，操作失败!";
                            $resComb = false;
                            return false;
                        }
                    } else { //更新组合商品
                        $result = $combGoodsModel->save($data_comb, [
                            'id' => $comb_id
                        ]);

                        if ($result !== false) {
                            $resComb = true;
                        } else {
                            $this->rollback();
                            $this->error ="更新组合商品时出现错误，操作失败!";
                            $resComb = false;
                            return false;
                        }
                    }
                    if (empty($comb_id)) {
                        $comb_id = $combGoodsModel->id;
                    }
                    array_push($combIds,  $comb_id);
                }
            }

            //删除不需要的组合商品
            $combGoodsModel = new CombinationGoodsModel();
            if (!empty($combIds)) {
                $idsStr = implode(",", $combIds);
                $condition = array(
                    'goods_id' => $goods_id,
                    'id' => array('not in', $idsStr)
                );
                $result = $combGoodsModel->where($condition)->delete();
                //$result = $goodsParamModel->where('id', 'not in', $idsStr)->delete();
            } else {
                $result = $combGoodsModel->where('goods_id', '=', $goods_id)->delete();
            }

            if ($result !== false) {
                $resComb1 = true;
            } else {
                $this->rollback();
                $this->error ="删除不需要的组合商品时出现错误, 操作失败!";
                $resComb1 = false;
                return false;
            }
            
            
            if (!($resGoods && $resSpec && $resSpecItem && $resSku && $resParm && $resComb)) {
                $this->rollback();
                $this->error ="操作时出现错误, 操作失败!";
                return false;
            }
            
            $this->commit();
            return $goods_id;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error ="操作时出现异常:".$e->getMessage().$e->getTraceAsString();
            return false;
        }
        
    }

    /**
     * ok-2ok
     * 修改 商品的 促销价格
     */
    public function modifyGoodsPromotionPrice($goods_id)
    {
        $discount_goods = new GoodsDiscountHandle();
        $goods = new GoodsModel();
        $goods_sku = new GoodsSkuModel();
        $discount = $discount_goods->getDiscountByGoodsid($goods_id);
        if ($discount == - 1) {
            // 当前商品没有参加活动
        } else {
            // 当前商品有正在进行的活动
            // 查询出商品的价格进行修改
            $goods_price = $goods->getInfo([
                'id' => $goods_id
            ], 'price');
            $goods->save([
                'promotion_price' => $goods_price['price'] * $discount / 10
            ], [
                'id' => $goods_id
            ]);
            // 查询出所有的商品sku价格进行修改
            $goods_sku_list = $goods_sku->getConditionQuery([
                'goods_id' => $goods_id
            ], 'id, price', '');
            foreach ($goods_sku_list as $k => $v) {
                $goods_sku = new GoodsSkuModel();
                $goods_sku->save([
                    'promotion_price' => $v['price'] * $discount / 10
                ], [
                    'id' => $v['id']
                ]);
            }
        }
    }




    /**
     * 获取指定条件下商品列表
     *
     * @param number $page_index
     * @param number $page_size
     * @param string $condition
     * @param string $order
     */
    public function getGoodsList($page_index = 1, $page_size = 0, $condition = '',  $order = '')
    {
        $goods_view_model = new GoodsViewModel();
        // 针对商品分类
        if (! empty($condition['gs.category_id'])) {
            $goods_category_handle = new GoodsCategoryHandle();
            $category_list = $goods_category_handle->getCategoryTreeList($condition['gs.category_id']);
            unset($condition['gs.category_id']);
            $query_goods_ids="";
            $goods_list=$goods_view_model->getGoodsViewQueryField($condition, "gs.id");
            if(!empty($goods_list) && count($goods_list)>0){
                foreach ($goods_list as $goods_obj){
                    if($query_goods_ids===""){
                        $query_goods_ids=$goods_obj["id"];
                    }else{
                        $query_goods_ids=$query_goods_ids.",".$goods_obj["id"];
                    }
                }
                $extend_query="";
                $category_str=explode(",", $category_list);
                foreach ( $category_str as $category_id){
                    if($extend_query===""){
                        $extend_query=" FIND_IN_SET( ".$category_id.",gs.ex_category_ids) ";
                    }else{
                        $extend_query=$extend_query." or FIND_IN_SET( ".$category_id.",gs.ex_category_ids) ";
                    }
                }
                $condition=" gs.id in (".$query_goods_ids.") and ( gs.category_id in (".$category_list.") or ".$extend_query.")";
            }
        }
      //  $goods_view = new NsGoodsViewModel();
        $list = $goods_view_model->getGoodsViewList($page_index, $page_size, $condition, $order);
        if (! empty($list['data'])) {
            // 用户针对商品的收藏
            foreach ($list['data'] as $k => $v) {
                /*
                if (! empty($this->uid)) {
                    $member = new Member();
                    $list['data'][$k]['is_favorite'] = $member->getIsMemberFavorites($this->uid, $v['goods_id'], 'goods');
                } else {
                    $list['data'][$k]['is_favorite'] = 0;
                }
                */
                // 查询商品单品活动信息
                $goods_preference = new GoodsPreferenceHandle();
                $goods_promotion_info = $goods_preference->getGoodsPromote($v['id']);
                $list["data"][$k]['promotion_info'] = $goods_promotion_info;

            }
        }
        return $list;
    }

    /**
     * 获取某种条件下商品数量
     *
     * @param  $condition
     */
    public function getGoodsCount($condition)
    {
        $goodsModel = new GoodsModel();
        $count = $goodsModel->where($condition)->count();
        return $count;
    }

    /**
     * 获取商品的sku信息
     *
     * @param  $goods_id
     */
    public function getGoodsSku($goods_id)
    {
        $goods_sku = new GoodsSkuModel();
        $list = $goods_sku->get([
            'goods_id' => $goods_id
        ]);
        return $list;
    }

    /**
     * 获取商品的主图片信息
     *
     * @param  $goods_id
     */
    public function getGoodsImg($goods_id)
    {
        $goods = new GoodsModel();
        $goods_info = $goods->getInfo([
            'id' => $goods_id
        ], 'thumb');
        $pic_info = array();
        if (! empty($goods_info)) {
            $picture = new AlbumPictureModel();
            $pic_info['pic_cover'] = '';
            if (! empty($goods_info['thumb'])) {
                $pic_info = $picture->get($goods_info['thumb']);
            }
        }
        return $pic_info;
    }

    /**
     * ok-2ok
     * 商品下架
     *
     * @param  $condition
     */
    public function modifyGoodsOffline($condition)
    {
        $goods = new GoodsModel();
        $data = array(
            "status" => 0,
            'sale_time' => time(),
            'update_time' => time()
        );
        $result = $goods->save($data, "id  in($condition)");
        if ($result > 0) {
            return  true; //SUCCESS;
        } else {
            return false;  //UPDATA_FAIL;
        }
    }

    /**
     * ok-2ok
     * 商品上架
     *
     * @param  $condition
     */
    public function modifyGoodsOnline($condition)
    {
        $goods = new GoodsModel();
        $data = array(
            "status" => 1,
            'sale_time' => time(),
            'update_time' => time()
        );
        $result = $goods->save($data, "id  in($condition)");
        if ($result > 0) {
            return true; //SUCCESS;
        } else {
            return false; //UPDATA_FAIL;
        }
    }

    /*
     * ok-2ok
    * 删除商品
    */
    public function deleteGoods($goods_ids)
    {
        $goods = new GoodsModel();
        $data = array(
            "status" => -1,
            'update_time' => time()
        );
        $result = $goods->save($data, array(
            "id"=>['in', $goods_ids]
        ));

        if (empty($result)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 获取单条商品的详细信息
     *
     * @param  $goods_id
     */
    public function getGoodsDetail($goods_id, $user_id=0)
    {
        // 查询商品主表
        $goods = new GoodsModel();
        $goods_detail = $goods->get($goods_id);
        if ($goods_detail == null) {
            return null;
        }
        /*促销信息*/

        $goods_preference = new GoodsPreferenceHandle();
        if (! empty($user_id)) {
            $member_discount = $goods_preference->getMemberLevelDiscount($user_id);
        } else {
            $member_discount = 1;
        }

        // 查询商品会员价

        if ($member_discount == 1) {
            $goods_detail['is_show_member_price'] = 0;
        } else {
            $goods_detail['is_show_member_price'] = 1;
        }


        $member_price = $member_discount * $goods_detail['price'];
        $goods_detail['member_price'] = $member_price;

        // 查询商品分组表
        /*
        $goods_group = new NsGoodsGroupModel();
        $goods_group_list = $goods_group->all($goods_detail['group_id_array']);
        $goods_detail['goods_group_list'] = $goods_group_list;
        */
        // 查询商品sku表
        $goods_sku = new GoodsSkuModel();
        $goods_sku_detail = $goods_sku->where('goods_id=' . $goods_id)->select();

        foreach ($goods_sku_detail as $k => $goods_sku) {
            $goods_sku_detail[$k]['member_price'] = $goods_sku['price'] * $member_discount;
        }

        $goods_detail['sku_list'] = $goods_sku_detail;
      /* $spec_list = json_decode($goods_detail['goods_spec_format'], true);
        if (! empty($spec_list)) {
            foreach ($spec_list as $k => $v) {
                foreach ($v["value"] as $m => $t) {
                    if (empty($t["spec_show_type"])) {
                        $spec_list[$k]["value"][$m]["spec_show_type"] = 1;
                    }
                }
            }
        }
        */
        //规格
        $goods_spec = new GoodsSpecModel();
        $spec_list = $goods_spec->where('goods_id=' . $goods_id)->select();

        if (! empty($spec_list)) {
            $goods_spec_item = new GoodsSpecItemModel();
            foreach ($spec_list as $spec_k=>$spec_v) {
                $spec_item_list = $goods_spec_item->where('spec_id=' . $spec_v['id'])->select();
                $spec_list[$spec_k]['spec_item_list']=$spec_item_list;
                $spec_list[$spec_k]['content']=unserialize($spec_v['content']);
              //  $spec_v['spec_item_list']=$spec_item_list;

            }
        }
        $goods_detail['spec_list'] = $spec_list;

        //参数
        $goods_param = new GoodsParamModel();
        $param_list = $goods_param->where('goods_id=' . $goods_id)->select();
        $goods_detail['param_list'] = $param_list;



        $is_comb =  $goods_detail['is_comb'];
        $total_price = 0;
        $total_market_price = 0;
       // $total_

   //     '','cost_price','price','promotion_price'
        if ($is_comb == 1) {
            $comb_goods = new CombinationGoodsModel();
            $comb_goods_list = $comb_goods->where('goods_id=' . $goods_id)->select();
            $goods_detail['comb_goods_list'] = $comb_goods_list;
          //  $comb_price = 0;
           // $comb_market_price = 0;
           // $comb_cost_price = 0;
           // $comb_promotion_price = 0;
            if (!empty($comb_goods_list)) {
                foreach ($comb_goods_list as $combK => $combV) {
                    $comb_goods_id = $combV['comb_goods_id'];
                     $comb_goods = $this->getGoodsDetail($comb_goods_id);
                    $comb_goods_list[$combK]['title'] = $comb_goods['title'];
                    $comb_goods_list[$combK]['unit'] = $comb_goods['unit'];
               // $comb_goods_list[$combK]['price'] = $comb_goods['price'];
                    $comb_goods_list[$combK]['thumb'] = $comb_goods['thumb_info'];
                    $comb_goods_num =   $comb_goods_list[$combK]['num'];

                     $comb_goods_list[$combK]['price'] = $comb_goods['price']; // * $comb_goods_num;
                    $comb_goods_list[$combK]['market_price'] = $comb_goods['market_price']; // * $comb_goods_num;
                    $comb_goods_list[$combK]['cost_price'] = $comb_goods['cost_price'];//  * $comb_goods_num;
                    $comb_goods_list[$combK]['promotion_price'] = $comb_goods['promotion_price'];//  * $comb_goods_num;
                    if (empty( $comb_goods['spec_list'])) {
                        $comb_goods_list[$combK]['spec_list'] = array();
                    } else {
                        $comb_goods_list[$combK]['spec_list'] = $comb_goods['spec_list'];
                    }




                 //    $comb_price =  $comb_price + $comb_goods_list[$combK]['price'];
                //    $comb_market_price = $comb_market_price + $comb_goods_list[$combK]['market_price'];
                 //   $comb_cost_price = $comb_cost_price + $comb_goods_list[$combK]['cost_price'];
                 //   $comb_promotion_price = $comb_promotion_price + $comb_goods_list[$combK]['promotion_price'];

                }
             }
           // $goods_detail['price'] = $comb_price;
          //  $goods_detail['market_price'] = $comb_market_price;
          //  $goods_detail['cost_price'] = $comb_cost_price;
           // $goods_detail['promotion_price'] = $comb_promotion_price;
        }
      //  $comb_goods_id =

       // $goods_detail['spec_list'] = $spec_list;
        // 查询图片表
        $goods_img = new AlbumPictureModel();
        $order = "instr('," . $goods_detail['thumb_url'] . ",',CONCAT(',',id,','))"; // 根据 in里边的id 排序
        $goods_img_list = $goods_img->getConditionQuery([
            'id' => [
                "in",
                $goods_detail['thumb_url']
            ]
        ], '*', $order);
         $img_temp_array = array();
        if (trim($goods_detail['thumb_url']) != "") {
            $img_array = array();
           
            $img_array = explode(",", $goods_detail['thumb_url']);
            foreach ($img_array as $k => $v) {
                if (! empty($goods_img_list)) {
                    foreach ($goods_img_list as $t => $m) {
                        if ($m["id"] == $v) {
                            $img_temp_array[] = $m;
                        }
                    }
                }
            }
        }
        $goods_picture = $goods_img->get($goods_detail['thumb']);
        $goods_detail["thumb_url_temp_array"] = $img_temp_array;
        $goods_detail['thumb_url_list'] = $goods_img_list;
        $goods_detail['thumb_info'] = $goods_picture;
        // 查询分类名称
        $category_name = $this->getGoodsCategoryName($goods_detail['pcate'], $goods_detail['ccate'], $goods_detail['tcate']);
        $goods_detail['category_name'] = $category_name;
        // 扩展分类
        $extend_category_array=array();

        if(!empty($goods_detail['ex_category_ids'])){
            $extend_category_ids=$goods_detail['ex_category_ids'];
            $extend_category_id_1s=$goods_detail['pcates'];
            $extend_category_id_2s=$goods_detail['ccates'];
            $extend_category_id_3s=$goods_detail['tcates'];
            $extend_category_id_str=explode(",", $extend_category_ids);
            $extend_category_id_1s_str=explode(",", $extend_category_id_1s);
            $extend_category_id_2s_str=explode(",", $extend_category_id_2s);
            $extend_category_id_3s_str=explode(",", $extend_category_id_3s);
            foreach ($extend_category_id_str  as $k=>$v){
                $extend_category_name = $this->getGoodsCategoryName($extend_category_id_1s_str[$k], $extend_category_id_2s_str[$k], $extend_category_id_3s_str[$k]);
                $extend_category_array[]=array(
                    "ex_category_name"=>$extend_category_name,
                    "ex_category_id"=>$v,
                    "ex_category_id_1"=>$extend_category_id_1s_str[$k],
                    "ex_category_id_2"=>$extend_category_id_2s_str[$k],
                    "ex_category_id_3"=>$extend_category_id_3s_str[$k]
                );
            }

        }
        $goods_detail['ex_category_name'] = "";
        $goods_detail['ex_category'] = $extend_category_array;

        // 查询商品类型相关信息
        /*
        if ($goods_detail['goods_attribute_id'] != 0) {
            $attribute_model = new NsAttributeModel();
            $attribute_info = $attribute_model->getInfo([
                'attr_id' => $goods_detail['goods_attribute_id']
            ], 'attr_name');
            $goods_detail['goods_attribute_name'] = $attribute_info['attr_name'];
            $goods_attribute_model = new NsGoodsAttributeModel();
            $goods_attribute_list = $goods_attribute_model->getConditionQuery([
                'goods_id' => $goods_id
            ], '*', '');

            $goods_detail['goods_attribute_list'] = $goods_attribute_list;
        } else {
            $goods_detail['goods_attribute_name'] = '';
            $goods_detail['goods_attribute_list'] = array();
        }
        */
        // 查询商品单品活动信息

        $goods_preference = new GoodsPreferenceHandle();
        $goods_promotion_info = $goods_preference->getGoodsPromote($goods_id);
        if (! empty($goods_promotion_info)) {
            $goods_discount_info = new PromotionDiscountModel();
            $goods_detail['promotion_detail'] = $goods_discount_info->getInfo([
                'id' => $goods_detail['promote_id']
            ], 'start_time, end_time,discount_name');
        }

        // 判断活动内容是否为空
        if (! empty($goods_detail['promotion_detail'])) {
           // $begin = getTimeTurnTimeStamp($goods_detail['promotion_detail']['start_time']);
            $begin = $goods_detail['promotion_detail']['start_time'];
           Log::write($goods_detail['promotion_detail']['start_time']);
            $tem=(string)$goods_detail;

            $goods_detail=json_decode($tem,true);
            $goods_detail['promotion_detail']['start_time_stamp'] = ($goods_detail['promotion_detail']['start_time']);//  getTimeTurnTimeStamp($goods_detail['promotion_detail']['start_time']);
            $goods_detail['promotion_detail']['end_time_stamp'] = ($goods_detail['promotion_detail']['end_time']); // getTimeTurnTimeStamp($goods_detail['promotion_detail']['end_time']);
            $goods_detail['promotion_detail']['cur_time_stamp'] = time();
            $goods_detail['promotion_detail']['time_diff'] = time() - $begin;


            $goods_detail['promotion_info'] = $goods_promotion_info;
        } else {
            $goods_detail['promotion_info'] = "";
        }

        // 查询商品满减送活动

        $goods_mansong = new GoodsMansongHandle();
        $goods_detail['mansong_name'] = $goods_mansong->getGoodsMansongName($goods_id);

        // 查询满额包邮活动
        $full = new PromotionHandle();
        $baoyou_info = $full->getPromotionFullMail();
        if ($baoyou_info['is_open'] == 1) {
            if ($baoyou_info['full_mail_money'] == 0) {
                $goods_detail['baoyou_name'] = '全场包邮';
            } else {
                $goods_detail['baoyou_name'] = '满' . $baoyou_info['full_mail_money'] . '元包邮';
            }
        } else {
            $goods_detail['baoyou_name'] = '';
        }




        $goods_express = new GoodsExpressHandle();
     //   $goods_detail['shipping_fee_name'] = $goods_express->getGoodsExpressTemplate($goods_id, 1, 1, 1);


/*
        $shop_model = new NsShopModel();
        $shop_name = $shop_model->getInfo(array(
            "shop_id" => $goods_detail["shop_id"]
        ), "shop_name");
        $goods_detail["shop_name"] = $shop_name["shop_name"];
        */
        //查询商品规格图片
        /*
        $goos_sku_picture = new NsGoodsSkuPictureModel();
        $goos_sku_picture_query = $goos_sku_picture->getConditionQuery(["goods_id"=>$goods_id], "*", '');

        $album_picture = new AlbumPictureModel();
        foreach($goos_sku_picture_query as $k=>$v){
            if($v["sku_img_array"] != ""){
                $spec_name = '';
                $spec_value_name = '';
                foreach($spec_list as $t=>$m){
                    if($m["spec_id"] == $v["spec_id"]){
                        foreach($m["value"] as $c=>$b){
                            if($b["spec_value_id"] == $v["spec_value_id"] ){
                                $spec_name = $b["spec_name"];
                                $spec_value_name = $b["spec_value_name"];
                            }
                        }
                    }
                }
                $goos_sku_picture_query[$k]["spec_name"] = $spec_name;
                $goos_sku_picture_query[$k]["spec_value_name"] = $spec_value_name;
                $tmp_img_array = $album_picture->getConditionQuery(["pic_id"=>["in",$v["sku_img_array"]]], "*", '');
                $pic_id_array = explode(',',(string)$v["sku_img_array"]);
                $goos_sku_picture_query[$k]["sku_picture_query"] = array();
                //var_dump($pic_id_array);
                $sku_picture_query_array = array();
                foreach($pic_id_array as $t=>$m){
                    foreach($tmp_img_array as $q=>$z){
                        if($m == $z["pic_id"]){
                            //var_dump($z);
                            $sku_picture_query_array[] = $z;
                        }
                    }
                }
                $goos_sku_picture_query[$k]["sku_picture_query"] = $sku_picture_query_array;
                //$goos_sku_picture_query[$k]["sku_picture_query"] = $album_picture->getConditionQuery(["pic_id"=>["in",$v["sku_img_array"]]], "*", '');
            }else{
                unset($goos_sku_picture_query[$k]);
            }
        }
        sort($goos_sku_picture_query);
        $goods_detail["sku_picture_array"] = $goos_sku_picture_query;
        */

        // 查询商品的已购数量
        $orderGoods = new OrderGoodsModel();
        $num = 0;
        $num = $orderGoods->getSum([
            "goods_id" => $goods_id,
            "buyer_id" => $user_id,
            "order_status" => array(
                "neq",
                5
            )
        ], "num");
        $goods_detail["purchase_num"] = $num;


        return $goods_detail;

    }

    /**
     * 获取条件查询出商品
     */
    public function getSearchGoodsList($page_index = 1, $page_size = 0, $condition = '', $order = '', $field = '*')
    {
        $goods = new GoodsModel();
        $result = $goods->pageQuery($page_index, $page_size, $condition, $order, $field);
        foreach ($result['data'] as $k => $v) {
            $picture = new AlbumPictureModel();
            $pic_info = array();
            $pic_info['pic_cover'] = '';
            if (! empty($v['thumb'])) {
                $pic_info = $picture->get($v['thumb']);
            }
            $result['data'][$k]['thumb_info'] = $pic_info;
        }
        return $result;
    }

    /**
     * 修改商品 推荐 1=热销 2=推荐 3=新品
     */
    public function modifyGoodsRecommend($goods_ids, $goods_type)
    {

        $this->startTrans();
        try {
            $goods_id_array = explode(',', $goods_ids);
            $goods_type = explode(',', $goods_type);
            $data = array(
                "is_new" => $goods_type[0],
                "is_recommend" => $goods_type[1],
                "is_hot" => $goods_type[2]
            );
            foreach ($goods_id_array as $k => $v) {
                $goods = new GoodsModel();
                $goods->save($data, [
                    'id' => $v
                ]);
            }
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = "操作时发生异常:". $e->getMessage().",操作失败";
            return false;
        }
    }

    /**
     * ok-2ok
     * 获取商品可得积分
     */
    public function getGoodsGivePoint($goods_id)
    {
        $goods = new GoodsModel();
        $point_info = $goods->getInfo([
            'id' => $goods_id
        ], 'give_point');
        return $point_info['give_point'];
    }

    /**
     * 通过商品skuid查询goods_id
     */
    public function getGoodsId($sku_id)
    {
        $goods_sku = new GoodsSkuModel();
        $sku_info = $goods_sku->getInfo([
            'id' => $sku_id
        ], 'goods_id');
        return $sku_info['goods_id'];
    }

    /**
     * 查询商品积分兑换
     */
    public function getGoodsPointExchange($goods_id)
    {
        $goods_model = new GoodsModel();
        $goods_info = $goods_model->getInfo([
            'id' => $goods_id
        ], 'point_exchange_type,point_exchange');
        if ($goods_info['point_exchange_type'] == 0) {
            return 0;
        } else {
            return $goods_info['point_exchange'];
        }
    }

    /**
     * 得到商品的排列
     *
     */
    public function getGoodsRank($condition)
    {
        $goods = new GoodsModel();
        $goods_list = $goods->where($condition)
            ->order(" sales_real desc ")
            ->limit(6)
            ->select();
        return $goods_list;
    }

    /**
     * 更改商品排序
     * @param  $goods_id
     * @param  $sort
     * @return boolean
     */
    public function updateGoodsSort($goods_id, $sort){
        $goods = new GoodsModel();
        return $goods->save(['sort'=>$sort],['id'=>$goods_id]);
    }

    /**
     * 获取随机商品
     */
    public function getRandGoodsList(){
        $goods = new GoodsModel();
        $result = $goods->getConditionQuery(['status'=>1], 'id', '');
        $res = array_rand($result,12);
        $goods_id_list = array();
        foreach($res as $v){
            $goods_id_list[] = $result[$v];
        }
        $goodsList = array();
        foreach($goods_id_list as $g){
            $goodsList[] = $this->getGoodsDetail($g['id']);
        }
        return $goodsList;
    }
    /*
     * 获取商品的
     */
    public function getGoodsSkuQuery($condition)
    {
        // TODO Auto-generated method stub
        $goods_sku_model = new GoodsSkuModel();
        $goods_query = $goods_sku_model->getConditionQuery($condition, "goods_id", "");
        return $goods_query;
    }

    public function getGoodsSkuBySpecs($specs) {
        $goods_sku_model = new GoodsSkuModel();

        $condition = array (
            "specs"=>$specs
        );
        $res = $goods_sku_model->getInfo($condition);
       // $res = $goods_sku_model->getConditionQuery($condition, "*", "");
        return $res;

    }


    /*******************购物车相关的操作 ******************************************/
    /**
     * 获取购物车中项目，根据cartid
     */
    public function getCartList($user_id, $carts)
    {
        $cart = new CartModel();
        $cart_list = $cart->getConditionQuery([
            'buyer_id' => $user_id
        ], '*', '');
        $cart_array = explode(',', $carts);
        $list = array();
        foreach ($cart_list as $k => $v) {
            $goods = new GoodsModel();
            $goods_info = $goods->getInfo([
                'id' => $v['goods_id']
            ], 'max_buy,status,has_option, point_exchange_type,point_exchange');
            // 获取商品sku信息
         //   $has_option = $goods_info['has_option'];
            if (!empty($v['sku_id'])) {
                $goods_sku = new GoodsSkuModel();
                $sku_info = $goods_sku->getInfo([
                    'id' => $v['sku_id']
                ], 'stock');
                if (empty($sku_info)) {
                    $cart->destroy([
                        'buyer_id' => $user_id,
                        'goods_id' =>$v['goods_id'],
                        'sku_id' => $v['sku_id']
                    ]);
                    continue;
                } else {
                    /* 如sku的库存量为0,则删除之*/
                    /*
                    if ($sku_info['stock'] == 0) {
                        $cart->destroy([
                            'buyer_id' => $user_id,
                            'sku_id' => $v['sku_id']
                        ]);
                        continue;
                    }
                    */
                }
            }

            //$v['stock'] = $sku_info['stock'];
            $v['max_buy'] = $goods_info['max_buy'];
            $v['point_exchange_type'] = $goods_info['point_exchange_type'];
            $v['point_exchange'] = $goods_info['point_exchange'];
            if ($goods_info['status'] != 1) {
                $this->cartDelete($user_id, $v['id']);
                unset($v);
            }
            $num = $v['num'];
            if ($goods_info['max_buy'] != 0 && $goods_info['max_buy'] < $v['num']) {
                $num = $goods_info['max_buy'];
            }
            /*
            if ($sku_info['stock'] < $num) {
                $num = $sku_info['stock'];
            }
            */
            if ($num != $v['num']) {
                // 更新购物车
                $this->cartAdjustNum($user_id,$v['id'], $sku_info['stock']);
                $v['num'] = $num;
            }
            // 获取图片信息
          //  $picture = new AlbumPictureModel();
        //    $picture_info = $picture->get($v['goods_picture']);
          //  $v['picture_info'] = $picture_info;
            if (in_array($v['id'], $cart_array)) {
                $list[] = $v;
            }
        }
        return $list;
    }

    /**
     * 获取购物车
     *
     * @param  $uid
     */
    public function getCartSelected($user_id)
    {

        $cart = new CartModel();
        $cart_goods_list = null;
        $cart_goods_list = $cart->getConditionQuery([
                'buyer_id' => $user_id,
                'selected'=> 1
            ], '*', '');

        $total_num = 0;
        if (!empty($cart_goods_list)) {
            foreach ($cart_goods_list as $k => $v) {

              //  if (empty($v) || empty($v['goods_id'])) {
             //       continue;
               // }
                $goods = new GoodsModel();
                $goods_info = $goods->getInfo([
                    'id' => $v['goods_id']
                ], 'is_comb, max_buy,status,point_exchange_type,has_option, point_exchange,title,price,total, thumb, min_buy ');
                // 获取商品sku信息
                $has_option = $goods_info['has_option'];
                $sku_info = "";
                if ( !empty($v['sku_id']) ) {
                    $goods_sku = new GoodsSkuModel();
                    $sku_info = $goods_sku->getInfo([
                        'id' => $v['sku_id']
                    ], 'stock, price, title, promotion_price');
                }
                //验证商品或sku是否存在,不存在则从购物车移除
                if($user_id > 0){
                    if(empty($goods_info)){
                        $cart->destroy([
                            'goods_id' => $v['goods_id'],
                            'buyer_id' => $user_id
                        ]);
                        unset($cart_goods_list[$k]);
                        continue;
                    }
                    if(!empty($v['sku_id']) && empty($sku_info)) {
                        unset($cart_goods_list[$k]);
                        $cart->destroy([
                            'buyer_id' => $user_id,
                            'goods_id' => $v['goods_id'],
                            'sku_id' => $v['sku_id']
                        ]);
                        continue;
                    }
                }
                //为cookie信息完善商品和sku信息
                $sku_name = "";
                if($user_id > 0){
                    //查看用户会员价

                    $goods_preference = new GoodsPreferenceHandle();
                    if (!empty($user_id)) {
                        $member_discount = $goods_preference->getMemberLevelDiscount($user_id);
                    } else {
                        $member_discount = 1;
                    }
                    if (!empty($sku_info)) {
                        $member_price = $member_discount * $sku_info['price'];
                        if ($member_price > $sku_info["promotion_price"]) {
                            $price = $sku_info["promotion_price"];
                        } else {
                            $price = $member_price;
                        }
                    } else {
                        $member_price = $member_discount * $goods_info['price'];
                        if ($member_price > $goods_info["promotion_price"]) {
                            $price = $goods_info["promotion_price"];
                        } else {
                            $price = $member_price;
                        }

                    }

                //    $price = $goods_info['price'];
                    $sku_name = "";
                    if (!empty($sku_info)) {
                     //   $price = $sku_info['price'];
                        $sku_name =  $sku_info['title'];
                    }
                    $update_data = array(
                        "goods_name"=>$goods_info["title"],
                        "sku_name"=> $sku_name,
                        "goods_picture"=>$goods_info["thumb"],
                        "price"=>$price
                    );
                    //更新数据
                    $cart->save($update_data, ["id"=>$v["id"]]);
                    $cart_goods_list[$k]["price"] = $price;
                    $cart_goods_list[$k]["goods_name"] = $goods_info["title"];
                    $cart_goods_list[$k]["sku_name"] = $sku_name;
                    $cart_goods_list[$k]["goods_picture"] = $goods_info["thumb"];
                }
                $stock = $goods_info['total'];
                if (!empty($sku_info)) {
                    $stock = $sku_info['stock'];
                }

                $cart_goods_list[$k]['stock'] = $stock; // $sku_info['stock'];
                $cart_goods_list[$k]['max_buy'] = $goods_info['max_buy'];
                $cart_goods_list[$k]['min_buy'] = $goods_info['min_buy'];
                $cart_goods_list[$k]['point_exchange_type'] = $goods_info['point_exchange_type'];
                $cart_goods_list[$k]['point_exchange'] = $goods_info['point_exchange'];
                if ($goods_info['status'] != 1) {
                    unset($cart_goods_list[$k]);
                    //更新cookie购物车
                    $this->cartDelete($user_id, $v['id']);
                    continue;
                }
                $num = $v['num'];
                if ($goods_info['max_buy'] != 0 && $goods_info['max_buy'] < $v['num']) {
                    $num = $goods_info['max_buy'];
                }
                if ( $stock < $num) {
                    $num =  $stock;
                }
                //商品最小购买数大于现购买数
                if($goods_info['min_buy'] > 0 && $num < $goods_info['min_buy']){
                    $num = $goods_info['min_buy'];
                }
                //商品最小购买数大于现有库存
                /*
                if($goods_info['min_buy'] > $sku_info['stock']){
                    unset($cart_goods_list[$k]);
                    //更新cookie购物车
                    $this->cartDelete($v['cart_id']);
                    continue;
                }
                */
                if ($num != $v['num']) {
                    // 更新购物车
                    $cart_goods_list[$k]['num'] = $num;
                    $this->cartAdjustNum($user_id,$v['id'], $num);


                }
                $total_num = $total_num + $num;

                if ($goods_info['is_comb'] == 1) {

                    $comb_goods =  new CombinationGoodsModel();
                    $condition = array (
                        'goods_id'=> $v['goods_id']
                    );
                    $field ="id, goods_id, comb_goods_id,  num";
                    $order = " id asc";
                    $comb_goods_list = $comb_goods->getConditionQuery($condition, $field, $order);

                    if (!empty($comb_goods_list)) {
                        foreach ($comb_goods_list as $combK => $combV) {
                            $comb_goods_id = $combV['comb_goods_id'];
                            $comb_goods = $this->getGoodsDetail($comb_goods_id);
                            $comb_goods_list[$combK]['title'] = $comb_goods['title'];
                            $comb_goods_list[$combK]['unit'] = $comb_goods['unit'];
                            // $comb_goods_list[$combK]['price'] = $comb_goods['price'];
                            $comb_goods_list[$combK]['thumb'] = $comb_goods['thumb_info'];
                            $comb_goods_num =   $comb_goods_list[$combK]['num'];

                            $comb_goods_list[$combK]['price'] = $comb_goods['price']; // * $comb_goods_num;
                            $comb_goods_list[$combK]['market_price'] = $comb_goods['market_price']; // * $comb_goods_num;
                            $comb_goods_list[$combK]['cost_price'] = $comb_goods['cost_price'];//  * $comb_goods_num;
                            $comb_goods_list[$combK]['promotion_price'] = $comb_goods['promotion_price'];//  * $comb_goods_num;



                        }
                    }





                    $cart_goods_list[$k]['comb_goods_list'] = $comb_goods_list;
                } else {
                    $cart_goods_list[$k]['comb_goods_list'] = array();
                }
            }
            //为购物车图片
            foreach ($cart_goods_list as $k => $v) {
                $picture = new AlbumPictureModel();
                $picture_info = $picture->get($v['goods_picture']);
                $cart_goods_list[$k]['picture_info'] = $picture_info;
                //  $cart_goods_list[$k]['selected'] = 0;
            }
            sort($cart_goods_list);

        }
        //   $cart_goods_list['aaa'] = $num;
        $cart_goods_list1['data'] = $cart_goods_list;
        $cart_goods_list1['total_num'] = $total_num;
      //  $data['cart_goods_list'] =
        return $cart_goods_list1;

    }

    /**
     * ok-2ok
     * 得到立即购买的商品信息
     */
    public function getNowBuyGoodsInfo($goods_list, $user_id)
    {
        $goods_list_array = explode(",", $goods_list);
        $select_goods_list = array();
        $total_num = 0;
        foreach ($goods_list_array  as $k => $v) {

            $goods_data = explode(':', $v);
            $goods_id = $goods_data[0];
            $sku_id = $goods_data[1];
            $num = $goods_data[2];

            $goods = new GoodsModel();
            $goods_info = $goods->getInfo([
                    'id' => $goods_id
            ], 'is_comb, max_buy,status,point_exchange_type,has_option, point_exchange,title,price,total, thumb, min_buy ');

            // 获取商品信息
            if(empty($goods_info)){
                $this->error ="所购买的商品不存在";
                Log::write("goods_info 为空，所购买的商品不存在");
                return array();
            }

            if ($goods_info['status'] != 1) {
                $this->error ="所购买的商品已不出售";
                Log::write("所购买的商品已不出售");
                return array();
            }

            $has_option = $goods_info['has_option'];
            if ($has_option== 0 && !empty($sku_id)) {
                $this->error ="所购买的商品已不使用SKU";
                Log::write("has_option=0，sku 无效， 所购买的商品已不使用SKU");
                return array();
            }

            $sku_info = "";
            if ( !empty($sku_id) ) {
                $goods_sku = new GoodsSkuModel();
                $sku_info = $goods_sku->getInfo([
                        'id' => $sku_id
                ], 'stock, price, title, promotion_price');
            }
                //验证商品或sku是否存在,不存在则从购物车移除
            if(!empty($sku_id) && empty($sku_info)) {
                $this->error ="所购买的商品SKU不存在";
                Log::write("sku_info为空， 所购买的商品SKU不存在");
                return array();
            }

            //查看用户会员价
            $goods_preference = new GoodsPreferenceHandle();
            if (!empty($user_id)) {
                $member_discount = $goods_preference->getMemberLevelDiscount($user_id);
            } else {
                $member_discount = 1;
            }
            if (!empty($sku_info)) {
                $member_price = $member_discount * $sku_info['price'];
                if ($member_price > $sku_info["promotion_price"]) {
                    $price = $sku_info["promotion_price"];
                } else {
                    $price = $member_price;
                }
            } else {
                $member_price = $member_discount * $goods_info['price'];
                if ($member_price > $goods_info["promotion_price"]) {
                    $price = $goods_info["promotion_price"];
                } else {
                    $price = $member_price;
                }
            }

            $sku_name = "";
            if (!empty($sku_info)) {
                        //   $price = $sku_info['price'];
                $sku_name =  $sku_info['title'];
            }
            $select_goods_list[$k]["goods_id"] = $goods_id;
            $select_goods_list[$k]["goods_name"] = $goods_info["title"];
            $select_goods_list[$k]["sku_id"] = $sku_id;
            $select_goods_list[$k]["sku_name"] = $sku_name;
            $select_goods_list[$k]["price"] = $price;
            $select_goods_list[$k]["goods_picture"] = $goods_info["thumb"];

            $stock = $goods_info['total'];
            if (!empty($sku_info)) {
                $stock = $sku_info['stock'];
            }

            $select_goods_list[$k]['stock'] = $stock; // $sku_info['stock'];
            $select_goods_list[$k]['max_buy'] = $goods_info['max_buy'];
            $select_goods_list[$k]['min_buy'] = $goods_info['min_buy'];
            $select_goods_list[$k]['point_exchange_type'] = $goods_info['point_exchange_type'];
            $select_goods_list[$k]['point_exchange'] = $goods_info['point_exchange'];

            if ($goods_info['max_buy'] != 0 && $goods_info['max_buy'] < $num) {
                $num = $goods_info['max_buy'];
            }

                //商品最小购买数大于现购买数
            if($goods_info['min_buy'] > 0 && $num < $goods_info['min_buy']){
                $num = $goods_info['min_buy'];
            }

            if ( $stock < $num) {
                $num =  $stock;
            }

            $select_goods_list[$k]['num'] = $num;

            $total_num = $total_num + $num;
            if ($goods_info['is_comb'] == 1) {

                $comb_goods =  new CombinationGoodsModel();
                $condition = array (
                        'goods_id'=> $goods_id
                    );
                $field ="id, goods_id, comb_goods_id,  num";
                $order = " id asc";
                $comb_goods_list = $comb_goods->getConditionQuery($condition, $field, $order);

                if (!empty($comb_goods_list)) {
                    foreach ($comb_goods_list as $combK => $combV) {
                        $comb_goods_id = $combV['comb_goods_id'];
                        $comb_goods = $this->getGoodsDetail($comb_goods_id);
                        $comb_goods_list[$combK]['title'] = $comb_goods['title'];
                        $comb_goods_list[$combK]['unit'] = $comb_goods['unit'];
                            // $comb_goods_list[$combK]['price'] = $comb_goods['price'];
                        $comb_goods_list[$combK]['thumb'] = $comb_goods['thumb_info'];
                        $comb_goods_num =   $comb_goods_list[$combK]['num'];

                        $comb_goods_list[$combK]['price'] = $comb_goods['price']; // * $comb_goods_num;
                        $comb_goods_list[$combK]['market_price'] = $comb_goods['market_price']; // * $comb_goods_num;
                        $comb_goods_list[$combK]['cost_price'] = $comb_goods['cost_price'];//  * $comb_goods_num;
                        $comb_goods_list[$combK]['promotion_price'] = $comb_goods['promotion_price'];//  * $comb_goods_num;
                    }
                }

                $select_goods_list[$k]['comb_goods_list'] = $comb_goods_list;
            } else {
                $select_goods_list[$k]['comb_goods_list'] = array();
            }
        }
        //图片
        foreach ($select_goods_list as $k => $v) {
            $picture = new AlbumPictureModel();
            $picture_info = $picture->get($v['goods_picture']);
            $select_goods_list[$k]['picture_info'] = $picture_info;
                //  $cart_goods_list[$k]['selected'] = 0;
        }
        sort($select_goods_list);


        //   $cart_goods_list['aaa'] = $num;
        $cart_goods_list1['data'] = $select_goods_list;
        $cart_goods_list1['total_num'] = $total_num;
        //  $data['cart_goods_list'] =
        return $cart_goods_list1;

    }

    /**
     * 获取购物车
     *
     * @param  $uid
     */
    public function getCart($user_id)
    {
        if($user_id > 0){
            $cart = new CartModel();
            $cart_goods_list = null;
            $cart_goods_list = $cart->getConditionQuery([
                    'buyer_id' => $user_id
            ], '*', '');
        }else{
            $cart_goods_list = cookie('cart_array');
            if(empty($cart_goods_list)){
                $cart_goods_list = array();
            }else{
                $cart_goods_list = json_decode($cart_goods_list,true);
            }
        }
        $total_num = 0;
        if (!empty($cart_goods_list)) {
            foreach ($cart_goods_list as $k => $v) {
                $goods = new GoodsModel();
                $goods_info = $goods->getInfo([
                    'id' => $v['goods_id']
                ], 'is_comb, max_buy,status,point_exchange_type,has_option, point_exchange,title,price,promotion_price ,total, thumb, min_buy ');
                // 获取商品sku信息
                $has_option = $goods_info['has_option'];
                $sku_info = "";
                if ( !empty($v['sku_id']) ) {
                    $goods_sku = new GoodsSkuModel();
                    $sku_info = $goods_sku->getInfo([
                        'id' => $v['sku_id']
                    ], 'stock, price, title, promotion_price');
                }
                //验证商品或sku是否存在,不存在则从购物车移除
                if($user_id > 0){
                    if(empty($goods_info)){
                        $cart->destroy([
                            'goods_id' => $v['goods_id'],
                            'buyer_id' => $user_id
                        ]);
                        unset($cart_goods_list[$k]);
                        continue;
                    }
                    if(!empty($v['sku_id']) && empty($sku_info)) {
                        unset($cart_goods_list[$k]);
                        $cart->destroy([
                            'buyer_id' => $user_id,
                            'goods_id' => $v['goods_id'],
                            'sku_id' => $v['sku_id']
                        ]);
                        continue;
                    }
                }else{
                    if(empty($goods_info)){
                        unset($cart_goods_list[$k]);
                        $this->cartDelete($user_id, $v['id']);
                        continue;
                    }
                    if(!empty($v['sku_id']) && empty($sku_info)) {
                        unset($cart_goods_list[$k]);
                        $this->cartDelete($user_id, $v['id']);
                        continue;
                    }
                }
                //为cookie信息完善商品和sku信息
                $sku_name = "";
                if($user_id > 0){
                    //查看用户会员价

                    $goods_preference = new GoodsPreferenceHandle();
                    if (!empty($user_id)) {
                        $member_discount = $goods_preference->getMemberLevelDiscount($user_id);
                    } else {
                        $member_discount = 1;
                    }
                    if (!empty($sku_info)) {
                        $member_price = $member_discount * $sku_info['price'];
                        if ($member_price > $sku_info["promotion_price"]) {
                            $price = $sku_info["promotion_price"];
                        } else {
                            $price = $member_price;
                        }
                    } else {
                        $member_price = $member_discount * $goods_info['price'];
                        if ($member_price > $goods_info["promotion_price"]) {
                            $price = $goods_info["promotion_price"];
                        } else {
                            $price = $member_price;
                        }
                    }


                   // $price = $goods_info['price'];
                    $sku_name = "";
                    if (!empty($sku_info)) {
                       // $price = $sku_info['price'];
                        $sku_name =  $sku_info['title'];
                    }
                    $update_data = array(
                        "goods_name"=>$goods_info["title"],
                        "sku_name"=> $sku_name,
                        "goods_picture"=>$goods_info["thumb"],
                        "price"=>$price
                    );
                    //更新数据
                    $cart->save($update_data, ["id"=>$v["id"]]);
                    $cart_goods_list[$k]["price"] = $price;
                    $cart_goods_list[$k]["goods_name"] = $goods_info["title"];
                    $cart_goods_list[$k]["sku_name"] = $sku_name;
                    $cart_goods_list[$k]["goods_picture"] = $goods_info["thumb"];
                }else{
                    $price = $goods_info['promotion_price'];
                    $sku_name = "";
                    if (!empty($sku_info)) {
                        $price = $sku_info['promotion_price'];
                        $sku_name =  $sku_info['title'];
                    }
                    $cart_goods_list[$k]["price"] = $price; // $sku_info["promotion_price"];
                    $cart_goods_list[$k]["goods_name"] = $goods_info["title"];
                    $cart_goods_list[$k]["sku_name"] = $sku_name; // $sku_info["sku_name"];
                    $cart_goods_list[$k]["goods_picture"] = $goods_info["thumb"];
                }
                $stock = $goods_info['total'];
                if (!empty($sku_info)) {
                    $stock = $sku_info['stock'];
                }

                $cart_goods_list[$k]['stock'] = $stock; // $sku_info['stock'];
                $cart_goods_list[$k]['max_buy'] = $goods_info['max_buy'];
                $cart_goods_list[$k]['min_buy'] = $goods_info['min_buy'];
                $cart_goods_list[$k]['point_exchange_type'] = $goods_info['point_exchange_type'];
                $cart_goods_list[$k]['point_exchange'] = $goods_info['point_exchange'];
                if ($goods_info['status'] != 1) {
                    unset($cart_goods_list[$k]);
                    //更新cookie购物车
                    $this->cartDelete($user_id, $v['id']);
                    continue;
                }
                $num = $v['num'];
                if ($goods_info['max_buy'] != 0 && $goods_info['max_buy'] < $v['num']) {
                    $num = $goods_info['max_buy'];
                }
                if ( $stock < $num) {
                    $num =  $stock;
                }
                //商品最小购买数大于现购买数
                if($goods_info['min_buy'] > 0 && $num < $goods_info['min_buy']){
                    $num = $goods_info['min_buy'];
                }
                //商品最小购买数大于现有库存
                /*
                if($goods_info['min_buy'] > $sku_info['stock']){
                    unset($cart_goods_list[$k]);
                    //更新cookie购物车
                    $this->cartDelete($v['cart_id']);
                    continue;
                }
                */
                if ($num != $v['num']) {
                    // 更新购物车
                    $cart_goods_list[$k]['num'] = $num;
                    $this->cartAdjustNum($user_id,$v['id'], $num);


                }
                $total_num = $total_num + $num;

                if ($goods_info['is_comb'] == 1) {

                    $comb_goods =  new CombinationGoodsModel();
                    $condition = array (
                        'goods_id'=> $v['goods_id']
                    );
                    $field ="id, goods_id, comb_goods_id,  num";
                    $order = " id asc";
                    $comb_goods_list = $comb_goods->getConditionQuery($condition, $field, $order);

                    if (!empty($comb_goods_list)) {
                        foreach ($comb_goods_list as $combK => $combV) {
                            $comb_goods_id = $combV['comb_goods_id'];
                            $comb_goods = $this->getGoodsDetail($comb_goods_id);
                            $comb_goods_list[$combK]['title'] = $comb_goods['title'];
                            $comb_goods_list[$combK]['unit'] = $comb_goods['unit'];
                            // $comb_goods_list[$combK]['price'] = $comb_goods['price'];
                            $comb_goods_list[$combK]['thumb'] = $comb_goods['thumb_info'];
                            $comb_goods_num =   $comb_goods_list[$combK]['num'];

                            $comb_goods_list[$combK]['price'] = $comb_goods['price']; // * $comb_goods_num;
                            $comb_goods_list[$combK]['market_price'] = $comb_goods['market_price']; // * $comb_goods_num;
                            $comb_goods_list[$combK]['cost_price'] = $comb_goods['cost_price'];//  * $comb_goods_num;
                            $comb_goods_list[$combK]['promotion_price'] = $comb_goods['promotion_price'];//  * $comb_goods_num;



                        }
                    }





                    $cart_goods_list[$k]['comb_goods_list'] = $comb_goods_list;
                } else {
                    $cart_goods_list[$k]['comb_goods_list'] = array();
                }
            }
            //为购物车图片
            foreach ($cart_goods_list as $k => $v) {
                $picture = new AlbumPictureModel();
                $picture_info = $picture->get($v['goods_picture']);
                $cart_goods_list[$k]['picture_info'] = $picture_info;
              //  $cart_goods_list[$k]['selected'] = 0;
            }
            sort($cart_goods_list);

        }
     //   $cart_goods_list['aaa'] = $num;
        $cart_goods_list['total_num'] = $total_num;
        return $cart_goods_list;

    }

    /**
     * 添加购物车(non-PHPdoc)
     */
    public function addCart($user_id,  $goods_id,  $sku_id, $price, $num, $selected,  $bl_id)
    {
        // 检测当前购物车中是否存在产品
        if (empty($sku_id)) {
            $sku_id = 0;
        }
        if($user_id > 0){
            $cart = new CartModel();
            $condition = array(
                'buyer_id' => $user_id,
                'goods_id' => $goods_id,
                'sku_id' => $sku_id
            );

            $goodsmodel = new GoodsModel();
            $goods_info = $goodsmodel->getInfo(['id' => $goods_id], 'thumb, title, price,max_buy');
            $goods_name = $goods_info['title'];
            $picture = $goods_info['thumb'];
            $max_buy = $goods_info['max_buy'];
            $sku_name = "";
            if (!empty($sku_id)) {
                $goods_sku = new GoodsSkuModel();
                //sku信息
                $sku_info = $goods_sku->getInfo([ 'id' => $sku_id], 'price, title, promotion_price');
                $sku_name = $sku_info['title'];
            }


            /*
            if (empty($sku_id)) {
                $condition = array(
                    'buyer_id' => $user_id,
                    'goods_id' => $goods_id
                );
            }else {
                $condition = array(
                    'buyer_id' => $user_id,
                    'sku_id' => $sku_id
                );
            }
            */
            $count = $cart->where($condition)->count();
            if ($count == 0 || empty($count)) {
                $data = array(
                    'buyer_id' => $user_id,
                    'goods_id' => $goods_id,
                    'goods_name' => $goods_name,
                    'sku_id' => $sku_id,
                    'sku_name' => $sku_name,
                    'price' => $price,
                    'num' => $num,
                    'goods_picture' => $picture,
                    'selected'=> $selected,
                    'bl_id' => $bl_id
                );
                $cart->save($data);
                $retval = $cart->id;
            } else {
                $cart = new CartModel();
                // 查询商品限购
              //  $goods = new GoodsModel();
                $get_num = $cart->getInfo($condition, 'id,num');
              //  $max_buy = $goods->getInfo([
              //      'id' => $goods_id
             //   ], 'max_buy');
                $new_num = $num + $get_num['num'];
                if ($max_buy != 0) {

                    if ($new_num > $max_buy) {
                        $new_num = $max_buy;
                    }
                }

                $data = array(
                    'num' => $new_num,
                    'selected'=>1
                );
                $retval = $cart->save($data, $condition);
                if ($retval) {
                    $retval = $get_num['id'];
                }
            }

        }else{
            $cart_array = cookie('cart_array');
            $data = array(
            //    'shop_id' => $shop_id,
                'goods_id' => $goods_id,
                'sku_id' => $sku_id,
                'selected'=> $selected,
                'num' => $num
            );

            if(!empty($cart_array)){
                $cart_array = json_decode($cart_array,true);
                $tmp_array = array();
                foreach($cart_array as $k=>$v){
                    $tmp_array[] = $v['id'];
                }
                $cart_id = max($tmp_array) + 1;
                $is_have = true;
                foreach($cart_array as $k=>$v){
                    if($v["goods_id"] == $goods_id && $v["sku_id"] == $sku_id){
                        $is_have = false;
                        $cart_array[$k]["num"] = $data["num"] + $v["num"];
                    }
                }
                if($is_have){
                    $data["id"] = $cart_id;
                    $cart_array[] = $data;
                }
            }else{
                $data["id"] = 1;
                $cart_array[] = $data;
            }
            $cart_array_string = json_encode($cart_array);
            try{
                cookie('cart_array', $cart_array_string, 3600);
                return 1;
            }catch(\Exception $e){
                return 0;
            }
            $retval = 1;
        }
        return $retval;
    }

    /**
     * 购物车数量修改(non-PHPdoc)
     */
    public function cartAdjustNum($user_id, $cart_id, $num)
    {
        if($user_id > 0){
            $cart = new CartModel();
            $data = array(
                'num' => $num
            );
            $retval = $cart->save($data, [
                'id' => $cart_id
            ]);
            return $retval;
        }else{
            $result = $this->updateCookieCartNum($cart_id, $num);
            return $result;
        }
    }

    /**
     * 将购物车中指定商品修改为已选择
     */
    public function cartAdjustSelected($user_id, $cart_id_str)
    {
        $cart_id_str = trim($cart_id_str);
        $cart_id_array = explode(",", $cart_id_str);
        if ($user_id > 0) {
            $cart = new CartModel();
            $data = array(
                'selected' => 0
            );
            $retval = $cart->save($data, [
                'buyer_id' => $user_id
            ]);
            $data = array(
                'selected' => 1
            );
            $retval = $cart->save($data, [
                'buyer_id' => $user_id,
                'id' => ['in', $cart_id_array]
            ]);
        } else {

            //获取购物车
            $cart_goods_list = cookie('cart_array');
            if (empty($cart_goods_list)) {
                $cart_goods_list = array();
            } else {
                $cart_goods_list = json_decode($cart_goods_list, true);
            }
            foreach ($cart_goods_list as $k => $v) {
                $cart_goods_list[$k]["selected"] = 0;
            }

            foreach ($cart_id_array as $k_id => $cart_id) {
                foreach ($cart_goods_list as $k => $v) {
                    if ($v["id"] == $cart_id) {
                        $cart_goods_list[$k]["selected"] = 1;
                    }
                }
            }
            sort($cart_goods_list);
            try {
                cookie('cart_array', json_encode($cart_goods_list), 3600);
                return 1;
            } catch (\Exception $e) {
                $this->error = $e->getMessage();
                return 0;
            }
        }

        return 1;
    }


    /**
     * 购物车项目删除(non-PHPdoc)
     */
    public function cartDelete($user_id, $cart_id_array)
    {
        if($user_id > 0){
            $cart = new CartModel();
            $retval = $cart->destroy($cart_id_array);
            return $retval;
        }else{
            $result = $this->deleteCookieCart($cart_id_array);
            return $result;
        }
    }

    /*
     * 删除cookie购物车
     */
    private function deleteCookieCart($cart_id_array)
    {
        //获取删除条件拼装
        $cart_id_array=trim($cart_id_array);
        if(empty($cart_id_array) && $cart_id_array != 0){
            return 0;
        }
        //获取购物车
        $cart_goods_list = cookie('cart_array');
        if(empty($cart_goods_list)){
            $cart_goods_list = array();
        }else{
            $cart_goods_list = json_decode($cart_goods_list,true);
        }
        foreach($cart_goods_list as $k=>$v){
            if(strpos((string)$cart_id_array, (string)$v["id"]) !== false){
                unset($cart_goods_list[$k]);
            }
        }
        if(empty($cart_goods_list)){
            cookie('cart_array', null);
            return 1;
        }else{
            sort($cart_goods_list);
            try{
                cookie('cart_array', json_encode($cart_goods_list) , 3600);
                return 1;
            }catch(\Exception $e){
                return 0;
            }
        }
    }
    /**
     * 修改cookie购物车的数量
     */
    private function updateCookieCartNum($cart_id, $num){
        //获取购物车
        $cart_goods_list = cookie('cart_array');
        if(empty($cart_goods_list)){
            $cart_goods_list = array();
        }else{
            $cart_goods_list = json_decode($cart_goods_list,true);
        }
        foreach($cart_goods_list as $k=>$v){
            if($v["id"] == $cart_id){
                $cart_goods_list[$k]["num"] = $num;
            }
        }
        sort($cart_goods_list);
        try{
            cookie('cart_array', json_encode($cart_goods_list) , 3600);
            return 1;
        }catch(\Exception $e){
            return 0;
        }
    }

    /*
     * 将cookie中的商品放入用户购物车
     */
    public function syncUserCart($user_id)
    {
        // TODO Auto-generated method stub
        $this->startTrans();
        try {
             $cart = new CartModel();
             $cart_query = $cart->getConditionQuery(["buyer_id"=>$user_id], '*', '');
             //获取购物车
             $cart_goods_list = cookie('cart_array');
            if(empty($cart_goods_list)){
                $cart_goods_list = array();
            }else{
                $cart_goods_list = json_decode($cart_goods_list,true);
             }
             $goodsmodel = new GoodsModel();
      //  $web_site = new WebSite();
             $goods_sku = new GoodsSkuModel();

       // $web_info = $web_site->getWebSiteInfo();
        //遍历cookie购物车
             if(!empty($cart_goods_list)){
                foreach($cart_goods_list as $k=>$v){
                //商品信息
                    $goods_info = $goodsmodel->getInfo(['id' => $v['goods_id']], 'thumb, title, price');
                //sku信息
                    $sku_info = $goods_sku->getInfo([ 'id' => $v['sku_id']], 'price, title, promotion_price');
                    $price = 0;
                    if (empty($goods_info)) {
                        break;
                     } else {
                         $price = $goods_info['price'];
                    }
                    if(!empty( $v['sku_id']) && empty($sku_info)){
                         break;
                     }
                     $sku_name = "";
                    if( !empty($sku_info)){
                        $price = $sku_info['price'];
                        $sku_name =  $sku_info['title'];
                     }

                //查看用户会员价，（我们没有会员价）
                /*
                $goods_preference = new GoodsPreference();
                if (!empty($this->uid)) {
                    $member_discount = $goods_preference->getMemberLevelDiscount($uid);
                } else {
                    $member_discount = 1;
                }
                $member_price = $member_discount * $sku_info['price'];

                if($member_price > $sku_info["promotion_price"]){
                    $price = $sku_info["promotion_price"];
                }else{
                    $price = $member_price;
                }
                */
                //判断此用户有无购物车
                    if(empty($cart_query)){
                    // 获取商品sku信息

                        //   public function addCart($user_id,  $goods_id, $goods_name, $sku_id, $sku_name, $price, $num, $picture,$seleccted, $bl_id)
                      //  addCart($user_id,  $goods_id,  $sku_id, $price, $num,  $bl_id)
                         $this->addCart($user_id, $v["goods_id"],  $v["sku_id"],  $price,$v["num"], $v['selected'], 0);
                     }else{
                        $is_have = true;
                        foreach($cart_query as $t=>$m){
                            if($m["sku_id"] == $v["sku_id"] && $m["goods_id"] == $v["goods_id"]){
                                $is_have = false;
                                $num = $m["num"] + $v["num"];
                                $this->cartAdjustNum($user_id, $m["id"], $num);
                                 break;
                            }
                         }
                        if($is_have){
                             $this->addCart($user_id,  $v["goods_id"], $v["sku_id"],  $price,$v["num"], $v['selected'],  0);
                        }
                    }
                }
             }
            cookie('cart_array', null);
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = "操作时发生异常:". $e->getMessage().",操作失败";
            return false;
        }
    }

    /*
     * 购物车大小
     */
    public function getCartSize($user_id)
    {

        $cartlist = $this->getCart($user_id);
        $num = $cartlist['total_num'];

        /*
        foreach ($cartlist as $v) {
           // if ($v["goods_id"] == $goods_id) {
                $num =  $num + $v["num"];
           // }
        }
        */
        $size = count($cartlist);
        //   $data = array (
        //      "cart_num"=>count($cartlist)
        // );
        $size = $size -1;
        $data = array (
            "cartcount" =>$size,
            "cartnum" =>$num
        );
        return $data;

     //   return json(resultArray(0,"操作成功", $data));
        // $this->assign("carcount", count($cartlist)); // 购物车商品数量
        // $this->assign("num", $num); // 购物车已购买商品数量
    }

    /**
     * ok-2ok
     * 得到商品列表的固定运费
     * @param $goods_list
     */
    public function getFixedShippingFeeByGoodsList($goods_list) {
        $shipping_fee = 0;
        if(!empty($goods_list)) {
            $goods_list_array = explode(',', $goods_list);
            foreach ($goods_list_array as $k => $v) {
                $goods_item = explode(":", $v);
                //获取商品goods_id
                $goods_id = $goods_item[0];

                $goods_model = new GoodsModel();
                $goods = $goods_model->get($goods_id);
                $dispatch_price = $goods['dispatch_price'];
                if (empty($dispatch_price)) {
                    $dispatch_price = 0;
                }
                if ($dispatch_price > $shipping_fee) {
                    $shipping_fee = $dispatch_price;
                }
            }
        }
        return $shipping_fee;

    }

    /**
     * 获取限时折扣的商品
     * @param string $order
     */
    public function getDiscountGoodsList($page_index = 1, $page_size = 0, $condition = array(), $order = '')
    {
        $goods_discount = new GoodsDiscountHandle();
        $goods_list = $goods_discount->getDiscountGoodsList($page_index, $page_size, $condition, $order);

        return $goods_list;
    }

    /**
     * 得到商品的运费模板
     */
    public function getGoodsExpressTemplate($goods_id, $province_id, $city_id, $district_id, $user_id)
    {
        $goods_express = new GoodsExpressHandle();
        $retval = $goods_express->getGoodsExpressTemplate($goods_id, $province_id, $city_id, $district_id, $user_id);
        return $retval;
    }

    /**
     * ok-2ok
     * 获取商品优惠劵
     */
    public function getGoodsCoupon($goods_id, $user_id=0)
    {
        $coupon_goods = new CouponGoodsModel();
        $coupon_type = new CouponTypeModel();
        $coupon = new CouponModel();
        // 通过商品id获取到优惠劵类型
        $coupon_goods_type_id_list = $coupon_goods->getConditionQuery([
            'goods_id' => $goods_id
        ], 'coupon_type_id', '');
        // 去除掉未开始的和已结束的
        foreach ($coupon_goods_type_id_list as $k => $v) {
            $res = $coupon_type->getInfo([
                'id' => $v['coupon_type_id']
            ], "start_time,end_time");
            if ($res['start_time'] > time() || time() > $res['end_time']) {
                unset($coupon_goods_type_id_list[$k]);
            }
        }
        // 获取全商品优惠劵
        $conditions = array(
            'start_time' => array(
                'ELT',
                time()
            ),
            'end_time' => array(
                'EGT',
                time()
            ),
            'range_type' => 1
        );
        $coupon_type_id_list = $coupon_type->getConditionQuery($conditions, 'id', '');
        foreach ($coupon_type_id_list as $v) {
            $v['coupon_type_id'] = $v['id'];
            array_push($coupon_goods_type_id_list, $v);
        }
        $coupon_list = array();
        foreach ($coupon_goods_type_id_list as $v) {
            // 已领取，已使用的数目
            $already_received = $coupon->getCount([
                'coupon_type_id' => $v['coupon_type_id'],
                "status" => [
                    'neq',
                    0
                ]
            ]);
            $condition = array(
                'start_time' => array(
                    'ELT',
                    time()
                ),
                'end_time' => array(
                    'EGT',
                    time()
                ),
                'id' => $v['coupon_type_id'],
                'count' => array(
                    'GT',
                    $already_received
                )
            );
            $coupon_detial = $coupon_type->getInfo($condition, 'money,max_fetch,at_least,id,start_time,end_time');
            if (! empty($coupon_detial)) {
                $coupon_detial['start_time'] = getTimeStampTurnTime($coupon_detial['start_time']);
                $coupon_detial['end_time'] = getTimeStampTurnTime($coupon_detial['end_time']);
                if (!empty($user_id)) {
                    $receive_quantity = $coupon->getCount([
                        "coupon_type_id" => $coupon_detial['id'],
                        "user_id" => $user_id
                    ]);
                    // if($coupon_detial['max_fetch'] == 0 || $coupon_detial['max_fetch']> $receive_quantity){
                    $coupon_detial['receive_quantity'] = $receive_quantity;
                }
                $coupon_list[] = $coupon_detial;
                // }
            }
        }
        return $coupon_list;
    }

    /**
     * ok-2ok
     * 得到（美肤日志)主商品的id
     * @return int
     */
    public function getMainGoodsId() {
        $goods_model = new GoodsModel();
        $condition = array(
            'is_main'=>1,
            'status'=>1
        );
        $order = 'sort desc, id desc';
        $field = "id";
        $goods_list = $goods_model->getConditionQuery($condition, $field, $order);

        if (!empty($goods_list)) {
            return $goods_list[0]['id'];
        } else {
            return 0;
        }
    }

}

