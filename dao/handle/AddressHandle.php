<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-08-20
 * Time: 21:34
 */

namespace dao\handle;

/**
 * 区域地址
 */
use dao\model\Area as AreaModle;
use dao\model\City as CityModel;
use dao\model\District as DistrictModel;
use dao\model\Province as ProvinceModel;
use dao\model\OffpayArea as OffpayAreaModel;
use dao\handle\BaseHandle as BaseHandle;


class AddressHandle extends BaseHandle
{
    /**
     * 获取区域列表
     */
    public function getAreaList()
    {
        $area = new AreaModle();
        $list = $area->getConditionQuery('', 'id,area_name', '');
        return $list;
    }

    /**
     * 获取省列表
     */
    public function getProvinceList($area_id = 0)
    {
        $province = new ProvinceModel();
        if ($area_id == - 1) {
            $list = array();
        } elseif ($area_id == 0) {
            $list = $province->getConditionQuery('', 'id,area_id,province_name,sort', 'sort, id');
        } else {
            $list = $province->getConditionQuery([
                'area_id' => $area_id
            ], 'id,area_id,province_name,sort', 'sort asc');
        }
      //  return "ddd";
        return $list;
    }

    /**
     * 根据省id组查询地址信息，并整理
     */
    public function getProvinceListById($province_id)
    {
        $province = new ProvinceModel();

        $condition = array(
            'id' => array(
                'in',
                $province_id
            )
        );
        $list = $province->getConditionQuery($condition, 'id,area_id,province_name,sort', 'sort asc');
        return $list;
    }

    /**
     * 根据省id组、市id组查询地址信息，并整理
     */
    public function getAddressListById($province_id_arr, $city_id_arr)
    {
        $province = new ProvinceModel();
        $city = new CityModel();

        $province_condition = array(
            'id' => array(
                'in',
                $province_id_arr
            )
        );
        $city_condition = array(
            'id' => array(
                'in',
                $city_id_arr
            )
        );
        $province_list = $province->getConditionQuery($province_condition, 'id,province_name', 'sort asc');
        $city_list = $city->getConditionQuery($city_condition, 'id,city_name,province_id', 'sort asc');
        $list = [];
        foreach ($province_list as $k => $v) {
            $list['province_list'][$k] = $v;
            $children_list = array();
            foreach ($city_list as $city_k => $city_v) {
                if ($v['id'] == $city_v['province_id']) {
                    $children_list[$city_k] = $city_v;
                }
            }
            $list['province_list'][$k]['city_list'] = $children_list;
        }

        return $list;
    }

    /**
     * 获取市列表
     */
    public function getCityList($province_id = 0)
    {
        $city = new CityModel();
        if ($province_id == 0) {
            $list = $city->getConditionQuery('', 'id,province_id,city_name,zipcode,sort', 'sort asc');
        } else {
            $list = $city->getConditionQuery([
                'province_id' => $province_id
            ], 'id,province_id,city_name,zipcode,sort', 'sort asc');
        }
        return $list;
    }

    /**
     * 获取区县列表
     */
    public function getDistrictList($city_id = 0)
    {
        $district = new DistrictModel();
        if ($city_id == 0) {
            $list = $district->getConditionQuery('', 'id,city_id,district_name,sort', 'sort asc');
        } else {
            $list = $district->getConditionQuery([
                'city_id' => $city_id
            ], 'id,city_id,district_name,sort', 'sort asc');
        }
        return $list;
    }

    /**
     * 获取省名称
     */
    public function getProvinceName($province_id)
    {
        $province = new ProvinceModel();

        if (! empty($province_id)) {
            $condition = array(
                'id' => array(
                    'in',
                    $province_id
                )
            );
            $list = $province->getConditionQuery($condition, 'province_name', '');
        }
        $name = '';
        if (! empty($list)) {
            foreach ($list as $k => $v) {
                $name .= $v['province_name'] . ',';
            }
            $name = substr($name, 0, strlen($name) - 1);
        }
        return $name;
    }

