<?php

namespace app\platform\controller;

use dao\handle\AgentHandle;
use dao\handle\PlatformUserHandle;
use dao\handle\SiteHandle;
use think\Db;
use think\Controller;
use app\platform\controller\Goods;
use app\platform\controller\BaseController;
use dao\handle\ExpressHandle as ExpressHandle;

header("Content-Type: text/html;charset=utf-8");

class Admin extends BaseController
{
    public function _initialize()
    {
        $platformInfo=$this->getSiteInfo();
        $this->assign('platformName',$platformInfo['platform_title']);
        $this->assign('platformInfo',$platformInfo);
        $copyInfo=$this->getCopyrightInfo();
        $this->assign('copyInfo',$copyInfo['copyright_desc']);

        if (strtolower(request()->action()) != 'login' && strtolower(request()->action()) != 'geturl' ) {
            $user_handle = new PlatformUserHandle();
            $auth = $user_handle->getUserAuth();
            if($auth['is_admin']!=1){
                $this->redirect('index/index');

                exit();
            }
            $this->assign('is_admin', $auth["is_admin"]);
            $site=new SiteHandle();
            if ($auth["is_admin"] == 1) {
                $contidion=array(
                    'level'=>array('eq',1),
                );
                $module_first =$site->getSystemModuleList(1,0,$contidion,'sort desc,id');

            } else {
                $contidion=array(
                    'level'=>array('eq',1),
                    'id'=>array('in',$auth['module_id_array'])
                );
                $module_first =$site->getSystemModuleList(1,0,$contidion,'sort desc,id');
            }
//            dump($module_first['data']);
            $module_first = json_decode(json_encode($module_first['data']),TRUE);
            $this->assign("module_first", $module_first);
        }
    }
    public function webconfig(){
        return $this->fetch();
    }
    public function seoconfig(){
        return $this->fetch();
    }
    public function shopset(){
        return $this->fetch();
    }
    public function expressmessage(){
        return $this->fetch();
    }
    public function copyrightinfo(){
        return $this->fetch();
    }
    public function picconfig(){
        return $this->fetch();
    }
    public function agentwithdrawsetting(){
        return $this->fetch();
    }
    public function mobilemessage(){
        return $this->fetch();
    }
    public function addmodule()
    {
        $this->assign("module_id", '');
        return $this->fetch();
    }

    public function modulelist()
    {
        return $this->fetch();
    }

