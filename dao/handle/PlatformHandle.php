<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-11-24
 * Time: 16:21
 */

namespace dao\handle;

use dao\handle\BaseHandle;
use dao\model\Account as AccountModel;

class PlatformHandle extends BaseHandle
{
    function __construct()
    {
        parent::__construct();
    }

    /**
     * ok-2ok
     * 平台帐户数据
     * @return array
     */
    public function getAccountData() {
        $account_model = new AccountModel();
        $account = $account_model->getInfo();
        $sales_money = $account['order_goods_total_money'] - $account['order_promotion_total_money']
            - $account['order_coupon_total_money'] - $account['order_point_total_money']
            - $account['order_refund_total_money'];

        $agent_commission_balance = $account['agent_commission_total_money']
            + $account['agent_commission_adjust_total_money']
            -  $account['agent_withdraw_total_money'];


        $data = array(
            'order_total_count'=> $account['order_total_count'], //订单总数
            'order_total_money'=> $account['order_total_money'], //订单总金额
            'order_goods_total_money'=> $account['order_goods_total_money'], //订单商品总金额
            'order_shipping_total_money'=> $account['order_shipping_total_money'], //订单运费总额
            'order_tax_total_money'=> $account['order_tax_total_money'], //订单开票税金总额
            'order_promotion_total_money'=> $account['order_promotion_total_money'], //订单优惠总额
            'order_coupon_total_money'=> $account['order_coupon_total_money'], //优惠券总额
            'order_point_total_money'=> $account['order_point_total_money'], //积分总额
            'order_platform_balance_total_money'=> $account['order_platform_balance_total_money'], //余额支付总额

            'order_refund_total_money'=> $account['order_refund_total_money'], //退款总额
            'sales_money'=>$sales_money,//销售总额
            'order_pay_total_money'=> $account['order_pay_total_money'], //实际收入总额（用户实际支付）
            'order_pay_total_money_weixin'=> $account['order_pay_total_money_weixin'], //微信支付总额
            'order_pay_total_money_ali'=> $account['order_pay_total_money_ali'], //支付宝支付总额
            'order_pay_total_money_offline'=> $account['order_pay_total_money_offline'], //线下支付总额

            'agent_commission_total_money'=> $account['agent_commission_total_money'], //代理商佣金总额
            'agent_commission_adjust_total_money'=> $account['agent_commission_adjust_total_money'], //代理商佣金调整总额
            'agent_withdraw_total_money'=> $account['agent_withdraw_total_money'], //代理商提款总额
            'agent_commission_balance'=>$agent_commission_balance //代理商提款剩余总额

        );
        return $data ;
    }

}