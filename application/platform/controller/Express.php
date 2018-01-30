<?php
/**
 * 物流控制器
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-10-19
 * Time: 18:43
 */

namespace app\platform\controller;

use app\platform\controller\BaseController;
use dao\handle\AddressHandle;
use dao\handle\ExpressHandle as ExpressHandle;


class Express extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 物流地址
     */
    public function expressAddress()
    {
        $express = new ExpressHandle();
        $pageindex = request()->post('page_index', 1);
        $list = $express->getPlatformExpressAddressList($pageindex, PAGESIZE, '', '');
        return json(resultArray(0, "操作成功", $list));
    }

    /**
     * 功能说明：获取省
     */


    /**
     * 功能说明：获取市
     */


    /**
     * 功能说明：获取区/县
     */


    /**
     * 添加物流地址
     *
     * @return Ambigous <multitype:unknown, multitype:unknown unknown string >
     */
    public function addExpressAddress()
    {
        // 获取数据一律使用三元运算符
        $contact = request()->post('contact', '');
        $mobile = request()->post('mobile', '');
        $phone = request()->post('phone', '');
        $company_name = request()->post('company_name', '');
        $province = request()->post('province', 0);
        $city = request()->post('city', 0);
        $district = request()->post('district', 0);
        $zipcode = request()->post('zipcode', '');
        $address = request()->post('address', '');
        $express = new ExpressHandle();
        // addPlatformExpressAddress($contact, $mobile, $phone, $company_name, $province, $city, $district, $zipcode, $address)

        $retval = $express->addPlatformExpressAddress($contact, $mobile, $phone, $company_name, $province, $city, $district, $zipcode, $address);
        if (empty($retval)) {
            return json(resultArray(2, "操作失败 " . $express->getError()));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * 功能说明： 根据id查看收货地址详情
     */
    public function ExpressAddressInfo()
    {
        $express_address_id = request()->post('express_address_id', '');
        $express = new ExpressHandle();
        //  selectPlatformExpressAddressInfo($express_address_id)
        $retval = $express->selectPlatformExpressAddressInfo($express_address_id);
        return json(resultArray(0, "操作成功", $retval));
    }

    /**
     * 修改物流地址
     *
     * @return Ambigous <multitype:unknown, multitype:unknown unknown string >
     */
    public function updateExpressAddress()
    {
        $express = new ExpressHandle();
        $express_address_id = request()->post('express_address_id');
        $contact = request()->post('contact');
        $mobile = request()->post('mobile');
        $phone = request()->post('phone');
        $company_name = request()->post('company_name');
        $province = request()->post('province');
        $city = request()->post('city');
        $district = request()->post('district');
        $zipcode = request()->post('zipcode');
        $address = request()->post('address');
        $retval = $express->updatePlatformExpressAddress($express_address_id, $contact, $mobile, $phone, $company_name, $province, $city, $district, $zipcode, $address);
        if ($retval === false) {
            return json(resultArray(2, "操作失败 " . $express->getError()));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * 运费模板管理-列表分页
     */
    public function freightTemplateList()
    {
        $pageindex = request()->post('page_index', 1);
        $page_page = request()->post('page_size', PAGESIZE);
        $co_id = request()->post("co_id", 0);
        if (!empty($co_id)) {
            $condition['co_id'] = $co_id;
        }

        $shippingfee_list = new ExpressHandle();
        // getShippingFeeList($page_index = 1, $page_size = 0, $condition = '', $order = '')
        $express_list_pagequery = $shippingfee_list->getShippingFeeList($pageindex, $page_page, $condition, 'is_default desc,create_time desc');
        //  $totalcount = $express_list_pagequery['total_count'];
        //  $pagecount = $express_list_pagequery['page_count'];
        $data = array(
            "co_id" => $co_id,
            'express_list' => $express_list_pagequery
        );
        //  $this->assign('data_length', count($express_list_pagequery['data']));
        //  $this->assign('totalcount', $totalcount);
        //  $this->assign('pagecount', $pagecount);
        //   $this->assign();
        // $this->assign('express_list_pagequery', $express_list_pagequery['data']); // 列表
        return json(resultArray(0, "操作成功", $data));
    }

    /**
     * 运费模板管理-添加
     */
    public function freightTemplateEdit()
    {
        $express = new ExpressHandle();
        $address = new AddressHandle();
        $retval = -1;
        $data = request()->post("data");
        $json_data = json_decode($data, true);
        $shipping_fee_id = $json_data['shipping_fee_id']; // 0：添加，大于0：修改
        $co_id = $json_data['co_id']; // 物流公司id
        $is_default = $json_data['is_default']; // 是否默认
        $shipping_fee_name = $json_data['shipping_fee_name']; // 运费模板名称
        $province_id_array = $json_data['province_id_array']; // 省id组
        $city_id_array = $json_data['city_id_array']; // 市id组
        $district_id_array = $json_data["district_id_array"]; // 区县id组

        $weight_is_use = $json_data['weight_is_use']; // 是否启用重量运费，0：不启用，1：启用
        $weight_snum = $json_data['weight_snum']; // 首重
        $weight_sprice = $json_data['weight_sprice']; // 首重运费
        $weight_xnum = $json_data['weight_xnum']; // 续重
        $weight_xprice = $json_data['weight_xprice']; // 续重运费

        $volume_is_use = $json_data['volume_is_use']; // 是否启用体积计算运费，0：不启用，1：启用
        $volume_snum = $json_data['volume_snum']; // 首体积量
        $volume_sprice = $json_data['volume_sprice']; // 首体积运费
        $volume_xnum = $json_data['volume_xnum']; // 续体积量
        $volume_xprice = $json_data['volume_xprice']; // 续体积运费

        $bynum_is_use = $json_data['bynum_is_use']; // 是否启用计件方式运费，0：不启用，1：启用
        $bynum_snum = $json_data['bynum_snum']; // 首件
        $bynum_sprice = $json_data['bynum_sprice']; // 首件运费
        $bynum_xnum = $json_data['bynum_xnum']; // 续件
        $bynum_xprice = $json_data['bynum_xprice']; // 续件运费

        if (empty($shipping_fee_id)) {
            $retval = $express->addShippingFee($co_id, $is_default, $shipping_fee_name, $province_id_array, $city_id_array, $district_id_array, $weight_is_use, $weight_snum, $weight_sprice, $weight_xnum, $weight_xprice, $volume_is_use, $volume_snum, $volume_sprice, $volume_xnum, $volume_xprice, $bynum_is_use, $bynum_snum, $bynum_sprice, $bynum_xnum, $bynum_xprice);

        } else {
            $retval = $express->updateShippingFee($shipping_fee_id, $is_default, $shipping_fee_name, $province_id_array, $city_id_array, $district_id_array, $weight_is_use, $weight_snum, $weight_sprice, $weight_xnum, $weight_xprice, $volume_is_use, $volume_snum, $volume_sprice, $volume_xnum, $volume_xprice, $bynum_is_use, $bynum_snum, $bynum_sprice, $bynum_xnum, $bynum_xprice);
        }
        if ($retval === false) {
            return json(resultArray(2, "操作失败 " . $express->getError()));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }


    public function getFreightTemplate()
    {
        $co_id = $this->param['co_id'];
     //   request()->get("co_id"); // 物流公司id

        $shipping_fee_id =  isset($this->param['shipping_fee_id']) ? $this->param['shipping_fee_id'] : 0; // request()->get("shipping_fee_id", 0); // 运费模板id（用于修改运费模板）
        $express = new ExpressHandle();
        $address = new AddressHandle();
        /*
            if (! $co_id && ! $shipping_fee_id) {
                $redirect = __URL(__URL__ . '/' . ADMIN_MODULE . "/express/expresscompany");
                $this->redirect($redirect);
            }
        */
        //   $this->assign("co_id", $co_id);///
        //  $this->assign("shipping_fee_id", $shipping_fee_id); ////
        $current_province_id_array = ""; // 省，排除当前编辑的省市id组
        $current_city_id_array = ""; // 市
        $current_district_id_array = ""; // 区县id组
        $is_default = $express->isHasExpressCompanyDefaultTemplate($co_id); // 获取当前物流公司是否有默认地区

        $shipping_fee_detail = [];
        if ($shipping_fee_id) {
            // 编辑修改
            $shipping_fee_detail = $express->shippingFeeDetail($shipping_fee_id);
            if ($shipping_fee_detail['is_default']) {
                $is_default = $shipping_fee_detail['is_default'];
            }
            $current_province_id_array = $shipping_fee_detail['province_id_array'];
            $current_city_id_array = $shipping_fee_detail['city_id_array'];
            $current_district_id_array = $shipping_fee_detail['district_id_array'];
            //  $this->assign("shipping_fee_detail", $shipping_fee_detail);
        }
        //  $this->assign("is_default", $is_default);////

        // 当前物流公司已存在的省市id组，将禁用，不能再选择,
        $existing_address_list = $express->getExpressCompanyProvincesAndCitiesById($co_id, $current_province_id_array, $current_city_id_array, $current_district_id_array);
        $address_list = $address->getAreaTree_ext($existing_address_list); // 获取地区树，排除当前物流公司的所有省市（修改时的省市不能禁用）

      //  $this->assign("address_list", $address_list);
        $data = array(
            "co_id" => $co_id,
            "shipping_fee_id" => $shipping_fee_id,
            "is_default" => $is_default,
            "shipping_fee_detail" => $shipping_fee_detail,
            "address_list"=> $address_list

        );

        return json(resultArray(0, "操作成功", $data));
        //     return view($this->style . 'Express/freightTemplateEdit');

    }

    /**
     * 运费模板删除
     */
    public function freightTemplateDelete()
    {
        $shipping_fee_id = request()->post('shipping_fee_id', '');
        $express = new ExpressHandle();
        $retval = $express->shippingFeeDelete($shipping_fee_id);
        if (empty($retval)) {
            return json(resultArray(2, "操作失败 " . $express->getError()));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * 物流公司模板信息
     */
    public function expressTemplate()
    {
        // 微物流->运单模版选择打印项全部检索
        $express = new ExpressHandle();
        // getExpressShippingItemsLibrary()
        $express_shipping_items_select = $express->getExpressShippingItemsLibrary();
        foreach ($express_shipping_items_select as $key => $value) {
            $field_name[$key] = str_replace("A", "", $value['field_name']);
        }
        array_multisort($field_name, SORT_NUMERIC, SORT_ASC, $express_shipping_items_select);
        $co_id = isset($this->param['co_id']) ? $this->param['co_id'] : 0;
        if (empty($co_id)) {
            return json(resultArray(2, "未获取到相关信息" ));
        } else {
            $id = $co_id;
        }
     //   if (!empty($co_id)) {
     //       $id = request()->get('co_id');
     //   } else {
            // $redirect = __URL(__URL__ . '/' . ADMIN_MODULE . "/express/expresscompany");
            // $this->redirect($redirect);
     //   }
        $express_company_detail = $express->expressCompanyDetail($id);
        $express_shipping_detail = $express->getExpressShipping($id);
        $sid = 0;
        if (!empty($express_shipping_detail)) {
            $sid = $express_shipping_detail["sid"];
        }
        $print = $express->getExpressShippingItems($sid);
        if (!empty($print)) {
            foreach ($print as $key => $value) {
                $field_name[$key] = str_replace("A", "", $value['field_name']);
            }
            array_multisort($field_name, SORT_NUMERIC, SORT_ASC, $print);
        }
       // $this->assign('express_company_select', $express_company_detail);
      //  $this->assign('express_shipping_select', $express_shipping_detail);
      //  $this->assign('print', $print);
      //  $this->assign("express_id", $id);
       // $this->assign('express_shipping_items_select', $express_shipping_items_select);

        $data = array(
            'express_company_select' => $express_company_detail,
            'express_shipping_select' => $express_shipping_detail,
            'print_express_shipping_items' => $print,
            "express_id" => $id,
            'express_shipping_items_library'=> $express_shipping_items_select

        );

        return json(resultArray(0, "操作成功", $data));
    }

    /**
     * 功能说明：运单模板管理-添加-添加保存-编辑保存
     * 更新 物流公司的模板
     */
    public function setPrintTemplate()
    {
        //$shopId = $this->instance_id;
        // 模板id
        $template_id = request()->post('template_id');
        // 物流id
        $co_id = request()->post('express_id', '');
        // 模板宽度
        $width = request()->post('width_length', 0);
        // 模板高度
        $height = request()->post('heigth_length', 0);
        // 图片路径
        $img = request()->post('img_url', '');
        // 打印项
        $itemsArray = request()->post('send_datas', '');
        $express_handle = new ExpressHandle();
        // 更新模板信息
        $retval = $express_handle->updateExpressShipping($template_id, $width, $height, $img, $itemsArray);
        if (empty($retval)) {
            return json(resultArray(2, "操作失败 " . $express_handle->getError()));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    public function getTemalteElement()
    {
        if (request()->post('template_id')) {
            $id = request()->post('template_id');
        } else {
            return false;
        }
        $express = new ExpressHandle();
        $express_shipping_select = $express->getExpressShippingItems($id);
        $data = $express_shipping_select[0][0];
        $data['template'] = $express_shipping_select[1];
        return json(resultArray(0, "操作成功", $data));
    }

    /**
     * 功能：进入物流公司
     */
    public function expressCompany()
    {
        $child_menu_list = array(
            array(
                'url' => "express/expresscompany",
                'menu_name' => "物流公司",
                "active" => 1
            ),
            array(
                'url' => "config/areamanagement",
                'menu_name' => "地区管理",
                "active" => 0
            ),
            array(
                'url' => "order/returnsetting",
                'menu_name' => "商家地址",
                "active" => 0
            ),
            array(
                'url' => "shop/pickuppointlist",
                'menu_name' => "自提点管理",
                "active" => 0
            ),
            array(
                'url' => "shop/pickuppointfreight",
                'menu_name' => "自提点运费",
                "active" => 0
            ),
            array(
                'url' => "config/distributionareamanagement",
                'menu_name' => "货到付款地区管理",
                "active" => 0
            )
        );

        $expressCompany = new ExpressHandle();
        $page_index = request()->post('page_index', 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $search_text = request()->post('search_text', '');
        $condition = array(
            'company_name|express_no' => array(
                    'like',
                    '%' . $search_text . '%'
            )
        );
       // getExpressCompanyList($page_index = 1, $page_size = 0, $condition = '', $order = '')
        $list = $expressCompany->getExpressCompanyList($page_index, $page_size, $condition);
        $data = array(
            'child_menu_list'=> $child_menu_list,
            'express_company_list'=> $list
        );

        return json(resultArray(0, "操作成功", $data));
      //  $this->assign('child_menu_list', $child_menu_list);
    }

    /**
     * 功能：添加物流公司
     */
    public function addExpressCompany()
    {
        $expressCompany = new ExpressHandle();
        $company_name = request()->post('company_name');
        $express_logo = request()->post('express_logo', '');
        $express_no = request()->post('express_no');
        $is_enabled = request()->post('is_enabled');
        $image = request()->post('image', '');
        $phone = request()->post('phone', '');
        $orders = request()->post('orders', 0);
        $is_default = request()->post('is_default', 0);
       // addExpressCompany($company_name, $express_logo, $express_no, $is_enabled, $image, $phone, $orders, $is_default)
        $retval = $expressCompany->addExpressCompany($company_name, $express_logo, $express_no, $is_enabled, $image, $phone, $orders, $is_default);
        if (empty($retval)) {
            return json(resultArray(2, "操作失败 " . $expressCompany->getError()));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * 功能：修改物流公司信息
     */
    public function updateExpressCompany()
    {
        $expressCompany = new ExpressHandle();
        $co_id = request()->post('co_id');
        $company_name = request()->post('company_name');
        $express_logo = request()->post('express_logo');
        $express_no = request()->post('express_no');
        $is_enabled = request()->post('is_enabled');
        $image = request()->post('image', '');
        $phone = request()->post('phone');
        $orders = request()->post('orders');
        $is_default = request()->post('is_default');
       // updateExpressCompany($co_id, $company_name, $express_logo, $express_no, $is_enabled, $image, $phone, $orders, $is_default)
  //updateExpressCompany($co_id, $company_name, $express_logo, $express_no, $is_enabled, $image, $phone, $orders, $is_default)

        $retval = $expressCompany->updateExpressCompany($co_id,  $company_name, $express_logo, $express_no, $is_enabled, $image,
                $phone, $orders, $is_default);
        if (empty($retval)) {
            return json(resultArray(2, "操作失败 " . $expressCompany->getError()));
        } else {
            return json(resultArray(0, "操作成功"));
        }

    }

    /*
     * 得到物流公司信息用于修改
     */
    public function getExpressCompany() {
        $co_id =isset($this->param['co_id']) ? $this->param['co_id'] : 0;
        if (empty($co_id)) {
            return json(resultArray(2, "未获取到物流公司信息" ));
        }
        $expressCompany = new ExpressHandle();
        $expressCompanyinfo = $expressCompany->expressCompanyDetail($co_id);
        return json(resultArray(0, "操作成功",$expressCompanyinfo));
    }

    /**
     * 删除物流公司
     */
    public function expressCompanyDelete()
    {
        $co_id = request()->post('co_id', 0);
        if (empty($co_id)) {
            return json(resultArray(2, "未获取到物流公司信息" ));
        }
        $expressCompany = new ExpressHandle();
        $retval = $expressCompany->expressCompanyDelete($co_id);
        if (empty($retval)) {
            return json(resultArray(2, "删除失败"));
        } else {
            return json(resultArray(0, "删除成功"));
        }
    }

    /**
     * 物流地址删除
     */
    public function deletePlatformExpressAddress()
    {
        $express_address_id = request()->post('express_address_id', 0);
        if (empty($express_address_id)) {
            return json(resultArray(2, "未获取到信息" ));
        }
        $expressCompany = new ExpressHandle();
        $retval = $expressCompany->deletePlatformExpressAddress($express_address_id);
        if (empty($retval)) {
            return json(resultArray(2, "删除失败"));
        } else {
            return json(resultArray(0, "删除成功"));
        }
    }

    /**
     * 功能说明：设置默认地址
     */
    public function modifyPlatformExpressAddress()
    {
        $expressCompany = new ExpressHandle();
        $addressType = request()->post('addressType', '');
        $express_address_id = request()->post('express_address_id', '');
        if ($addressType == 0) {
          //  modifyPlatformExpressAddressConsigner($express_address_id, $is_consigner)
            $retval = $expressCompany->modifyPlatformExpressAddressConsigner($express_address_id, 1);
        } elseif ($addressType == 1) {
           // modifyPlatformExpressAddressReceiver($express_address_id, $is_receiver)
            $retval = $expressCompany->modifyPlatformExpressAddressReceiver($express_address_id, 1);
        }
        if (empty($retval)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }

    }

}