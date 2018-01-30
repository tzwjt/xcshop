<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-12-07
 * Time: 21:52
 */

namespace app\shop\controller;

use app\shop\controller\BaseController;

use dao\extend\QRcode;
use dao\handle\ConfigHandle;
use dao\handle\MemberHandle as MemberHandle;
use dao\handle\member\MemberUserHandle as MemberUserHandle;
use dao\handle\OrderHandle;
use dao\handle\UnifyPayHandle;
use dao\handle\SiteHandle;
use think\Controller;
use think\Log as Log;
use think\Request;
use think\Session;

/**
 * 支付控制器
 *
 * @author Administrator
 *
 */
class Pay extends BaseController
{

    public $style;

    public $shop_config;

    public function __construct()
    {
        parent::__construct();

        $config = new ConfigHandle();

        // 购物设置
        $this->shop_config = $config->getShopConfig();

    }

    /* 演示版本 */
    public function demoVersion()
    {
       // return view($this->style . 'Pay/demoVersion');
    }

    /**
     * ok-2ok
     * 获取支付相关信息
     */
    public function getPayValue()
    {
        //身份验证
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");
        if ($user_id == 0) {
            return $authRet;
        }

        $out_trade_no = request()->post('out_trade_no');
        if (empty($out_trade_no)) {
            return json(resultArray(2,"没有获取到支付信息"));
        }

        $pay = new UnifyPayHandle();
     //   $pay_config = $pay->getPayConfig();

        $pay_value = $pay->getPayInfo($out_trade_no);
        if (empty($pay_value)) {
            return json(resultArray(2,"订单主体信息已发生变动!"));
        }

        if ($pay_value['pay_status'] == 1) {
            // 订单已经支付
            return json(resultArray(2,"订单已经支付，无需再次支付!"));
        }
        if ($pay_value['type'] == 1) {
            // 订单
            $order_status = $this->getOrderStatusByOutTradeNo($out_trade_no);
            // 订单关闭状态下是不能继续支付的
            if ($order_status == 5) {
                return json(resultArray(2,"订单已关闭"));
            }
        }

        $zero1 = time(); // 当前时间 ,注意H 是24小时 h是12小时
        $zero2 = $pay_value['create_time'];
        if ($zero1 > ($zero2 + ($this->shop_config['order_buy_close_time'] * 60))) {
            return json(resultArray(2,"订单已关闭"));
        } else {
            $data = array (
             //   "pay_config" =>$pay_config,
                'pay_value'=> $pay_value
            );

            return json(resultArray(0,"操作成功", $data));
        }
    }

    /**
     * ok-2ok
     * 支付完成后回调界面
     * status 1 成功
     */
    public function payCallback()
    {
        $out_trade_no = request()->post('out_trade_no'); // 流水号
        $msg = request()->post('msg'); // 测试，-1：在其他浏览器中打开，1：成功，2：失败
        $order_no = $this->getOrderNoByOutTradeNo($out_trade_no);
        $data = array(
            "status"=> $msg,
            "order_no"=> $order_no

        );
      //  "Pay/payCallback"
        return json(resultArray(0,"操作成功", $data));
    }

