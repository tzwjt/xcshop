<?php

/**
 * 代理商帐户处理
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-10-14
 * Time: 0:02
 */
namespace dao\handle\agentaccount;

use dao\handle\AgentHandle;
use dao\handle\BaseHandle as BaseHandle;
use dao\model\Order as OrderModel;
use dao\model\OrderGoods as OrderGoodsModel;
use dao\model\AgentAccountOrderRecords as AgentAccountOrderRecordsModel;
use dao\model\AgentAccount as AgentAccountModel;
use dao\model\AgentAccountCommissionRecords as AgentAccountCommissionRecordsModel;
use dao\model\AgentAccountRateRecords as AgentAccountRateRecordsModel;
use dao\handle\ConfigHandle as ConfigHandle;
use dao\model\Agent as AgentModel;
use dao\model\AgentAccountRefundRecords as AgentAccountRefundRecordsModel;
use think\Log;


class AgentAccountHandle extends BaseHandle
{
    /**
     * 添加代理高的订单数据记录表
     */
    public function addAgentAccountOrderRecords($serial_no,$operation_id, $agent_id, $order_id,$buyer_id, $from_level,
                                                $order_money, $order_goods_money,$order_promotion_money,
                                                $order_coupon_money, $order_point_money,$order_pay_money,$pay_type, $pay_time,
                                                $account_type,  $remark)
    {
        $model = new AgentAccountOrderRecordsModel();
        $records_list = $model->getInfo([
            'operation_id'=> $operation_id,
            "order_id" => $order_id,
            "agent_id" => $agent_id,
            'from_level'=>$from_level
        ], "id");
        if (empty($records_list)) {
            $this->startTrans();
            try {
                $data = array(
                    'serial_no' => $serial_no,
                    'operation_id' => $operation_id,
                    'agent_id'=> $agent_id,
                    'order_id'=>$order_id,
                    'buyer_id'=> $buyer_id,
                    'from_level'=> $from_level,
                    'order_money'=> $order_money,
                    'order_goods_money'=> $order_goods_money,
                    'order_promotion_money'=> $order_promotion_money,
                    'order_coupon_money' => $order_coupon_money,
                    'order_point_money' => $order_point_money,
                    'order_pay_money'=> $order_pay_money,
                    'pay_type'=> $pay_type,
                    'pay_time'=> $pay_time,
                    'account_type' => $account_type,
                    'remark' => $remark,
                );
                $records_id = $model->save($data);
                if (empty($records_id)) {
                    $this->rollback();
                    return false;
                }
                // 更新代理商帐户的订单数据相关的字段
                $res = $this->updateAgentAccountOrderData($agent_id, $from_level, $order_money,$order_goods_money,$order_promotion_money,
                    $order_coupon_money, $order_point_money,$order_pay_money);
                if (empty($res)) {
                    $this->rollback();
                    return false;
                }
                $this->commit();
               // return $model->id;
                return true;
            } catch (\Exception $e) {
                $this->rollback();
                $this->error = $e->getMessage();
                return false;
            }
        }
    }

    /**
     * 更新代理商帐户的订单数据相关的字段
     */
    private function updateAgentAccountOrderData($agent_id, $from_level, $order_money,$order_goods_money,$order_promotion_money,
                                                 $order_coupon_money, $order_point_money,$order_pay_money)
    {
        $account_model = new AgentAccountModel();
        $account_info = $account_model->get(['agent_id'=>$agent_id]);
        // 没有的新建账户
        if (empty($account_info)) {
            $data = array(
                'agent_id' => $agent_id
            );
            $account_model->save($data);
            $account_info = $account_model->get(['agent_id'=>$agent_id]);
        }
        $retval = false;
        if ($from_level == 1) { //直接订单
            $data = array(
                'order_total_count_first' => $account_info['order_total_count_first'] + 1,
                'order_total_money_first' => $account_info['order_total_money_first'] + $order_money,
                'order_goods_total_money_first' => $account_info['order_goods_total_money_first'] + $order_goods_money,
                'order_promotion_total_money_first' => $account_info['order_promotion_total_money_first'] + $order_promotion_money,
                'order_coupon_total_money_first' => $account_info['order_coupon_total_money_first'] + $order_coupon_money,
                'order_point_total_money_first' => $account_info['order_point_total_money_first'] + $order_point_money,
                'order_pay_total_money_first' => $account_info['order_pay_total_money_first'] + $order_pay_money,

            );
            $retval = $account_model->save($data, [
                'agent_id' => $agent_id
            ]);
        } else if ($from_level == 2) { //间接订单
            $data = array(
                'order_total_count_second' => $account_info['order_total_count_second'] + 1,
                'order_total_money_second' => $account_info['order_total_money_second'] + $order_money,
                'order_goods_total_money_second' => $account_info['order_goods_total_money_second'] + $order_goods_money,
                'order_promotion_total_money_second' => $account_info['order_promotion_total_money_second'] + $order_promotion_money,
                'order_coupon_total_money_second' => $account_info['order_coupon_total_money_second'] + $order_coupon_money,
                'order_point_total_money_second' => $account_info['order_point_total_money_second'] + $order_point_money,
                'order_pay_total_money_second' => $account_info['order_pay_total_money_second'] + $order_pay_money,
            );
            $retval = $account_model->save($data, [
                'agent_id' => $agent_id
            ]);
        }

        if ($retval === false) {
            return false;

        } else {
            return true;
        }
    }

