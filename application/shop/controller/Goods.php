<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-08-20
 * Time: 18:58
 */
/**
 * @date : 2017.08.17
 * @version : v1.0
 */

namespace app\shop\controller;
use app\shop\controller\BaseController;
use dao\handle\GoodsCategoryHandle;
use dao\handle\GoodsHandle as GoodsHandle;
use dao\handle\AddressHandle as AddressHandle;
use dao\handle\MemberHandle;
use dao\handle\promotion\GoodsExpressHandle;
use think\Session;

//use data\service\GoodsBrand as GoodsBrand;


class Goods extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 商品详情
     */
    public function goodsDetail()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");


        $goods_id = isset($this->param['goods_id']) ? $this->param['goods_id'] : 0;
        if ($goods_id == 0) {
          //  $this->error("没有获取到商品信息");
            return json(resultArray(2,"没有获取到商品信息"));
        }
        $goodsHandle = new GoodsHandle();
        $goods_detail = $goodsHandle->getGoodsDetail($goods_id, $user_id);

        if(empty($goods_detail))
        {
          //  $this->error("没有获取到商品信息");
            return json(resultArray(2,"没有获取到商品信息"));
        }
        //把属性值相同的合并
        /*
        $goods_attribute_list = $goods_detail['goods_attribute_list'];
        $goods_attribute_list_new =array();
        foreach($goods_attribute_list as $item){
            $attr_value_name = '';
            foreach ($goods_attribute_list as $key=>$item_v){
                if($item_v['attr_value_id'] == $item['attr_value_id']){
                    $attr_value_name .= $item_v['attr_value_name']. ',';
                    unset($goods_attribute_list[$key]);
                }
            }
            if(!empty($attr_value_name)){
                array_push($goods_attribute_list_new, array('attr_value_id'=>$item['attr_value_id'],'attr_value'=>$item['attr_value'],'attr_value_name'=>rtrim($attr_value_name,',')));
            }

        }
        $goods_detail['goods_attribute_list'] = $goods_attribute_list_new;
       */

      //   * 根据定位的用户位置查询当前运费
        /*
        $user_location = get_city_by_ip();
        $this->assign("user_location", get_city_by_ip()); // 获取用户位置信息
        if ($user_location['status'] == 1) {
            // 定位成功，查询当前城市的运费
            $goods_express = new GoodsExpress();
            $address = new Address();
            $province = $address->getProvinceId($user_location["province"]);
            $city = $address->getCityId($user_location["city"]);
            $express = $goods_express->getGoodsExpressTemplate($goods_id, $province['province_id'], $city['city_id']);
            $goods_info["shipping_fee_name"] = $express;
        }

        $this->assign('goods_info',$goods_info['shipping_fee_name']);
        */
        //var_dump($goods_info['shipping_fee_name']);
        /*
        $this->assign("goods_detail", $goods_detail);
        $this->assign("shopname", $this->shop_name);
        $this->assign("price", intval($goods_detail["promotion_price"]));
        $this->assign("goods_id", $goods_id);
        $this->getCartInfo($goods_id);
        // 分享
        $ticket = $this->getShareTicket();
        $this->assign("signPackage", $ticket);
        // 评价数量
        $evaluates_count = $goods->getGoodsEvaluateCount($goods_id);
        $this->assign('evaluates_count', $evaluates_count);
        return view($this->style . 'Goods/goodsDetail');
        */
        $goods_detail["price"] = intval($goods_detail["promotion_price"]);
        return json(resultArray(0,"操作成功", $goods_detail));
    }

    /**
     * ok-2ok
     * 得到商品优惠券
     * @return \think\response\Json
     */
    public function goodsGoupon() {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");

        $goods_id = request()->post("goods_id");
        $goods = new GoodsHandle();
        $coupon_list = $goods->getGoodsCoupon($goods_id, $user_id);
        return json(resultArray(0,"操作成功", $coupon_list));

    }


    /**
     * 商品详情
     *
     * @return Ambigous <\think\response\View, \think\response\$this, \think\response\View>
     */
    public function goodsDetail2()
    {

        $goods_id = isset($this->param['goods_id']) ? $this->param['goods_id'] : 0;

        if ($goods_id == 0) {
            return json(resultArray(2,"没有获取到商品信息"));
        }
        $goods = new GoodsHandle();
        $goods_detail = $goods->getGoodsDetail($goods_id);
        if (empty($goods_detail)) {
            return json(resultArray(2,"没有获取到商品信息"));
        }
        // 把属性值相同的合并


        // 获取当前时间
        $current_time = $this->getCurrentTime();
        $this->assign('ms_time', $current_time);
        $this->assign("goods_detail", $goods_detail);
        $this->assign("shopname", $this->shop_name);
        $this->assign("price", intval($goods_detail["promotion_price"]));
        $this->assign("goods_id", $goods_id);
        $this->assign("title_before",$goods_detail['goods_name']);

        // 分享
        $ticket = $this->getShareTicket();
        $this->assign("signPackage", $ticket);



        // 查询点赞记录表，获取详情再判断当天该店铺下该商品该会员是否已点赞


        // 当前用户是否收藏了该商品


        return view($this->style . 'Goods/goodsDetail');
    }



    /**
     * 商品详情
     */
    public function goodsDetail1()
    {
        $goods_id = isset($this->param['goods_id']) ? $this->param['goods_id'] : 0;

        if ($goods_id == 0) {
            return json(resultArray(2,"没有获取到商品信息"));
        }
        $goods_handle = new GoodsHandle();
        $goods_detail = $goods_handle->getGoodsDetail($goods_id);
        if (empty($goods_detail)) {
            return json(resultArray(2,"没有获取到商品信息"));
        }


        // 获取当前时间
        $current_time = $this->getCurrentTime();
        $this->assign('ms_time', $current_time);
        $this->assign("goods_detail", $goods_detail);

        $this->assign("price", intval($goods_detail["promotion_price"]));
        $this->assign("goods_id", $goods_id);
     //   $this->getCartInfo($goods_id);
        // 分享
        $ticket = $this->getShareTicket();
        $this->assign("signPackage", $ticket);
        // 评价数量
       // $evaluates_count = $goods->getGoodsEvaluateCount($goods_id);
      //  $this->assign('evaluates_count', $evaluates_count);
        // 美洽客服
      //  $shop_id = $this->instance_id;
      //  $config_service = new WebConfig();
      // $list = $config_service->getcustomserviceConfig($shop_id);
     //   if (empty($list)) {
     //       $list['id'] = '';
      //      $list['value']['service_addr'] = '';
     //   }
     //   $this->assign("list", $list);

        // 查询点赞记录表，获取详情再判断当天该店铺下该商品该会员是否已点赞
        /*
        $goods = new GoodsService();
        $shop_id = $this->instance_id;
        $uid = $this->uid;
        $click_detail = $goods->getGoodsSpotFabulous($shop_id, $uid, $goods_id);
        $this->assign('click_detail', $click_detail);
*/
        // 当前用户是否收藏了该商品
        /*
        if (isset($this->uid)) {
            $member = new Member();
            $is_member_fav_goods = $member->getIsMemberFavorites($this->uid, $goods_id, 'goods');
        }
        $this->assign("is_member_fav_goods", $is_member_fav_goods);
*/
       // return view($this->style . 'Goods/goodsDetail');
    }

    public function currentTime()
    {
        $current_time = $this->getCurrentTime();
        return json(resultArray(0, "操作成功", $current_time));
    }

    /**
     * ok-2ok
     * 得到美肤日志主商品id
     * @return \think\response\Json
     */
    public function getMainGoodsId() {
        $goods_handle = new GoodsHandle();
        $goods_id = $goods_handle->getMainGoodsId();
        if ($goods_id <= 0) {
            return json(resultArray(2, "主商品不存在", 0));
        } else {
            return json(resultArray(0, "操作成功", $goods_id));
        }
    }

    /**
     * 得到分享ticket
     * @return \think\response\Json
     */
    public function getWxShareTicket()
    {
        $ticket = $this->getShareTicket();
        return json(resultArray(0, "操作成功", $ticket));
    }
        //$this->assign("signPackage", $ticket);


    /**
     * ok-2ok
     * 查询当前商品的运费
     */
    public function getShippingFeeNameByLocation()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");

        $goods_id = request()->post("goods_id");
        $province = request()->post("province");
        $city = request()->post("city");
        $district = request()->post("district");
        $express = "";
        if (! empty($goods_id)) {

           // $user_location = get_city_by_ip();
           // if ($user_location['status'] == 1) {
                // 定位成功，查询当前城市的运费
                $goods_express = new GoodsExpressHandle();
                $address = new AddressHandle();
                $province = $address->getProvinceId($province);
                $city = $address->getCityId($city);
                $district =  $address->getDistrictId($district);
                    //$address->getCityFirstDistrict($city['id']);
              //  $express = $goods_express->getGoodsExpressTemplate($goods_id, $province['id'], $city['id'], $district, $user_id);
              $express = $goods_express->getGoodsExpressTemplate($goods_id, $province, $city, $district, $user_id);
                //getGoodsExpressTemplate($goods_id, $province_id, $city_id, $district_id)
          //  }
        }
        return json(resultArray(0, "操作成功", $express));
      //  return $express;
    }

    /**
     * 得到当前时间戳的毫秒数
     *
     * @return number
     */
    public function getCurrentTime()
    {
        $time = time();
        $time = $time * 1000;
        return $time;
    }

    /**
     * 功能：商品评论
     * 创建人：李志伟
     * 创建时间：2017年2月23日11:12:57
     */

    /**
     * 平台商品分类列表
     */
    public function goodsClassificationList()
    {
        $goods_category_handle  = new GoodsCategoryHandle();
        $goods_category_list_1 = $goods_category_handle->getGoodsCategoryList(1, 0, [
            "status" => 1,
            "level" => 1
        ],'sort');

        $goods_category_list_2 = $goods_category_handle->getGoodsCategoryList(1, 0, [
            "status" => 1,
            "level" => 2
        ],'sort');
        $goods_category_list_3 = $goods_category_handle->getGoodsCategoryList(1, 0, [
            "status" => 1,
            "level" => 3
        ],'sort');

        $goods_category = array (
            "goods_category_list_1" =>  $goods_category_list_1["data"],
            "goods_category_list_2" => $goods_category_list_2["data"],
             "goods_category_list_3" =>  $goods_category_list_3["data"]
        );
        return json(resultArray(0,"操作成功", $goods_category));

    }

    /**
     * 商品分类列表
     */
    public function goodsCategoryList()
    {
        $goodscateHandle = new GoodsCategoryHandle();
        $one_list = $goodscateHandle->getGoodsCategoryListByParentId(0, 1);
        if (! empty($one_list)) {
            foreach ($one_list as $k => $v) {
                $two_list = array();
                $two_list = $goodscateHandle->getGoodsCategoryListByParentId($v['id'], 1);
                $v['child_list'] = $two_list;
                if (! empty($two_list)) {
                    foreach ($two_list as $k1 => $v1) {
                        $three_list = array();
                        $three_list = $goodscateHandle->getGoodsCategoryListByParentId($v1['id'], 1);
                        $v1['child_list'] = $three_list;
                    }
                }
            }
        }
      //  return $one_list;
        return json(resultArray(0,"操作成功", $one_list));
    }

    /**
     * 商品分类列表
     */
    public function getNoChildCategoryList()
    {
        $goodscateHandle = new GoodsCategoryHandle();
        $one_list = $goodscateHandle->getGoodsCategoryListByParentId(0,1);
        $res = array();
        if (! empty($one_list)) {
            foreach ($one_list as $k => $v) {
                if ($v['is_parent'] == 0) {
                    array_push($res, $v);
                }
                $two_list = array();
                $two_list = $goodscateHandle->getGoodsCategoryListByParentId($v['id'],1);
                $v['child_list'] = $two_list;
                if (! empty($two_list)) {
                    foreach ($two_list as $k1 => $v1) {
                        if ($v1['is_parent'] == 0) {
                            array_push($res,$v1);
                        }
                        $three_list = array();
                        $three_list = $goodscateHandle->getGoodsCategoryListByParentId($v1['id'],1);
                        $v1['child_list'] = $three_list;
                        if (!empty($three_list)) {
                            array_push($res,$three_list);
                        }
                    }
                }
            }
        }
        //  return $one_list;
        return json(resultArray(0,"操作成功", $res));
    }

    /**
     * 加入购物车前显示商品规格
     */
    public function joinCartInfo()
    {
        $goodsHandle = new GoodsHandle();
        $goods_id = isset($this->param['goods_id']) ? $this->param['goods_id'] : '';
        $goods_detail = $goodsHandle->getGoodsDetail($goods_id);
        return json(resultArray(0,"操作成功", $goods_detail));
        /*
        $this->assign("goods_detail", $goods_detail);
        $this->assign("style",$this->style);
        return view($this->style . 'joinCart');
        */
    }

    /**
     * 搜索商品显示
     */
    public function goodsSearchList()
    {

        $sear_name = isset($this->param['sear_name']) ? $this->param['sear_name'] : '';
        $sear_type = isset($this->param['sear_type']) ? $this->param['sear_type'] : '';
        $order_state = isset($this->param['order_status']) ? $this->param['order_status'] : 'desc';
        $controlType = isset($this->param['controlType']) ? $this->param['controlType'] : '';
         //   $shop_id = isset($_POST['shop_id']) ? $_POST['shop_id'] : '';
        $goodsHandle = new GoodsHandle();
        $condition['title'] = [
                'like',
                '%' . $sear_name . '%'
        ];
            // 排序类型
        switch ($sear_type) {
                case 1:
                    $order = 'create_time desc'; // 时间
                    break;
                case 2:
                    $order = 'sales desc'; // 销售
                    break;
                case 3:
                    $order = 'promotion_price ' . $order_state; // 促销价格
                    break;
                default:
                    $order = '';
                    break;
        }
        switch ($controlType) {
                case 1:
                    $condition = [
                        'is_new' => 1
                    ];
                    break;
                case 2:
                    $condition = [
                        'is_hot' => 1
                    ];
                    break;
                case 3:
                    $condition = [
                        'is_recommend' => 1
                    ];
                    break;
                default:
                    break;
        }
        $condition['gs.status'] = 1;
        /*
            if (! empty($shop_id)) {
                $condition['ng.shop_id'] = $shop_id;
            }
             */
        $search_good_list = $goodsHandle->getGoodsList(1, 0, $condition, $order);
        return json(resultArray(0,"操作成功", $search_good_list['data']));
           // return $search_good_list['data'];

    }

    /**
     * 商品列表
     */
    public function goodsList()
    {
        // 查询购物车中商品的数量
        /*
        $uid = $this->uid;
        $goods = new GoodsService();
        $cartlist = $goods->getCart($uid);
        $this->assign('uid', $uid);
        $this->assign("carcount", count($cartlist));
        */
        $category_id = isset($this->param["category_id"]) ? $this->param["category_id"] : ""; // 商品分类
        $brand_id = isset($this->param["brand_id"]) ? $this->param["brand_id"] : ""; // 品牌
        $order = isset($this->param["order"]) ? $this->param["order"] : ""; // 商品排序分类
        $sort = isset($this->param["sort"]) ? $this->param["sort"] : "desc"; // 商品排序分类

        switch ($order) {
                case 1: // 销量
                    $order = 'sales ';
                    break;
                case 2: // 新品
                    $order = 'is_new ';
                    break;
                case 3: // 价钱
                    $order = 'promotion_price ';
                    break;
                default:
                    $order = 'sale_time ';
                    break;
        }

        $orderby = ""; // 排序方式
        if ($order != "") {
                $orderby = $order . " " . $sort.",gs.sort desc, gs.id desc";
        }else{
                $orderby = " gs.sort desc, gs.id desc";
        }

        $condition = array();
        if (! empty($category_id)) {
                $condition["gs.category_id"] = $category_id;
        } else
            if (! empty($brand_id)) {
                    $condition["gs.brand_id"] = array(
                        "in",
                        $brand_id
                    );
            }
        $condition["gs.status"] = 1;
        $goodsHandle = new GoodsHandle();
        $goods_list = $goodsHandle->getGoodsList(1, 0, $condition, $orderby);
        return json(resultArray(0,"操作成功", $goods_list));
           // return $goods_list;
            /*
        } else {
            $category_id = isset($_GET["category_id"]) ? $_GET["category_id"] : ""; // 商品分类
            $brand_id = isset($_GET["brand_id"]) ? $_GET["brand_id"] : ""; // 品牌
            $this->assign('brand_id', $brand_id);
            $this->assign('category_id', $category_id);
            return view($this->style . 'Goods/goodsList');
        }
            */
    }

    /**
     * 得到热销、新品、推荐商品
     * key=1:推荐, key=2:热销, key=3:新品
     */
    public function getGoodsListByRecommendHotNew() {
        $page_index = isset($this->param["page_index"]) ? $this->param["page_index"] : 1;
        $page_size = isset($this->param["page_size"]) ? $this->param["page_size"] : 0;
        $key  = isset($this->param["key"]) ? $this->param["key"] : 1;
        if ($key == 1) {
            $condition["gs.is_recommend"] = 1; //推荐
        } else if ($key == 2) {
            $condition["gs.is_hot"] = 1; //热销
        } else if ($key == 3) {
            $condition["gs.is_new"] = 1; //新品
        }
        $condition["gs.status"] = 1;
        $orderby = " gs.sort desc, gs.id desc";
        $goodsHandle = new GoodsHandle();
        $goods_list = $goodsHandle->getGoodsList($page_index, $page_size, $condition, $orderby);
        return json(resultArray(0,"操作成功", $goods_list));
    }





    /**
     * 将属性字符串转化为数组
     */
    private function stringChangeArray($string)
    {
        if (trim($string) != "") {
            $temp_array = explode(";", $string);
            $attr_array = array();
            foreach ($temp_array as $k => $v) {
                $v_array = array();
                if (strpos($v, ",") === false) {
                    $attr_array = array();
                    break;
                } else {
                    $v_array = explode(",", $v);
                    if (count($v_array) != 3) {
                        $attr_array = array();
                        break;
                    } else {
                        $attr_array[] = $v_array;
                    }
                }
            }
            return $attr_array;
        } else {
            return array();
        }
    }

    /**
     * 获取所有地址：省市县
     */
    public function getAddress()
    {
        // 省
        $addressHandle = new AddressHandle();
        $province_list = $addressHandle->getProvinceList();
        $list["province_list"] = $province_list;

        // 市
        $city_list = $addressHandle->getCityList();
        $list["city_list"] = $city_list;
        // 区县
        $district_list = $addressHandle->getDistrictList();
        $list["district_list"] = $district_list;

        return json(resultArray(0,"操作成功", $list));
    }

    /**
     * 查询商品的sku信息
     */
    public function getGoodsSkuInfo()
    {
        $goods_id = $_POST["goods_id"];
        $goodsHandle = new GoodsHandle();
        $res = $goodsHandle->getGoodsSku($goods_id);
        return json(resultArray(0,"操作成功", $res));

    }

    /**
     * 根据条件查询商品列表：商品分类查询，关键词查询，价格区间查询，品牌查询
     */
    public function getGoodsListByConditions($category_id, $brand_id, $min_price, $max_price, $keyword, $page, $page_size, $order, $is_send_free, $stock, $platform_proprietary, $province_id, $attr_array, $spec_array)
    {
        $goodsHandle = new GoodsHandle();
        $condition = null;
        if ($category_id != "") {
            // 商品分类Id
            $condition["gs.category_id"] = $category_id;
        }
        // 品牌Id
        if ($brand_id != "") {
            $condition["gs.brand_id"] = array(
                "in",
                $brand_id
            );
        }

        // 价格区间
        if ($max_price != "") {
            $condition["gs.promotion_price"] = [
                [
                    ">=",
                    $min_price
                ],
                [
                    "<=",
                    $max_price
                ]
            ];
        }
        // 关键词
        if ($keyword != "") {
            $condition["gs.goods_name"] = array(
                "like",
                "%" . $keyword . "%"
            );
        }

        // 包邮
        if ($is_send_free != "") {
            $condition["gs.is_send_free"] = $is_send_free;
        }

        // 仅显示有货
        if ($stock != "") {
            $condition["gs.total"] = array(
                ">",
                $stock
            );
        }

        // 平台直营
        /*
        if ($platform_proprietary != "") {
            $condition["ng.shop_id"] = $platform_proprietary;
        }
        */

        // 商品所在地
        if ($province_id != "") {
            $condition["gs.province"] = $province_id;
        }
        // 属性 (条件拼装)
        /*
        $array_count = count($attr_array);
        $goodsid_str = "";
        $attr_str_where = "";
        if (! empty($attr_array)) {
            // 循环拼装sql属性条件
            foreach ($attr_array as $k => $v) {
                if ($attr_str_where == "") {
                    $attr_str_where = "(attr_value_id = '$v[2]' and attr_value_name='$v[1]')";
                } else {
                    $attr_str_where = $attr_str_where . " or " . "(attr_value_id = '$v[2]' and attr_value_name='$v[1]')";
                }
            }
            if ($attr_str_where != "") {
                $attr_query = $this->goods->getGoodsAttributeQuery($attr_str_where);

                $attr_array = array();
                foreach ($attr_query as $t => $b) {
                    $attr_array[$b["goods_id"]][] = $b;
                }
                $goodsid_str = "0";
                foreach ($attr_array as $z => $x) {
                    if (count($x) == $array_count) {
                        if ($goodsid_str == "") {
                            $goodsid_str = $z;
                        } else {
                            $goodsid_str = $goodsid_str . "," . $z;
                        }
                    }
                }
            }
        }
        */
        // 规格条件拼装
        /*
        $spec_count = count($spec_array);
        $spec_where = "";
        if ($spec_count > 0) {
            foreach ($spec_array as $k => $v) {
                if ($spec_where == "") {
                    $spec_where = " attr_value_items_format like '%{$v}%' ";
                } else {
                    $spec_where = $spec_where . " or " . " attr_value_items_format like '%{$v}%' ";
                }
            }

            if ($spec_where != "") {

                $goods_query = $this->goods->getGoodsSkuQuery($spec_where);
                $temp_array = array();
                foreach ($goods_query as $k => $v) {
                    $temp_array[] = $v["goods_id"];
                }
                $goods_query = array_unique($temp_array);
                if (! empty($goods_query)) {
                    if ($goodsid_str != "") {
                        $attr_con_array = explode(",", $goodsid_str);
                        $goods_query = array_intersect($attr_con_array, $goods_query);
                        $goods_query = array_unique($goods_query);
                        $goodsid_str = "0," . implode(",", $goods_query);
                    } else {
                        $goodsid_str = "0,";
                        $goodsid_str .= implode(",", $goods_query);
                    }
                } else {
                    $goodsid_str = "0";
                }
            }
        }

        if ($goodsid_str != "") {
            $condition["goods_id"] = [
                "in",
                $goodsid_str
            ];
        }
        */
        $condition['gs.status'] = 1;
        $list = $goodsHandle->getGoodsList($page, $page_size, $condition, $order);

        return $list;
    }

    /**
     * 根据关键词返回商品列表
     */
    public function getGoodsListByKeyWord()
    {
        $page_index = 1;
        $page_size = 0;
        $keyword = "";
        $order = "";
        $list = null;
        $goodsHandle = new GoodsHandle();
        if (isset($this->param["keyword"])) {
            $page_index = $this->param["page_index"];
            $page_size = $this->param["page_size"];
            $keyword = $this->param["keyword"];
            $order = $this->param["order"];
          //  $goodsHandle = new GoodsHandle();
            $list = $goodsHandle->getGoodsList($page_index, $page_size, array(
                "gs.title" => array(
                    "like",
                    "%" . $keyword . "%"
                ),
                "gs.status" => 1
            ), $order);
        } else {
            // 没有条件，查询全部
            $list = $goodsHandle->getGoodsList($page_index, $page_size, array("gs.status" => 1), $order);
        }
        return json(resultArray(0,"操作成功", $list));
        //  return $list;
    }

    /**
     * 获取销量排行榜的商品列表
     */
    public function getSalesGoodsList()
    {
        $goodsHandle = new GoodsHandle();
        $condition = array("gs.status" => 1);
        $list = $goodsHandle->getGoodsList(1, 3, $condition, "sales desc");
        //  return $list["data"];
        return json(resultArray(0,"操作成功", $list["data"]));
    }

    /**
     * 获取商品详情
     */
    public function getGoodsDetail()
    {
        $goodsHandle = new GoodsHandle();
        $goods_id = isset($this->param['goods_id']) ? $this->param['goods_id'] : '';
        $goods_detail = $goodsHandle->getGoodsDetail($goods_id);
        // return $goods_detail;
        return json(resultArray(0,"操作成功", $goods_detail));
    }

    /**
     * 随机获取商品列表
     */
    public function getRandGoodsList()
    {
        $goodsHandle = new GoodsHandle();
        $res = $goodsHandle->getRandGoodsList();
        //   return $res;
        return json(resultArray(0,"操作成功", $res));
    }

    /****************************************** 2017-11-04*****************************/
    /**
     * 积分中心
     *
     * @return \think\response\View
     */
    public function integralCenter()
    {
        $platform = new Platform();
        // 积分中心广告位
        $discount_adv = $platform->getPlatformAdvPositionDetail(1165);
        $this->assign('discount_adv', $discount_adv);
        // 积分中心商品
        $this->goods = new GoodsService();
        $order = "";
        // 排序
        $id = request()->get('id', '');
        if ($id) {
            if ($id == 1) {
                $order = "sales desc";
            } else
                if ($id == 2) {
                    $order = "collects desc";
                } else
                    if ($id == 3) {
                        $order = "evaluates desc";
                    } else
                        if ($id == 4) {
                            $order = "shares desc";
                        } else {
                            $id = 0;
                            $order = "";
                        }
        } else {
            $id = 0;
        }

        $page_index = request()->get('page', 1);
        $condition = array(
            "ng.state" => 1,
            "ng.point_exchange_type" => array(
                'NEQ',
                0
            )
        );
        $page_count = 25;
        $hotGoods = $this->goods->getGoodsList(1, 4, $condition, $order);
        $allGoods = $this->goods->getGoodsList($page_index, $page_count, $condition, $order);
        if ($page_index) {
            if (($page_index > 1 && $page_index <= $allGoods["page_count"])) {
                $page_index = 1;
            }
        }
        $this->assign("id", $id);
        $this->assign('page', $page_index);
        $this->assign("allGoods", $allGoods);
        $this->assign("hotGoods", $hotGoods);
        $this->assign('page_count', $allGoods['page_count']);
        $this->assign('total_count', $allGoods['total_count']);
        return view($this->style . 'Goods/integralCenter');
    }

    /**
     * 积分中心 全部积分商品
     *
     * @return \think\response\View
     */
    public function integralCenterList()
    {
        return view($this->style . 'Goods/integralCenterList');
    }

    /**
     * 积分中心全部商品Ajax
     */
    public function integralCenterListAjax()
    {
        $platform = new Platform();
        if (request()->isAjax()) {
            // 积分中心商品
            $this->goods = new GoodsService();
            $order = "";
            // 排序
            $id = request()->post('id', '');
            if ($id) {
                if ($id == 1) {
                    $order = "sales desc";
                } else
                    if ($id == 2) {
                        $order = "collects desc";
                    } else
                        if ($id == 3) {
                            $order = "evaluates desc";
                        } else
                            if ($id == 4) {
                                $order = "shares desc";
                            } else {
                                $id = 0;
                                $order = "";
                            }
            } else {
                $id = 0;
            }

            $page_index = request()->post('page', '1');
            $condition = array(
                "ng.state" => 1,
                "ng.point_exchange_type" => array(
                    'NEQ',
                    0
                )
            );
            $page_count = 25;
            $allGoods = $this->goods->getGoodsList($page_index, $page_count, $condition, $order);
            return $allGoods['data'];
        }
    }

    /**
     * 设置点赞送积分
     */
    public function getClickPoint()
    {
        if (request()->isAjax()) {
            $shop_id = $this->instance_id;
            $uid = $this->uid;
            $goods_id = request()->post('goods_id', '');
            $goods = new GoodsService();
            $retval = $goods->setGoodsSpotFabulous($shop_id, $uid, $goods_id);
            return AjaxReturn($retval);
        }
    }

    /**
     * 获取商品分类下的商品
     */
    public function getCategoryChildGoods()
    {
        if (request()->isAjax()) {
            $page = request()->post("page", 1);
            $category_id = request()->post("category_id", 0);
            $goods = new GoodsService();
            if ($category_id == 0) {
                $condition['ng.state'] = 1;
                $res = $goods->getGoodsList($page, PAGESIZE, $condition, "ng.sort desc,ng.create_time desc");
            } else {
                $condition['ng.category_id'] = $category_id;
                $condition['ng.state'] = 1;
                $res = $goods->getGoodsList($page, PAGESIZE, $condition, "ng.sort desc,ng.create_time desc");
            }
            return $res;
        }
    }



    /*************************** web专用*********************************/
    /**
     * 商品详情,web专用
     */
    public function goodsInfoWeb()
    {
        $goodsCategoryHandle = new GoodsCategoryHandle();
        $goodsHandle = new GoodsHandle();
        $goods_id = 0;
        $data = array();
        if (isset($this->param["goods_id"])) {

            //  $this->goods_group = new GoodsGroupService();
            //  $this->shop = new ShopService();
            //  $this->member = new MemberService();
            $goods_id = (int) $this->param["goods_id"];
            //    $this->assign('goods_id', $goodsid); // 将商品id传入方便查询当前商品的评论
            //    $this->member->addMemberViewHistory($goodsid);

            // 商品详情
            $goods_info = $goodsHandle->getGoodsDetail($goods_id);
            // var_dump($goods_info["sku_picture_list"]);
            if (empty($goods_info)) {
                //  $redirect = __URL(__URL__ . '/index');
                //     $this->redirect($redirect);
            }
            /**
             * sku多图数据
             * @var 
             */
            //   $sku_picture_list=$this->goods->getGoodsSkuPicture($goodsid);
            //   $goods_info["sku_picture_list"]=$sku_picture_list;
            // 规格图片
            // 判断规格数组中图片路径是id还是路径
            $spec_list = $goods_info["spec_list"];
            if (! empty($spec_list)) {
                /*
                $album = new Album();
                foreach ($spec_list as $k => $v) {
                    foreach ($v["value"] as $t => $m) {
                        if($m["spec_show_type"] == 3){
                            if (is_numeric($m["spec_value_data"])) {
                                $picture_detail = $album->getAlubmPictureDetail([
                                    "pic_id" => $m["spec_value_data"]
                                ]);

                                if (! empty($picture_detail)) {
                                    $spec_list[$k]["value"][$t]["spec_value_data"] = $picture_detail["pic_cover_micro"];
                                    $spec_list[$k]["value"][$t]["spec_value_data_big_src"] = $picture_detail["pic_cover_big"];
                                }else{
                                    $spec_list[$k]["value"][$t]["spec_value_data"] = '';
                                    $spec_list[$k]["value"][$t]["spec_value_data_big_src"] = '';
                                }
                            }else{
                                $spec_list[$k]["value"][$t]["spec_value_data_big_src"] = $m["spec_value_data"];
                            }
                        }

                    }
                }
                */
                $goods_info['spec_list'] = $spec_list;
            }
            // 把属性值相同的合并
            /*
            $goods_attribute_list = $goods_info['goods_attribute_list'];
            $goods_attribute_list_new = array();
            foreach ($goods_attribute_list as $item) {
                $attr_value_name = '';
                foreach ($goods_attribute_list as $key => $item_v) {
                    if ($item_v['attr_value_id'] == $item['attr_value_id']) {
                        $attr_value_name .= $item_v['attr_value_name'] . ',';
                        unset($goods_attribute_list[$key]);
                    }
                }
                if (! empty($attr_value_name)) {
                    array_push($goods_attribute_list_new, array(
                        'attr_value_id' => $item['attr_value_id'],
                        'attr_value' => $item['attr_value'],
                        'attr_value_name' => rtrim($attr_value_name, ',')
                    ));
                }
            }
            $goods_info['goods_attribute_list'] = $goods_attribute_list_new;
            */
            //   $goods_param_list = $goods_info['param_list'];
            /*
            $goods_info['member_price'] = sprintf("%.2f", $goods_info['member_price']);
            if ($goods_info['match_ratio'] == 0) {
                $goods_info['match_ratio'] = 100;
            }
            if ($goods_info['match_point'] == 0) {
                $goods_info['match_point'] = 5;
            }
            */
            // 处理小数
            /*
            $goods_info['match_ratio'] = round($goods_info['match_ratio'], 2);
            $goods_info['match_point'] = round($goods_info['match_point'], 2);
            $this->assign("goods_info", $goods_info);

            $Config = new Config();
            $seoconfig = $Config->getSeoConfig($this->instance_id);
            if (! empty($goods_info['keywords'])) {
                $seoconfig['seo_meta'] = $goods_info['keywords']; // 关键词
            }
            $seoconfig['seo_desc'] = $goods_info['goods_name'];
            */
            // 标题title(商品详情页面)
            /*
            $this->assign("title_before", $goods_info['goods_name'] . ' - ');
            $this->assign("seoconfig", $seoconfig);

            $this->assign("goods_sku_count", count($goods_info["sku_list"]));
            $this->assign("spec_list", count($goods_info["spec_list"]));

            $this->assign("shop_id", $goods_info['shop_id']); // 所属店铺id

            $default_gallery_img = ""; // 图片必须都存在才行
            for ($i = 0; $i < count($goods_info["img_list"]); $i ++) {
                if ($i == 0) {
                    $default_gallery_img = $goods_info["img_list"][$i]["pic_cover_big"];
                }
            }
            $this->assign("default_gallery_img", $default_gallery_img);
*/
            // 店内商品销量排行榜

            $goods_rank = $goodsHandle->getGoodsList(1, 0, array(
                "gs.category_id" => $goods_info["category_id"]
            ), "gs.sales desc");
            $data["goods_rank"] = $goods_rank["data"];
            //   $this->assign("goods_rank", $goods_rank["data"]);

            // 店内商品收藏数排行榜
            $goods_collection = $goodsHandle->getGoodsList(1, 0, array(
                "gs.category_id" => $goods_info["category_id"]
            ), "gs.collects desc");
            $data["goods_collection"] = $goods_collection["data"];
            //  $this->assign("goods_collection", $goods_collection["data"]);

            // 当前用户是否收藏了该商品,uid是从baseController获取到的
            /*
            $is_member_fav_goods = - 1;
            if (isset($this->uid)) {
                $is_member_fav_goods = $this->member->getIsMemberFavorites($this->uid, $goodsid, 'goods');
            }
            $this->assign("is_member_fav_goods", $is_member_fav_goods);
           */
            // 评价数量
            //    $evaluates_count = $goodsHandle->getGoodsEvaluateCount($goods_id); //商品评价数
            //  $this->assign('evaluates_count', $evaluates_count);
            //   $data["goods_collection"] = $evaluates_count;
            $integral_flag = 0; // 是否是积分商品

            if ($goods_info["point_exchange_type"] == 1) {
                $integral_flag ++;
                // 积分中心-->商品详情界面
            }
            //    $this->assign("integral_flag", $integral_flag);
            $data["integral_flag"] = $integral_flag;
            $consult_list = array();
            // 购买咨询 全部
            /*
            $this->goods = new GoodsService();
            $consult_list[0] = $this->goods->getConsultList(1, 5, [
                'goods_id' => $goodsid
            ], 'consult_addtime desc');
            // 商品咨询
            $consult_list[1] = $this->goods->getConsultList(1, 5, [
                'goods_id' => $goodsid,
                'ct_id' => 1
            ], 'consult_addtime desc');

            // 支付咨询
            $consult_list[2] = $pay_consult_list = $this->goods->getConsultList(1, 5, [
                'goods_id' => $goodsid,
                'ct_id' => 2
            ], 'consult_addtime desc');

            // 发票及保险咨询
            $consult_list[3] = $invoice_consult_list = $this->goods->getConsultList(1, 5, [
                'goods_id' => $goodsid,
                'ct_id' => 3
            ], 'consult_addtime desc');

            $this->assign('consult_list', $consult_list);
            */
            /*
            $user_location = get_city_by_ip();
            $this->assign("user_location", get_city_by_ip()); // 获取用户位置信息
            if ($user_location['status'] == 1) {
                // 定位成功，查询当前城市的运费
                $goods_express = new GoodsExpress();
                $address = new Address();
                $province = $address->getProvinceId($user_location["province"]);
                $city = $address->getCityId($user_location["city"]);
                $district = $address->getCityFirstDistrict($city['city_id']);
                $express = $goods_express->getGoodsExpressTemplate($goodsid, $province['province_id'], $city['city_id'], $district);
                $goods_info["shipping_fee_name"] = $express;
            }
            $web_info = $this->web_site->getWebSiteInfo();
            $this->assign('shipping_name', $goods_info["shipping_fee_name"]);
            */
            if (! $goods_info["category_id"] == "") {
                $category_name = $goodsCategoryHandle->getCategoryParentQuery($goods_info["category_id"]);
            } else {
                $category_name = "全部分类";
            }
            //    $this->assign("category_name", $category_name);
            $data["category_name"] = $category_name;
            // 获取商品的优惠劵
            /*
            $goods_coupon_list = $this->goods->getGoodsCoupon($goodsid, $this->uid);
            $this->assign("goods_coupon_list", $goods_coupon_list);

            if ($goods_info["promotion_info"] == '限时折扣') {
                // 活动-->商品详情界面
                return view($this->style . 'Goods/goodsInfoPromotion');
            } else {
                // 基础-->商品详情界面
                return view($this->style . 'Goods/goodsInfo');
            }
            */
            $data['goods_info']= $goods_info;
            return json(resultArray(0,"操作成功", $data));
        } else {
            /*
            $redirect = __URL(__URL__ . '/index');
            $this->redirect($redirect);
            */
        }

    }

    /**
     * 商品列表--web专用
     */
    public function goodsListWeb()
    {
        $data = array();
        $category_id = isset($this->param["category_id"]) ? $this->param["category_id"] : ""; // 商品分类
        $keyword = isset($this->param["keyword"]) ? $this->param["keyword"] : ""; // 关键词
        //  $goods_name = isset($this->param["goods_name"]) ? $this->param["goods_name"] : ""; // 关键词
        $is_send_free = isset($this->param["is_send_free"]) ? $this->param["is_send_free"] : ""; // 是否包邮，0：包邮；1：运费价格
        $stock = isset($this->param["stock"]) ? $this->param["stock"] : ""; // 仅显示有货，大于0
        $page = isset($this->param['page']) ? $this->param['page'] : '1'; // 当前页
        $order = isset($this->param["order"]) ? $this->param["order"] : "";
        $sort = isset($this->param["sort"]) ? $this->param["sort"] : "desc";
        $brand_id = isset($this->param['brand_id']) ? $this->param['brand_id'] : '';
        $brand_name = isset($this->param['brand_name']) ? $this->param['brand_name'] : ''; // 品牌名称
        $min_price = isset($this->param['min_price']) ? $this->param['min_price'] : ''; // 价格区间,最小
        $max_price = isset($this->param['max_price']) ? $this->param['max_price'] : ''; // 最大
        //     $platform_proprietary = isset($_GET["platform_proprietary"]) ? $_GET["platform_proprietary"] : ""; // 平台自营 shopid== 1
        $province_id = isset($this->param["province_id"]) ? $this->param["province_id"] : ""; // 商品所在地
        $province_name = isset($this->param["province_name"]) ? $this->param["province_name"] : ""; // 所在地名称
        // 属性筛选get参数
        // $attr = isset($_GET["attr"]) ? $_GET["attr"] : ""; // 属性值
        //     $spec = isset($this->param["spec"]) ? $this->param["spec"] : ""; // 规格值
        // $this->assign("attr_str", $attr);
        //  $this->assign("spec_str", $spec);
        // 将属性条件字符串转化为数组
        //  $attr_array = $this->stringChangeArray($attr);
        //   $this->assign("attr_array", $attr_array);
        // 规格转化为数组
        /* 处理规格 sku
        if ($spec != "") {
            $spec_array = explode(";", $spec);
        } else {
            $spec_array = array();
        }
        $spec_remove_array = array();
        foreach ($spec_array as $k => $v) {
            $spec_remove_array[] = explode(":", $v);
        }
        */
        $orderby = ""; // 排序方式
        if ($order != "") {
            $orderby = $order . " " . $sort . ",gs.sort asc";
        } else {
            $orderby = "gs.sort asc";
        }
        //  $this->assign("order", $order); // 要排序的字段
        $this->assign("sort", $sort); // 升序降序
        $data['order'] = $order;
        $data['sort'] = $sort;

        $goods_category_handle = new GoodsCategoryHandle();
        if ($category_id != "") {
            // 获取商品分类下的品牌列表、价格区间
            $category_brands = null;
            $category_price_grades = null;

            // 查询品牌列表，用于筛选
            //    $category_brands = $this->goods_category->getGoodsCategoryBrands($category_id);

            // 查询价格区间，用于筛选
            //   $category_price_grades = $this->goods_category->getGoodsCategoryPriceGrades($category_id);
            $category_count = 0; // 默认没有数据
            //   if ($category_brands != "") {
            //         $category_count = 1; // 有数据
            //    }
            $goodsCategoryHandle = new GoodsCategoryHandle();
            $goods_category_info = $goodsCategoryHandle->getGoodsCategoryDetail($category_id);
            /*
           $Config = new Config();
           $seoconfig = $Config->getSeoConfig($this->instance_id);
           if (! empty($goods_category_info['keywords'])) {
               $seoconfig['seo_meta'] = $goods_category_info['keywords']; // 关键词
           }
           $seoconfig['seo_desc'] = $goods_category_info['category_name'];
           // 标题title(商品详情页面)
           $this->assign("title_before", $goods_category_info['keywords'] . ' - ');
           $this->assign("seoconfig", $seoconfig);
            */
            //    $attr_id = $goods_category_info["attr_id"];
            // 查询商品分类下的属性和规格集合
            /*
            $goods_attribute = $this->goods->getAttributeInfo([
                "attr_id" => $attr_id
            ]);
            $attribute_detail = $this->goods->getAttributeServiceDetail($attr_id, [
                'is_search' => 1
            ]);
            */
            /*
            $attribute_list = array();
            if (! empty($attribute_detail['value_list']['data'])) {
                $attribute_list = $attribute_detail['value_list']['data'];
                foreach ($attribute_list as $k => $v) {
                    $is_unset = 0;
                    if (! empty($attr_array)) {
                        foreach ($attr_array as $t => $m) {
                            if (trim($v["attr_value_id"]) == trim($m[2])) {
                                unset($attribute_list[$k]);
                                $is_unset = 1;
                            }
                        }
                    }
                    if ($is_unset == 0) {
                        $value_items = explode(",", $v['value']);
                        $attribute_list[$k]['value'] = trim($v["value"]);
                        $attribute_list[$k]['value_items'] = $value_items;
                    }
                }
            }
            $attr_list = $attribute_list;
            */
            // 查询本商品类型下的关联规格
            /*
            $goods_spec_array = array();
            if ($goods_attribute["spec_id_array"] != "") {
                $goods_spec_array = $this->goods->getGoodsSpecQuery([
                    "spec_id" => [
                        "in",
                        $goods_attribute["spec_id_array"]
                    ]
                ]);
                foreach ($goods_spec_array as $k => $v) {
                    if (! empty($spec_remove_array)) {
                        foreach ($spec_remove_array as $t => $m) {
                            if ($v["spec_id"] == $m[0]) {
                                $spec_remove_array[$t][2] = $v["spec_name"];
                                foreach ($v["values"] as $z => $c) {
                                    if ($c["spec_value_id"] == $m[1]) {
                                        $spec_remove_array[$t][3] = $c["spec_value_name"];
                                    }
                                }
                                unset($goods_spec_array[$k]);
                            }
                        }
                    }
                }
                sort($goods_spec_array);
            }
             */
            /*
            $this->assign("attr_or_spec", $attr_list);
            $this->assign("category_brands", $category_brands);
            $this->assign("category_count", $category_count);
            $this->assign("category_price_grades", $category_price_grades);
            $this->assign("category_price_grades_count", count($category_price_grades));
            $this->assign("goods_spec_array", $goods_spec_array); // 分类下的规格
            */
        }
        // var_dump($goods_spec_array[0]["values"]);
        //新品推荐
        /*
        $this->platform = new PlatformService(); // 新品推荐
        $goods_new_list = $this->platform->getPlatformGoodsRecommend(1);
        $this->assign("goods_new_list", $goods_new_list);
       */
        // 销量排行榜
       // getSalesGoodsList()
      //  $goods_sales_list = $this->getSalesGoodsList($category_id);
          $goods_sales_list = $this->getSalesGoodsList();
        //  $this->assign("goods_sales_list", $goods_sales_list);
        $data["goods_sales_list"] = $goods_sales_list;

        // 浏览历史
        /*
        $member_histrorys = $this->getMemberHistories();
        $this->assign('member_histrorys', $member_histrorys);
        */
        // 猜您喜欢
        /*
        $guess_member_likes = $this->member->getGuessMemberLikes();
        $this->assign("guess_member_likes", $guess_member_likes);
        $this->assign("guess_member_likes_count", count($guess_member_likes));
      */
        // -----------------查询条件筛选---------------------
        //$this->assign("category_id", $category_id); // 商品分类ID
        //  $this->assign("brand_id", $brand_id); // 品牌ID
        //$this->assign("brand_name", $brand_name); // 品牌ID
        // $this->assign("min_price", $min_price); // 最小
        // $this->assign("max_price", $max_price); // 最大
        //  $this->assign("shipping_fee", $shipping_fee); // 是否包邮
        //   $this->assign("stock", $stock); // 仅显示有货
        //  $this->assign("platform_proprietary", $platform_proprietary); // 平台自营
        $this->assign("province_name", $province_name);
        $data['condition']["category_id"] = $category_id;
        $data['condition']["min_price"] = $min_price;
        $data['condition']["max_price"] = $max_price;
        $data['condition']["is_send_free"] =  $is_send_free;
        $data['condition']["stock"] = $stock;
        $page_size = 12;
        // -----------------查询条件筛选----------------------
        /*
           $url_parameter = $_SERVER['QUERY_STRING']; // get参数
           // 筛选属性参数
           $url_parameter_array = explode("&", $url_parameter);
           // 去除参数中的规格 属性参数
           foreach ($url_parameter_array as $k => $v) {
               if (strpos($v, "attr") === 0) {
                   unset($url_parameter_array[$k]);
               } else
                   if (strpos($v, "spec") === 0) {
                       unset($url_parameter_array[$k]);
                   }
           }

           $url_parameter_array = array_unique($url_parameter_array);
           $url_parameter = implode("&", $url_parameter_array);
        */
        // $index_num = strpos($url_parameter, "&attr");
        $attr_url = "";
        // $order_url = "";
        // if (! $index_num === false) {
        // if (! empty($attr_array)) {
        // $attr_url = mb_substr($url_parameter, $index_num);
        // }
        // $url_parameter = mb_substr($url_parameter, 0, $index_num);
        // } else {
        // $order_url = $url_parameter;
        // }
        // //将规格get参数整合
        // $spec_index_num = strpos($url_parameter, "&spec");
        /*
        if ($attr != "") {
            $attr_url .= "&attr=$attr";
        }
        if ($spec != "") {
            $attr_url .= "&spec=$spec";
        }

        $this->assign("attr_url", $attr_url);
        $url_parameter_not_shipping = str_replace("&shipping_fee=0", "", $url_parameter); // 筛选：排除包邮
        $url_parameter_not_price = str_replace("&min_price=$min_price&max_price=$max_price", "", $url_parameter); // 筛选：排除价格区间
        $url_brand_name = str_replace("%2C", ",", rawurlencode($brand_name));
        $url_parameter_not_brand = str_replace("&brand_id=$brand_id&brand_name=" . $url_brand_name . "", "", $url_parameter); // 筛选：排除品牌
        $url_parameter_not_stock = str_replace("&stock=$stock", "", $url_parameter); // 筛选：排除仅显示有货
        $url_parameter_not_platform_proprietary = str_replace("&platform_proprietary=$platform_proprietary", "", $url_parameter); // 筛选：排除平台自营
        $url_parameter_not_order = str_replace("&order=$order&sort=$sort", "", $url_parameter); // 排序，排除之前的排序规则，防止重复，
        $url_parameter_not_province_id = str_replace("&province_id=$province_id&province_name=" . urlencode($province_name) . "", "", $url_parameter); // 排序，排除之前的排序规则，防止重复，

        $this->assign("url_parameter", $url_parameter); // 正常
        $this->assign("url_parameter_not_order", $url_parameter_not_order); // 排序，排除之前的排序规则，防止重复，
        $this->assign("url_parameter_not_shipping", $url_parameter_not_shipping); // 筛选：排除包邮
        $this->assign("url_parameter_not_price", $url_parameter_not_price . $attr_url); // 筛选：排除价格，
        $this->assign("url_parameter_not_brand", $url_parameter_not_brand . $attr_url); // 筛选：排除品牌
        $this->assign("url_parameter_not_stock", $url_parameter_not_stock); // 筛选：排除仅显示有货
        $this->assign("url_parameter_not_platform_proprietary", $url_parameter_not_platform_proprietary); // 筛选：排除平台自营
        $this->assign("url_parameter_not_province_id", $url_parameter_not_province_id); // 筛选：排除平台自营
        $this->assign("user_location", get_city_by_ip()); // 获取用户位置信息
        */
        $platform_proprietary = "";
        $attr_array = "";
        $spec_array = "";
        $goods_list = $this->getGoodsListByConditions($category_id, $brand_id, $min_price, $max_price,  $keyword, $page, $page_size, $orderby, $is_send_free, $stock, $platform_proprietary, $province_id, $attr_array, $spec_array);

        //  $this->assign("goods_list", $goods_list); // 返回商品列表
        $data["goods_list"] = $goods_list;
        $category_name = "";
        if (! $category_id == "") {
            $category_name = $goods_category_handle->getCategoryParentQuery($category_id);
        } else {
            $category_name = "全部分类";
        }

        // if (count($goods_list["data"]) > 0) {
        // $category_name = $goods_list["data"][0]["category_name"]; // 面包屑
        // }
        //     $this->assign("spec_array", $spec_remove_array);
        //  $this->assign("category_name", $category_name);

        //  $this->assign('page_count', $goods_list['page_count']);
        //  $this->assign('total_count', $goods_list['total_count']);
        //   $this->assign('page', $page);
        $data["category_name"] = $category_name;
        $data["page_count"] =  $goods_list['page_count'];
        $data["total_count"] = $goods_list['total_count'];
        $data["page"] = $page;
        //   return view($this->style . '/Goods/goodsList');
        return json(resultArray(0,"操作成功", $data));
    }

   /*
    * 根据所选规格的组合返回sku信息
    */
     public function getGoodsSkuBySpecs($specs)
     {
         $specs = $this->param["specs"];
         $goodsHandle = new GoodsHandle();
         $data = $goodsHandle->getGoodsSkuBySpecs($specs);
         if (empty($data)) {
             return json(resultArray(2, "没有得到相关数据"));
         } else {
             return json(resultArray(0, "操作成功", $data));
         }
     }

    /********************************处理购物车********************************************************/
    /**
     * 返回商品数量和当前商品的限购
     */
    public function getCartInfo()
    {
        $goods_id = $this->param['goods_id'];
        $goodsHandle = new GoodsHandle();
        $user_id = Session::get("MEMBER_USER_ID");
        if (empty($user_id)) {
            $user_id = 0;
        }
        $cartlist = $goodsHandle->getCart($user_id);
        $num = 0;
        foreach ($cartlist as $v) {
            if ($v["goods_id"] == $goods_id) {
                $num = $v["num"];
            }
        }
        $data = array (
          "carcount"=>count($cartlist),
          "curGoodsNum" => $num
        );

        return json(resultArray(0,"操作成功", $data));
       // $this->assign("carcount", count($cartlist)); // 购物车商品数量
       // $this->assign("num", $num); // 购物车已购买商品数量
    }





    public function syncUserCart()
    {

        $goodsHandle = new GoodsHandle();
        $user_id = Session::get("MEMBER_USER_ID");
        if (empty($user_id)) {
            $user_id = 0;
            return json(resultArray(2,"用户不存在，操作失败"));
        }
        $ret =  $goodsHandle->syncUserCart($user_id);
        if (empty($ret)) {
            return json(resultArray(2,"操作失败,".$goodsHandle->getError()));
        }
        return json(resultArray(0,"操作成功"));
    }

    /*
     * 购物车中商品数量
     */
    public function getCartNum()
    {
        $goodsHandle = new GoodsHandle();
        $user_id = Session::get("MEMBER_USER_ID");
        if (empty($user_id)) {
            $user_id = 0;
        }
        $cartlist = $goodsHandle->getCart($user_id);
        $num = 0;
        /*
        foreach ($cartlist as $v) {
            if ($v["goods_id"] == $goods_id) {
                $num = $v["num"];
            }
        }
        */

        $data = count($cartlist);
    //   $data = array (
      //      "cart_num"=>count($cartlist)
       // );
        $data = $data - 1;
        return json(resultArray(0,"操作成功", $data));
        // $this->assign("carcount", count($cartlist)); // 购物车商品数量
        // $this->assign("num", $num); // 购物车已购买商品数量
    }

    /*
     * 购物车中商品数量
     */
    public function getCartSize()
    {
        $goodsHandle = new GoodsHandle();
        $user_id = Session::get("MEMBER_USER_ID");
        if (empty($user_id)) {
            $user_id = 0;
        }
        $data = $goodsHandle->getCartSize($user_id);
        return json(resultArray(0,"操作成功", $data));
    }

    /**
     * 购物车页面
     */
    public function cart()
    {


        $goodsHandle = new GoodsHandle();
        $user_id = Session::get("MEMBER_USER_ID");
        if (empty($user_id)) {
            $user_id = 0;
        }
        $cartlist = $goodsHandle->getCart( $user_id);
        // 店铺，店铺中的商品
        /*
        $list = Array();
        for ($i = 0; $i < count($cartlist); $i ++) {
            // $cartlist[$i]["goods_name"] = mb_substr($cartlist[$i]["goods_name"], 0,20,"utf-8");
            // $cartlist[$i]["sku_name"] = mb_substr($cartlist[$i]["goods_name"], 0,20,"utf-8");
            $list[$cartlist[$i]["shop_id"] . ',' . $cartlist[$i]["shop_name"]][] = $cartlist[$i];
        }
        */
        $total_num = $cartlist['total_num'];
        unset($cartlist['total_num']);
        $data = array (
            "cartlist" => $cartlist,
            "cartcount" => count($cartlist),
            'cartnum' => $total_num
        );
       // $this->assign("list", $list);
       // $this->assign("countlist", count($cartlist));
       // return view($this->style . '/Goods/cart');
        return json(resultArray(0,"操作成功", $data));
    }

    /**
     * 添加购物车
     */
    public function addCart()
    {
        $goods_id = $this->param["goods_id"];
      //  $goods_name = $this->param["goods_name"];
        $sku_id = isset($this->param["sku_id"]) ? $this->param["sku_id"] : 0;
      //  $sku_name = isset($this->param["sku_name"]) ? $this->param["sku_name"] : "";
        $price = $this->param["price"];
        $num = $this->param["num"];
   //     $picture = $this->param["picture"];


        $user_id = Session::get("MEMBER_USER_ID");
        if (empty($user_id)) {
            $user_id = 0;
        }
            /* if($cart_tag == "addCart") { */
        $goodsHandle = new GoodsHandle();

       // $retval = $goodsHandle->addCart($user_id, $goods_id, $goods_name, $sku_id, $sku_name, $price, $num, $picture, 0);
       // addCart($user_id,  $goods_id,  $sku_id, $price, $num,  $bl_id)
       // addCart($user_id,  $goods_id,  $sku_id, $price, $num, $selected,  $bl_id)
          $retval = $goodsHandle->addCart($user_id, $goods_id,  $sku_id, $price, $num, 1, 0);


        if (empty($retval)) {
            return json(resultArray(2, "操作失败"));
        } else {
            $data= $goodsHandle->getCartSize($user_id);
            return json(resultArray(0, "操作成功", $data));
        }
    }

    /**
     * 购物车修改数量
     */
    public function cartAdjustNum()
    {
        $cart_id =  $this->param["cart_id"];
        $num = $this->param["num"];
        $user_id = Session::get("MEMBER_USER_ID");
        if (empty($user_id)) {
            $user_id = 0;
        }
        $goodsHandle = new GoodsHandle();
        $retval = $goodsHandle-> cartAdjustNum($user_id, $cart_id, $num);
        if ($retval == 0) {
            return json(resultArray(2, "操作失败"));
        } else {
            $data= $goodsHandle->getCartSize($user_id);
            return json(resultArray(0, "操作成功",$data));
        }
    }



    /**
     * 购物车修改数量
     */
    public function cartAdjustSelected()
    {
        $cart_id_str =  $this->param["selected_cart_id"];
        $user_id = Session::get("MEMBER_USER_ID");
        if (empty($user_id)) {
            $user_id = 0;
        }
        $goodsHandle = new GoodsHandle();
        $retval = $goodsHandle->cartAdjustSelected($user_id, $cart_id_str);
        if ($retval == 0) {
            return json(resultArray(2, "操作失败,".$goodsHandle->getError()));
        } else {
            $data= $goodsHandle->getCartSize($user_id);
            return json(resultArray(0, "操作成功",$data));
        }
    }

    /**
     * 购物车项目删除
     */
    public function cartDelete()
    {

        $cart_id_array = $this->param["del_id"];// $_POST['del_id'];
        $goodsHandle = new GoodsHandle();
        $user_id = Session::get("MEMBER_USER_ID");
        if (empty($user_id)) {
            $user_id = 0;
        }
        $retval = $goodsHandle->cartDelete($user_id, $cart_id_array);
        if (empty($retval)) {
            return json(resultArray(2, "删除操作失败"));
        } else {
            $data= $goodsHandle->getCartSize($user_id);
            return json(resultArray(0, "删除操作成功", $data));
        }
    }

    /**
     * 添加购物车(用于未登陆添加购物车)
     */
    /*
    public function addCart()
    {
        $goods = new GoodsService();
        $uid = $this->uid;
        $cart_detail = $_POST['cart_detail'];
        $goods_id = $cart_detail['goods_id'];
        $goods_name = $cart_detail['goods_name'];
        $shop_id = $this->instance_id;
        $web_info = $this->web_site->getWebSiteInfo();
        $count = $cart_detail['count'];
        $sku_id = $cart_detail['sku_id'];
        $sku_name = $cart_detail['sku_name'];
        $price = $cart_detail['price'];
        $cost_price = $cart_detail['cost_price'];
        $picture_id = $cart_detail['picture_id'];
        $_SESSION['order_tag'] = ""; // 清空订单
        $retval = $goods->addCart($uid, $shop_id, $web_info['title'], $goods_id, $goods_name, $sku_id, $sku_name, $price, $count, $picture_id, 0);
        return $retval;
    }
*/



    /**
     * 获取购物车信息
     */
    public function getShoppingCart()
    {
        $goodsHandle = new GoodsHandle();
        $user_id = Session::get("MEMBER_USER_ID");
        if (empty($user_id)) {
            $user_id = 0;
        }
        $cart_list = $goodsHandle->getCart( $user_id);
        return json(resultArray(0, "操作成功", $cart_list));
    }

    /**
     * 根据cartid删除购物车中的商品
     */
    /*
    public function deleteShoppingCartById()
    {
        $goods = new GoodsService();
        $cart_id_array = $_POST["cart_id_array"];
        $res = $goods->cartDelete($cart_id_array);
        $_SESSION['order_tag'] = ""; // 清空订单
        return AjaxReturn($res);
    }
    */

    /**
     * 更新购物车中商品数量
     * 创建人：王永杰
     * 创建时间：2017年2月15日 15:43:23
     *
     * @return 
     */
    /*
    public function updateCartGoodsNumber()
    {
        $goods = new GoodsService();
        $cart_id = $_POST["cart_id"];
        $num = $_POST["num"];
        $_SESSION['order_tag'] = ""; // 清空订单
        $res = $goods->cartAdjustNum($cart_id, $num);
        return $res;
    }

*/


}