    /**
     * ok-2ok
     * 订单微信支付
     */
    public function wchatPay()
    {
        //身份验证
        /**
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");
        if ($user_id == 0) {
            return $authRet;
        }
        **/
        $user_id = Session::get("MEMBER_USER_ID");
        if (empty($user_id)) {
            return json(resultArray(1,"身份验证未通过"));
        }


        $out_trade_no = request()->get('out_trade_no', '');
       if (empty($out_trade_no)) {
           return json(resultArray(2,"没有获取到支付信息"));
       }
        if (! is_numeric($out_trade_no)) {
            return json(resultArray(2,"没有获取到支付信息"));
        }


        $pay = new UnifyPayHandle();

        $pay_value = $pay->getPayInfo($out_trade_no);
        if (empty($pay_value)) {
            return json(resultArray(2,"订单主体信息已发生变动!"));
        }

        if ($pay_value['pay_status'] == 1) {
            // 订单已经支付
            return json(resultArray(2,"订单已经支付，无需再次支付!"));
        }
        if ($pay_value['type'] == 1) {
            // 订单
            $order_status = $this->getOrderStatusByOutTradeNo($out_trade_no);
            // 订单关闭状态下是不能继续支付的
            if ($order_status == 5) {
                return json(resultArray(2,"订单已关闭"));
            }
        }

        $zero1 = time(); // 当前时间 ,注意H 是24小时 h是12小时
        $zero2 = $pay_value['create_time'];
        if ($zero1 > ($zero2 + ($this->shop_config['order_buy_close_time'] * 60))) {
            return json(resultArray(2,"订单已关闭"));
        }

        $red_url = str_replace("/index.php", "", __URL__);
        $red_url = str_replace("index.php", "", $red_url);
        $red_url = $red_url . "/weixinpay.php";
        Log::write('red_url:'.$red_url);
        $pay = new UnifyPayHandle();
        if (! isWeixin()) {
            if (Request::instance()->isMobile()) {
                //手机网页
                 $res = $pay->wchatPay($out_trade_no, 'MWEB', $red_url);
                if ($res["return_code"] == "SUCCESS" && $res["result_code"] == "SUCCESS" ) {
                    $this->redirect($res["mweb_url"]);
                } else {
                    $this->redirect( "/wap/index/self");
                   // return json(resultArray(2, "统一下单失败!".$res["err_code_des"]));
                }
                // $this->redirect($res["mweb_url"]);

            } else {
                // PC端扫码支付
                $res = $pay->wchatPay($out_trade_no, 'NATIVE', $red_url);


                if ($res["return_code"] == "SUCCESS") {
                    if (empty($res['code_url'])) {
                        $code_url = "生成支付二维码失败!";
                        return json(resultArray(2, "生成支付二维码失败!"));
                    } else {
                        $code_url = $res['code_url'];
                    }
                    if (!empty($res["err_code"]) && $res["err_code"] == "ORDERPAID" && $res["err_code_des"] == "该订单已支付") {
                        return json(resultArray(2, $res["err_code_des"]));
                        //  $this->redirect(__URL(__URL__ . "/member/index"));
                    }
                } else {
                    $code_url = "生成支付二维码失败!";
                    return json(resultArray(2, "生成支付二维码失败!"));
                }
                $path = getQRcode($code_url, "upload/qrcode/pay", $out_trade_no);
                // $this->assign("path", __ROOT__ . '/' . $path);
                $pay_value = $pay->getPayInfo($out_trade_no);

                //$this->assign('pay_value', $pay_value);
                Log::write("path:".__ROOT__ . '/' . $path);
                $data = array(
                    "path" => __ROOT__ . '/' . $path,
                    'pay_value' => $pay_value

                );
                return json(resultArray(0, "操作成功", $data));
            }
           // return view($this->style . "Pay/pcWeChatPay");
            // }
        } else {
            // jsapi支付
            $res = $pay->wchatPay($out_trade_no, 'JSAPI', $red_url);
            if (! empty($res["return_code"]) && $res["return_code"] == "FAIL") {   // && $res["return_msg"] == "JSAPI支付必须传openid") {
               // return view("wap@index/self");
               // return json(resultArray(2, $res["return_msg"]));
                $this->redirect( "/wap/index/self");
            } else {
                $retval = $pay->getWxJsApi($res);
                $this->assign("out_trade_no", $out_trade_no);
                $this->assign('jsApiParameters', $retval);
                /**
                $data = array(
                    "out_trade_no"=> $out_trade_no,
                    'jsApiParameters'=> $retval
                );
                 * **/
               // return json(resultArray(0, "操作成功", $data));
                return view("wap@index/weixinPay");
               // return view($this->style . 'Pay/weixinPay');
            }
        }
    }

    /**
     * ok-2ok
     * 微信支付异步回调（只有异步回调对订单进行处理）
     */
    public function wchatUrlBack()
    {
        $postStr = file_get_contents('php://input');
        Log::write("post:".$postStr);
        if (! empty($postStr)) {
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $pay = new UnifyPayHandle();
            $check_sign = $pay->checkSign($postObj, $postObj->sign);

            if ($postObj->result_code == 'SUCCESS' && $check_sign == 1) {

                $retval = $pay->onlinePay($postObj->out_trade_no, 1, "");

                $xml = "<xml>
                    <return_code><![CDATA[SUCCESS]]></return_code>
                    <return_msg><![CDATA[支付成功]]></return_msg>
                </xml>";
                echo $xml;
            } else {
                $xml = "<xml>
                    <return_code><![CDATA[FAIL]]></return_code>
                    <return_msg><![CDATA[支付失败]]></return_msg>
                </xml>";
                echo $xml;
            }
        }
    }


    /**
     * ok-2ok
     * 微信支付同步回调（不对订单处理）
     */
    public function wchatPayResult()
    {
        //身份验证
        $user_id = Session::get("MEMBER_USER_ID");
        if (empty($user_id)) {
            return json(resultArray(1,"身份验证未通过"));
        }

        $out_trade_no = request()->get('out_trade_no');
        $msg = request()->get('msg');
        $order_no = $this->getOrderNoByOutTradeNo($out_trade_no);
        /**
        $data = array(
            "status" => $msg,
            "order_no" => $order_no
        );
        **/
        $this->assign("status", $msg);
        $this->assign("order_no", $order_no);
        if (request()->isMobile()) {
            return view("wap@index/payReturn");
        } else {
            return view( "web@index/payReturn");
        }
     //   return json(resultArray(0, "操作成功", $data));
        /**
        if (request()->isMobile()) {
            return view($this->style . "Pay/payCallback");
        } else {
            return view($this->style . "Pay/payCallbackPc");
        }
         * **/
    }