    /**
     * 获取市名称
     */
    public function getCityName($city_id)
    {
        $city = new CityModel();
        if (! empty($city_id)) {
            $condition = array(
                'id' => array(
                    'in',
                    $city_id
                )
            );
            $list = $city->getConditionQuery($condition, 'city_name', '');
        }

        $name = '';
        if (! empty($list)) {
            foreach ($list as $k => $v) {
                $name .= $v['city_name'] . ',';
            }
            $name = substr($name, 0, strlen($name) - 1);
        }
        return $name;
    }

    /**
     * 获取区县名称
     */
    public function getDistrictName($district_id)
    {
        $dictrict = new DistrictModel();

        if (! empty($district_id)) {
            $condition = array(
                'id' => array(
                    'in',
                    $district_id
                )
            );
            $list = $dictrict->getConditionQuery($condition, 'district_name', '');
        }

        $name = '';
        if (! empty($list)) {
            foreach ($list as $k => $v) {
                $name .= $v['district_name'] . ',';
            }
            $name = substr($name, 0, strlen($name) - 1);
        }
        return $name;
    }

    /**
     * 获取地区树
     */
    public function getAreaTree($existing_address_list)
    {
        $list = array();
        $area_list = $this->getAreaList();
        $list = $area_list;
        foreach ($area_list as $k_area => $v_area) {
            $province_list = $this->getProvinceList($v_area['id'] == 0 ? - 1 : $v_area['id']);
            foreach ($province_list as $key_province => $v_province) {
                $province_list[$key_province]['is_disabled'] = 0; // 是否可用，0：可用，1：不可用
                if (! empty($existing_address_list) && count($existing_address_list['province_id_array'])) {
                    foreach ($existing_address_list['province_id_array'] as $province_id) {
                        if ($province_id == $v_province['id']) {
                            $province_list[$key_province]['is_disabled'] = 1;
                        }
                    }
                }
                $city_list = $this->getCityList($v_province['id']);

                foreach ($city_list as $k => $city) {
                    $city_list[$k]['is_disabled'] = 0; // 是否可用，0：可用，1：不可用
                    if (! empty($existing_address_list) && count($existing_address_list['city_id_array'])) {
                        foreach ($existing_address_list['city_id_array'] as $city_id) {
                            if ($city_id == $city['id']) {
                                $city_list[$k]['is_disabled'] = 1;
                            }
                        }
                    }
                }

                $province_list[$key_province]['city_list'] = $city_list;
            }
            $list[$k_area]['province_list'] = $province_list;
            $list[$k_area]['province_list_count'] = count($province_list);
        }
        return $list;
    }

    /**
     * 获取地址 返回（例如： 山西省 太原市 小店区）
     */
    public function getAddress($province_id, $city_id, $district_id)
    {
        $province = new ProvinceModel();
        $city = new CityModel();
        $district = new DistrictModel();
        $province_name = $province->getInfo('id = ' . $province_id, 'province_name');
        $city_name = $city->getInfo('id = ' . $city_id, 'city_name');
        $district_name = $district->getInfo('id = ' . $district_id, 'district_name');
        $address = $province_name['province_name'] . '&nbsp;' . $city_name['city_name'] . '&nbsp;' . $district_name['district_name'];
        return $address;
    }

    /**
     * 获取省id
     */
    public function getProvinceId($province_name)
    {
        $province = new ProvinceModel();
        $province_id = $province->getInfo([
            'province_name' => $province_name
        ], 'id');
        return $province_id['id'];
    }



    /**
     * 获取市id
     */
    public function getCityId($city_name)
    {
        $city = new CityModel();
        $city_id = $city->getInfo([
            'city_name' => $city_name
        ], 'id');
        return $city_id['id'];
    }

    /**
     * ok-2ok
     * 获取区id
     */
    public function getDistrictId($district_name)
    {
        $district = new DistrictModel();
        $district_id = $district->getInfo([
            'district_name' => $district_name
        ], 'id');
        return $district_id['id'];
    }
    
      /**
     * 获取市id
     */
    public function getCityIdByProviceIdAndCityName($province_id, $city_name)
    {
        $city = new CityModel();
        $city_id = $city->getInfo([
            'province_id' =>$province_id,
            'city_name' => $city_name
        ], 'id');
        return $city_id['id'];
    }
    
