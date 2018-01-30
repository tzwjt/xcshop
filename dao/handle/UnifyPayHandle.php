<?php
/**
 * UnifyPayHandle.php
 * 统一支付
 * @date : 2017.8.17
 * @version : v1.0
 */
namespace dao\handle;


use dao\handle\BaseHandle as BaseHandle;
use dao\model\OrderPayment as OrderPaymentModel;
use dao\model\Order as OrderModel;
use dao\handle\pay\WeiXinPay;
use dao\handle\pay\AliPay;
use dao\handle\ConfigHandle;
//use app\wap\controller\Assistant;
//use dao\service\niubusiness\NbsBusinessAssistant;
use think\Log;
use think\Cache;

class UnifyPayHandle extends BaseHandle
{

    function __construct()
    {
        parent::__construct();
    }
    /**
     * 创建订单支付编号 ok
     */
     public function createOutTradeNo()
    {
      //  $cache = Cache::get("niubfd".time());
        $cache = Cache::get("HNFD".time());
        if(empty($cache))
        {
            Cache::set("HNFD".time(), 1000);
            $cache = Cache::get("HNFD".time());
        }else{
            $cache = $cache+1;
            Cache::set("HNFD".time(), $cache);
        }
        $no = time().rand(1000,9999).$cache;
        return $no;
    }

    /**
     * ok-2ok
     * 获取支付配置
     */
    public function getPayConfig()
    {
       
        $instance_id = 0;
        $config = new ConfigHandle();
        $wchat_pay = $config->getWpayConfig($instance_id);
        $ali_pay = $config->getAlipayConfig($instance_id);
        $data_config = array(
            'wchat_pay_config' => $wchat_pay,
            'ali_pay_config'   => $ali_pay
        );
        return $data_config;
    }

    /**
     * 创建待支付单据
     * @param  $pay_no
     * @param  $pay_body
     * @param  $pay_detail
     * @param  $pay_money
     * @param  $type  订单类型  1. 商城订单  2.
     * @param  $pay_money
     */
    public function createPayment($out_trade_no,$agent_id, $pay_body, $pay_detail, $pay_money, $type, $type_alis_id)
    {
        $pay = new OrderPaymentModel();
        $data = array(
           // 'shop_id'       => $shop_id,
            'agent_id'=> $agent_id,
            'out_trade_no'  => $out_trade_no,
            'type'          => $type,
            'type_alis_id'  => $type_alis_id,
            'pay_body'      => $pay_body,
            'pay_detail'    => $pay_detail,
            'pay_money'     => $pay_money,
            'create_time'   => time()
        );
        if($pay_money <= 0)
        {
            $data['pay_status'] = 1;
        }
        $res = $pay->save($data);
        return $res;
    }

    /**
     * 根据支付编号修改待支付单据
     * @param  $out_trade_no
     * @param  $shop_id
     * @param  $pay_body
     * @param  $pay_detail
     * @param  $pay_money
     * @param  $type 订单类型  1. 商城订单  2.
     * @param  $type_alis_id
     */
    public function updatePayment($agent_id, $out_trade_no, $pay_body, $pay_detail, $pay_money, $type, $type_alis_id)
    {
        $pay = new OrderPaymentModel();
        $data = array(
           // 'shop_id'       => $shop_id,
            'agent_id'       => $agent_id,
            'type'          => $type,
            'type_alis_id'  => $type_alis_id,
            'pay_body'      => $pay_body,
            'pay_detail'    => $pay_detail,
            'pay_money'     => $pay_money,
            'update_time'   => time()
        );
        if($pay_money <= 0)
        {
            $data['pay_status'] = 1;
        }
        $res = $pay->save($data,['out_trade_no'=>$out_trade_no]);
        return $res;
    }

    /**
     * 删除待支付单据
     * @param  $out_trade_no
     */
    public function delPayment($out_trade_no){
        $pay = new OrderPaymentModel();
        $res = $pay->where('out_trade_no',$out_trade_no)->delete();
        return $res;
    }

