<?php

/**
 * 处理平台帐户
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-10-14
 * Time: 15:42
 */

namespace dao\handle\platformaccount;

use dao\handle\BaseHandle as BaseHandle;
use dao\model\Order as OrderModel;
use dao\model\OrderGoods as OrderGoodsModel;
use dao\model\AgentAccountOrderRecords as AgentAccountOrderRecordsModel;
use dao\model\AgentAccount as AgentAccountModel;
use dao\model\AccountOrderRecords as AccountOrderRecordsModel;
use dao\model\Account as AccountModel;
use dao\model\AccountCommissionRecords as AccountCommissionRecordsModel;
use dao\handle\ConfigHandle as ConfigHandle;
use dao\handle\AgentHandle as AgentHandle;
use dao\model\Agent as AgentModel;
use dao\handle\agentaccount\AgentAccountHandle as AgentAccountHandle;
use think\Log;
use dao\model\AccountRefundRecords as AccountRefundRecordsModel;

class PlatformAccountHandle extends BaseHandle
{
    /**
     * 添加平台的订单记录
     */
    public function addAccountOrderRecords($operation_id, $agent_id, $order_id,$buyer_id, $order_money, $order_goods_money,
                                           $order_shipping_money,$order_tax_money,$order_promotion_money,
                                        $order_coupon_money,$order_point_money, $order_platform_balance_money,
                                           $order_user_balance_money, $order_coin_money,$order_pay_money,
                                           $order_pay_money_weixin,$order_pay_money_ali,$order_pay_money_offline,$pay_type,$pay_time,
                                           $back_point, $back_coupon_id,$back_coupon_money, $account_type,$remark)
    {
        $order_model = new AccountOrderRecordsModel();

        $records_list = $order_model->getInfo([
            'operation_id'=> $operation_id,
            "order_id" => $order_id,
            "agent_id" => $agent_id,
        ], "id");
        if (empty($records_list)) {
            $this->startTrans();
            try {
                $data = array(
                     'serial_no' => getSerialNo(),
                    'operation_id' => $operation_id,
                     'agent_id' => $agent_id,
                     'order_id' => $order_id,
                    'buyer_id' => $buyer_id,
                     'order_money' => $order_money,
                    'order_goods_money' => $order_goods_money,
                     'order_shipping_money' => $order_shipping_money,
                     'order_tax_money' => $order_tax_money,
                     'order_promotion_money' => $order_promotion_money,
                     'order_coupon_money' => $order_coupon_money,
                     'order_point_money'=> $order_point_money,
                     'order_platform_balance_money'=> $order_platform_balance_money,
                     'order_user_balance_money' => $order_user_balance_money,
                     'order_coin_money'=> $order_coin_money,
                     'order_pay_money' => $order_pay_money,
                     'order_pay_money_weixin' => $order_pay_money_weixin,
                     'order_pay_money_ali'=> $order_pay_money_ali,
                    'order_pay_money_offline'=>$order_pay_money_offline,
                    'pay_type'=> $pay_type,
                    'pay_time'=> $pay_time,
                    'back_point' => $back_point,
                     'back_coupon_id' => $back_coupon_id,
                     'back_coupon_money'=> $back_coupon_money,
                     'account_type' => $account_type,
                     'type_alis_id' => $order_id,
                    'remark'=> $remark
                );
                $res1 =  $order_model->save($data);
                if (empty($res1)) {
                     $this->rollback();
                     return false;
                }
            // 更新订单的总金额字段
                $res2 =  $this->updateAccountOrder( $order_money,$order_goods_money,$order_shipping_money,
                $order_tax_money,$order_promotion_money,$order_coupon_money,
                $order_point_money,$order_platform_balance_money,$order_user_balance_money,
                $order_coin_money,$order_pay_money,$order_pay_money_weixin,
                $order_pay_money_ali,$order_pay_money_offline,$back_point,
               $back_coupon_money);
                if (empty($res2)) {
                     $this->rollback();
                    return false;
                }
                // 添加平台的整体资金流水
                 //  $this->addAccountRecords($shop_id, 0, "商场订单支付成功!", $money, 1, $type_alis_id, "商场订单在线支付!");
                 $this->commit();
                 return true;
             } catch (\Exception $e) {
                Log::write('addAccountOrderRecords' . $e->getMessage());
                $this->error = $e->getMessage();
                $this->rollback();
                return false;
            }
        }
    }