    /**
     * 获取区id
     */
    public function getDistrictIdByCityIdAndDistrictName($city_id, $district_name)
    {
        $district = new DistrictModel();
        $dist_id = $district->getInfo([
            'city_id' =>$city_id,
            'district_name' => $district_name
        ], 'id');
        return $dist_id['id'];
    }

    /**
     * 获取区id
     */
    public function getDistrictByProvinceNameAndCityName($province, $city)
    {
        $list = "";
        $province_model = new ProvinceModel();
        $province_id = $province_model->getInfo([
            'province_name' => ['like', $province.'%']
        ], 'id');
        if (!empty($province_id)) {
            $city_model = new CityModel();
            $city_id = $city_model->getInfo([
                'province_id' => $province_id['id'],
                'city_name' => ['like', $city.'%']
            ], 'id');

            if (!empty($city_id)) {
                $district_model = new DistrictModel();
                $condition = array(
                    'city_id' => $city_id['id']
                );
                $list = $district_model->getConditionQuery($condition, 'id, district_name', '');
            }
        }
        return $list;
    }

    /**
     * 添加市级地区
     */
    public function addOrUpdateCity($city_id, $province_id, $city_name, $zipcode = '', $sort = '')
    {
        $city = new CityModel();
        $data = array(
            "province_id" => $province_id,
            "city_name" => $city_name,
            "zipcode" => $zipcode,
            "sort" => $sort
        );
        if ($city_id > 0 && $city_id != 0) {
            $res = $city->save($data, [
                'id' => $city_id
            ]);
            return $res;
        } else {
            $city->save($data);
            return $city->id;
        }
    }

    /**
     * 添加县级地区
     */
    public function addOrUpdateDistrict($district_id, $city_id, $district_name, $sort = '')
    {
        $district = new DistrictModel();
        $data = array(
            "city_id" => $city_id,
            "district_name" => $district_name,
            "sort" => $sort
        );
        if ($district_id > 0 && $district_id != 0) {
            return $district->save($data, [
                "id" => $district_id
            ]);
        } else {
            $district->save($data);
            return $district->id;
        }
    }

    /**
     * 修改省级区域
     */
    public function updateProvince($province_id, $province_name, $sort, $area_id)
    {
        $province = new ProvinceModel();
        $data = array(
            "province_name" => $province_name,
            "sort" => $sort,
            "area_id" => $area_id
        );
        return $province->save($data, [
            "id" => $province_id
        ]);
    }

    /**
     * 添加省级区域
     */
    public function addProvince($province_name, $sort, $area_id)
    {
        $province = new ProvinceModel();
        $data = array(
            "province_name" => $province_name,
            "sort" => $sort,
            "area_id" => $area_id
        );
        $province->save($data);
        return $province->id;
    }

    /**
     * 删除 省
     */
    public function deleteProvince($province_id)
    {
        $province = new ProvinceModel();
        $city = new CityModel();
        $this->startTrans();
        try {
            $city_list = $city->getConditionQuery([
                'province_id' => $province_id
            ], 'id', '');
            foreach ($city_list as $k => $v) {
                $this->deleteCity($v['id']);
            }
            $province->destroy($province_id);
            $this->commit();
            return 1;
        } catch (\Exception $e) {
            $this->rollback();
            return $e->getMessage();
        }
    }


    /**
     * 删除 市
     */
    public function deleteCity($city_id)
    {
        $city = new CityModel();
        $district = new DistrictModel();
        $this->startTrans();
        try {
            $district->destroy([
                'city_id' => $city_id
            ]);
            $city->destroy($city_id);
            $this->commit();
            return 1;
        } catch (\Exception $e) {
            $this->rollback();
            return $e->getMessage();
        }
    }

    /**
     * 删除 县
     */
    public function deleteDistrict($district_id)
    {
        $district = new DistrictModel();
        return $district->destroy($district_id);
    }

