<?php
/**
 * 财务控制器
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-11-18
 * Time: 20:16
 */

namespace app\platform\controller;


use dao\handle\AgentHandle;
use dao\handle\agentaccount\AgentAccountHandle;
use dao\handle\ConfigHandle;
use dao\handle\PlatformHandle;
use dao\handle\PlatformUserHandle;

class Finance extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * ok-2ok
     * 代理商提现列表
     */
    public function agentWithdrawList()
    {
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $user_phone = request()->post('user_phone','');
        $search_text = request()->post('search_text','');
        $status = request()->post('status','all');

        $agent = new AgentHandle();
        $condition = array();

        if ($status !=  'all') {
            $condition['status'] = $status;
        }

        if (!empty($user_phone)) {
            $condition["mobile"] = array(
                "like",
                "" . $user_phone . "%"
            );
        }

        if (!empty($search_text)) {
            $condition1["agent_name|identify_code"] = array(
                    "like",
                    "" . $search_text . "%"
                );

            $ids = $agent->getAgentIdList( $condition1);
            if (!empty($ids)) {
                $condition['agent_id'] = array(
                    'in', $ids
                );
            }
        }

        $list = $agent->getAgentBalanceWithdraw($page_index, $page_size, $condition,  'ask_for_date desc');
        return json(resultArray(0,"操作成功", $list));
    }

    /**
     * ok-2ok
     * 得到代理商的提现数据
     * @return \think\response\Json
     */
    public function getAgentWithdrawData()
    {
        $agent_id = request()->post('agent_id','');
        if ($agent_id === '') {
            return json(resultArray(2, "未指定代理商id"));
        }

        $agent_account = new AgentAccountHandle();
        $proceeds_money_remain = $agent_account->getAgentBalance($agent_id);
        if (empty($proceeds_money_remain)) {
            $proceeds_money_remain = 0;
        }
        $config = new ConfigHandle();
        $balanceConfig = $config->getAgentBalanceWithdrawConfig();
        $cash_min = $balanceConfig['value']["agent_withdraw_cash_min"];
        $multiple = $balanceConfig['value']["agent_withdraw_multiple"];
        $poundage = $balanceConfig['value']["agent_withdraw_poundage"];
        $withdraw_message = $balanceConfig['value']["agent_withdraw_message"];

        $data = array (
            'proceeds_money_remain'=>$proceeds_money_remain,
            'withdraw_min' =>$cash_min,
            'withdraw_multiple'=>$multiple,
            'withdraw_poundage_rate'=>$poundage,
            'withdraw_message'=>$withdraw_message,
        );
        return json(resultArray(0,"操作成功", $data));
    }

    /**
     * ok-2ok
     * 代理商提现审核
     */
    public function agentWithdrawAudit()
    {
        $withdraw_id = request()->post('withdraw_id',0);
        if (empty($withdraw_id )) {
            return json(resultArray(2, "未指定提现id"));
        }
        $status = request()->post('status');
        $audit_name =  request()->post('audit_name');
        $memo = request()->post('memo');
        $agent = new AgentHandle();
        $retval = $agent->agentBalanceWithdrawAudit($withdraw_id, $status, $audit_name, $memo);

        if (empty($retval)) {
            if (empty($agent->getError())) {
                return json(resultArray(2, "操作失败"));
            } else {
                return json(resultArray(2,$agent->getError() ));
            }
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * 拒绝提现请求
     *
     * @return Ambigous <multitype:unknown, multitype:unknown unknown string >
     */
    public function userCommissionWithdrawRefuse()
    {
        $id = request()->post('id','');
        $status = request()->post('status','');
        $remark = request()->post('remark','');
        $member = new MemberService();
        $retval = $member->userCommissionWithdrawRefuse($this->instance_id, $id, $status, $remark);
        return AjaxReturn($retval);
    }



    /**
     * ok-2ok
     * 获取代理商提现详情
     */
    public function getAgentWithdrawInfo()
    {
        $withdraw_id = request()->post('withdraw_id',0);
        if (empty($withdraw_id )) {
            return json(resultArray(2, "未指定提现id"));
        }
        $agent = new AgentHandle();
        $data = $agent->getAgentWithdrawDetails($withdraw_id);
        return json(resultArray(0, "操作成功", $data));
    }

    /**
     * ok-2ok
     * 代理商提现计入
     * @return \think\response\Json
     */
    public function agentWithdrawWrite() {
        $withdraw_id = request()->post("withdraw_id");
        $operator = request()->post("operator");
        $withdraw_time = request()->post("payment_date");

        $agent = new AgentHandle();
        $res = $agent->agentBalanceWithdrawWrite($withdraw_id, $operator, $withdraw_time);

        if (empty($res)) {
            return json(resultArray(2, "操作失败 ".$agent->getError()));
        } else {
            return json(resultArray(0, "操作成功"));
        }

    }

    /**
     * ok-2ok
     * 平台帐户数据
     * @return \think\response\Json
     */
    public function platformAccountData() {
        $platform = new PlatformHandle();
        $account = $platform->getAccountData();
        return json(resultArray(0, "操作成功", $account));
    }

    /**
     * ok-2ok
     * 得到代理商帐户列表
     * @return \think\response\Json
     */
    public function agentAccountList() {
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $search_text = request()->post('search_text', '');

        $agent = new AgentHandle();
        if (!empty($search_text)) {
            $condition1 = array(
                'agent_name|identify_code' => array(
                    'like',
                    '%' . $search_text . '%'
                )
            );
            $agent_ids = $agent->getAgentIdList( $condition1);
            $condition = array(
                'agent_id'=> ['in', $agent_ids ]
            );
        } else {
            $condition='';
        }

        $list = $agent->getAgentAccountList($page_index,$page_size,$condition);
        return json(resultArray(0, "操作成功", $list));
    }

    /**
     * ok-2ok
     * 得到代理商佣金记录列表
     * @return \think\response\Json
     */
    public function agentCommissionRecordsList() {
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $account_type=isset($this->param['account_type']) ? $this->param['account_type'] : 0;
        //  $operation_id =  request()->post("operation_id", 0);
        $search_text = request()->post('search_text', '');


        $start_date = request()->post('start_date') == "" ? 0 : request()->post('start_date');
        $end_date = request()->post('end_date') == "" ? 0 : request()->post('end_date');
        if($start_date != 0 && $end_date != 0){
            $condition["create_time"] = [
                [
                    ">",
                    getTimeTurnTimeStamp($start_date)
                ],
                [
                    "<",
                    getTimeTurnTimeStamp($end_date)
                ]
            ];
        }elseif($start_date != 0 && $end_date == 0){
            $condition["create_time"] = [
                [
                    ">",
                    getTimeTurnTimeStamp($start_date)
                ]
            ];
        }elseif($start_date == 0 && $end_date != 0){
            $condition["create_time"] = [
                [
                    "<",
                    getTimeTurnTimeStamp($end_date)
                ]
            ];
        }

        $agent = new AgentHandle();
        if (!empty($search_text)) {
            $condition1 = array(
                'agent_name|identify_code' => array(
                    'like',
                    '%' . $search_text . '%'
                )
            );
            $agent_ids = $agent->getAgentIdList( $condition1);
            $condition['agent_id'] = ['in', $agent_ids ];
        }

        if (!empty($account_type)) {
            $condition['account_type']= $account_type;
        }

        $list = $agent->getAgentCommissionRecordsList($page_index,$page_size,$condition);
        return json(resultArray(0, "操作成功", $list));
    }

    /**
     * ok-2ok
     * 得到代理商佣金获得记录列表
     * @return \think\response\Json
     */
    public function agentAddCommissionRecordsList() {
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post('page_size', PAGESIZE);
        // $account_type=isset($this->param['account_type']) ? $this->param['account_type'] : 0;
        //  $operation_id =  request()->post("operation_id", 0);
        $search_text = request()->post('search_text', '');


        $start_date = request()->post('start_date') == "" ? 0 : request()->post('start_date');
        $end_date = request()->post('end_date') == "" ? 0 : request()->post('end_date');
        if($start_date != 0 && $end_date != 0){
            $condition["create_time"] = [
                [
                    ">",
                    getTimeTurnTimeStamp($start_date)
                ],
                [
                    "<",
                    getTimeTurnTimeStamp($end_date)
                ]
            ];
        }elseif($start_date != 0 && $end_date == 0){
            $condition["create_time"] = [
                [
                    ">",
                    getTimeTurnTimeStamp($start_date)
                ]
            ];
        }elseif($start_date == 0 && $end_date != 0){
            $condition["create_time"] = [
                [
                    "<",
                    getTimeTurnTimeStamp($end_date)
                ]
            ];
        }

        $agent = new AgentHandle();
        if (!empty($search_text)) {
            $condition1 = array(
                'agent_name|identify_code' => array(
                    'like',
                    '%' . $search_text . '%'
                )
            );
            $agent_ids = $agent->getAgentIdList( $condition1);
            $condition['agent_id'] = ['in', $agent_ids ];
        }

        //   if (!empty($account_type)) {

        $condition['account_type']= ['<>', 3];
        // }

        $list = $agent->getAgentCommissionRecordsList($page_index,$page_size,$condition);
        return json(resultArray(0, "操作成功", $list));
    }

    /**
     * ok-2ok
     * 得到代理商佣金调整记录列表
     * @return \think\response\Json
     */
    public function agentAdjustCommissionRecordsList() {
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post('page_size', PAGESIZE);
        // $account_type=isset($this->param['account_type']) ? $this->param['account_type'] : 0;
        //  $operation_id =  request()->post("operation_id", 0);
        $search_text = request()->post('search_text', '');


        $start_date = request()->post('start_date') == "" ? 0 : request()->post('start_date');
        $end_date = request()->post('end_date') == "" ? 0 : request()->post('end_date');
        if($start_date != 0 && $end_date != 0){
            $condition["create_time"] = [
                [
                    ">",
                    getTimeTurnTimeStamp($start_date)
                ],
                [
                    "<",
                    getTimeTurnTimeStamp($end_date)
                ]
            ];
        }elseif($start_date != 0 && $end_date == 0){
            $condition["create_time"] = [
                [
                    ">",
                    getTimeTurnTimeStamp($start_date)
                ]
            ];
        }elseif($start_date == 0 && $end_date != 0){
            $condition["create_time"] = [
                [
                    "<",
                    getTimeTurnTimeStamp($end_date)
                ]
            ];
        }

        $agent = new AgentHandle();
        if (!empty($search_text)) {
            $condition1 = array(
                'agent_name|identify_code' => array(
                    'like',
                    '%' . $search_text . '%'
                )
            );
            $agent_ids = $agent->getAgentIdList( $condition1);
            $condition['agent_id'] = ['in', $agent_ids ];
        }

        //   if (!empty($account_type)) {

        $condition['account_type']= 3;
        // }

        $list = $agent->getAgentCommissionRecordsList($page_index,$page_size,$condition);
        return json(resultArray(0, "操作成功", $list));
    }


    /**
     * ok-2ok
     * 得到代理商提现记录列表
     * @return \think\response\Json
     */
    public function agentWithdrawRecordsList() {
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $search_text = request()->post('search_text', '');
        $start_date = request()->post('start_date') == "" ? 0 : request()->post('start_date');
        $end_date = request()->post('end_date') == "" ? 0 : request()->post('end_date');
        if($start_date != 0 && $end_date != 0){
            $condition["create_time"] = [
                [
                    ">",
                    getTimeTurnTimeStamp($start_date)
                ],
                [
                    "<",
                    getTimeTurnTimeStamp($end_date)
                ]
            ];
        }elseif($start_date != 0 && $end_date == 0){
            $condition["create_time"] = [
                [
                    ">",
                    getTimeTurnTimeStamp($start_date)
                ]
            ];
        }elseif($start_date == 0 && $end_date != 0){
            $condition["create_time"] = [
                [
                    "<",
                    getTimeTurnTimeStamp($end_date)
                ]
            ];
        }

        $agent = new AgentHandle();
        if (!empty($search_text)) {
            $condition1 = [
                'agent_name|identify_code' => array(
                    'like',
                    '%' . $search_text . '%'
                )
            ];
            $agent_ids = $agent->getAgentIdList( $condition1);
            $condition = [
                'agent_id'=> ['in', $agent_ids ]
            ];
        }

        if (empty($condition)) {
            $condition = '';
        }

        $list = $agent->getAgentWithdrawRecordsList($page_index,$page_size,$condition);
        return json(resultArray(0, "操作成功", $list));
    }






}