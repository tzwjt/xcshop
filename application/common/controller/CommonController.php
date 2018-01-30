<?php
/**
 * CommonController.php
 * @date : 2017.08.01
 * @version : v1.0
 */
namespace app\common\controller;

\think\Loader::addNamespace('dao', 'dao/');

use dao\handle\ConfigHandle;
use think\Controller;
use think\Request;

use dao\handle\AddressHandle;
use dao\handle\SiteHandle;

class CommonController extends Controller
{
    public $param;
    public function _initialize()
    {
        parent::_initialize();
        /*防止跨域*/      
        /*
        header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, authKey, sessionId");
     */
        $param =  Request::instance()->param();    
              
        $this->param = $param;
    }

    public function object_array($array) 
    {  
        if (is_object($array)) {  
            $array = (array)$array;  
        } 
        if (is_array($array)) {  
            foreach ($array as $key=>$value) {  
                $array[$key] = $this->object_array($value);  
            }  
        }  
        return $array;  
    }

    /**
     * 获取大区列表
     */
    public function getArea()
    {
        $address = new AddressHandle();
        $area_list = $address->getAreaList();

        return json(resultArray(0,"操作成功",$area_list));
    }

    /**
     * 获取省列表，商品添加时用户可以设置商品所在地
     */
    public function getProvince()
    {
        $address = new AddressHandle();
        $area_id = isset($this->param['area_id']) ? $this->param['area_id'] : 0;
        $province_list = $address->getProvinceList($area_id);

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



    /**
     * 获取区县列表
     */
    public function getDistrict()
    {
        $address = new AddressHandle();
        $city_id = isset($this->param['city_id']) ? $this->param['city_id'] : 0;
        $district_list = $address->getDistrictList($city_id);
        return json(resultArray(0,"操作成功",$district_list));
    }


    /**
     *根据省名、市名返回所在市的区县
     */
    public function getDistrictByProvinceAndCity() {
        $province = $this->param["province"];
        $city = $this->param["city"];
        $address = new AddressHandle();
        $district_list = $address->getDistrictByProvinceNameAndCityName($province, $city);
        return json(resultArray(0,"操作成功",$district_list));

    }

    /**
     * 根据ip得到城市
     * @return \think\response\Json
     */
    public function getCityByIp() {
        $cip = isset($this->param['ip']) ? $this->param['ip'] : '';
        $data = get_city_by_ip($cip);
        return json(resultArray(0,"操作成功",$data));
    }

    /**
     * ok-2ok
     * 得到站点信息
     * @return \think\response\Json
     */
    public function getSiteInfo() {
        $site = new SiteHandle();
        $site_info = $site->getSiteInfo();
        $data = array(
            'platform_site_name'=> $site_info['platform_site_name'],
            'agent_site_name'=> $site_info['agent_site_name'],
            'web_shop_site_name'=> $site_info['web_shop_site_name'],
            'wap_shop_site_name'=> $site_info['wap_shop_site_name'],
            'platform_title'=> $site_info['platform_title'],
            'agent_title'=> $site_info['agent_title'],
            'web_shop_title'=> $site_info['web_shop_title'],
            'wap_shop_title'=> $site_info['wap_shop_title'],
            'logo'=> $site_info['logo'],
            'web_icp'=> $site_info['web_icp'],
            'web_icp_link' => $site_info['web_icp_link'],
            'web_address'=> $site_info['web_address'],
            'web_qrcode'=> $site_info['web_qrcode'],
            'web_email'=> $site_info['web_email'],
            'web_phone'=> $site_info['web_phone'],
            'web_qq'=> $site_info['web_qq'],
            'web_weixin'=> $site_info['web_weixin'],
            'third_count' =>  $site_info['third_count'],

        );

        if (request()->isAjax()) {
            return json(resultArray(0, "操作成功", $data));
        } else {
            return $data;
        }
    }

    /**
     * ok-2ok
     * 得到Seo信息
     * @return \think\response\Json
     */
    public function getSeoInfo() {
        $config = new ConfigHandle();
        $seo_info = $config->getSeoConfig(0);

        if (request()->isAjax()) {
            return json(resultArray(0,"操作成功",$seo_info));
        } else {
            return $seo_info;
        }
    }

    /**
     * ok-2ok
     * 得到版权信息
     * @return \think\response\Json
     */
    public function getCopyrightInfo() {
        $config = new ConfigHandle();
        $copyright_info = $config->getCopyrightConfig(0);
        if (request()->isAjax()) {
            return json(resultArray(0, "操作成功", $copyright_info));
        } else {
            return $copyright_info;
        }
    }


    /*
     *
     */
}
 