    /**
     * ok-2ok
     * 微信二维码支付状态
     */
    public function wchatQrcodePay()
    {
        $out_trade_no = request()->post("out_trade_no");
        $pay = new UnifyPayHandle();
        $payResult = $pay->getPayInfo($out_trade_no);

        if ($payResult['pay_status'] > 0) {
            $retval = array(
                    "code" => 1,
                    "message" => ''
                );
        } else {
            $retval = array(
                "code" => 0,
                "message" => ''
            );
        }
        return json(resultArray(0, "操作成功", $retval));
    }

    /**
     * ok-2ok
     * 支付宝支付
     */
    public function aliPay()
    {
        //身份验证
        /**
        $authRet =$this->checkAuth();
        $user_id =  $this->user_id;  //Session::set("USER_ID");
        if ($user_id == 0) {
            return $authRet;
        }
        **/
        $user_id = Session::get("MEMBER_USER_ID");
        if (empty($user_id)) {
            return json(resultArray(1,"身份验证未通过"));
        }

        $out_trade_no = request()->post('out_trade_no');
        if (empty($out_trade_no)) {
            return json(resultArray(2,"没有获取到支付信息"));
        }

        if (! is_numeric($out_trade_no)) {
            return json(resultArray(2,"没有获取到支付信息"));
        }

        $pay = new UnifyPayHandle();
        //   $pay_config = $pay->getPayConfig();

        $pay_value = $pay->getPayInfo($out_trade_no);
        if (empty($pay_value)) {
            return json(resultArray(2,"订单主体信息已发生变动!"));
        }

        if ($pay_value['pay_status'] == 1) {
            // 订单已经支付
            return json(resultArray(2,"订单已经支付，无需再次支付!"));
        }
        if ($pay_value['type'] == 1) {
            // 订单
            $order_status = $this->getOrderStatusByOutTradeNo($out_trade_no);
            // 订单关闭状态下是不能继续支付的
            if ($order_status == 5) {
                return json(resultArray(2,"订单已关闭"));
            }
        }

        $zero1 = time(); // 当前时间 ,注意H 是24小时 h是12小时
        $zero2 = $pay_value['create_time'];
        if ($zero1 > ($zero2 + ($this->shop_config['order_buy_close_time'] * 60))) {
            return json(resultArray(2,"订单已关闭"));
        }

        if (! isWeixin()) {
            $notify_url = str_replace("/index.php", '', __URL__);
            $notify_url = str_replace("index.php", '', $notify_url);
            $notify_url = $notify_url . "/alipay.php";
            $return_url = request()->domain(). url(__URL__ . '/shop/Pay/aliPayReturn');
            $show_url =request()->domain().url( __URL__ . '/shop/Pay/aliUrlBack');
            $pay = new UnifyPayHandle();
            Log::write("支付宝------------------------------------" . $notify_url);
            Log::write("支付宝--------------------------------return_url：" . $return_url);
            $res = $pay->aliPay($out_trade_no, $notify_url, $return_url, $show_url);
          //  echo "<meta charset='UTF-8'><script>window.location.href='" . $res . "'</script>";
            $data = array(
                "status"=> 1,
                "res"=> $res
            );
            return json(resultArray(0, "操作成功", $data ));
        } else {
            // echo "点击右上方在浏览器中打开";
          //  $this->assign("status", - 1);
            $order_no = $this->getOrderNoByOutTradeNo($out_trade_no);
          //  $this->assign("order_no", $order_no);

            $data = array(
                "status"=> -1,
                "order_no"=> $order_no
            );

           return json(resultArray(0, "操作成功", $data));
/*
            if (request()->isMobile()) {
                return view( "wap@Index/payReturn");
            } else {
                return view("web@Index/payReturn");
            }
             */
        }
    }