    /**
     * $upType 修改类型 1排序 2名称
     * $regionType 修改地区类型 1省 2市 3县
     */
    public function updateRegionNameAndRegionSort($upType, $regionType, $regionName, $regionSort, $regionId)
    {
        if ($regionType == 1) {
            $province = new ProvinceModel();
            if ($upType == 1) {
                $res = $province->save([
                    'sort' => $regionSort
                ], [
                    'id' => $regionId
                ]);
                return $res;
            }
            if ($upType == 2) {
                $res = $province->save([
                    'province_name' => $regionName
                ], [
                    'id' => $regionId
                ]);
                return $res;
            }
        }

        if ($regionType == 2) {
            $city = new CityModel();
            if ($upType == 1) {
                $res = $city->save([
                    'sort' => $regionSort
                ], [
                    'id' => $regionId
                ]);
                return $res;
            }

            if ($upType == 2) {
                $res = $city->save([
                    'city_name' => $regionName
                ], [
                    'id' => $regionId
                ]);
                return $res;
            }
        }

        if ($regionType == 3) {
            $district = new DistrictModel();
            if ($upType == 1) {
                $res = $district->save([
                    'sort' => $regionSort
                ], [
                    'id' => $regionId
                ]);
                return $res;
            }
            if ($upType == 2) {
                $res = $district->save([
                    'district_name' => $regionName
                ], [
                    'id' => $regionId
                ]);
                return $res;
            }
        }
    }

    /**
     * 通过省级id获取其下级的数量
     */
    public function getCityCountByProvinceId($province_id)
    {
        $city = new CityModel();
        $count = $city->getCount([
            'id' => $province_id
        ]);
        return $count;
    }

    /**
     * 通过市级id获取其下级的数量
     */
    public function getDistrictCountByCityId($city_id)
    {
        $district = new DistrictModel();
        $count = $district->getCount([
            'id' => $city_id
        ]);
        return $count;
    }

    /**
     * 添加配送地区
     */
    public function addOrUpdateDistributionArea($shop_id, $province_id, $city_id, $district_id)
    {
        /*
        $offpayArea = new NsOffpayAreaModel();
        $res = $this->getDistributionAreaInfo($shop_id);
        if ($res == '') {
            $data = array(
                "shop_id" => $shop_id,
                "province_id" => $province_id,
                "city_id" => $city_id,
                "district_id" => $district_id
            );
            return $offpayArea->save($data);
        } else {
            $data = array(
                "province_id" => $province_id,
                "city_id" => $city_id,
                "district_id" => $district_id
            );
            return $offpayArea->save($data, [
                'shop_id' => $shop_id
            ]);
        }
        */
    }

    /**
     * 获取配送地区
     */
    public function getDistributionAreaInfo($shop_id)
    {
        /*
        $offpayArea = new NsOffpayAreaModel();
        $res = $offpayArea->getInfo([
            'shop_id' => $shop_id
        ], "province_id,city_id,district_id");
        return $res;
        */
    }

    /**
     * ok-2ok
     * 检测某个地址是否可以 货到付款
     */
    public function getDistributionAreaIsUser( $province_id, $city_id, $district_id)
    {

        $offpayArea = new OffpayAreaModel();
        $is_use = false;
        $off_list = $offpayArea->where(" FIND_IN_SET(" . $province_id . ", province_id) AND FIND_IN_SET( " . $city_id . ", city_id) AND FIND_IN_SET(" . $district_id . ", district_id) ")->select();
        if (! empty($off_list) && count($off_list) > 0) {
            $is_use = true;
        } else {
            $is_use = false;
        }
        return $is_use;

    }