    /**
     * 添加代理高的佣金记录表
     */
    public function addAgentAccountCommissionRecordsOnAdd($operation_id, $serial_no, $agent_id, $order_id, $from_level,
                                                          $account_money, $sign, $account_type, $remark)
    {
        $model = new AgentAccountCommissionRecordsModel();
        $records_list = $model->getInfo([
            'operation_id'=> $operation_id,
            "order_id" => $order_id,
            "agent_id" => $agent_id,
            'from_level'=>$from_level
        ], "id");
        if (empty($records_list)) {
            $this->startTrans();
            try {
                $account_model = new AgentAccountModel();
                $account_info = $account_model->get(['agent_id'=>$agent_id]);
                $commission_rate_first = 0;
                $commission_rate_second = 0;
                if (empty($account_info) || empty($account_info['commission_rate_first'])) {
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
                    $this->addAgentCommissionRateRecords(20, $agent_id, $commission_rate_first, $commission_rate_second,
                        1, '初次设置佣金率--从配置表中取值');
                } else {
                    $commission_rate_first = $account_info['commission_rate_first'];
                    $commission_rate_second = $account_info['commission_rate_second'];
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
                $records_id = $model->save($data);
                if (empty($records_id)) {
                    $this->rollback();
                    return false;
                }
                // 更新代理商帐户的佣金数据相关的字段
                $res = $this->updateAgentAccountCommission($agent_id,$from_level,$commission_money);
                if (empty($res)) {
                    $this->rollback();
                    return false;
                }
                $this->commit();
               // return $model->id;
                return true;
            } catch (\Exception $e) {
                $this->rollback();
                $this->error = $e->getMessage();
                return false;
            }
        }
    }

    /**
     * 更新代理商帐户的佣金相关的字段
     */
    private function updateAgentAccountCommission($agent_id,$from_level,$commission_money)
    {
        $account_model = new AgentAccountModel();
        $account_info = $account_model->get(['agent_id'=>$agent_id]);
        // 没有的新建账户
        if (empty($account_info)) {
            $data = array(
                'agent_id' => $agent_id
            );
            $account_model->save($data);
            $account_info = $account_model->get(['agent_id'=>$agent_id]);
        }
        $retval = false;
        if ($from_level == 1) { //直接订单
            $data = array(
                'commission_total_money_first' => $account_info['commission_total_money_first'] + $commission_money,
            );
            $retval = $account_model->save($data, [
                'agent_id' => $agent_id
            ]);
        } else if ($from_level == 2) { //间接订单
            $data = array(
                'commission_total_money_second' => $account_info['commission_total_money_second'] + $commission_money,
            );
            $retval = $account_model->save($data, [
                'agent_id' => $agent_id
            ]);
        }

        if ($retval === false) {
            return false;
        } else {
            return true;
        }
       // return $retval;
    }


    public function addAgentCommissionRateRecords($operation_id, $agent_id, $commission_rate_first, $commission_rate_second,
                                              $account_type, $remark ) {
        $model =  new AgentAccountRateRecordsModel();
        $this->startTrans();
        try {
            $data = array(
                'serial_no' => getSerialNo(),
                 'operation_id' => $operation_id,
                'agent_id'=> $agent_id,
                 'commission_rate_first'=>$commission_rate_first,
                 'commission_rate_second'=> $commission_rate_second,
                'account_type' => $account_type,
                'remark' => $remark,
            );
            $records_id = $model->save($data);
            if (empty($records_id)) {
                $this->rollback();
                return false;
            }
            // 更新代理商帐户的订单数据相关的字段
            $res = $this->updateAgentAccountCommissionRate($agent_id,$commission_rate_first, $commission_rate_second );
            if (empty($res)) {
                $this->rollback();
                return false;
            }
            $this->commit();
           // return $model->id;
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function updateAgentAccountCommissionRate($agent_id,$commission_rate_first, $commission_rate_second ) {
        $account_model = new AgentAccountModel();
        $account_info = $account_model->get(['agent_id'=>$agent_id]);
        // 没有的新建账户
        if (empty($account_info)) {
            $data = array(
                'agent_id' => $agent_id
            );
            $account_model->save($data);
            $account_info = $account_model->get(['agent_id'=>$agent_id]);
        }
        $retval = false;

        $data = array(
            'commission_rate_first' => $commission_rate_first,
            'commission_rate_second' => $commission_rate_second,
        );
        $retval = $account_model->save($data, [
            'agent_id' => $agent_id
        ]);

        if ($retval === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 添加代理商的退款记录
     */
    public function addAgentAccountRefundRecords($operation_id,$order_id,$order_goods_id,$agent_id, $from_level,$goods_money,
                                            $refund_money,$account_type,$remark )
    {
        $agent_refund_model = new AgentAccountRefundRecordsModel();

        $this->startTrans();
        try {
            $data = array(
                'serial_no' => getSerialNo(),
                'operation_id' => $operation_id,
                'agent_id' => $agent_id,
                'order_id' => $order_id,
                'order_goods_id' => $order_goods_id,
                'from_level' => $from_level,
                'goods_money' => $goods_money,
                'refund_money' => $refund_money,
                'account_type' => $account_type,
                'remark' => $remark
            );
            $res1 = $agent_refund_model->save($data);
            if (empty($res1)) {
                $this->rollback();
                Log::write('agent_refund_model->save 出错');
                return false;
            }
            // 更新代理商帐户的退款金额
            $agent_account_model = new AgentAccountModel();
            $agent_account_info = $agent_account_model->get(['agent_id' => $agent_id]);
            $from_level == (int)$from_level;

            if ($from_level == 1) {
                $account_data = array(
                    'order_refund_total_money_first' => $agent_account_info['order_refund_total_money_first'] + $refund_money
                );
                $resval = $agent_account_model->save($account_data, ['id' => $agent_account_info['id']]);
                if ($resval === false) {
                    $this->rollback();
                    Log::write('agent_account_model->save 出错');
                    return false;
                }
             } else if ($from_level == 2) {
                $account_data = array(
                    'order_refund_total_money_second' => $agent_account_info['order_refund_total_money_second'] + $refund_money
                );
                $resval = $agent_account_model->save($account_data, ['id' => $agent_account_info['id']]);
                if ($resval === false) {
                    $this->rollback();
                    Log::write('agent_account_model->save 出错');
                    return false;
                }
            }

            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->rollback();
            return false;
        }
    }

    /**
     * 添加代理商的退佣金记录
     */
    public function addAgentRefundCommissonReconds($operation_id,$order_id,$order_goods_id,$agent_id, $from_level,
                                                 $refund_money,$remark )
    {
        $agent_records_model = new AgentAccountCommissionRecordsModel();
        $records  = $agent_records_model->getInfo(
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
                'agent_id' => $agent_id,
                'order_id' => $order_id,
                'order_goods_id' => $order_goods_id,
                'from_level' => $from_level,
                'account_money' => $refund_money,
                'commission_rate' => $commission_rate,
                'commission_money'=>$commission_money,
                'sign'=>0,
                'account_type'=>2,
                'remark' => $remark
            );
            $res1 = $agent_records_model->save($data);
            if (empty($res1)) {
                $this->rollback();
                Log::write('agent_records_model->save 出错');
                return false;
            }
            // 更新代理商帐户的佣金
            $agent_account_model = new AgentAccountModel();
            $agent_account_info = $agent_account_model->get(['agent_id' => $agent_id]);
            $from_level == (int)$from_level;

            if ($from_level == 1) {
                $commission_total_money_first = $agent_account_info['commission_total_money_first'] - $commission_money;
                if ($commission_total_money_first < 0) {
                    $commission_total_money_first = 0;
                }
                $account_data = array(
                    'commission_total_money_first' => $commission_total_money_first  //$agent_account_info['commission_total_money_first'] - $commission_money
                );
                $resval = $agent_account_model->save($account_data, ['id' => $agent_account_info['id']]);
                if ($resval === false) {
                    $this->rollback();
                    Log::write('agent_account_model->save 出错');
                    return false;
                }
            } else if ($from_level == 2) {
                $commission_total_money_second = $agent_account_info['commission_total_money_second'] - $commission_money;
                if ($commission_total_money_second < 0) {
                    $commission_total_money_second = 0;
                }
                $account_data = array(
                    'commission_total_money_second' => $commission_total_money_second // $agent_account_info['commission_total_money_second'] - $commission_money
                );
                $resval = $agent_account_model->save($account_data, ['id' => $agent_account_info['id']]);
                if ($resval === false) {
                    $this->rollback();
                    Log::write('agent_account_model->save 出错');
                    return false;
                }
            }

            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->rollback();
            return false;
        }
    }

    /**
     * ok-2ok
     * 得到指定代理商的帐户余额
     * @param $agent_id
     * @return int
     */
    public function getAgentBalance($agent_id) {
        $agent_account_model = new AgentAccountModel();
        $agent_account =  $agent_account_model->get(['agent_id'=>$agent_id]);
        $balance = 0;
       // Log::write("aa");
        if (!empty($agent_account)) {

            $commission_total_money_first = $agent_account['commission_total_money_first'];
          //  Log::write("commission_total_money_first:".$commission_total_money_first);
            $commission_total_money_second = $agent_account['commission_total_money_second'];
          //  Log::write("commission_total_money_second:".$commission_total_money_second);
            $commission_adjust_total_money = $agent_account['commission_adjust_total_money'];
         //   Log::write("commission_adjust_total_money:".$commission_adjust_total_money);
            $total_withdraw_amount= $agent_account['total_withdraw_amount'];
         //   Log::write("total_withdraw_amount:".$total_withdraw_amount);

            $balance = $commission_total_money_first + $commission_total_money_second + $commission_adjust_total_money
                       - $total_withdraw_amount;
          //  Log::write("balance:".$balance);
        }
        return $balance;
    }

}