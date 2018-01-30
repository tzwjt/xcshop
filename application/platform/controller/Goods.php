<?php
namespace app\platform\controller;

use app\platform\controller\BaseController;
use dao\handle\ExpressHandle;
use dao\model\GoodsCategory as GoodsCategoryModel;
use dao\model\Goods as GoodsModel;
use dao\model\GoodsView as GoodsViewModel;
use dao\handle\GoodsHandle as GoodsHandle;
use dao\handle\GoodsCategoryHandle as GoodsCategoryHandle;
use dao\handle\AddressHandle as AddressHandle;


class Goods extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function getCategoryByParent()
    {
        //if (request()->isAjax()) {
        $parentId = request()->post("pId", 0);
        $goodsCategoryHandle = new GoodsCategoryHandle();
        $res = $goodsCategoryHandle->getGoodsCategoryListByParentId($parentId);
        return json(resultArray(0,"操作成功", $res));
        // }
    }
    
    public function index() {
        return "hello World";
    }
    
    /**
     * 通过节点的ID查询得到某个节点下的子集
     */
    public function getChildCateGory()
    {
        $categoryID = request()->post("category_id"); //$_POST["categoryID"];
        $goodsCategoryHandle = new GoodsCategoryHandle();
        $list = $goodsCategoryHandle->getGoodsCategoryListByParentId($categoryID);
       // return $list;
        return json(resultArray(0,"操作成功", $list));
    }
    
     /**
     * 通过节点的ID查询得到分类详情
     */
    public function getGoodsCategoryDetail()
    {
        $categoryID = request()->post("category_id"); //$_POST["categoryID"];
        $goodsCategoryHandle = new GoodsCategoryHandle();
        $list = $goodsCategoryHandle->getGoodsCategoryDetail($categoryID);
       // return $list;
        return json(resultArray(0,"操作成功", $list));
    }
    
   
    
    /**
     * 全部商品分类列表
     */
    public function getGoodsCategoryList()
    {
        $goodsCategoryHandle = new GoodsCategoryHandle();
        $one_list = $goodsCategoryHandle->getFormatGoodsCategoryList();
        return json(resultArray(0,"操作成功", $one_list));
      //  $this->assign("category_list", $one_list);
       // return view($this->style . "Goods/goodsCategoryList");
    }
    
    
    
    
    
    
    public function addGoodsCategory() {
        
        
        $goodsCategoryHandle = new GoodsCategoryHandle();
        $categoryName = request()->post("category_name");
        $thumb = request()->post("thumb", '');
        $pid = request()->post("pid", 0);
        $isRecommand = request()->post("is_recommand", 1);
        $description = request()->post("description", '');
        $sort = request()->post("sort", 0);
        $status = request()->post("status", 1);
        $isHome = request()->post('is_home', 1);
        
        $advImg = request()->post('adv_img', '');
        $advUrl = request()->post('adv_url', '');
        $result = $goodsCategoryHandle->addOrUpdateGoodsCategory(0, $categoryName, $description, $thumb,
            $pid, $sort, $isRecommand, $isHome, $status, $advImg, $advUrl);
        // return AjaxReturn($result);
      
      //  return $result;
        
        if ($result) {
            return json(resultArray(0,"操作成功"));
        } else {
            return json(resultArray(2,$goodsCategoryHandle->getError()));
        }
     
    }
    
    /**
     * 修改商品分类
     */
    public function updateGoodsCategory()
    {
        $goodsCategoryHandle = new GoodsCategoryHandle();
      
        $categoryId = request()->post("category_id");
        $categoryName = request()->post("category_name");
        $thumb = request()->post("thumb");
        $pid = request()->post("pid", 0);
        $isRecommand = request()->post("is_recommand");
        $description = request()->post("description");
        $sort = request()->post("sort", 0);
        $status = request()->post("status");
        $isHome = request()->post('is_home');
        
        $advImg = request()->post('adv_img');
        $advUrl = request()->post('adv_url');
        
        if (empty($categoryId) || $categoryId < 1) {
            return json(resultArray(2,"更新操作失败"));
        }
            
       
        $result = $goodsCategoryHandle->addOrUpdateGoodsCategory($categoryId, $categoryName, $description, $thumb,
            $pid, $sort, $isRecommand, $isHome, $status, $advImg, $advUrl);
        
        
        if ($result) {
            return json(resultArray(0,"更新作操成功"));
        } else {
            return json(resultArray(2,$goodsCategoryHandle->getError()));
        }
    }
    
    
    /**
     * 删除商品分类
     */
    public function deleteGoodsCategory()
    {
        $goodsCategoryHandle = new GoodsCategoryHandle();
        $category_id = request()->post('category_id'); //$_POST['category_id'];
        $res = $goodsCategoryHandle->deleteGoodsCategory($category_id);
        if ($res > 0) {
            return json(resultArray(0,"删除操作成功"));
        }
        return json(resultArray(2,$goodsCategoryHandle->getError()));
    }
    
    /**
     * 修改 商品 分类 单个字段
     */
    public function modifyGoodsCategoryField()
    {
        $goodsCategoryHandle = new GoodsCategoryHandle();
        $fieldid = $_POST['fieldid'];
        $fieldname = $_POST['fieldname'];
        $fieldvalue = $_POST['fieldvalue'];
        $res = $goodsCategoryHandle->ModifyGoodsCategoryField($fieldid, $fieldname, $fieldvalue);
        if ($res > 0) {
            return json(resultArray(0,"修改操作成功"));
        } else {
            return json(resultArray(2,"修改操作失败"));
        }
    }
    
    
    
    
    /**
     * 添加商品基本信息
     */
    public function addOrUpdateGoodsBasic()
    {
        
        $goodsModel = new GoodsModel();
        $param = $this->param;
        
        $result = $goodsModel->addOrUpdateGoodsBasic($param);
        
        if ($result) {
            return json(resultArray(0,"操作成功"));
        } else {
            return json(resultArray(2,$goodsModel->getError()));
        }
    }
    
    /**
     * 商品分类选择
     *
     * @return  <\think\response\View, \think\response\$this, \think\response\View>
     */
    /*
    public function dialogSelectCategory()
    {
        $category_id = request()->get("category_id", 0);
        $goodsid = request()->get("goodsid", 0);
        $flag = request()->get("flag", 'category');
        // 扩展分类标签id,用户回调方法
        $box_id = request()->get("box_id", '');
        // 已选择扩展分类(用于控制重复选择)
        $category_extend_id = request()->get("category_extend_id", '');
        if (! empty($category_extend_id) && $category_id != 0) {
            $category_extend_id = explode(",", $category_extend_id);
            foreach ($category_extend_id as $k => $v) {
                if ($v == $category_id) {
                    unset($category_extend_id[$k]);
                }
            }
            sort($category_extend_id);
            $category_extend_id = implode(',', $category_extend_id);
        }
        $this->assign("flag", $flag);
        $this->assign("goodsid", $goodsid);
        $this->assign("box_id", $box_id);
        $this->assign("category_extend_id", $category_extend_id);
        
        $goods_category = new GoodsCategory();
        $list = $goods_category->getGoodsCategoryListByParentId(0);
        $this->assign("cateGoryList", $list);
        $category_select_ids = "";
        $category_select_names = "";
        if ($category_id != 0) {
            $category_select_result = $goods_category->getParentCategory($category_id);
            $category_select_ids = $category_select_result["category_ids"];
            $category_select_names = $category_select_result["category_names"];
        }
        $this->assign("category_select_ids", $category_select_ids);
        $this->assign("category_select_names", $category_select_names);
        return view($this->style . 'Goods/dialogSelectCategory');
    }
  */
     /**
     * 根据商品ID查询单个商品，然后进行编辑操作
     */
    public function goodsSelect()
    {
        $goods_handle = new GoodsHandle();
        $goods = $goods_handle->getGoodsDetail($this->param['goods_id']);
        return json(resultArray(0,"操作成功", $goods));
    }

    /**
     * 商品列表
     */
    public function goodsList()
    {
        $goodsHandle = new GoodsHandle();

        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", PAGESIZE);
        $start_date = request()->post('start_date') == '' ? 0 : getTimeTurnTimeStamp(request()->post('start_date'));
        $end_date = request()->post('end_date') == '' ? 0 : getTimeTurnTimeStamp(request()->post('end_date'));
        $goods_name = request()->post('goods_name', '');
        $status = request()->post('status', '');
        $category_id_1 = request()->post('category_id_1', '');
        $category_id_2 = request()->post('category_id_2', '');
        $category_id_3 = request()->post('category_id_3', '');
        $condition = '';
        if ($start_date != 0 && $end_date != 0) {
                $condition["gs.create_time"] = [
                    [
                        ">=",
                        $start_date
                    ],
                    [
                        "=<",
                        $end_date
                    ]
                ];
        } elseif ($start_date != 0 && $end_date == 0) {
                $condition["gs.create_time"] = [
                    [
                        ">=",
                        $start_date
                    ]
                ];
        } elseif ($start_date == 0 && $end_date != 0) {
                $condition["gs.create_time"] = [
                    [
                        "<=",
                        $end_date
                    ]
                ];
        }

        if ($status != "") {
            $condition["gs.status"] = $status;
        }
        if (! empty($goods_name)) {
                $condition["gs.title"] = array(
                    "like",
                    "%" . $goods_name . "%"
                );
        }
        if ($category_id_3 != "") {
            $condition["gs.tcate"] = $category_id_3;
        } elseif ($category_id_2 != "") {
            $condition["gs.ccate"] = $category_id_2;
        } elseif ($category_id_1 != "") {
            $condition["gs.pcate"] = $category_id_1;
        }
         //   $condition["ng.shop_id"] = $this->instance_id;
        $result = $goodsHandle->getGoodsList($page_index, $page_size, $condition,
         //   'gs.id, gs.sort, gs.title, gc.category_name, gs.market_price, gs.total, gs.sales, gs.status, sap.pic_cover_micro',
            [
                'gs.sort' => 'asc',
                'gs.create_time' => 'desc'
        ]);
        //return $result;
        return json(resultArray(0,"操作成功", $result));

    }
    

    /**
     * 功能说明：添加或更新商品时 ajax调用的函数
     */
    public function addOrUpdateGoods()
    {
        /*
        $goodsParams = array (   
            'goods_id' => request()->post('goods_id', 0),
            'category_id' => request()->post('category_id', 0),
            'ex_category_ids' => request()->post('ex_category_ids', 0),
            'sort' => request()->post('sort', 0),
            'title' => request()->post('title'),
            'sub_title' => request()->post('sub_title'),
            'short_title' => request()->post('short_title'),
            'keywords' => request()->post('keywords'),
            'unit' => request()->post('unit'),
            'status' => request()->post('status'),
            'type' => request()->post('type'),
            'thumb' => request()->post('thumb'),
   
            'thumb_url' => request()->post('thumb_url'),
            'thumb_first' => request()->post('thumb_first'),
            'product_price' => request()->post('product_price'),
            'market_price' => request()->post('market_price'),
            'cost_price' => request()->post('cost_price'),
            'sales' => request()->post('sales'),
            'is_new' => request()->post('is_new'),
            'is_hot' => request()->post('is_hot'),
            'is_recommand' => request()->post('is_recommand'),
            'is_send_free' => request()->post('is_send_free'),
            'is_no_discount' => request()->post('is_no_discount'),
            'cash' => request()->post('cash'),
            'dispatch_type' => request()->post('dispatch_type'),
            'dispatch_id' => request()->post('dispatch_id'),
             'dispatch_price' => request()->post('dispatch_price'),
            'invoice' => request()->post('invoice'),
            'quality' => request()->post('quality'),
            'repair' => request()->post('repair'),
            'seven' => request()->post('seven'),
            'province' => request()->post('province'),
            'city' => request()->post('city'),
            'groups_type' => request()->post('groups_type'),
            'auto_receive' => request()->post('auto_receive'),
            'can_no_trefund' => request()->post('can_no_trefund'),
            'goods_sn' => request()->post('goods_sn'),
            'product_sn' => request()->post('product_sn'),
            'weight' => request()->post('weight'),
            'total' => request()->post('total'),
            'show_total' => request()->post('show_total'),
             'total_cnf' => request()->post('total_cnf'),
            'has_option' => request()->post('has_option'),
            'content' => request()->post('content'),
            'spec_array' => request()->post('spec_array'),
            'goods_sku_array' => request()->post('goods_sku_array'),
            'goods_param_array' => request()->post('goods_param_array'));
       
     */
     
         foreach ($this->param as $k=>$v) {
             if ($v === true || $v === 'true') {
                 $this->param[$k] = 1;
             }
              if ($v === false || $v === 'false') {
                $this->param[$k] = 0;
             }
         }
                   
/*            
         $this->param ['is_hot'] =    ($this->param ['is_hot'] === 'true' || $this->param ['is_hot'] === true )? 1 : 0;
         $this->param ['is_new'] =    ($this->param ['is_new'] === 'true' || $this->param ['is_new'] === true )? 1 : 0;
         $this->param ['is_recommend'] =    ($this->param ['is_recommend'] === 'true' || $this->param ['is_recommend'] === true )? 1 : 0;
         $this->param ['is_member_discount'] =    ($this->param ['is_member_discount'] === 'true' || $this->param ['is_member_discount'] === true )? 1 : 0;
         $this->param ['cash'] =    ($this->param ['cash'] === 'true' || $this->param ['cash'] === true )? 1 : 0;
         $this->param ['cashier'] =    ($this->param ['cashier'] === 'true' || $this->param ['cashier'] === true )? 1 : 0;
         $this->param ['invoice'] =    ($this->param ['invoice'] === 'true' || $this->param ['invoice'] === true )? 1 : 0;
         $this->param ['repair'] =    ($this->param ['repair'] === 'true' || $this->param ['repair'] === true )? 1 : 0;
         $this->param ['seven'] =    ($this->param ['seven'] === 'true' || $this->param ['seven'] === true )? 1 : 0;
         $this->param ['quality'] =    ($this->param ['quality'] === 'true' || $this->param ['quality'] === true )? 1 : 0;
         $this->param ['groups_type'] =    ($this->param ['groups_type'] === 'true' || $this->param ['groups_type'] === true )? 1 : 0;
         $this->param ['can_no_trefund'] =    ($this->param ['can_no_trefund'] === 'true' || $this->param ['can_no_trefund'] === true )? 1 : 0;
         $this->param ['presell_over'] =    ($this->param ['presell_over'] === 'true' || $this->param ['presell_over'] === true )? 1 : 0;
         $this->param ['presell_start'] =    ($this->param ['presell_start'] === 'true' || $this->param ['presell_start'] === true )? 1 : 0;
       
         $this->param ['show_total'] =    ($this->param ['show_total'] === 'true' || $this->param ['show_total'] === true )? 1 : 0;
         $this->param ['has_option'] =    ($this->param ['has_option'] === 'true' || $this->param ['has_option'] === true )? 1 : 0;
         */
         
                            
        $goodsHandle = new GoodsHandle();
        $res = $goodsHandle->addOrEditGoods($this->param);
        
        if ($res===false) {
            return json(resultArray(2,$goodsHandle->getError()));
           
        } else {
             return json(resultArray(0,"操作成功", $res));
        }
    }

    public function getExpressCompanyList()
    {
        // 物流公司
        $express = new ExpressHandle();
        $condition = array(
                'is_enabled' => 1
            );
        $order = 'is_default desc, id asc';
        $expressCompanyList = $express->getExpressCompanyList(1, 0, $condition, $order);
        return json(resultArray(0,"操作成功", $expressCompanyList));
    }

    /**
     * 得到相应类型的商品列表
     */
    public function getGoodsListByType() {
        $page_index = isset($this->param["page_index"]) ? $this->param["page_index"] : 1;
        $page_size = isset($this->param["page_size"]) ? $this->param["page_size"] : 0;
        $type  = $this->param["type"];
        $condition["gs.type"] = $type;

      //  $condition["gs.status"] = 1;
        $orderby = " gs.sort desc, gs.id desc";
        $goodsHandle = new GoodsHandle();
        $goods_list = $goodsHandle->getGoodsList($page_index, $page_size, $condition, $orderby);
        return json(resultArray(0,"操作成功", $goods_list));
    }

    /**
     * ok-2ok
     * 删除商品
     */
    public function deleteGoods()
    {
        $goods_ids = request()->post('goods_ids');
        $goodHandle = new GoodsHandle();
        $retval = $goodHandle->deleteGoods($goods_ids);

        if (empty($retval)) {
            return json(resultArray(2,"删除失败"));
        } else {
            return json(resultArray(0,"删除成功"));
        }
    }

    /**
     * ok-2ok
     * 商品上架
     */
    public function modifyGoodsOnline()
    {
        $condition = $_POST["goods_ids"]; // 将商品id用,隔开
        $goods_detail = new GoodsHandle();
        $retval = $goods_detail->modifyGoodsOnline($condition);

        if ($retval) {
            return json(resultArray(0,"操作成功"));
        } else {
            return json(resultArray(2,"操作失败"));
        }
    }

    /**
     * ok-2ok
     * 商品下架
     */
    public function modifyGoodsOffline()
    {
        $condition = $_POST["goods_ids"]; // 将商品id用,隔开
        $goods_detail = new GoodsHandle();
        $retval = $goods_detail->modifyGoodsOffline($condition);

        if ($retval) {
            return json(resultArray(0,"操作成功"));
        } else {
            return json(resultArray(2,"操作失败"));
        }
    }

    /**
     * 获取筛选后的商品
     */
    public function getSearchGoodsList()
    {
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", PAGESIZE);
        $condition = isset($_POST['condition']) && $_POST['condition'] != '' ? (" title like  '%{$_POST['condition']}%'") : "";
        $goods_detail = new GoodsHandle();
        $result = $goods_detail->getSearchGoodsList($page_index, $page_size, $condition);
        return json(resultArray(0,"操作成功",  $result));
    }

    /**
     * 修改商品 推荐 1=热销 2=推荐 3=新品
     */
    public function modifyGoodsRecommend()
    {
        $goods_ids = $_POST["goods_id"];
        $recommend_type = $_POST["recommend_type"];
        $goods_detail = new GoodsHandle();
        $retval = $goods_detail->modifyGoodsRecommend($goods_ids, $recommend_type);
        if ($retval) {
            return json(resultArray(0,"操作成功"));
        } else {
            return json(resultArray(2,$goods_detail->getError()));
        }
    }

    /**
     * 商品分类选择
     */
    public function dialogSelectCategory()
    {
        $category_id = request()->post("category_id", 0);
        $goodsid = request()->post("goods_id", 0);
        $flag = request()->post("flag", 'category');
        // 扩展分类标签id,用户回调方法
        $box_id = request()->post("box_id", '');
        // 已选择扩展分类(用于控制重复选择)
        $category_extend_id = request()->post("category_extend_id", '');
        if (! empty($category_extend_id) && $category_id != 0) {
            $category_extend_id = explode(",", $category_extend_id);
            foreach ($category_extend_id as $k => $v) {
                if ($v == $category_id) {
                    unset($category_extend_id[$k]);
                }
            }
            sort($category_extend_id);
            $category_extend_id = implode(',', $category_extend_id);
        }

        $res = array (
            "flag" => $flag,
            "goods_id" =>  $goodsid,
            "box_id" =>  $box_id,
            "category_extend_id"=> $category_extend_id
         );
        /*
        $this->assign("flag", $flag);
        $this->assign("goodsid", $goodsid);
        $this->assign("box_id", $box_id);
        $this->assign("category_extend_id", $category_extend_id);
        */

        $goods_category_handle = new GoodsCategoryHandle();
        $list = $goods_category_handle->getGoodsCategoryListByParentId(0);
        $res['cateGoryList']= $list;
     //   $this->assign("cateGoryList", $list);
        $category_select_ids = "";
        $category_select_names = "";
        if ($category_id != 0) {
            $category_select_result = $goods_category_handle->getParentCategory($category_id);
            $category_select_ids = $category_select_result["category_ids"];
            $category_select_names = $category_select_result["category_names"];
        }
        $res['category_select_ids']=  $category_select_ids;
        $res['category_select_names']= $category_select_names;
      //  $this->assign("category_select_ids", $category_select_ids);
     //   $this->assign("category_select_names", $category_select_names);
        return json(resultArray(0,"操作成功", $res));
      //  return view($this->style . 'Goods/dialogSelectCategory');
    }

    /**
     * 更改商品排序
     */
    public function updateGoodsSort()
    {
        $goods_id = request()->post("goods_id", "");
        $sort = request()->post("sort", "");
        $goodsHandle = new GoodsHandle();
        $res = $goodsHandle->updateGoodsSort($goods_id, $sort);
        if ($res > 0) {
            return json(resultArray(0,"操作成功"));
        } else {
            return json(resultArray(2,"操作失败"));
        }
    }

    /**
     * 获取省列表，商品添加时用户可以设置商品所在地
     */
    public function getProvince()
    {
        $address = new AddressHandle();
        $province_list = $address->getProvinceList();

        return json(resultArray(0,"操作成功",$province_list));
    }

    /**
     * 获取城市列表
     */
    public function getCity()
    {
        $address = new AddressHandle();
        $province_id = isset($this->param['province_id']) ? $this->param['province_id'] : 0;
        $city_list = $address->getCityList($province_id);
        return json(resultArray(0,"操作成功",$city_list));
    }
}