    /**
     * 运费模板的数据整理
     * @param unknown $existing_address_list
     */
    public function getAreaTree_ext($existing_address_list)
    {
        $list = array();
        $select_district_id_array=[];
        if(!empty($existing_address_list)){
            $select_district_id_array=$existing_address_list["district_id_array"];
        }
        //查询所有的地区信息
        $area_list = $this->getAreaList();
        //查询所有的省信息
        $province_list = $this->getProvinceList();
        //查询所有的市信息
        $city_list = $this->getCityList();
        //查询所有的区县的信息
        $district_list = $this->getDistrictList();

        $district_id_deal_array=[];
        //先整理所有区县的是否禁用的整理
        foreach ($district_list as $k_district=>$v_district){
            $is_set=false;
            $is_disabled=0;
            $district_id=$v_district["id"];
            $district_id_deal_array[$district_id]=$k_district;
            $is_set=in_array($district_id, $select_district_id_array);
            if($is_set){
                $is_disabled=1;
            }
            $district_list[$k_district]["is_disabled"]=$is_disabled;
        }
        //整理市的集合
        foreach ($city_list as $k_city=>$v_city){
            $deal_array=$this->dealCityDistrictData($v_city["id"], $district_list, $district_id_deal_array, $existing_address_list["city_id_array"]);
            $child_district_array=$deal_array["child_district"];
            $is_disabled=$deal_array["is_disabled"];
            $city_list[$k_city]["district_list"]=$child_district_array;
            $city_list[$k_city]["is_disabled"]=$is_disabled;
            $city_list[$k_city]["district_list_count"]=count($child_district_array);
        }
        //整理省的集合
        foreach ($province_list as $k_province=>$v_province){
            $deal_array=$this->dealProvinceCityData($v_province["id"], $city_list, $existing_address_list["province_id_array"]);
            $child_city_array=$deal_array["child_city"];
            $is_disabled=$deal_array["is_disabled"];
            $province_list[$k_province]["city_list"]=$child_city_array;
            $province_list[$k_province]["is_disabled"]=$is_disabled;
            $province_list[$k_province]["city_count"]=count($child_city_array);
            $province_list[$k_province]["city_disabled_count"]=0;
        }
        //整理地区的集合
        foreach ($area_list as $k_area => $v_area){
            $deal_array=$this->dealAreaProvinceData($v_area["id"], $province_list);
            $child_province_array=$deal_array["child_province"];
            $is_disabled=$deal_array["is_disabled"];
            $area_list[$k_area]["province_list"]=$child_province_array;
            $area_list[$k_area]["is_disabled"]=$is_disabled;
            $area_list[$k_area]["province_list_count"]=count($child_province_array);
        }
        return $area_list;

    }

    /**
     * 处理市和 地区的信息
     */
    private function dealCityDistrictData($city_id, $district_list, $district_id_deal_array, $select_city_ids){
        $is_disabled=1;
        $district_child_list = $this->getDistrictList($city_id);
        foreach ($district_child_list as $k=>$district_obj){
            $dis_id=$district_obj["id"];
            $k_num=$district_id_deal_array[$dis_id];
            $district_child_list[$k]["is_disabled"]=$district_list[$k_num]["is_disabled"];
            if($district_list[$k_num]["is_disabled"]==0){
                $is_disabled=0;
            }
        }
        if(empty($district_child_list)){
            $is_set=in_array($city_id, $select_city_ids);
            if($is_set){
                $is_disabled=1;
            }else{
                $is_disabled=0;
            }
        }
        return array(
            "child_district"=>$district_child_list,
            "is_disabled"=>$is_disabled
        );
    }

    /**
     * 处理省和市的信息
     * @param unknown $province_id
     * @param unknown $city_list
     */
    private function dealProvinceCityData($province_id, $city_list, $province_id_array){
        $city_child_array=[];
        $is_disabled=1;
        foreach ($city_list as $city_obj){
            if($city_obj["province_id"]==$province_id){
                $city_child_array[]=$city_obj;
                if($city_obj["is_disabled"]==0){
                    $is_disabled=0;
                }
            }
        }
        if(empty($city_child_array)){
            $is_set=in_array($province_id, $province_id_array);
            if($is_set){
                $is_disabled=1;
            }else{
                $is_disabled=0;
            }
        }
        return array(
            "child_city"=>$city_child_array,
            "is_disabled"=>$is_disabled
        );
    }
    /**
     * 处理区域的信息
     * @param unknown $area_id
     * @param unknown $province_list
     */
    private function dealAreaProvinceData($area_id, $province_list){
        $province_child_array=[];
        $is_disabled=1;
        foreach ($province_list as $province_obj){
            if($province_obj["area_id"]==$area_id){
                $province_child_array[]=$province_obj;
                if($province_obj["is_disabled"]==0){
                    $is_disabled=0;
                }
            }
        }
        return array(
            "child_province"=>$province_child_array,
            "is_disabled"=>$is_disabled
        );
    }

    /**
     * ok-2ok
     * 获取市的第一个区
     */
    public function getCityFirstDistrict($city_id)
    {
        $district_model = new DistrictModel();
        $data = $district_model->getFirstData([
            'id' => $city_id
        ], '');
        if (! empty($data)) {
            return $data['id'];
        } else {
            return 0;
        }
    }
}