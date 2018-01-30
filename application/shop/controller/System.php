<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-12-20
 * Time: 17:08
 */

namespace app\shop\controller;

use app\shop\controller\BaseController;
use dao\handle\ShopSiteSettingHandle;



class System extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * ok-2ok
     * 得到手机店的首页块
     * @return \think\response\Json
     */
    public function wapShopIndexBlock() {
        $setting = new ShopSiteSettingHandle();
        $condition = array(
            'sort'=>1, //手机端
            'is_use'=>1
        );
        $fields = "sort, position, title, type, content, is_use";
        $order = 'position asc';

        $list = $setting->getShopIndexBlockList(1,  0, $condition, $fields,  $order);
        return json(resultArray(0,"操作成功", $list));
    }

    /**
     * ok-2ok
     * 得到PC店的首页块
     * @return \think\response\Json
     */
    public function pcShopIndexBlock() {
        $setting = new ShopSiteSettingHandle();
        $condition = array(
            'sort'=>2, //PC端
            'is_use'=>1
        );
        $fields = "sort, position, title, type, content, is_use";
        $order = 'position asc';

        $list = $setting->getShopIndexBlockList(1,  0, $condition, $fields,  $order);
        return json(resultArray(0,"操作成功", $list));
    }

    /**
     * ok-2ok
     * 得到商城介绍
     * @return \think\response\Json
     */
    public function shopIntroduce() {
        $setting = new ShopSiteSettingHandle();
        $condition = array(
            'sort'=>3, //手机端PC端
            'is_use'=>1
        );
        $fields = "sort, title, pic, content, is_use";

        $data = $setting->getShopIntroduce($condition, $fields);
        return json(resultArray(0,"操作成功", $data));
    }

    /**
     * ok-2ok
     * 得到手机店的菜单
     * @return \think\response\Json
     */
    public function wapShopMenu() {
        $setting = new ShopSiteSettingHandle();
        $condition = array(
            'sort'=>1, //手机端
            'is_use'=>1
        );
        $fields = "sort, position, menu_name, menu_logo, menu_url, is_use";
        $order = 'position asc';

        $list = $setting->getShopMenuList(1, 0, $condition, $fields,  $order);
        return json(resultArray(0,"操作成功", $list));
    }

    /**
     * ok-2ok
     * 得到PC店的菜单
     * @return \think\response\Json
     */
    public function pcShopMenu() {
        $setting = new ShopSiteSettingHandle();
        $condition = array(
            'sort'=>2, //PC端
            'is_use'=>1
        );
        $fields = "sort, position, menu_name, menu_logo, menu_url, is_use";
        $order = 'position asc';

        $list = $setting->getShopMenuList(1, 0, $condition, $fields,  $order);
        return json(resultArray(0,"操作成功", $list));
    }

}