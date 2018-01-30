<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-10-05
 * Time: 20:19
 */

namespace app\platform\controller;

use dao\handle\AddressHandle;
use dao\handle\AgentHandle;
use dao\handle\ConfigHandle;
use dao\handle\MemberHandle;
use dao\handle\OrderHandle;
use think\Log;


class Agent extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function addAgent()
    {

        $sort = request()->post("sort", 0);
        $agent_name = request()->post("agent_name");
        $identify_code = request()->post("identify_code");
        $p_agent_id = request()->post("p_agent_id", 0);
        $agent_level = request()->post("agent_level", 1);
        $agent_type = request()->post("agent_type", 1);
        $province_id = request()->post("province_id", 0);
        $city_id = request()->post("city_id", 0);
        $area_id = request()->post("area_id", 0);
        $district_id = request()->post("district_id", 0);
        $agent_status = request()->post("agent_status", 0);
        $create_by = request()->post("create_by", '');
        $agent_user_name =  request()->post("user_name");
        $agent_user_pwd =  request()->post("user_pwd");
        $agent_user_real_name =  request()->post("user_real_name", '');
        $agent_user_level = request()->post("user_level",1);
        $agent_user_type =   request()->post("user_type",1);
        $agent_user_status =   request()->post("user_status",1);

        $agentHandle = new AgentHandle();
        $result =  $agentHandle->addAgent($sort, $agent_name, $identify_code, $p_agent_id, $agent_level,  $agent_type,$area_id,
            $province_id, $city_id, $district_id, $agent_status, $create_by,
            $agent_user_name, $agent_user_pwd,  $agent_user_real_name, $agent_user_level, $agent_user_type,
            $agent_user_status);


        /*
        addAgent($sort, $agent_name, $identify_code, $pid, $agent_level,  $agent_type,$area_id,
            $province_id, $city_id, $district_id, $agent_status, $create_by,
            $agent_user_name, $agent_user_pwd,  $agent_user_real_name, $agent_user_level, $agent_user_type,
            $agent_user_status);
        */





        if ($result) {
            return json(resultArray(0,"操作成功"));
        } else {
            return json(resultArray(2,$agentHandle->getError()));
        }
    }

    public function updateAgentInfo()
    {

        $agent_id = request()->post("agent_id");
        $sort = request()->post("sort");
        $agent_name = request()->post("agent_name");
        $identify_code = request()->post("identify_code");
        $p_agent_id = request()->post("pid");
        $agent_level = request()->post("agent_level");
        $agent_type = request()->post("agent_type");
        $province_id = request()->post("province_id");
        $city_id = request()->post("city_id");
        $area_id = request()->post("area_id");
        $district_id = request()->post("district_id");
        $agent_status = request()->post("agent_status");
        $company_name = request()->post("company_name");
        $company_address = request()->post("company_address");
        $company_lng  = request()->post("company_lng");

        $company_lat  = request()->post("company_lat");
        $business_licence_pic = request()->post("business_licence_pic");
        $legal_person_name = request()->post("legal_person_name");
        $legal_person_mobile = request()->post("legal_person_mobile");
        $legal_person_id_no = request()->post("legal_person_id_no");
        $legal_person_id_front = request()->post("legal_person_id_front");
        $legal_person_id_back = request()->post("legal_person_id_back");



        $agentHandle = new AgentHandle();
        $result = $agentHandle-> updateAgentInfo($agent_id, $p_agent_id, $sort, $agent_name, $identify_code, $agent_level, $agent_type,
            $company_name,$area_id, $province_id, $city_id,$district_id, $company_address, $company_lng,$company_lat,
            $business_licence_pic, $legal_person_name, $legal_person_mobile,$legal_person_id_no,
            $legal_person_id_front, $legal_person_id_back, $agent_status);

        /*updateAgentInfo($agent_id, $p_agent_id, $sort, $agent_name, $identify_code, $agent_level, $agent_type,
            $company_name, $province_id, $city_id,$district_id, $agent_address, $business_licence_pic,
            $legal_person_name, $legal_person_mobile, $legal_person_id_front, $legal_person_id_back, $agent_status);
        */
      /*
        $result =  $agentHandle->addAgent($sort, $agent_name, $identify_code, $pid, $agent_level,  $agent_type,$area_id,
            $province_id, $city_id, $district_id, $agent_status, $create_by,
            $agent_user_name, $agent_user_pwd,  $agent_user_real_name, $agent_user_level, $agent_user_type,
            $agent_user_status);
      */




        if ($result) {
            return json(resultArray(0,"操作成功"));
        } else {
            return json(resultArray(2,$agentHandle->getError()));
        }
    }

    public function agentList() {

        $page_index = request()->post("page_index", 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $search_text = request()->post('search_text', '');
        $status = request()->post('status', '-1');

        $child_menu_list = array(
            array(
                'url' => "agent/agentList",
                'menu_name' => "全部",
                "active" => $status == -1 ? 1 : 0
            ),
            array(
                'url' => "agent/agentList?status=0",
                'menu_name' => "未完善",
                "active" => $status == 0 ? 1 : 0
            ),
            array(
                'url' => "agent/agentList?status=1",
                'menu_name' => "待审核",
                "active" => $status == 1 ? 1 : 0
            ),
            array(
                'url' => "agent/agentList?status=3",
                'menu_name' => "已通过",
                "active" => $status == 2 ? 1 : 0
            ),
            array(
                'url' => "agent/agentList?status=4",
                'menu_name' => "未通过",
                "active" => $status == 3 ? 1 : 0
            ),
             array(
                 'url' => "agent/agentList?status=4",
                 'menu_name' => "暂停",
                 "active" => $status == 4 ? 1 : 0
             ),
            array(
                'url' => "agent/agentList?status=5",
                'menu_name' => "无效",
                "active" => $status == 5 ? 1 : 0
            )
        );

        $condition = array(
            'ag.agent_name|ag.identify_code|au.user_name' => array(
                'like',
                '%' . $search_text . '%'
            )
        );

        if ($status != '-1' && $status != -1 ) {
            $condition['ag.status'] = $status;
        }

        $agentHandle = new AgentHandle();
      //  getAgentList($page_index = 1, $page_size = 0, $condition = '', $order = '', $field = '*')
      //  $field = 'ag.*, au.user_name, sp.province_name, sc.city_name,
       //     sd.district_name, aa.order_total_count_first, order_total_count_second, ';
        $list = $agentHandle->getAgentListWithAccount($page_index, $page_size, $condition);
        $data = array(
            'child_menu_list' => $child_menu_list,
            'agent_list' =>$list
        );

    //    getAgentList($page_index, $page_size, $condition, $order = 'ag.sort desc, ag.id desc');

      //  getAgentList($page_index , $page_size, $condition, 'id desc', '*');


        return json(resultArray(0, "操作成功", $data));

    }

    public function getAgentByType() {
      //  isset($this->param['agent_type']) ? $this->param['agent_type'] : 0;
        $agent_type = request()->post("agent_type", 0);
        $agentHandle = new AgentHandle();
       $list = $agentHandle->getValidAgentByType($agent_type);
        return json(resultArray(0, "操作成功", $list));
    }


    /*
     * 得代理商信息用于更新状态
     */
   public function getAgentForStatus()
   {
       $agent_id = request()->post("agent_id");
       $agent = new AgentHandle();
       $field = "id, p_agent_id, agent_name, identify_code,agent_type, status, province_id,city_id,district_id";
       $agent_info = $agent->getAgentInfoById($agent_id, $field);
       return json(resultArray(0, "操作成功", $agent_info));
   }


    /*
     * 更新代理商状态
     */
    public function setAgentStatus() {
        $agent_id = request()->post("agent_id");
        $agent_status = request()->post("agent_status");
        $agent = new AgentHandle();
       // setAgentStatus($agent_id, $agent_status)
        $res = $agent->setAgentStatus($agent_id, $agent_status);
        if (empty($res)) {
            return json(resultArray(2, "操作失败 ".$agent->getError()));
        } else {
            return json(resultArray(0, "操作成功"));
        }


    }

    /*
    * 得代理商详情
    */
    public function agentDetails()
    {
        $agent_id = request()->post("agent_id");
        $agent = new AgentHandle();
        $field = "*";
        $agent_info = $agent->getAgentInfoById($agent_id, $field);
        return json(resultArray(0, "操作成功", $agent_info));
    }

    /**
     * 得到代理商登录用户的信息
     */
    public function getAgentUserInfo() {
        $agent_id = request()->post("agent_id");
        $agent = new AgentHandle();
        $data = $agent->getAgentUserInfo($agent_id);
        return json(resultArray(0, "操作成功", $data));
    }

    /*
     * 设置代理商用户密码
     */
    public function setAgentUserPassword() {
        $user_id = request()->post("user_id");
        $password = request()->post("password");
        $agent = new AgentHandle();
        $res = $agent->setAgentUserPassword($user_id, $password);
        if (empty($res)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * 得到代理商的会员用户列表
     */
    public function getMemberListByAgent() {
        $agent_id = request()->post("agent_id");
        $page_index = request()->post("page_index",1);
        $page_size = request()->post("page_size",PAGESIZE);
        $condition = ['mu.agent_id' => $agent_id];
        $order=' mu.id desc ';
        $field = 'mu.id, mu.user_name, mu.login_phone, mu.email,  mu.agent_id,ag.agent_name, mu.user_type,  mu.status, mu.last_login_time,  mu.login_count, mu.create_time,'.
            ' mi.nickname, mi.head_img, mi.mobile, mi.real_name, mi.sex, sp.province_name, sc.city_name, sd.district_name,'.
            '  mu.member_level, ml.level_name, ml.goods_discount';

        $member = new MemberHandle();
        $member_list = $member->getMemberList($page_index, $page_size, $condition, $order, $field);
        return json(resultArray(0, "操作成功", $member_list));
    }



    /**
     * 得到代理商的订单列表
     */
    public function getOrderListByAgent() {
        $agent_id = request()->post("agent_id");
        $page_index = request()->post("page_index",1);
        $page_size = request()->post("page_size",PAGESIZE);
        $condition = ['agent_id' => $agent_id];
        $order = new OrderHandle();
        $order_list = $order->getOrderList($page_index, $page_size, $condition, 'id desc ' );
        return json(resultArray(0, "操作成功", $order_list));
    }

    /**
     * 得到代理商的下级列表
     */
    public function getSonAgentList() {
        $agent_id = request()->post("agent_id");
        $page_index = request()->post("page_index",1);
        $page_size = request()->post("page_size",PAGESIZE);
        $condition = ['p_agent_id' => $agent_id];
        $agent = new AgentHandle();
        $field = 'ag.*, au.user_name, sp.province_name, sc.city_name, sd.district_name';
       // getAgentList($page_index = 1, $page_size = 0, $condition = '', $order = '', $field='')
        $agent_list = $agent->getAgentListWithAccount($page_index, $page_size, $condition);
     //   $agent_list = $agent->getAgentList($page_index, $page_size, $condition, 'ag.sort desc, ag.id desc', $field);
        return json(resultArray(0, "操作成功", $agent_list));
    }

    /*
     * 设置代理商佣金率
     */
    public function setAgentCommissionRate()
    {
        $agent_id = request()->post("agent_id");
        $rate_first = request()->post("rate_first");
        $rate_second = request()->post("rate_second",0);
        $agent = new AgentHandle();
        $res = $agent->setAgentCommissionRate($agent_id, $rate_first, $rate_second);
        if (empty($res)) {
            return json(resultArray(2, "操作失败 ".$agent->getError()));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /*
    * 调整代理商佣金
    */
    public function adjustAgentCommission()
    {
        $agent_id = request()->post("agent_id");
        $adjust_money = request()->post("adjust_money");
        $agent = new AgentHandle();
        $res = $agent->adjustAgentCommission($agent_id, $adjust_money);
        if (empty($res)) {
            return json(resultArray(2, "操作失败 ".$agent->getError()));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

//agentWithdraw($agent_id, $withdraw_money,$operator,$withdraw_time)

    public function agentWithdraw() {
        $agent_id = request()->post("agent_id");
        $withdraw_money = request()->post("withdraw_money");
        $operator = request()->post("operator");
        $withdraw_time = request()->post("withdraw_time");
        $agent = new AgentHandle();
        $res = $agent->agentWithdraw($agent_id, $withdraw_money,$operator,$withdraw_time);
        if (empty($res)) {
            return json(resultArray(2, "操作失败 ".$agent->getError()));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * 得到代理商佣金明细
     */
    public function getCommssionDetailsByAgent()  {
        $agent_id = request()->post("agent_id");
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", PAGESIZE);
        $agent = new AgentHandle();
        $data = $agent->getCommssionRecordsByAgent($page_index,$page_size,  $agent_id);
        return json(resultArray(0, "操作成功", $data));
    }

    /**
     * 得到代理商收益提取明细
     */
    public function getWithdrawDetailsByAgent()  {
        $agent_id = request()->post("agent_id");
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", PAGESIZE);
        $agent = new AgentHandle();
        $data = $agent->getWithdrawRecordsByAgent($page_index,$page_size, $agent_id);
        return json(resultArray(0, "操作成功", $data));
    }

    public function getValidAgentTreeForPickupPoint() {
        $agentHandle = new AgentHandle();
        $status = 2;
        $list = $agentHandle->getAgentTree($status, 'ag.id, ag.p_agent_id, ag.agent_name, ag.identify_code,ag.agent_type,ag.status');

        return json(resultArray(0, "操作成功", $list));

    }

    public function getAgentTree() {
        $agentHandle = new AgentHandle();
        $data = $agentHandle->getAgentTree();
        return json(resultArray(0, "操作成功", $data));
    }

    /**
     * 得到默认佣金率
     * @return \think\response\Json
     */
    public function getDefaultCommissionRate() {
        $agentHandle = new AgentHandle();
        $data = $agentHandle->getDefaultCommissionRate();
        return json(resultArray(0, "操作成功", $data));
    }


    /**
     * 设置默认佣金率
     * @return \think\response\Json
     */
    public function setDefaultCommissionRate() {
        $first_rate = request()->post("first_rate");
        $second_rate = request()->post("second_rate");
        $son_first_rate = request()->post("son_first_rate");
        $son_second_rate = request()->post("son_second_rate", 0);
        $agentHandle = new AgentHandle();
        $res = $agentHandle->setDefaultCommissionRate($first_rate, $second_rate, $son_first_rate, $son_second_rate);
        if (empty($res)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * 代理商总数据
     * @return \think\response\Json
     */
    public function  agentTotalData() {
        $agentHandle = new AgentHandle();
        $data = $agentHandle->agentTotalData();
        return json(resultArray(0, "操作成功", $data));
    }


    /**
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



    /**
     * 自提点列表
     */
    public function pickupPointList()
    {
        $child_menu_list = array(
            array(
                'url' => "express/expresscompany",
                'menu_name' => "物流公司",
                "active" => 0
            ),
            array(
                'url' => "config/areamanagement",
                'menu_name' => "地区管理",
                "active" => 0
            ),
            array(
                'url' => "order/returnsetting",
                'menu_name' => "商家地址",
                "active" => 0
            ),
            array(
                'url' => "shop/pickuppointlist",
                'menu_name' => "自提点管理",
                "active" => 1
            ),
            array(
                'url' => "shop/pickuppointfreight",
                'menu_name' => "自提点运费",
                "active" => 0
            ),
            array(
                'url' => "config/distributionareamanagement",
                'menu_name' => "货到付款地区管理",
                "active" => 0
            )
        );


        $agent = new AgentHandle();
        $page_index = request()->post('page_index', 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $search_text = request()->post('search_text', '');
        $condition = array(
                'name' => array(
                    'like',
                    '%' . $search_text . '%'
                )
            );
         //   getPickupPointList($page_index = 1, $page_size = 0, $where = '', $order = '')
        $list = $agent->getPickupPointList($page_index, $page_size, $condition, 'create_time asc');
        $data = array(
            'child_menu_list'=>$child_menu_list,
            'pickup_point_list'=> $list
        );

        return json(resultArray(0, "操作成功", $data));
    }

    /**
     * 自提点运费菜单
     */
    public function pickupPointFreight(){
        $child_menu_list = array(
            array(
                'url' => "express/expresscompany",
                'menu_name' => "物流公司",
                "active" => 0
            ),
            array(
                'url' => "config/areamanagement",
                'menu_name' => "地区管理",
                "active" => 0
            ),
            array(
                'url' => "order/returnsetting",
                'menu_name' => "商家地址",
                "active" => 0
            ),
            array(
                'url' => "shop/pickuppointlist",
                'menu_name' => "自提点管理",
                "active" => 0
            ),
            array(
                'url' => "shop/pickuppointfreight",
                'menu_name' => "自提点运费",
                "active" => 1
            ),
            array(
                'url' => "config/distributionareamanagement",
                'menu_name' => "货到付款地区管理",
                "active" => 0
            )
        );

      //  $this->assign('child_menu_list', $child_menu_list);

        $config = new ConfigHandle();
        $config_info = $config->getConfig(0, 'PICKUPPOINT_FREIGHT');
        if (!empty($config_info)) {
            $pickup_point_freight = json_decode($config_info['value']);
        } else {
            $pickup_point_freight = [];
        }
        $data = array (
            'child_menu_list'=> $child_menu_list,
            'pickup_point_freight'=> $pickup_point_freight
        );
        return json(resultArray(0, "操作成功", $data));

    }

    /**
     * 修改自提点运费菜单
     */
    public function setPickupPointFreight(){
        $is_enable = request()->post('is_enable',0);
        $pickup_freight = request()->post('pickup_freight',0);
        $manjian_freight = request()->post('manjian_freight',0);
        $config_handle = new ConfigHandle();
       // setPickupPointFreight($is_enable, $pickup_freight, $manjian_freight)
        $res = $config_handle->setPickupPointFreight($is_enable, $pickup_freight, $manjian_freight);
        if (empty($res)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * 添加自提点
     */
    public function addPickupPoint()
    {
       // if (request()->isAjax()) {
        $agent = new AgentHandle();
        $agent_id =  request()->post('agent_id');
        $name = request()->post('name');
        $status = request()->post('status', 1);
        $address = request()->post('address');
        $contact = request()->post('contact');
        $phone = request()->post('phone');
        $province_id = request()->post('province_id',0);
        $city_id = request()->post('city_id',0);
        $district_id = request()->post('district_id',0);
        $longitude = request()->post('longitude', '');
        $latitude = request()->post('latitude', '');
      //  addPickupPoint($agent_id, $name, $address, $contact, $phone, $province_id, $city_id, $district_id, $longitude, $latitude)
        $res = $agent->addPickupPoint($agent_id, $name,$status, $address, $contact, $phone, $province_id, $city_id, $district_id, $longitude, $latitude);

        if (empty($res)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * 修改自提点
     */
    public function updatePickupPoint()
    {

        $agent = new AgentHandle();
        $pickup_point_id = request()->post('pickup_point_id');
        $agent_id = request()->post('agent_id');
        $name = request()->post('name');
        $status = request()->post('status', 1);
        $address = request()->post('address');

        $contact = request()->post('contact');
        $phone = request()->post('phone');
        $province_id = request()->post('province_id');
        $city_id = request()->post('city_id');
        $district_id = request()->post('district_id');
        $longitude = request()->post('longitude');
        $latitude = request()->post('latitude');

        $res = $agent->updatePickupPoint($pickup_point_id, $agent_id, $name, $status, $address, $contact, $phone, $province_id, $city_id, $district_id, $longitude, $latitude);

        if (empty($res)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * 取得自提点数据用于修改
     */
    public function getPickupPoint() {
        $pickup_point_id = isset($this->param['pickup_point_id']) ? $this->param['pickup_point_id'] : 0;
        if (empty($pickup_point_id)) {
            return json(resultArray(2, "未获取到相关数据"));
        }
        $agent = new AgentHandle();
        $pickupPoint_detail = $agent->getPickupPointDetail($pickup_point_id);
        $data = array(
            'pickup_point_id'=> $pickup_point_id,
            'pickup_point_detail'=> $pickupPoint_detail
        );
        return json(resultArray(0, "操作成功",$data));

    }

    /**
     * 自提点详情
     */
    public function pickupPointDetail() {
        $pickup_point_id = isset($this->param['pickup_point_id']) ? $this->param['pickup_point_id'] : 0;
        if (empty($pickup_point_id)) {
            return json(resultArray(2, "未获取到相关数据"));
        }
        $agent = new AgentHandle();
        $data= $agent->getPickupPointDetail($pickup_point_id);

        return json(resultArray(0, "操作成功",$data));

    }

    /**
     * 删除自提点
     */
    public function deletePickupPoint()
    {

        $pickup_point_id = request()->post('pickup_point_id');

        $agent = new AgentHandle();
        $res = $agent->deletePickupPoint($pickup_point_id);
        if (empty($res)) {
            return json(resultArray(2, "删除失败"));
        } else {
            return json(resultArray(0, "删除成功"));
        }
    }

    /**
     * 获取省列表
     */


    /**
     * 获取城市列表
     *
    */


    /**
     * 获取区域地址
     */

    /**
     * 获取选择地址
     */
    public function getSelectAddress()
    {
        $province_id = request()->post('province_id',0);
        $city_id = request()->post('city_id',0);
        $address = new AddressHandle();
        $province_list = $address->getProvinceList();
        $city_list = $address->getCityList($province_id);
        $district_list = $address->getDistrictList($city_id);
        $data["province_list"] = $province_list;
        $data["city_list"] = $city_list;
        $data["district_list"] = $district_list;
        return $data;
    }

    /******************** 代理提现相关**************************************/





    /**
     * ok-2ok
     * 获取银行账户信息
     */
    public function getAgentBankAccountInfo()
    {
        $agent = new AgentHandle();
        $account_id = request()->post('account_id');
        $agent_id = request()->post('agent_id');

        if (empty($account_id)) {
            return json(resultArray(2, "未指定帐户"));
        }
        if (empty($agent_id)) {
            return json(resultArray(2, "未指定代理商"));
        }

        $data = $agent->getAgentBankAccountDetail($agent_id, $account_id);
        return json(resultArray(0, "操作成功",$data ));
    }

    /**
     * ok-2ok
     * 代理商银行账户列表
     */
    public function agentBankAccountList()
    {
        $page_index = request()->post('page_index', 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $search_text = request()->post('search_text', '');
        $condition1 = array(
            'agent_name|identify_code' => array(
                'like',
                '%' . $search_text . '%'
            )
        );

        $agent = new AgentHandle();
        $agent_ids = $agent->getAgentIdList($condition1);
        $condition = array(
            'agent_id'=>['in',$agent_ids ]
        );
        $order='agent_id asc, is_default desc, id desc';
        $field='*';
        $account_list = $agent->getAgentBankAccountList($page_index,$page_size, $condition, $order,  $field);

        return json(resultArray(0, "操作成功", $account_list));
    }

    /**
     * ok-2ok
     * 设置代理商银行账户备注
     */
    public function setAgentBankAccountMemo()
    {
        $account_id = request()->post('account_id');
        $agent_id = request()->post('agent_id');
        $memo = request()->post('memo');
        if (empty($account_id)) {
            return json(resultArray(2, "未指定帐户"));
        }
        if (empty($agent_id)) {
            return json(resultArray(2, "未指定代理商"));
        }
        $agent = new AgentHandle();
        $retval = $agent->setAgentBankAccountMemo($agent_id, $account_id, $memo);
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
     * ok-2ok
     * 得到代理商银行账户备注
     */
    public function getAgentBankAccountMemo()
    {
        $account_id = request()->post('account_id');
        $agent_id = request()->post('agent_id');
        if (empty($account_id)) {
            return json(resultArray(2, "未指定帐户"));
        }
        if (empty($agent_id)) {
            return json(resultArray(2, "未指定代理商"));
        }
        $agent = new AgentHandle();
        $data = $agent->getAgentBankAccountMemo($agent_id, $account_id);

        return json(resultArray(0, "操作成功", $data));
    }

    /******************************* 代理商体验店相关 **********************************/
    /**
     * ok-2ok
     * 增加代理商小店
     * @return \think\response\Json
     */
    public function addAgentShop() {
        $agent = new AgentHandle();
        $platform = request()->post('platform', 0);
        if (empty($platform)) {
            $agent_id = request()->post('agent_id');
            if (empty($agent_id)) {
                return json(resultArray(2, "未指定代理商 "));
            }
        } else {
            $agent_id = $agent->getPlatformAgentId();
        }
        $title = request()->post('title');
        $keyword = request()->post('keyword');
        $shop_name = request()->post('shop_name');
        $shop_banner = request()->post('shop_banner');
        $shop_address = request()->post('shop_address');
        $shop_lng = request()->post('shop_lng');
        $shop_lat = request()->post('shop_lat');
        $shop_phone = request()->post('shop_phone');
        $contacts = request()->post('contacts');
        $business_begin = request()->post('business_begin');
        $business_end = request()->post('business_end');
        $shop_introduce = request()->post('shop_introduce');
        $shop_content = request()->post('shop_content');
        $status = 1;

        $res = $agent->addAgentShop($agent_id,$title, $keyword,$shop_name, $shop_banner, $shop_address,
            $shop_lng, $shop_lat, $shop_phone, $contacts, $business_begin,$business_end,
            $shop_introduce, $shop_content, $status);

        if (empty($res)) {
            return json(resultArray(2, "操作失败 ".$agent->getError()));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 修改代理商小店
     * @return \think\response\Json
     */
    public function updateAgentShop() {
        $agent = new AgentHandle();
        $platform = request()->post('platform', 0);
        if (empty($platform)) {
            $agent_id = request()->post('agent_id');
            if (empty($agent_id)) {
                return json(resultArray(2, "未指定代理商 "));
            }
        } else {
            $agent_id = $agent->getPlatformAgentId();
        }

        $agent_shop_id = request()->post('agent_shop_id');
        $title = request()->post('title');
        $keyword = request()->post('keyword');
        $shop_name = request()->post('shop_name');
        $shop_banner = request()->post('shop_banner');
        $shop_address = request()->post('shop_address');
        $shop_lng = request()->post('shop_lng');
        $shop_lat = request()->post('shop_lat');
        $shop_phone = request()->post('shop_phone');
        $contacts = request()->post('contacts');
        $business_begin = request()->post('business_begin');
        $business_end = request()->post('business_end');
        $shop_introduce = request()->post('shop_introduce');
        $shop_content = request()->post('shop_content');
        $status =  request()->post('status');

        $agent = new AgentHandle();
        $res = $agent->updateAgentShop($agent_shop_id, $agent_id,$title, $keyword,$shop_name, $shop_banner, $shop_address,
            $shop_lng, $shop_lat, $shop_phone, $contacts, $business_begin,$business_end,
            $shop_introduce, $shop_content, $status);

        if (empty($res)) {
            return json(resultArray(2, "操作失败 ".$agent->getError()));
        } else {
            return json(resultArray(0, "操作成功"));
        }

    }

    /**
     * ok-2ok
     * 修改代理商小店状态
     * @return \think\response\Json
     */
    public function updateAgentShopStatus() {
        $agent = new AgentHandle();
        $platform = request()->post('platform', 0);
        if (empty($platform)) {
            $agent_id = request()->post('agent_id');
            if (empty($agent_id)) {
                return json(resultArray(2, "未指定代理商 "));
            }
        } else {
            $agent_id = $agent->getPlatformAgentId();
        }

        $agent_shop_id = request()->post('agent_shop_id');
        $status =  request()->post('status');

        $agent = new AgentHandle();
        $res = $agent->updateAgentShopStatus($agent_id, $agent_shop_id,  $status);

        if (empty($res)) {
            return json(resultArray(2, "操作失败 ".$agent->getError()));
        } else {
            return json(resultArray(0, "操作成功"));
        }

    }

    /**
     * ok-2ok
     * 代理商体验店详情
     * @return \think\response\Json
     */
    public function agentShopDetails() {
        $agent = new AgentHandle();
        $platform = request()->post('platform', 0);
        if (empty($platform)) {
            $agent_id = request()->post('agent_id');
            if (empty($agent_id)) {
                return json(resultArray(2, "未指定代理商 "));
            }
        } else {
            $agent_id = $agent->getPlatformAgentId();
        }

        $agent = new AgentHandle();
        $condition = array (
          //  'id'=>$agent_shop_id,
            'agent_id'=>$agent_id
        );
        $shop = $agent->getAgentShopDetails($condition);
        return json(resultArray(0, "操作成功", $shop));
    }

    /**
     * ok-2ok
     * 删除指定的代理商体验店
     * @return \think\response\Json
     */
    public function delAgentShop() {
        $agent = new AgentHandle();
        $platform = request()->post('platform', 0);
        if (empty($platform)) {
            $agent_id = request()->post('agent_id');
            if (empty($agent_id)) {
                return json(resultArray(2, "未指定代理商 "));
            }
        } else {
            $agent_id = $agent->getPlatformAgentId();
        }

        $agent_shop_id = request()->post('agent_shop_id');
        if (empty($agent_shop_id)) {
            return json(resultArray(2, "未指定体验店"));
        }
        $agent = new AgentHandle();
        $res = $agent->delAgentShop($agent_id, $agent_shop_id);
        if (empty($res)) {
            return json(resultArray(2, "删除失败"));
        } else {
            return json(resultArray(0, "删除成功"));
        }
    }

    /**
     * ok-2ok
     * 代理商体验店列表
     */
    public function agentShopList()
    {
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $search_text = request()->post('search_text','');
        $status = request()->post('status','all');

        $agent = new AgentHandle();
        $condition = array();

        if ($status !=  'all') {
            $condition['status'] = $status;
        }


        if (!empty($search_text)) {
            $condition1["agent_name|identify_code"] = array(
                "like",
                "" . $search_text . "%"
            );

            $ids = $agent->getAgentIdList( $condition1);
        //    Log::write($ids);
          //  if (!empty($ids)) {
            if ($ids === '') {
                $condition['agent_id']=-1;
            } else {
                $condition['agent_id'] = array(
                    'in', $ids
                );
            }
           // }
        }

        $order='id desc';
        $field='*';

        $list = $agent->getAgentShopList($page_index, $page_size, $condition, $order,$field);
        return json(resultArray(0,"操作成功", $list));
    }


    /**
     * ok-2ok
     * 得到代理商体验店二维码
     * @return \think\response\Json
     */
    public function getAgentShopQRcode() {
        $agent_id = request()->post('agent_id');

        $agent_shop_id = request()->post('agent_shop_id');
        if (empty($agent_shop_id)) {
            return json(resultArray(2, "未指定体验店"));
        }

        $agent = new AgentHandle();
        $condition = array (
            'id'=>$agent_shop_id,
            'agent_id'=>$agent_id
        );
        $shop = $agent->getAgentShopDetails($condition);

        if (empty($shop)) {
            return json(resultArray(2, "体验店不存在"));
        }

        $url = request()->domain();
        $url = $url. __ROOT__.'/index.php?s=wap/index/agentShop&shopid='.$agent_shop_id;
        $path='upload/qrcode/agentshop';
        $qrcode_name = 'agentshopcode'.$agent_shop_id;
        $ret['path'] =  getQRcode($url, $path, $qrcode_name);
        return json(resultArray(0,"操作成功", $ret));
    }


    /**
     * ok-2ok
     * 加盟店升级
     * @param $agent_id
     * @return \think\response\Json
     */
    public function agentUpgrade() {
        $agent_id = request()->post('agent_id');
        if (empty($agent_id)) {
            return json(resultArray(2, "未指定代理商 "));
        }

        $agent = new AgentHandle();
        $ret = $agent->agentUpgrade($agent_id);
        if (empty($ret)) {
            return json(resultArray(2, "操作失败".$agent->getError()));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 直接代理商降级
     * @return \think\response\Json
     */
    public function agentDowngrade() {
        $agent_id = request()->post('agent_id');
        if (empty($agent_id)) {
            return json(resultArray(2, "未指定代理商 "));
        }
        $p_agent_id = request()->post('p_agent_id');

        $agent = new AgentHandle();
        $ret = $agent->agentDowngrade($agent_id, $p_agent_id);
        if (empty($ret)) {
            return json(resultArray(2, "操作失败".$agent->getError()));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 得到有效的直接代理商
     * @return \think\response\Json
     */
    public function validFirstAgentList() {
        $agent_id = request()->post('agent_id', 1);

        $condition = array(
            'ag.id' => ['<>', $agent_id],
            'ag.agent_type'=>1,
            'ag.status'=>2
        );
        $agent = new AgentHandle();
        $order = 'ag.id asc';
        $field='ag.id, ag.agent_name, ag.identify_code';
        $list = $agent->getAgentList(1, 0, $condition, $order, $field);
        return json(resultArray(0, "操作成功", $list));
    }

    /**
     * ok-2ok
     * 加盟店更换父级代理商
     * @return \think\response\Json
     */
    public function updatePAgent() {
        $agent_id = request()->post('agent_id');
        if (empty($agent_id)) {
            return json(resultArray(2, "未指定代理商 "));
        }
        $p_agent_id = request()->post('p_agent_id');
        if (empty($p_agent_id)) {
            return json(resultArray(2, "未指定父级代理商 "));
        }

        $agent = new AgentHandle();
        $ret = $agent->updatePAgent($agent_id, $p_agent_id);
        if (empty($ret)) {
            return json(resultArray(2, "操作失败".$agent->getError()));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }
}