    /**
     * ok-2ok
     * 支付宝支付异步回调
     */
    public function aliUrlBack()
    {
        Log::write("支付宝------------------------------------进入回调用");
        $pay = new UnifyPayHandle();
        $verify_result = $pay->getVerifyResult('notify');
        if ($verify_result) { // 验证成功
            $out_trade_no = request()->post('out_trade_no');
            // 支付宝交易号
            $trade_no = request()->post('trade_no');

            // 交易状态
            $trade_status = request()->post('trade_status');

            Log::write("支付宝------------------------------------交易状态：" . $trade_status);
            if ($trade_status == 'TRADE_FINISHED') {
                // 判断该笔订单是否在商户网站中已经做过处理
                // 如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                // 如果有做过处理，不执行商户的业务程序
                // 注意：
                // 退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知

                // 调试用，写文本函数记录程序运行情况是否正常
                // logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
                $retval = $pay->onlinePay($out_trade_no, 2, $trade_no);

                Log::write("支付宝------------------------------------retval：" . $retval);
                // $res = $order->orderOnLinePay($out_trade_no, 2);
            } else
                if ($trade_status == 'TRADE_SUCCESS') {
                    // 判断该笔订单是否在商户网站中已经做过处理
                    // 如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                    // 如果有做过处理，不执行商户的业务程序

                    // 注意：
                    // 付款完成后，支付宝系统发送该交易状态通知
                    $retval = $pay->onlinePay($out_trade_no, 2, $trade_no);

                    Log::write("支付宝------------------------------------retval：" . $retval);
                    // $res = $order->orderOnLinePay($out_trade_no, 2);
                    // 调试用，写文本函数记录程序运行情况是否正常
                    // logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
                }

            // ——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

            echo "success"; // 请不要修改或删除

            // $this->assign("status", 1);
            // $this->assign("out_trade_no", $out_trade_no);
            // return view($this->style . "Pay/payCallback");

            // ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        } else {
            // 验证失败
            echo "fail";

            // $this->assign("status", 2);
            // $this->assign("out_trade_no", $out_trade_no);
            // return view($this->style . "Pay/payCallback");
            // 调试用，写文本函数记录程序运行情况是否正常
        } // logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
    }

    /**
     * ok-2ok
     * 支付宝支付同步回调（页面）（不对订单进行处理）
     */
    public function aliPayReturn()
    {
        $out_trade_no = request()->get('out_trade_no');

        $order_no = $this->getOrderNoByOutTradeNo($out_trade_no);
        $this->assign("order_no", $order_no);
        $pay = new UnifyPayHandle();
        $verify_result = $pay->getVerifyResult('return');
        if ($verify_result) {
            $trade_no = request()->get('trade_no');
            $trade_status = request()->get('trade_status', '');

            if ($trade_no == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS') {
                // return view($this->style . 'Pay/pay_success');
                // echo "<h1>支付成功</h1>";
                $this->assign("status", 1);
                /**
                $data = array (
                    "orderNumber"=> $out_trade_no,
                    "order_no"=>$order_no,
                    "status"=>1,
                    "msg"=> 1
                );
                 * **/
              //  return json(resultArray(0, "操作成功", $data));

                if (request()->isMobile()) {
                    return view("wap@index/payReturn");
                } else {
                    return view( "web@index/payReturn");
                }

            } else {
                echo "trade_status=" . $trade_status;
            }
           $this->assign("orderNumber", $out_trade_no);
            $this->assign("msg", 1);
        } else {
            $this->assign("orderNumber", $out_trade_no);
            $this->assign("msg", 0);
            // echo "<h1>支付失败</h1>";
            $this->assign("status", 2);
            /**
            $data = array(
                "orderNumber"=> $out_trade_no,
                "msg"=> 0,
                "status"=> 2
            );
            return json(resultArray(0, "操作成功", $data));
             * **/

            if (request()->isMobile()) {
                return view("wap@index/payReturn");
            } else {
                return view( "web@index/payReturn");
            }

            // echo "验证失败";
        }
    }

    /**
     * ok-2ok
     * 根据流水号查询订单编号
     */
    private function getOrderNoByOutTradeNo($out_trade_no)
    {
        $pay = new UnifyPayHandle();
        $order = new OrderHandle();
        $pay_value = $pay->getPayInfo($out_trade_no);
        $order_no = "";
        if ($pay_value['type'] == 1) {
            // 订单
            $list = $order->getOrderNoByOutTradeNo($out_trade_no);

            if (! empty($list)) {
                foreach ($list as $v) {
                    $order_no .= $v['order_no'];
                }
            }
        } elseif ($pay_value['type'] == 4) {
            // 余额充值不进行处理
        }
        return $order_no;
    }

    /**
     * ok-2ok
     * 根据外部交易号查询订单状态，订单关闭状态下是不能继续支付的
     */
    private function getOrderStatusByOutTradeNo($out_trade_no)
    {
        $order = new OrderHandle();
        $order_status = $order->getOrderStatusByOutTradeNo($out_trade_no);

        if (! empty($order_status)) {
            return $order_status['order_status'];
        }
        return 0;
    }
}