<?php
namespace app\wap\controller;
use app\common\controller\CommonController;
use app\shop\controller\System;
use dao\handle\GoodsHandle;
use think\Db;
use think\Controller;
//use app\common\controller\CommonController;
header("Content-Type: text/html;charset=utf-8");
class Index extends CommonController
{
    public function _initialize()
    {
        $siteInfo = $this->getSiteInfo();
        $this->assign("siteInfo", $siteInfo);
        $seoInfo = $this->getSeoInfo();
        $this->assign("seoInfo", $seoInfo);
        $copyrightInfo = $this->getCopyrightInfo();
        $this->assign("copyrightInfo", $copyrightInfo);

        $this->assign("show_back", 1);
        $action = strtolower(request()->action());
        $this->assign("actionName", $action);
        if ($action != "login" && $action != "register") {
            session("upUrl", request()->url());
        }
        $this->assign("upUrl", session("upUrl"));
//        dump(session("upUrl"));
    }

    public function index()
    {
//        dump($_SESSION);
//        $system=new System();
//        $shopIndex=$system->wapShopIndexBlock();
//
//        dump($shopIndex);
//        exit();


        $this->assign("show_back", 0);
        return $this->fetch();
    }

    public function salelist($type = '')
    {
        $this->assign("type", $type);
        $this->assign("url", url('detail'));
        return $this->fetch();
    }

    public function detail($list = '')
    {
        if ($list == '') {
            $goods_handle = new GoodsHandle();
            $list = $goods_handle->getMainGoodsId();
            if ($list <= 0) {
                $this->error('主商品不存在...', 'index');
            }
        }
        $this->assign("id", $list);
        $this->assign("detailUrl", url('shop/goods/getGoodsDetail'));
        return $this->fetch();
    }

    public function login($nexturl = "")
    {
        if ($nexturl == "") {
            $this->assign("nextUrl", url("index"));
        } else {
            $this->assign("nextUrl", url($nexturl));
        }
        $this->assign('next', $nexturl);
        return $this->fetch();
    }

    public function register($nexturl = '')
    {
        $this->assign('nexturl', $nexturl);

        return $this->fetch();
    }

    public function address()
    {
        return $this->fetch();
    }

    public function shopcart()
    {
        return $this->fetch();
    }

    public function orderlist($goods_list = '')
    {
        $this->assign("goods_list", $goods_list);
        return $this->fetch();
    }

    public function pay($order_id = '')
    {
        $this->assign("order_id", $order_id);
        return $this->fetch();
    }

    public function addaddress($orginalurl, $address_id = '', $province = '', $city = '', $district = '')
    {
        $this->assign("orginalurl", $orginalurl);
        $this->assign("address_id", $address_id);
        $this->assign("province", $province);
        $this->assign("city", $city);
        $this->assign("district", $district);
        return $this->fetch();
    }

    public function self()
    {
        return $this->fetch();
    }

    public function order($type)
    {
        $this->assign("status", $type);
        return $this->fetch();
    }

    public function order_detail($order_id)
    {
        $this->assign("order_id", $order_id);
        return $this->fetch();
    }

    public function tyd()
    {
        return $this->fetch();
    }

    public function app()
    {
        return $this->fetch();
    }

    public function about()
    {
        return $this->fetch();
    }

    public function order_info($order_id)
    {
        $this->assign("order_id", $order_id);
        return $this->fetch();
    }

    public function refund_detail($order_goods_id)
    {
        $this->assign("order_goods_id", $order_goods_id);
        return $this->fetch();
    }

    public function personaldata()
    {
        return $this->fetch();
    }

    public function memberCoupon($type = 1)
    {
        $this->assign("type", $type);
        return $this->fetch();
    }

    public function integralwater()
    {
        return $this->fetch();
    }

    public function balancewater()
    {
        return $this->fetch();
    }

    public function modifyface()
    {
        return $this->fetch();
    }

    public function memberAddress()
    {
        return $this->fetch();
    }

    public function myAgent()
    {
        return $this->fetch();
    }

    public function appInfo()
    {
        return $this->fetch();
    }

    public function payReturn()
    {
        return $this->fetch();
    }

    public function aliPay($out_trade_no)
    {
        $this->assign('out_trade_no', $out_trade_no);
//        $this->assign('url',$url);
        return $this->fetch();
    }

    public function expressInfo($order_id)
    {
        $this->assign("order_id", $order_id);
        return $this->fetch('expressInfo');
    }

    public function Security()
    {
        return $this->fetch();
    }
}