    public function editmodule($module_id = '')
    {
        $this->assign("module_id", $module_id);
        return $this->fetch('addmodule');
    }
    public function notifyindex(){
        return $this->fetch();
    }
    public function customservice(){
        return $this->fetch();
    }
    public function paylist(){
        return $this->fetch();
    }
    public function notifytemplate(){
        $this->assign("type",'');
        return $this->fetch();
    }
    public function smstemplate(){
        $this->assign("type",1);
        return $this->fetch('notifytemplate');
    }
    public function payaliconfig(){
        return $this->fetch();
    }
    public function wchatpayconfig(){
        return $this->fetch();
    }
    public function originalroadrefundsetting($type='wechat'){
       if($type=='wechat'){
           return $this->fetch('originalroadrefundsettingbywchat');
       }else{
           return $this->fetch('originalroadrefundsettingbyalipay');
       }
    }
    public function emailmessage(){
        return $this->fetch();
    }
    public function emailtemplate($type=''){
        $this->assign("type",$type);
        return $this->fetch();
    }
    public function platformShop()
    {
        return $this->fetch();
    }
    public function pickuppointfreight()
    {
        return $this->fetch();
    }
    public function addexpresscompany()
    {
        $this->assign('co_id', '');
        return $this->fetch();
    }
    public function pickuppointlist()
    {
        return $this->fetch();
    }
    public function editexpresscompany($co_id)
    {
        $this->assign('co_id', $co_id);
        return $this->fetch('addexpresscompany');
    }
    public function freighttemplatelist($co_id)
    {
        $this->assign('co_id', $co_id);
        return $this->fetch();
    }
    public function freighttemplateedit($co_id, $shipping_fee_id = 0)
    {
        $this->assign('co_id', $co_id);
        $this->assign('shipping_fee_id', $shipping_fee_id);
        return $this->fetch();
    }
    public function pickuppoint($pickup_point_id = 0)
    {
        $this->assign("pickup_point_id", $pickup_point_id);
        return $this->fetch();
    }
    public function expresstemplate($co_id)
    {
        $express = new ExpressHandle();
        // getExpressShippingItemsLibrary()
        $express_shipping_items_select = $express->getExpressShippingItemsLibrary();
        foreach ($express_shipping_items_select as $key => $value) {
            $field_name[$key] = str_replace("A", "", $value['field_name']);
        }
        array_multisort($field_name, SORT_NUMERIC, SORT_ASC, $express_shipping_items_select);
//        $co_id = isset($this->param['co_id']) ? $this->param['co_id'] : 0;
        if (empty($co_id)) {
            return json(resultArray(2, "未获取到相关信息"));
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
        if ($express_shipping_detail["width"] == 0) {
            $express_shipping_detail["width"] = 869;
        }
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
//         $this->assign('express_company_select', $express_company_detail);
//          $this->assign('express_shipping_select', $express_shipping_detail);
        //  $this->assign('print', $print);
        $info = array();
        for ($i = 0; $i < count($print); $i++) {
            $info[$i] = json_decode((string)$print[$i], true);
//            $display=$info[$i]["is_print"]==1?"block":"none";
            $info[$i]["style"] = "display:" . ($info[$i]["is_print"] == 1 ? "block" : "none") . ";position:absolute;left:" . $info[$i]["x"] . "px;top:" . $info[$i]["y"] . "px;";
        }

        $this->assign("express_shipping_detail", $express_shipping_detail);
        $this->assign("info", $info);
        $this->assign("length", count($info));
//        dump($express_shipping_items_select);
//
//        dump($express_company_detail);
//        dump($express_shipping_detail);
//        dump($info);
//        exit();

//        $this->assign("express_id", $id);
//        $this->assign('express_shipping_items_select', $express_shipping_items_select);
//
//        $data = array(
//            'express_company_select' => $express_company_detail,
//            'express_shipping_select' => $express_shipping_detail,
//            'print_express_shipping_items' => $print,
//            "express_id" => $id,
//            'express_shipping_items_library' => $express_shipping_items_select
//
//        );
////        echo $print[0]->data;
//        dump($print[0]);
////        return json(resultArray(0, "操作成功", $data));
//        exit();
        $this->assign("co_id", $co_id);
        return $this->fetch();
    }
    public function returnsetting()
    {
        return $this->fetch();
    }
    public function expresscompany()
    {
        return $this->fetch();
    }
    public function payinfo(){
        return $this->fetch();
    }
    public function shopindexlist(){
        return $this->fetch();
    }
    public function addshopindex(){
        $this->assign("block_id",'');

        return $this->fetch();

    }
    public function editshopindexblock($block_id=''){
        $this->assign("block_id",$block_id);
        return $this->fetch("addshopindex");
    }
    public function addShopMenu(){
        $this->assign("menu_id",'');

//        $this->assign("type",$type);
//        $this->assign()
        return $this->fetch();
    }
    public function editShopMenu($menu_id=''){
        $this->assign("menu_id",$menu_id);
        return $this->fetch('addShopMenu');

    }
    public function shopMenuList(){
        return $this->fetch();
    }
    public function shopIntroduce(){
        return $this->fetch();
    }
    public function document($name='meiqia'){
        if($name=='meiqia'){
            return $this->fetch('meiqia');
        }else if($name=='kf5'){
            return $this->fetch('kf5');
        }else{
            $this->error('链接有误','index/index');
        }
    }
}