    /**
     * ok-2ok
     * 线上支付主动根据支付方式执行支付成功的通知
     * @param unknown $out_trade_no
     */
    public function onlinePay($out_trade_no, $pay_type, $trade_no)
    {
        $pay = new OrderPaymentModel();
        try{
            $pay_info = $pay->getInfo(['out_trade_no' => $out_trade_no]);
            if($pay_info['pay_status'] == 1)
            {
               // return 1;
                return true;
                exit();
            }
            $data = array(
                'pay_status'     => 1,
                'pay_type'       => $pay_type,
                'pay_time'       => time(),
                'trade_no'      => $trade_no
            );
            $retval = $pay->save($data, ['out_trade_no' => $out_trade_no]);
            $pay_info = $pay->getInfo(['out_trade_no' => $out_trade_no], 'type');
            switch ( $pay_info['type']){
                case 1:
                    $order = new OrderHandle();
                    $order_model = new OrderModel();
                    $order_info = $order_model->get(['out_trade_no' => $out_trade_no]);
                    $user_id = $order_info['buyer_id'];
                    $user_type = 1;
                    $order->orderOnLinePay($user_id, $user_type,$out_trade_no, $pay_type);

                    break;
                case 2:
                    /*
                    $assistant = new NbsBusinessAssistant();
                    $assistant->payOnlineBusinessAssistantApply($out_trade_no);
                    */
                    break;
                case 4:
                    //充值
                    /*
                    $member = new Member();
                    $member->payMemberRecharge($out_trade_no, $pay_type);
                    */
                    break;
                default:
                    break;
            }
          //  return 1;
            return true;
        }catch(\Exception $e)
        {
            Log::write("weixin-------------------------------".$e->getMessage());
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**ok-2ok
     * 只是执行单据支付，不进行任何处理用于执行支付后被动调用
     * @param  $out_trade_no
     * @param  $pay_type
     */
    public function offLinePay($out_trade_no, $pay_type)
    {
        $pay = new OrderPaymentModel();
        $data = array(
            'pay_status'     => 1,
            'pay_type'       => $pay_type,
            'pay_time'   => time()
        );
        $retval = $pay->save($data, ['out_trade_no' => $out_trade_no]);
        if ($retval === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 获取支付信息
     * @param  $out_trade_no
     */
    public function getPayInfo($out_trade_no)
    {
        $pay = new OrderPaymentModel();
        $info = $pay->getInfo(['out_trade_no' => $out_trade_no], '*');
        return $info;
    }
    /**
     * 重新设置编号，用于修改价格订单
     * @param  $out_trade_no
     * @param  $new_no
     * @return Ambigous <number, \think\false, boolean, string>
     */
    public function modifyNo($out_trade_no, $new_no)
    {
        $pay = new OrderPaymentModel();
        $data = array(
            "out_trade_no" => $new_no
        );
        $retval = $pay->where(['out_trade_no' => $out_trade_no])->update($data);
        return $retval;
    }
    /**
     * 修改支付价格
     * @param  $out_trade_no
     */
    public function modifyPayMoney($out_trade_no, $pay_money)
    {
        $pay = new OrderPaymentModel();
        $data = array(
            'pay_money'       => $pay_money
        );
        $retval = $pay->save($data, ['out_trade_no' => $out_trade_no]);
    }

	/**
     * ok-2ok
     * 微信支付
     */
    public function wchatPay($out_trade_no, $trade_type, $red_url)
    {
        $data = $this->getPayInfo($out_trade_no);
        if($data < 0)
        {
            return $data;
        }
        $weixin_pay = new WeiXinPay();
        if($trade_type == 'JSAPI')
        {
            Log::write('openid before');
            $openid = $weixin_pay->get_openid();
          //  Log::write('openid:'.$openid);

            $product_id = '';
        }
        if($trade_type == 'NATIVE')
        {
            $openid = '';
            $product_id = $out_trade_no;
        }
        if($trade_type == 'MWEB')
        {
            $openid = '';
            $product_id = $out_trade_no;
        }
        Log::write('setWeiXinPay before');
        $retval = $weixin_pay->setWeiXinPay($data['pay_body'], $data['pay_detail'], $data['pay_money']*100, $out_trade_no, $red_url, $trade_type, $openid, $product_id);
       // Log::write('setWeiXinPay after:'.$retval);
        return $retval;
    }

	/**
     * ok-2ok
     * 支付宝支付
     */
    public function aliPay($out_trade_no, $notify_url, $return_url, $show_url)
    {
        $data = $this->getPayInfo($out_trade_no);
        if($data < 0)
        {
            return $data;
        }
        $ali_pay = new AliPay();
        $retval = $ali_pay->setAliPay($out_trade_no, $data['pay_body'], $data['pay_detail'], $data['pay_money'], 3, $notify_url, $return_url, $show_url);
        return $retval;
    }

    /***
     * ok-2ok
     * 得到微信js支付接口
     */
    public function getWxJsApi($UnifiedOrderResult)
    {
        $weixin_pay = new WeiXinPay();
        $retval = $weixin_pay->GetJsApiParameters($UnifiedOrderResult);
        return $retval;
    }

    /**
     * ok-2ok
     * 支付宝支付，对类型进行验证
     */
    public function getVerifyResult($type){
        $pay = new AliPay();
        $verify = $pay->getVerifyResult($type);
        return $verify;
    }

    /**
     * ok-2ok
     * 微信支付检测签名串
     * @param  $post_obj
     * @param  $sign
     */
    public function checkSign($post_obj, $sign)
    {
        $weixin_pay = new WeiXinPay();
        $retval = $weixin_pay->checkSign($post_obj, $sign);
        return $retval;
    }

}
