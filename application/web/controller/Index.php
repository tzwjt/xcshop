<?php
namespace app\web\controller;
use app\common\controller\CommonController;
use dao\handle\GoodsHandle;
use think\Cookie;
use think\Db;
use think\Controller;
header("Content-Type: text/html;charset=utf-8");
class Index extends CommonController
{
    public function _initialize()
    {
        $siteInfo=$this->getSiteInfo();
        $this->assign("siteInfo",$siteInfo);
        $seoInfo=$this->getSeoInfo();
        $this->assign("seoInfo",$seoInfo);
        $copyrightInfo=$this->getCopyrightInfo();
        $this->assign("copyrightInfo",$copyrightInfo);
        //设置返回地址
        $action = strtolower(request()->action());
        $this->assign("actionName", $action);
        if ($action != "login" && $action != "register") {
            session("upUrl", request()->url());
        }
        $this->assign("upUrl", session("upUrl"));
//        dump(session("upUrl"));
//        exit();
    }
    public function index()
    {
        $this->assign("url",url('detail'));

        return $this->fetch();
    }
    public function salelist($type=''){
        $this->assign("type",$type);
        $this->assign("url",url('detail'));
        return $this->fetch();
    }
    public function detail($list=''){
        if($list==''){
            $goods_handle=new GoodsHandle();
//            $goods_handle = new GoodsHandle();
            $list = $goods_handle->getMainGoodsId();
            if($list<=0){
                $this->error('主商品不存在...','index');
            }
        }
        $this->assign("id",$list);
        $this->assign("detailUrl",url('shop/goods/getGoodsDetail'));
        return $this->fetch();
    }
    public function login($nexturl=""){
        if($nexturl==""){
            $this->assign("nextUrl",url("index"));
        }else{
            $this->assign("nextUrl",url($nexturl));
        }
        $this->assign('next',$nexturl);
        return $this->fetch();
    }
    public function register($nexturl=''){
        $this->assign('nexturl',$nexturl);
        return $this->fetch();

    }
    public function shopcart(){
        return $this->fetch();
    }
    public function orderlist($goods_list=''){
        $this->assign("goods_list",$goods_list);
        return $this->fetch();
    }
    public function auto_login(){
        if(!empty(Cookie::get("login_phone")) && !empty(Cookie::get("password"))){

        }else{
            $this->redirect("login");
        }
    }
    public function pay($order_id=''){
        $this->assign("order_id",$order_id);
        return $this->fetch();
    }
    public function self(){
        return $this->fetch();
    }
    public function about(){
        return $this->fetch();
    }
    public function app(){
        return $this->fetch();
    }
    public function memberOrderList($type){
//        全部:all，待付款：0，待发货:1, 待收货:2, 已收货: 3,  退款/售后：4
        $status_name='';
        switch ($type){
            case "all":
                $status_name="全部订单";
                break;
            case 0:
                $status_name="代付款";
                break;
            case 1:
                $status_name="代发货";
                break;
            case 2:
                $status_name="待收货";
                break;
            case 3:
                $status_name="已收货";
                break;
            case 4:
                $status_name="退款/售后";
                break;
        }
        $this->assign("status_name",$status_name);
        $this->assign("type",$type);
        return $this->fetch();
    }
    public function personData(){
        return $this->fetch();
    }
    public function address(){
        return $this->fetch();
    }
    public function refund_detail($order_goods_id){
        $this->assign("order_goods_id",$order_goods_id);
        return $this->fetch();
    }
    public function agency(){
        return $this->fetch();
    }
    public function memberCoupon(){
        return $this->fetch();

    }
    public function balancewater(){
        return $this->fetch();

    }
    public function integralwater(){
        return $this->fetch();
    }
    public function coupon(){
        return $this->fetch();
    }
    public function changepwd(){
        return $this->fetch();
    }
    public function orderdetail($order_id){
        $this->assign('order_id',$order_id);
        return $this->fetch();
    }
    public function tyd(){
        return $this->fetch();

    }
    public function weChatPay($out_trade_no){
        $this->assign('out_trade_no',$out_trade_no);
        return $this->fetch();
    }
    public function security(){
        return $this->fetch();

    }
}
