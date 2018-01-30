<?php
/**
 * ExpressHandle.php
 */
namespace dao\handle;

/**
 * 处理物流
 */
use dao\handle\BaseHandle as BaseHandle;
use dao\model\OrderShippingFee as OrderShippingFeeModel; //运费模板
use dao\handle\AddressHandle as AddressHandle;
use dao\model\ExpressCompany as OrderExpressCompanyModel; //物流公司
use dao\model\PlatformExpressAddress as PlatformExpressAddressModel;
use dao\model\ExpressShippingItemsLibrary as ExpressShippingItemsLibraryModel; //打印库
use dao\model\ExpressShipping as ExpressShippingModel;   //打印模板
use dao\model\ExpressShippingItems as ExpressShippingItemsModel;  //打印模板项
use dao\model\BaseModel;
use think\Log;

class ExpressHandle extends BaseHandle
{

    /**
     * 获取物流模板列表-okkkok
     */
    public function getShippingFeeList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        $order_shipping_fee = new OrderShippingFeeModel();
        $list = $order_shipping_fee->pageQuery($page_index, $page_size, $condition, $order, '*');
        $address = new AddressHandle();
        foreach ($list['data'] as $k => $v) {
            $address = new AddressHandle();
            $list['data'][$k]['address_list'] = $address->getAddressListById($v['province_id_array'], $v['city_id_array']);
        }
        return $list;
    }

    /**
     * 添加物流模板-okkk-ok
     */
    public function addShippingFee($co_id, $is_default, $shipping_fee_name, $province_id_array, $city_id_array,$district_id_array,
                                   $weight_is_use, $weight_snum, $weight_sprice, $weight_xnum, $weight_xprice, $volume_is_use,
                                   $volume_snum, $volume_sprice, $volume_xnum, $volume_xprice, $bynum_is_use, $bynum_snum, $bynum_sprice,
                                   $bynum_xnum, $bynum_xprice)
    {
        $order_shipping_fee = new OrderShippingFeeModel();
        $this->startTrans();
        try {
            $data = array(
                'shipping_fee_name' => $shipping_fee_name,
                'co_id' => $co_id,
                'is_default' => $is_default,
                'province_id_array' => $province_id_array,
                'city_id_array' => $city_id_array,
                'district_id_array' => $district_id_array,
               // 'shop_id' => $this->instance_id,
                'weight_is_use' => $weight_is_use,
                'weight_snum' => $weight_snum,
                'weight_xnum' => $weight_xnum,
                'weight_sprice' => $weight_sprice,
                'weight_xprice' => $weight_xprice,
                'volume_is_use' => $volume_is_use,
                'volume_snum' => $volume_snum,
                'volume_sprice' => $volume_sprice,
                'volume_xnum' => $volume_xnum,
                'volume_xprice' => $volume_xprice,
                'bynum_is_use' => $bynum_is_use,
                'bynum_snum' => $bynum_snum,
                'bynum_sprice' => $bynum_sprice,
                'bynum_xnum' => $bynum_xnum,
                'bynum_xprice' => $bynum_xprice,
              //  'create_time' => time()
            );
           $res =  $order_shipping_fee->save($data);
            if (empty($res)) {
                $this->rollback();
                Log::write('order_shipping_fee->save(data) 不能正确执行!');
                return false;

            }
            //$this->dealWithExpressCompany($co_id);
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            Log::write("检测错误".$e->getMessage());
            $this->error = $e->getMessage();
           // return $e->getMessage();
            return false;
        }
      //  return - 1;
        return false;

    }

    /** okkk-ok
     * 修改运费模板
     */
    public function updateShippingFee($shipping_fee_id, $is_default, $shipping_fee_name, $province_id_array,
                                      $city_id_array,$district_id_array, $weight_is_use, $weight_snum, $weight_sprice,
                                      $weight_xnum, $weight_xprice, $volume_is_use, $volume_snum, $volume_sprice,
                                      $volume_xnum, $volume_xprice, $bynum_is_use, $bynum_snum, $bynum_sprice,
                                      $bynum_xnum, $bynum_xprice)
    {
        $order_shipping_fee = new OrderShippingFeeModel();
     //   $order_shipping_fee_ext = new OrderShippingFeeExtendModel();
        $this->startTrans();
        try {
            $data = array(
                'shipping_fee_name' => $shipping_fee_name,
                'is_default' => $is_default,
                'province_id_array' => $province_id_array,
                'city_id_array' => $city_id_array,
                'district_id_array' => $district_id_array,
                'weight_is_use' => $weight_is_use,
                'weight_snum' => $weight_snum,
                'weight_xnum' => $weight_xnum,
                'weight_sprice' => $weight_sprice,
                'weight_xprice' => $weight_xprice,
                'volume_is_use' => $volume_is_use,
                'volume_snum' => $volume_snum,
                'volume_sprice' => $volume_sprice,
                'volume_xnum' => $volume_xnum,
                'volume_xprice' => $volume_xprice,
                'bynum_is_use' => $bynum_is_use,
                'bynum_snum' => $bynum_snum,
                'bynum_sprice' => $bynum_sprice,
                'bynum_xnum' => $bynum_xnum,
                'bynum_xprice' => $bynum_xprice,
                'update_time' => time()
            );
            $res = $order_shipping_fee->save($data, [
                'id' => $shipping_fee_id
            ]);
            if ($res === false) {
                $this->rollback();
                Log::write('order_shipping_fee->save(data, [ \'id\' => shipping_fee_id
                        ]) 不能正确执行!');
                return false;
            }
            $shipping_fee_info = $order_shipping_fee->getInfo(['id' => $shipping_fee_id], 'co_id');
            //$this->dealWithExpressCompany($shipping_fee_info['co_id']);
            $this->commit();
           // return 1;
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            //return $e->getMessage();
            return false;
        }
      //  return - 1;
        return false;
    }

    /**
     * 处理物流公司禁用-ookk

     */
    public function dealWithExpressCompany($express_company_id)
    {
        $disabled_area = '';
        $disabled_province = '';
        $disabled_city = '';
        $disabled_district = '';
        $address = new AddressHandle();
        //查询所有的地区信息
        $area_list = $address->getAreaList();
        //查询所有的省信息
        $province_list = $address->getProvinceList();
        //查询所有的市信息
        $city_list = $address->getCityList();
        //查询所有的区县的信息
        $district_list = $address->getDistrictList();
        $company_address_list = $this->getExpressCompanyAddressList($express_company_id);
        //查询物流公司所有地区，省，市，区
        foreach ($district_list as $k_district=>$v_district){
            $is_set=false;
            $is_disabled=0;
            $district_id=$v_district["id"];
            $district_id_deal_array[$district_id]=$k_district;
            $is_set=in_array($district_id, $company_address_list['district_id_array']);
            if($is_set){
               $disabled_district .= $district_id.',';
            }
        }
        //整理市的集合
        foreach ($city_list as $k_city=>$v_city){
            $city_is_disabled=$this->dealCityDistrictData($v_city["id"], $company_address_list["district_id_array"]);
            if($city_is_disabled)
            {
                $disabled_city .= $v_city["id"].',';
            }
           
        }
        //整理省的集合
        foreach ($province_list as $k_province=>$v_province){
            $province_is_disabled=$this->dealProvinceCityData($v_province['id'], $company_address_list['city_id_array']);
            if($province_is_disabled)
            {
                $disabled_province .= $v_province['id'].',';
            }
        }
        $express_company_model = new OrderExpressCompanyModel();
        $data = array(
            'disabled_province' => $disabled_province,
            'disabled_city'      => $disabled_city,
            'disabled_district' => $disabled_district
        );
        $express_company_model->save($data,['id' => $express_company_id]);
        
    }
    /**
     * 处理市和 地区的信息-ookk
     */
    private function dealCityDistrictData($city_id,$select_district_ids){
        if(empty($select_district_ids))
        {
            return 1;
        }
        $address = new AddressHandle();
        $is_disabled=1;
        $district_child_list = $address->getDistrictList($city_id);
        if(!empty($district_child_list))
        {
            foreach ($district_child_list as $k=>$district_obj){
                if(!in_array($district_obj['id'], $select_district_ids))
                {
                    $is_disabled = 0;
                    break;
                }
            }
        }
      
       return $is_disabled;
    }

    /**
     * 处理省和市的信息-ookk
     */
    private function dealProvinceCityData($province_id, $city_id_array){
        if(empty($city_id_array))
        {
            return 1;
        }
         $address = new AddressHandle();
        $is_disabled=1;
        $city_child_list = $address->getCityList($province_id);
        if(!empty($city_child_list))
        {
            foreach ($city_child_list as $city_obj){
                if(!in_array($city_obj['id'], $city_id_array))
                {
                    $is_disabled = 0;
                    break;
                }
            }
        }
      
        return $is_disabled;
    }
    
    /** okkk
     * 查询物流公司所有的省，市，区
     */
    public function getExpressCompanyAddressList($express_company_id)
    {
        $order_shipping_fee = new OrderShippingFeeModel();
        $province_id_array = '';
        $city_id_array = '';
        $district_id_array = '';
        $shipping_fee_list = $order_shipping_fee->getConditionQuery(['co_id' => $express_company_id], 'province_id_array,city_id_array,district_id_array', '');
        foreach ($shipping_fee_list as $k => $v)
        {
            if(!empty($v['province_id_array']))
            {
                $province_id_array = $province_id_array.$v['province_id_array'].',';
            }
            if(!empty($v['city_id_array']))
            {
                $city_id_array = $city_id_array.$v['city_id_array'].',';
              
            }
            if(!empty($v['district_id_array']))
            {
                $district_id_array = $district_id_array.$v['district_id_array'].',';
            }
        }
        if(!empty($province_id_array))
        {
            $province_id_array = explode(',', $province_id_array);
            $province_array = array_filter($province_id_array);
        }else{
            $province_array = array();
        }
        if(!empty($city_id_array))
        {
            $city_id_array = explode(',', $city_id_array);
            $city_array = array_filter($city_id_array);
        }else{
            $city_array = array();
        }
        if(!empty($district_id_array))
        {
            $district_id_array = explode(',', $district_id_array);
            $district_array = array_filter($district_id_array);
        }else{
            $district_array = array();
        }
        
        return array(
            'province_id_array' => $province_array,
            'city_id_array'     => $city_array,
            'district_id_array' => $district_array
        );
    }

    /**-ookk
     * 运费模板详情
     */
    public function shippingFeeDetail($shipping_fee_id)
    {
        $order_shipping_fee = new OrderShippingFeeModel();
        $order_shipping_fee_info = $order_shipping_fee->get($shipping_fee_id);
        $address = new AddressHandle();
        $province = $address->getProvinceList();
        $city = $address->getCityList();
        $address_name = "";
        $province_array = explode(",", $order_shipping_fee_info["province_id_array"]);
        $city_array = explode(",", $order_shipping_fee_info["city_id_array"]);
        foreach ($province_array as $e) {
            foreach ($province as $p) {
                if ($e == $p["id"]) {
                    $address_name = $address_name . $p["province_name"] . ",";
                }
            }
        }
        foreach ($city_array as $c) {
            foreach ($city as $z) {
                if ($c == $z["id"]) {
                    // $address_name = $address_name . $z["city_name"] . ",";
                }
            }
        }
        $address_name = substr($address_name, 0, strlen($address_name) - 1);
        $order_shipping_fee_info["address_name"] = $address_name;
        return $order_shipping_fee_info;
    }

    /**-ookk
     * 运费模板删除
     */
    public function shippingFeeDelete($shipping_fee_id)
    {
        $order_shipping_fee = new OrderShippingFeeModel();
        $condition = array(
            'id' => array(
                "in",
                $shipping_fee_id
            )
        );
       
        $order_shipping_return = $order_shipping_fee->destroy($condition);
     /*    if(is_int($shipping_fee_id))
        {
            $shipping_fee_array = $shipping_fee_id;
        }else{
            $shipping_fee_array = explode(',', $shipping_fee_id);
        }
       
        if(is_array($shipping_fee_array))
        {
            $info = $order_shipping_fee->getInfo(['shipping_fee_id' => $shipping_fee_array[0]],'co_id');
            $this->dealWithExpressCompany($info['co_id']);
        }else{
            $this->dealWithExpressCompany($shipping_fee_id);
        } */
        
        if ($order_shipping_return > 0) {
          //  return 1;
            return true;
        } else {
           // return - 1;
            return false;
        }
    }

    /**
     * ookk
     * 运费模板查询
     */
    public function shippingFeeQuery($where = "", $fields = "*")
    {
        $order_shipping_fee = new OrderShippingFeeModel();
        return $order_shipping_fee->getConditionQuery($where, $fields, '');
    }

    /**
     * okk
     * 获取物流公司
     */
    public function getExpressCompanyList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        $ns_express_company = new OrderExpressCompanyModel();
        $list = $ns_express_company->pageQuery($page_index, $page_size, $condition, $order, '*');
        return $list;
    }

    /**
     * okkk
     * 添加物流公司
     *
     */
    public function addExpressCompany($company_name, $express_logo, $express_no, $is_enabled, $image, $phone, $orders, $is_default)
    {
        $express_company = new OrderExpressCompanyModel();
        if (empty($company_name)) {
            $this->error = "物流公司名称不能为空";
            return false;
        }

        $count = $express_company->where([
            'company_name' => $company_name
        ])->count();
        if ($count > 0) {
            $this->error = "此物流公司名称已存在";
            return false; //USER_REPEAT;
        }
        if (empty($express_no)) {
            $this->error = "物流公司编号不能为空";
            return false;
        }

        $count = $express_company->where([
            'express_no' => $express_no
        ])->count();
        if ($count > 0) {
            $this->error = "此物流公司编号已存在";
            return false; //USER_REPEAT;
        }
        $express_company = new OrderExpressCompanyModel();
        $this->startTrans();
        try {
            if ($is_default == 1) {
                $this->defaultExpressCompany();
            }
            $data = array(
              //  'shop_id' => $shop_id,
                'company_name' => $company_name,
                'express_logo' => $express_logo,
                'express_no' => $express_no,
                'is_enabled' => $is_enabled,
                'image' => $image,
                'phone' => $phone,
                'orders' => $orders,
                'is_default' => $is_default
            );
            $res = $express_company->save($data);
            if (empty($res)) {
                $this->rollback();
                Log::write('express_company->save(data) 此操作失败!');
                return false;
            }
            $co_id = $express_company->id;
            $sid = $this->addExpressShipping( $company_name, $co_id);
            $this->addExpressShippingItems($sid);
            $this->commit();
           // return express_company->id;
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
           // return $e->getCode();
        }
    }

    /**
     * okkk
     * 把别的改为未默认,把当前设置为默认
     */
    public function defaultExpressCompany()
    {
        $express_company = new OrderExpressCompanyModel();
        $data = array(
            'is_default' => 0
        );
        $express_company->save($data, [
          //  'shop_id' => $this->instance_id
            'id'=> ['>',0]
        ]);
    }

    /**
     * ookk
     * 添加物流的模板库
     */
    private function addExpressShipping( $company_name, $co_id)
    {
        $express_model = new ExpressShippingModel();
        $data = array(
            "template_name" => $company_name,
            "co_id" => $co_id,
            "size_type" => 1,
            "width" => 0,
            "height" => 0,
            "image" => ""
        );
        $express_model->save($data);
        return $express_model->sid;
    }

    /**ookk
     * 添加运单打印项
     */
    private function addExpressShippingItems($sid)
    {
        $library_model = new ExpressShippingItemsLibraryModel();
        $library_list = $library_model->getConditionQuery([
          //  "shop_id" => $shop_id,
            "is_enabled" => 1
        ], "*", "");
        $x_length = 10;
        $y_length = 11;
        foreach ($library_list as $library_obj) {
            $item_model = new ExpressShippingItemsModel();
            $data = array(
                "sid" => $sid,
                "field_name" => $library_obj["field_name"],
                "field_display_name" => $library_obj["field_display_name"],
                "is_print" => 1,
                "x" => $x_length,
                "y" => $y_length
            );
            $y_length = $y_length + 25;
            $item_model->save($data);
        }
    }

    /**
     * okkk
     * 修改物流公司
     */
    public function updateExpressCompany($co_id, $company_name, $express_logo, $express_no, $is_enabled, $image, $phone, $orders, $is_default)
    {
        $express_company = new OrderExpressCompanyModel();

        if (empty($co_id) || $co_id < 1) {
            $this->error = "未指明物流公司id, 操作失改!";
            return false;
        }

        if (empty($company_name)) {
            $this->error = "物流公司名称不能为空";
            return false;
        }

        $express_company_info = $express_company->get($co_id);

        if ($express_company_info['company_name'] != $company_name) {
            $count = $express_company->where([
                'company_name' => $company_name
            ])->count();
            if ($count > 0) {
                $this->error = "物流公司名称" . $company_name . "已存在";
                return false;
                // return USER_REPEAT;
            }
        }

        if (empty($express_no)) {
            $this->error = "物流公司编号不能为空";
            return false;
        }

        if ($express_company_info['express_no'] != $express_no) {
            $count = $express_company->where([
                'express_no' => $express_no
            ])->count();
            if ($count > 0) {
                $this->error = "物流公司编号" . $express_no . "已存在";
                return false;
                // return USER_REPEAT;
            }
        }

        if ($is_default == 1) {
            $this->defaultExpressCompany();
        }
        $data = array(
            'company_name' => $company_name,
            'express_logo' => $express_logo,
            'express_no' => $express_no,
            'is_enabled' => $is_enabled,
            'image' => $image,
            'phone' => $phone,
            'orders' => $orders,
            'is_default' => $is_default
        );
        $res = $express_company->save($data, [
            'id' => $co_id
        ]);
        if ($res === false) {
            return false;
        } else {
            return true;
        }
       // return $res;
    }

    /**
     * okkk
     * 物流公司详情
     *
     */
    public function expressCompanyDetail($co_id)
    {
        $express_company = new OrderExpressCompanyModel();
        return $express_company->get($co_id);
    }

    /**
     * okkk
     * 删除物流公司
     */
    public function expressCompanyDelete($co_id)
    {
        $express_company = new OrderExpressCompanyModel();
        $conditon = array(
          //  'shop_id' => $this->instance_id,
            'id' => array(
                'in',
                $co_id
            )
        );
        $ns_express_company_return = $express_company->destroy($conditon);
        if ($ns_express_company_return > 0) {
           // return 1;
            return true;
        } else {
           // return - 1;
            return false;
        }
    }

    /**
     * okkk-2ok
     * 物流公司列表
     */
    public function expressCompanyQuery($where = "", $field = "*")
    {
        $express_company = new OrderExpressCompanyModel();
        return $express_company->where($where)
            ->field($field)
            ->select();
    }

    /**
     * 添加平台物流地址
     *
     */
    public function addPlatformExpressAddress($contact, $mobile, $phone, $company_name, $province, $city, $district, $zipcode, $address)
    {
        $express_address = new PlatformExpressAddressModel();
        $data_consigner = array(
            'is_consigner' => 0,
            'is_receiver' => 0
        );
        $express_address->save($data_consigner, [
           // 'shop_id' => $this->instance_id
            'id'=> ['>',0]
        ]);
        $express_address = new PlatformExpressAddressModel();
        $data = array(
          //  'shop_id' => $this->instance_id,
            'contact' => $contact,
            'mobile' => $mobile,
            'phone' => $phone,
            'company_name' => $company_name,
            'province' => $province,
            'city' => $city,
            'district' => $district,
            'zipcode' => $zipcode,
            'address' => $address,
            'is_consigner' => 1,
            'is_receiver' => 1,
            'status'=> 1
           // 'create_date' => time()
        );
        $retval = $express_address->save($data);
        return $retval;
      //  $express_address_id = $express_address->id;
       // return $express_address_id;
    }

    /**
     * 修改平台物流地址
     *
     */
    public function updatePlatformExpressAddress($express_address_id, $contact, $mobile, $phone, $company_name, $province, $city, $district, $zipcode, $address)
    {
        $express_address = new PlatformExpressAddressModel();
        $data = array(
            'contact' => $contact,
            'mobile' => $mobile,
            'phone' => $phone,
            'company_name' => $company_name,
            'province' => $province,
            'city' => $city,
            'district' => $district,
            'zipcode' => $zipcode,
            'address' => $address,
          //  'modify_date' => time()
        );
        $retval = $express_address->save($data, [
            'id' => $express_address_id
        ]);
        return $retval;
    }

    /**
     * okkk
     * 修改平台发货标记
     */
    public function modifyPlatformExpressAddressConsigner($express_address_id, $is_consigner)
    {
        $express_address = new PlatformExpressAddressModel();
        $express_address->save([
            'is_consigner' => 0
        ], [
            'id' => ["<>", $express_address_id]
        ]);
        $retval = $express_address->save([
            'is_consigner' => $is_consigner
        ], [
            'id' => $express_address_id
        ]);

        if ($retval === false) {
            return false;
        } else {
            return true;
        }
       // return $retval;
    }

    /**okkk
     * 修改公司收货标记
     */
    public function modifyPlatformExpressAddressReceiver($express_address_id, $is_receiver)
    {
        $express_address = new PlatformExpressAddressModel();
        $express_address->save([
            'is_receiver' => 0
        ],  [
            'id' => ["<>", $express_address_id]
        ]);
        $retval = $express_address->save([
            'is_receiver' => $is_receiver
        ], [
            'id' => $express_address_id
        ]);
      //  return $retval;
        if ($retval === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * okkk
     * 获取平台的物流地址
     */
    public function getPlatformExpressAddressList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        $express_address = new PlatformExpressAddressModel();
        $list = $express_address->pageQuery($page_index, $page_size, $condition, $order, '*');
        if (! empty($list['data'])) {
            $address = new AddressHandle();
            foreach ($list['data'] as $k => $v) {
                $address_info = $address->getAddress($v['province'], $v['city'], $v['district']);
                $list['data'][$k]['address_info'] = $address_info;
            }
        }
        return $list;
    }

    /**
     * okkk
     * 获取公司默认收货地址
     */
    public function getDefaultPlatformExpressAddressForReceiver($shop_id)
    {
        $express_address = new PlatformExpressAddressModel();
        $data = $express_address->getInfo([
          //  'shop_id' => $shop_id,
            'is_receiver' => 1
        ], '*');
        if (! empty($data)) {
            $address = new AddressHandle();
            $address_info = $address->getAddress($data['province'], $data['city'], $data['district']);
            $data['address_info'] = $address_info;
        }
        return $data;
    }

    /**
     * 获取公司默认收货地址
     */
    public function getDefaultPlatformExpressAddress()
    {
        $express_address = new PlatformExpressAddressModel();
        $data = $express_address->getInfo([
          //  'shop_id' => $shop_id,
            'is_receiver' => 1
        ], '*');
        if (! empty($data)) {
            $address = new AddressHandle();
            $address_info = $address->getAddress($data['province'], $data['city'], $data['district']);
            $data['address_info'] = $address_info;
        }
        return $data;
    }

    /**
     * okkk
     * 删除平台物流地址
     *
     * @param  $express_address_id_array
     *            ','隔开
     */
    public function deletePlatformExpressAddress($express_address_id_array)
    {
        $express_address = new PlatformExpressAddressModel();
       // $shop_id = $this->instance_id;
        $condition = array(
           // 'shop_id' => $shop_id,
            'id' => $express_address_id_array
        );
        $retval = $express_address->destroy($condition);
        if ($retval > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * okkk
     * 查询单条平台物流地址详情
     */
    public function selectPlatformExpressAddressInfo($express_address_id)
    {
        $express_address = new PlatformExpressAddressModel();
       // $shop_express_address = new NsShopExpressAddressModel();
        $retval = $express_address->getInfo([
            'id' => $express_address_id
        ], '*');
        return $retval;
    }

    /**
     * okkk
     * 获取物流模板内容库
     */
    public function getExpressShippingItemsLibrary()
    {
        $express_model = new ExpressShippingItemsLibraryModel();
        $item_list = $express_model->getConditionQuery('', "*", "");
        return $item_list;
    }

    /**
     * ok-2ok
     * 得到物流打印模板
     *
     */
    public function getExpressShipping($co_id)
    {
        $express_model = new ExpressShippingModel();
        $express_obj = $express_model->getInfo([
            "co_id" => $co_id
        ], "*");
        return $express_obj;
    }

    /**
     * okkk-2ok
     * 得到物流模板的打印项
     */
    public function getExpressShippingItems($sid)
    {
        $express_model = new ExpressShippingItemsModel();
        $item_list = $express_model->getConditionQuery([
            "sid" => $sid
        ], "*", "");
        return $item_list;
    }

    /**
     * okkk
     * 修改物流模板的打印项的位置
     */
    public function updateExpressShippingItem($sid, $itemsArray)
    {
        $items_str = explode(";", $itemsArray);
        foreach ($items_str as $item_obj) {
            $item_obj_str = explode(",", $item_obj);
            $data = array(
                "field_display_name" => $item_obj_str[1],
                "is_print" => $item_obj_str[2],
                "x" => $item_obj_str[3],
                "y" => $item_obj_str[4]
            );
            $field_name = $item_obj_str[0];
            $express_item_model = new ExpressShippingItemsModel();
            $express_item_model->save($data, [
                "sid" => $sid,
                "field_name" => $field_name
            ]);
        }
    }

    /**
     * 更新物流模板的信息, 以及打印的信息
     *
     */
    public function updateExpressShipping($template_id, $width, $height, $imgUrl, $itemsArray)
    {
        $express_model = new ExpressShippingModel();
        $this->startTrans();
        try {
            $data = array(
                "width" => $width,
                "height" => $height,
                "image" => $imgUrl
            );
            $result = $express_model->save($data, [
                "sid" => $template_id
            ]);

            if ($result === false) {
                $this->rollback();
                Log::write('express_model->save(data, ["sid" => template_id
                        ]); 此操作失败!');
                return false;

            }
            $this->updateExpressShippingItem($template_id, $itemsArray);
            $this->commit();
           // return 1;
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error =$e->getMessage();
            return false;
           // return $e->getCode();
        }
    }

    /**
     *
     * 根据物流公司id查询是否有默认地区
     */
    public function isHasExpressCompanyDefaultTemplate($co_id)
    {
        $order_shipping_fee = new OrderShippingFeeModel();
        $list = $order_shipping_fee->getConditionQuery([
            'co_id' => $co_id
        ], 'is_default', '');
      //  $is_default = 1; // 是否有默认地区 1,可以添加默认地区：0，不可以添加默认地区
        $is_default = 0;
        foreach ($list as $v) {
            if ($v['is_default']) {
              //  $is_default = 0;
                $is_default = 1;
                break;
            }
        }
        return $is_default;
    }

    /**
     *
     * 获取物流公司的省市id组，排除默认地区、以及当前编辑的运费模板省市id组
     */
    public function getExpressCompanyProvincesAndCitiesById($co_id, $current_province_id_array, $current_city_id_array, $current_district_id_array)
    {
        $curr_province_id_array = []; // 省id组
        $curr_city_id_array = []; // 市id组
        $curr_district_id_array = []; // 区县id组
                                      
        // 编辑运费模板时的省id组排除
        if (! empty($current_province_id_array)) {
            if (! strstr($current_province_id_array, ',')) {
                array_push($curr_province_id_array, $current_province_id_array);
            } else {
                $curr_province_id_array = explode(',', $current_province_id_array);
            }
        }
        
        // 编辑运费模板时的市id组排除
        if (! empty($current_city_id_array)) {
            if (! strstr($current_city_id_array, ',')) {
                array_push($curr_city_id_array, $current_city_id_array);
            } else {
                $curr_city_id_array = explode(',', $current_city_id_array);
            }
        }
        
        // 编辑运费模板时的区县id组排除
        if (! empty($current_district_id_array)) {
            if (! strstr($current_district_id_array, ',')) {
                array_push($curr_district_id_array, $current_district_id_array);
            } else {
                $curr_district_id_array = explode(',', $current_district_id_array);
            }
        }
        
        $ns_order_shipping_fee = new OrderShippingFeeModel();
        $list = $ns_order_shipping_fee->getConditionQuery([
            'co_id' => $co_id,
            'is_default' => 0
        ], 'province_id_array,city_id_array,district_id_array', '');
        
        // 1.把当前公司的所有省市id进行组拼
        $province_id_array = [];
        $city_id_array = [];
        $district_id_array = [];
        
        $res_list['province_id_array'] = [];
        $res_list['city_id_array'] = [];
        $res_list['district_id_array'] = [];
        
        foreach ($list as $k => $v) {
            
            if (! strstr($v['province_id_array'], ',')) {
                array_push($province_id_array, $v['province_id_array']);
            } else {
                $temp_province_array = explode(",", $v['province_id_array']);
                foreach ($temp_province_array as $temp_province_id) {
                    array_push($province_id_array, $temp_province_id);
                }
            }
            
            if (! strstr($v['city_id_array'], ',')) {
                array_push($city_id_array, $v['city_id_array']);
            } else {
                $temp_city_array = explode(",", $v['city_id_array']);
                foreach ($temp_city_array as $temp_city_id) {
                    array_push($city_id_array, $temp_city_id);
                }
            }
            
            if (! strstr($v['district_id_array'], ',')) {
                array_push($district_id_array, $v['district_id_array']);
            } else {
                $temp_district_array = explode(",", $v['district_id_array']);
                foreach ($temp_district_array as $temp_district_id) {
                    array_push($district_id_array, $temp_district_id);
                }
            }
        }
        
        // 2.排除当前编辑用到的省id组
        if (count($province_id_array)) {
            foreach ($province_id_array as $province_id) {
                $flag = true;
                foreach ($curr_province_id_array as $temp_province_id) {
                    
                    if ($province_id == $temp_province_id) {
                        $flag = false;
                    }
                }
                if ($flag) {
                    array_push($res_list['province_id_array'], $province_id);
                }
            }
        }
        
        // 3.排除当前编辑用到的市id组
        if (count($city_id_array)) {
            foreach ($city_id_array as $city_id) {
                $flag = true;
                foreach ($curr_city_id_array as $temp_city_id) {
                    
                    if ($city_id == $temp_city_id) {
                        $flag = false;
                    }
                }
                if ($flag) {
                    array_push($res_list['city_id_array'], $city_id);
                }
            }
        }
        
        // 4.排除当前编辑用到的区县id组
        if (count($district_id_array)) {
            foreach ($district_id_array as $district_id) {
                $flag = true;
                foreach ($curr_district_id_array as $temp_district_id) {
                    
                    if ($district_id == $temp_district_id) {
                        $flag = false;
                    }
                }
                if ($flag) {
                    array_push($res_list['district_id_array'], $district_id);
                }
            }
        }
        
        return $res_list;
    }
}