    /**
     * 更新平台账户的订单数据
     */
    private function updateAccountOrder( $order_money,$order_goods_money,$order_shipping_money,
                               $order_tax_money,$order_promotion_money,$order_coupon_money,
                                         $order_point_money,$order_platform_balance_money,$order_user_balance_money,
                                $order_coin_money,$order_pay_money,$order_pay_money_weixin,
                                         $order_pay_money_ali,$order_pay_money_offline,$back_point,
                                         $back_coupon)
    {
        $account_model = new AccountModel();

        $account_obj = $account_model->getInfo();
        if (empty($account_obj)) {
            $data = array(
                'order_total_count' => 0
            );
            $account_model->save($data);
            $account_obj = $account_model->get($account_model->id);

        }

        $data = array(
            'order_total_count' => $account_obj['order_total_count'] + 1,
            'order_total_money' => $account_obj['order_total_money'] + $order_money,
            'order_goods_total_money'=>$account_obj['order_goods_total_money'] +  $order_goods_money,
            'order_shipping_total_money'=>$account_obj['order_shipping_total_money'] +  $order_shipping_money,
            'order_tax_total_money'=>$account_obj['order_tax_total_money'] +  $order_tax_money,
            'order_promotion_total_money'=> $account_obj['order_promotion_total_money'] +  $order_promotion_money,
            'order_coupon_total_money'=>$account_obj['order_coupon_total_money'] +  $order_coupon_money,
            'order_point_total_money' =>$account_obj['order_point_total_money'] +  $order_point_money,
            'order_platform_balance_total_money'=>$account_obj['order_platform_balance_total_money'] + $order_platform_balance_money,
            'order_user_balance_total_money'=>$account_obj['order_user_balance_total_money'] +  $order_user_balance_money,
            'order_coin_total_money'=>$account_obj['order_coin_total_money'] +  $order_coin_money,
            'order_pay_total_money'=>$account_obj['order_pay_total_money'] + $order_pay_money,
            'order_pay_total_money_weixin'=>$account_obj[ 'order_pay_total_money_weixin'] +  $order_pay_money_weixin,
            'order_pay_total_money_ali'=>$account_obj['order_pay_total_money_ali'] +  $order_pay_money_ali,
            'order_pay_total_money_offline'=>$account_obj['order_pay_total_money_offline'] + $order_pay_money_offline,
            'back_point_total'=>$account_obj['back_point_total'] + $back_point,
            'back_coupon_total'=>$account_obj['back_coupon_total'] + $back_coupon
        );
       $ret = $account_model->save($data, [
            'id' => $account_obj['id']
        ]);

        if ($ret === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 添加平台帐户中的佣金记录表
     */
    public function addPlatformAccountCommissionRecordsOnAdd($operation_id, $serial_no, $agent_id, $order_id, $from_level,
                                                          $account_money, $sign, $account_type, $remark)
    {
        $model = new AccountCommissionRecordsModel();
        $records_list = $model->getInfo([
            'operation_id'=> $operation_id,
            "order_id" => $order_id,
            "agent_id" => $agent_id,
            'from_level'=>$from_level
        ], "id");
        if (empty($records_list)) {
            $this->startTrans();
            try {
                $agent_account_model = new AgentAccountModel();
                $agent_account_info = $agent_account_model->get(['agent_id'=>$agent_id]);
                $commission_rate_first = 0;
                $commission_rate_second = 0;
                if (empty($agent_account_info) || empty($agent_account_info['commission_rate_first'])) {
                    $config = new ConfigHandle();
                    $agent_model = new AgentModel();
                    $agent = $agent_model->get($agent_id);
                    if ($agent['agent_type'] == 1) {
                        $commission_rate_first = $config->getValidConfig(0, 'AGENT_COMMISSION_RATE_FIRST');
                        $commission_rate_first = (float)$commission_rate_first['value'];
                        $commission_rate_second = $config->getValidConfig(0, 'AGENT_COMMISSION_RATE_SECOND');
                        $commission_rate_second = (float)$commission_rate_second['value'];
                    } else if ($agent['agent_type'] == 2) {
                        $commission_rate_first = $config->getValidConfig(0, 'AGENT_SON_COMMISSION_RATE_FIRST');
                        $commission_rate_first = (float)$commission_rate_first['value'];
                        $commission_rate_second = $config->getValidConfig(0, 'AGENT_SON_COMMISSION_RATE_SECOND');
                        $commission_rate_second = (float)$commission_rate_second['value'];
                    }
                    $agent_account = new AgentAccountHandle();
                    $agent_account->addAgentCommissionRateRecords(20, $agent_id, $commission_rate_first, $commission_rate_second,
                        1, '初次设置佣金率--从配置表中取值');
                } else {
                    $commission_rate_first = $agent_account_info['commission_rate_first'];
                    $commission_rate_second = $agent_account_info['commission_rate_second'];
                }

                $commission_rate = 0;
                if ($from_level === 1) {
                    $commission_rate = $commission_rate_first;
                } else if ($from_level === 2) {
                    $commission_rate = $commission_rate_second;
                }
                $commission_money = $account_money * $commission_rate;
                $data = array(
                    'serial_no' => $serial_no,
                    'operation_id' => $operation_id,
                    'agent_id'=> $agent_id,
                    'order_id'=>$order_id,
                    'from_level'=> $from_level,
                    'account_money'=> $account_money,
                    'commission_rate'=> $commission_rate,
                    'commission_money'=> $commission_money,
                    'sign' => $sign,
                    'account_type' => $account_type,
                    'remark' => $remark,
                );
                Log::write($data);
                Log::write("model save before");
                $records_id = $model->save($data);
                Log::write("model save after");
                if (empty($records_id)) {
                    $this->rollback();
                    Log::write("model save error");
                    return false;
                }
                // 更新平台帐户的佣金数据相关的字段
                $res = $this->updatePlatformAccountCommission($commission_money);
                if (empty($res)) {
                    $this->rollback();
                    return false;
                }
                $this->commit();
              //  return $model->id;
                return true;
            } catch (\Exception $e) {
                $this->rollback();
                $this->error = $e->getMessage();
                return false;
            }
        }
    }

    /**
     * 更新平台帐户的佣金相关的字段
     */
    private function updatePlatformAccountCommission($commission_money)
    {
        $account_model = new AccountModel();

        $account_obj = $account_model->getInfo();
        if (empty($account_obj)) {
            $data = array(
                'agent_commission_total_money' => 0
            );
            $account_model->save($data);
            $account_obj = $account_model->get($account_model->id);

        }


        $retval = false;
        $data = array(
            'agent_commission_total_money' => $account_obj['agent_commission_total_money'] + $commission_money,
        );
        $retval = $account_model->save($data, [
                'id' => $account_obj['id']
            ]);

        if ($retval === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 添加平台的退款记录
     */
    public function addAccountRefundRecords($operation_id,$order_id,$order_goods_id,$agent_id,$goods_money,
                                               $refund_money,$account_type,$remark )
    {
        $refund_model = new AccountRefundRecordsModel();

        $this->startTrans();
        try {
            $data = array(
                    'serial_no' => getSerialNo(),
                    'operation_id' => $operation_id,
                    'order_id' => $order_id,
                    'order_goods_id' => $order_goods_id,
                    'agent_id' => $agent_id,
                    'goods_money'=> $goods_money,
                    'refund_money'=>$refund_money,
                    'account_type'=>$account_type,
                    'remark'=> $remark
                );
            $res1 =  $refund_model->save($data);
            if (empty($res1)) {
                $this->rollback();
                Log::write('refund_model->save 出错');
                return false;
            }
            // 更新平台帐户的退款金额
            $account_model = new AccountModel();
            $account_info = $account_model->getInfo();
            $account_data = array(
                'order_refund_total_money'=>$account_info['order_refund_total_money'] + $refund_money
            );

            $res2 = $account_model->save($account_data, ['id'=>$account_info['id']]);
            if ($res2 === false) {
                $this->rollback();
                Log::write('account_model->save 出错');
                return false;
            }

            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }



    /**
     * 添加平台的退佣金记录
     */
    public function addPlatformRefundCommissonReconds($operation_id,$order_id,$order_goods_id,$agent_id, $from_level,
                                                   $refund_money,$remark )
    {
        $account_records_model = new AccountCommissionRecordsModel();
        $records  = $account_records_model->getInfo(
            array(
                'order_id' =>$order_id,
                'account_type'=> 1,
                'from_level'=>$from_level,
                'agent_id' => $agent_id
            ),   '*');
        if (empty($records)) {
            return true;
        }

        $this->startTrans();
        try {
            $commission_rate = $records['commission_rate'];
            $commission_money = $refund_money * $commission_rate;
            if ($commission_money > $records['commission_money'] ) {
                $commission_money = $records['commission_money'];
            }

            $data = array(
                'serial_no' => getSerialNo(),
                'operation_id' => $operation_id,
                'order_id' => $order_id,
                'order_goods_id' => $order_goods_id,
                'agent_id' => $agent_id,
                'account_money' => $refund_money,
                'commission_rate' => $commission_rate,
                'commission_money'=>$commission_money,
                'sign'=>0,
                'account_type'=>2,
                'from_level' => $from_level,
                'remark' => $remark
            );
            $res1 = $account_records_model->save($data);
            if (empty($res1)) {
                $this->rollback();
                Log::write('account_records_model->save 出错');
                return false;
            }
            // 更新平台帐户的佣金
            $account_model = new AccountModel();
            $account_info = $account_model-> getInfo();
            $from_level == (int)$from_level;


            $agent_commission_total_money = $account_info['agent_commission_total_money'] - $commission_money;
            if ($agent_commission_total_money < 0) {
                $agent_commission_total_money = 0;
            }
            $account_data = array(
                'agent_commission_total_money' => $agent_commission_total_money  //$agent_account_info['commission_total_money_first'] - $commission_money
            );
            $resval = $account_model->save($account_data, ['id' => $account_info['id']]);
            if ($resval === false) {
                $this->rollback();
                Log::write('account_model->save 出错');
                return false;
            }

            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->rollback();
            return false;
        }
    }
}