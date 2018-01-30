<?php
/**
 * 代理商的相关业务逻辑
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-29
 * Time: 1:18
 */

namespace dao\handle;

use dao\model\Agent as AgentModel;
use dao\model\AgentAccountCommissionRecords as AgentAccountCommissionRecordsModel;
use dao\model\AgentAccountRateRecords as AgentAccountRateRecordsModel;
use dao\model\AgentAccountWithdrawRecords;
use dao\model\AgentView as AgentViewModel;
use dao\model\AgentUser as AgentUserModel;
use dao\handle\agent\AgentUserHandle;
use dao\handle\AddressHandle;
use dao\model\MemberUser;
use dao\model\PickupPoint as PickupPointModel;
use dao\model\AgentAccount as AgentAccountModel;
use dao\model\MemberUser as MemberUserModel;
use think\Log;
use dao\handle\agentaccount\AgentAccountHandle;
use dao\model\AccountCommissionRecords as AccountCommissionRecordsModel;
use dao\model\Account as AccountModel;
use dao\model\AgentAccountWithdrawRecords as AgentAccountWithdrawRecordsModel;
use dao\model\Order as OrderModel;
use dao\model\AgentBankAccount as AgentBankAccountModel;
use think\Exception;
use dao\model\AgentWithdraw as AgentWithdrawModel;
use dao\model\AgentShop as AgentShopModel;




class AgentHandle extends BaseHandle
{
    /**
     * 根据代理商id,得到代理商名字
     * @param $agent_id
     * @return mixed
     */
    private $agent;

    function __construct()
    {
        parent::__construct();
        $this->agent = new AgentModel();
    }


    public function getAgentNameById($agent_id) {
        $platform_agent = $this->getPlatformAgent();
        if ($agent_id == $platform_agent['id']) {
            return $platform_agent['agent_name'];
        }
        if (empty($agent_id)) {
            return "";
        }

        $agent_model = new AgentModel();
        $agent = $agent_model->get($agent_id);
        return $agent['agent_name'];
    }

    public function getAgentById($agent_id) {
        $platform_agent = $this->getPlatformAgent();
        if ($agent_id == $platform_agent['id']) {
            $agent = $platform_agent;
        } else {
            if (empty($agent_id)) { //如果agent_id=0这个方要用于平台代理商的上级
               return  array (
                    'id' => $agent_id ,
                    'agent_name'=> '',
                    'p_agent_id'=> '',
                    'identify_code'=> '' ,
                    'agent_type' => 0,
                    'status'=>2
                );
            }
            $agent_model = new AgentModel();
            $agent = $agent_model->get($agent_id);
            $address = new AddressHandle();
            if (isset($agent['province_id'])) {
                $agent['province'] = $address->getProvinceName($agent['province_id']);
            }
            if (isset($agent['city_id'])) {
                $agent['city'] = $address->getCityName($agent['city_id']);
            }
            if (isset($agent['district_id'])) {
                $agent['district'] = $address->getDistrictName($agent['district_id']);
            }
        }

        $agent['status_name'] = $this->getAgentStatusNameById($agent['status']);
        return $agent;
    }

    public function getAgentInfoById($agent_id, $field) {
        $platform_agent = $this->getPlatformAgent();
        if ($agent_id == $platform_agent['id']) {
            $agent = $platform_agent;
        } else {
            if (empty($agent_id)) { //如果agent_id=0这个方要用于平台代理商的上级
                return  array (
                    'id' => $agent_id ,
                    'agent_name'=> '',
                    'p_agent_id'=> '',
                    'identify_code'=> '' ,
                    'agent_type' => 0,
                    'status'=>2
                );
            }

            $agent_model = new AgentModel();
            $agent = $agent_model->getInfo(['id'=> $agent_id], $field);
            $address = new AddressHandle();

            if (isset($agent['province_id'])) {
                $agent['province'] = $address->getProvinceName($agent['province_id']);
            }
            if (isset($agent['city_id'])) {
                $agent['city'] = $address->getProvinceName($agent['city_id']);
            }
            if (isset($agent['district_id'])) {
                $agent['district'] = $address->getProvinceName($agent['district_id']);
            }

             if (isset($agent['p_agent_id'])) {
                 $agent['p_agent_name'] = $this->getAgentNameById($agent['p_agent_id']);

             }
        }
        if (isset($agent['status'])) {
            $agent['status_name'] = $this->getAgentStatusNameById($agent['status']);
        }
        if (isset($agent['agent_type'])) {
            $agent['agent_type_name'] = $this->getAgentTypeNameById($agent['agent_type']);
        }

        return $agent;
    }


    public function getAgentTypeNameById($type_id) {
        $type_name = '';
        if ($type_id == 0) {
            $type_name = '平台';
        } else if ($type_id == 1) {
            $type_name = '直接代理商';
        } else if ($type_id == 2) {
            $type_name = '加盟店';
        }
        return $type_name;
    }

    /**
     * ok-2ok
     * 根据代理商用户id得到代理商用户
     * @param $user_id
     * @return null|static
     */
    public function getAgentUserByUserId($user_id) {
        $agent_user_handle = new AgentUserHandle();
        $agent_user = $agent_user_handle->getAgentUserById($user_id);
        return $agent_user;
    }

    /**
     * ok-2ok
     * 更改代理商状态
     * @param $agent_id
     * @param $agent_status
     * @return bool
     */
    public function setAgentStatus($agent_id, $agent_status) {
        $platform_agent = $this->getPlatformAgent();
        if ($agent_id == $platform_agent['id']) {
            $this->error = '平台不可设置!';
            return false;
        }

        $agent_model = new AgentModel();
        $agent =$agent_model->get($agent_id);
        if (empty($agent)) {
            $this->error = '指定代理商不存在!';
            return false;
        }

        $this->startTrans();
        try {
            $data = array (
                 'status'=>$agent_status
            );
            $retval = $agent_model->save($data, ['id'=>$agent_id]);
            if ($retval === false) {
                $this->rollback();
                Log::write('agent_model->save(data, [\'id\'=>agent_id]) 出错');
                return false;
             }

            $agent_user_model = new AgentUserModel();

            if ($agent_status == 5) { //表示“无效”
                $data1 = array(
                    'status' => 0
                );
                $retval =  $agent_user_model->save($data1, ['agent_id'=>$agent_id]);
            } else if ($agent_status == 0 || $agent_status == 1 || $agent_status == 2 ||
                        $agent_status == 3 || $agent_status == 4 ) {
                $data1 = array(
                    'status' => 1
                );
                $retval =  $agent_user_model->save($data1, ['agent_id'=>$agent_id]);
            }
            if ($retval === false) {
                $this->rollback();
                Log::write('agent_user_model->save(data1, [\'agent_id\'=>agent_id]) 出错');
                return false;
            }

            if ($agent_status == 2) {
                //设置代理商默认佣金率
                $this->setAgentDefaultCommissionRate($agent_id);
            }

            $this->commit();
            return true;
        } catch (\Exception $ex) {
            $this->rollback();
            $this->error = $ex->getMessage();
            return false;
        }
    }


    /**
     * ok-2ok
     * 设置代理商默认佣金率
     * @param $agent_id
     * @return bool
     */
    public function setAgentDefaultCommissionRate($agent_id) {
        $platform_agent = $this->getPlatformAgent();
        if ($agent_id == $platform_agent['id']) {
            $this->error = '平台不可设置!';
            return false;
        }

        $agent_model = new AgentModel();
        $agent = $agent_model->get($agent_id);
        if (empty($agent)) {
            $this->error = '指定代理商不存在!';
            return false;
        }

        $commission_rate_first =0;
        $commission_rate_second = 0;
        $config = new ConfigHandle();
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

        $this->startTrans();
        $rate_first= $commission_rate_first;
        $rate_second = $commission_rate_second;

        try {
            $agent_rate_records_model = new AgentAccountRateRecordsModel();
            //   $agent_rate = $agent_rate_records_model->get(['agent_id' => $agent_id]);
            $data = array(
                'serial_no' => getSerialNo(),
                'operation_id' => 42,
                'agent_id' => $agent_id,
                'commission_rate_first' => $rate_first,
                'commission_rate_second' => $rate_second,
                'account_type' => 3,
                'remark' => '平台设置代理商默认佣金率',
                'create_time'=>time()
            );
            $res = $agent_rate_records_model->save($data);
            if ($res <= 0) {
                $this->rollback();
                Log::write('agent_rate_records->save(data) 出错');
                return false;
            }

            $agent_account_model = new AgentAccountModel();
            $agent_account = $agent_account_model->get(['agent_id' => $agent_id]);
            $data = array(
                'commission_rate_first' => $rate_first,
                'commission_rate_second' => $rate_second,
            );

            if (empty($agent_account)) {
                $data['agent_id'] = $agent_id;
                $data['create_time']=time();
                $res = $agent_account_model->save($data);
                if ($res <= 0) {
                    $this->rollback();
                    Log::write('agent_account_mode->save(data) 出错');
                    return false;
                }
            } else {
                $data['update_time']=time();
                $res = $agent_account_model->save($data, ['id' => $agent_account['id']]);
                if ($res <= 0) {
                    $this->rollback();
                    Log::write('agent_rate_records->save(data,[\'id\'=>agent_rate[\'id\'] ] 出错');
                    return false;
                }
            }
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error ="操作时出现异常:".$e->getMessage();
            return false;
        }
    }



    public function getPlatformAgent() {
        $config_handle = new ConfigHandle();
        $platform_agent_id = ($config_handle->getValidConfig(0, 'PLATFORM_AGENT_ID'));
        if (isset($platform_agent_id)) {
            $platform_agent_id = (int)($platform_agent_id['value']);
            Log::write("platform_agent_id=".$platform_agent_id);
        }
        $platform_agent_name = $config_handle->getValidConfig(0, 'PLATFORM_AGENT_NAME');
        $platform_agent_name = $platform_agent_name['value'];
        $platform_agent_identify_code = $config_handle->getValidConfig(0, 'PLATFORM_AGENT_IDENTIFY_CODE');
        $platform_agent_identify_code = $platform_agent_identify_code['value'];

        if (!isset($platform_agent_id)) {
            $platform_agent_id = 0;
        }

        if (empty($platform_agent_name)) {
            $platform_agent_name = '平台';
        }

        if (empty($platform_agent_identify_code)) {
            $platform_agent_identify_code = 'A0001';
        }

        $data = array (
            'id' => $platform_agent_id ,
            'agent_name'=> $platform_agent_name,
            'p_agent_id'=> 0,
            'identify_code'=> $platform_agent_identify_code ,
            'agent_type' => 0,
            'status'=>2
        );
        return $data;
    }

    public function isPlatformAgentById($agent_id) {
        $platform_agent = $this->getPlatformAgent();
        if ($platform_agent['id'] == $agent_id) {
            return true;
        } else {
            return false;
        }
    }

    public function isPlatformAgentByCode($identify_code) {
        $platform_agent = $this->getPlatformAgent();
        if ($platform_agent['identify_code'] === $identify_code) {
            return true;
        } else {
            return false;
        }
    }


    public function getValidAgentByType($agent_type) {
        if ($agent_type === 0) {
            return $this->getPlatformAgent();
        }
        $res = $this->agent->getConditionQuery(
        array(
            'agent_type' =>$agent_type,
            'status'=> 2
        ), "*", "sort desc, id desc");

        return $res;
    }




    public function getValidAgentByIdentifyCode($identify_code) {
        $platform_agent = $this->getPlatformAgent();
        if ($identify_code == $platform_agent['identify_code'] ) {
            return $platform_agent;
        }

        $condition = array(
            'identify_code' => $identify_code,
            'status'=> 2
        );
        $res = $this->agent->getInfo($condition, '*');
        return $res;
    }

    public function getValidAgentByAddress($province_id, $city_id, $district_id ) {
        $res = $this->agent->getConditionQuery(array(
            'status'=>2,
            'province_id'=>$province_id,
            'city_id'=>$city_id,
            'district_id'=>$district_id), 'id, agent_name, p_agent_id, identify_code, agent_type', 'sort desc, id');
        if (empty($res)) {
            $res = $this->agent->getConditionQuery(array(
                'status'=>2,
                'province_id'=>$province_id,
                'city_id'=>$city_id,
                'district_id' => 0), 'id, agent_name, p_agent_id, identify_code, agent_type', 'sort desc, id');

        }
        if (empty($res)) {
            $res = $this->agent->getConditionQuery(array(
                'status'=>2,
                'province_id'=>$province_id,
                'city_id'=>0,
                'district_id' => 0 ), 'id, agent_name, p_agent_id, identify_code, agent_type', 'sort desc, id');

        }


        if (empty($res)) {
            $data = $this->getPlatformAgent();
            $res[] = $data;
            /*
            $res = $this->agent->getConditionQuery(array(
                'status' => 1,
                'province_id' => 0,
                'city_id' => 0,
                'district_id' => 0),  'id, agent_name, p_agent_id, identify_code, agent_type', 'sort desc, id');
            */
        }

        return $res;
    }

    public function getPlatformAgentId() {
        $res = $this->getPlatformAgent();
        /*
        $condition = array(
            'agent_type' => 0,
            'status'=> 1
        );
        $res = $this->agent->getInfo($condition, 'id');
        */
        return $res['id'];
    }

    /**
     * 新增代理商
     */
    public function addAgent($sort, $agent_name, $identify_code, $p_agent_id, $agent_level,  $agent_type,$area_id,
                             $province_id, $city_id, $district_id, $agent_status, $create_by,
                             $agent_user_name, $agent_user_password,  $agent_user_real_name, $agent_user_level, $agent_user_type,
                             $agent_user_status) {
        if (empty($agent_name)) {
            $this->error = "代理商名称不能为空";
            return false;
        }

        $count = $this->agent->where([
            'agent_name' => $agent_name
        ])->count();
        if ($count > 0) {
            $this->error = "此代理商名称已存在";
            return false; //USER_REPEAT;
        }

        if (empty($identify_code)) {
            $this->error = "代理商识别码不能为空";
            return false;
        }

        $count = $this->agent->where([
            'identify_code' => $identify_code
        ])->count();

        if ($count > 0) {
            $this->error = "此代理商识别码已存在";
            return false; //USER_REPEAT;
        }

        if ($p_agent_id == 0) {
           $p_agent = $this->getPlatformAgent();
            $p_agent_id = $p_agent['id'];
        }

        try {
            $this->startTrans();
            $agent = new AgentModel();
            $data = array(
                'sort' => $sort,
                'agent_name' => $agent_name,
                'p_agent_id' => $p_agent_id,
                'agent_level' => $agent_level,
                'identify_code' => $identify_code,
                'agent_type' => $agent_type,
                'area_id'=> $area_id,
                'province_id' => $province_id,
                'city_id' => $city_id,
                'district_id' => $district_id,
                'status' => $agent_status,
                'create_by' => $create_by,
            );
            $agent->save($data);
            $agentId = $agent->id;
            if ($agentId < 1) {
                $this->rollback();
                $this->error = "保存代理商时出错, 操作失败!";
                return false;
            }
            $agentUserHandle = new AgentUserHandle();
            $agentUserId = $agentUserHandle->addAgentUser($agent_user_name, $agent_user_password, $agentId, $agent_user_real_name,
                $agent_user_level, $agent_user_type, $agent_user_status);
            if (empty($agentUserId)) {
                $this->rollback();
                $this->error = $agentUserHandle->getError();
                return false;
            }
           //新增代理商帐户
            $agent_account_model = new AgentAccountModel();
            $data = array(
                'agent_id'=>$agentId
            );
            $res = $agent_account_model->save($data);
            if (empty($res)) {
                $this->rollback();
                Log::write('agent_account_model->save(data) 出错');
                return false;
            }

            $this->commit();
            return $agentId;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error ="操作时出现异常:".$e->getMessage();
            return false;
        }
    }

    /**
     * 新增代理商
     */
    public function updateAgentInfo($agent_id, $p_agent_id, $sort, $agent_name, $identify_code, $agent_level, $agent_type,
                                $company_name,$area_id, $province_id, $city_id,$district_id, $company_address,$company_lng,$company_lat,
                                    $business_licence_pic, $legal_person_name, $legal_person_mobile,$legal_person_id_no,
                                    $legal_person_id_front, $legal_person_id_back, $agent_status) {
        if (empty($agent_id) || $agent_id < 1) {
            $this->error = "未指明代理商id, 操作失改!";
            return false;
        }

        if (empty($agent_name)) {
            $this->error = "代理商名称不能为空";
            return false;
        }

        $agent_info = $this->agent->get($agent_id);


        if ($agent_info['agent_name'] != $agent_name) {
            $count = $this->agent->where([
                'agent_name' => $agent_name
            ])->count();
            if ($count > 0) {
                $this->error = "代理商名称" . $agent_name . "已存在";
                return false;
                // return USER_REPEAT;
            }
        }

        if (empty($identify_code)) {
            $this->error = "代理商识别码不能为空";
            return false;
        }

        if ($agent_info['identify_code'] != $identify_code) {
            $count = $this->agent->where([
                'identify_code' => $identify_code
            ])->count();
            if ($count > 0) {
                $this->error = "代理商识别码" . $identify_code . "已存在";
                return false;
                // return USER_REPEAT;
            }
        }

        if ($p_agent_id == 0) {
            $p_agent = $this->getPlatformAgent();
            $p_agent_id = $p_agent['id'];
        }

        try {
            $this->startTrans();
            $agent = new AgentModel();
            $data = array(
                'sort' => $sort,
                'agent_name' => $agent_name,
                'p_agent_id' => $p_agent_id,
                'agent_level' => $agent_level,
                'identify_code' => $identify_code,
                'agent_type' => $agent_type,
                'area_id'=>$area_id,
                'province_id' => $province_id,
                'city_id' => $city_id,
                'district_id' => $district_id,
                'status' => $agent_status,
                'company_name' => $company_name,
                'company_address'=>$company_address,
                'company_lng'=> $company_lng,
                'company_lat'=> $company_lat,
                'business_licence_pic'=> $business_licence_pic,

                'legal_person_name' => $legal_person_name,
                'legal_person_mobile'=>$legal_person_mobile,
                'legal_person_id_no'=>$legal_person_id_no,
                'legal_person_id_front'=>$legal_person_id_front,
                'legal_person_id_back'=>$legal_person_id_back
            );
           $retval = $agent->save($data, ['id'=>$agent_id]);

            if (empty($retval)) {
                $this->rollback();
                $this->error = "更新代理商信息时出错, 操作失败!";
                return false;
            }
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error ="操作时出现异常:".$e->getMessage();
            return false;
        }

    }

    /**
     * 得到代理商的id列表
     * @param string $condition
     * @param string $order
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getAgentIdList( $condition = '', $order = '') {
        $agent = new AgentModel();
        $agent_list = $agent->getConditionQuery($condition, 'id', $order);
        $ids = '';
        if (!empty($agent_list)) {
            foreach ($agent_list as $k => $v) {
                $ids = $ids .$v['id'].',';
            }
        }
       if (!empty($ids)) {
           $ids = substr($ids,0,strlen($ids)-1);
       }
       return $ids;
    }

    public function getAgentList($page_index = 1, $page_size = 0, $condition = '', $order = '', $field=''){

        $agent_view = new AgentViewModel();
       // getAgentViewList($page_index, $page_size, $condition, $order)
       // getAgentViewQuery($page_index, $page_size, $condition,  $order, $field='')
       $result =  $agent_view->getAgentViewList($page_index,$page_size,$condition, $order,$field);
        /*
        $result =  $this->agent->pageQuery($page_index, $page_size, $condition, $order, $field);
        foreach ($result['data'] as $k => $v) {
             $address = new AddressHandle();
             $result['data'][$k]['province_name'] = $address->getProvinceName($v['province_id']); //省
             $result['data'][$k]['city_name'] = $address->getCityName($v['city_id']); //余额
             $result['data'][$k]['district_name'] = $address->getDistrictName($v['district_id']);
         }
        */
        return $result;
    }


    public function getValidMemberCountFirst($agent_id) {
        $member = new MemberUserModel();
        $condition = array(
            'agent_id' => $agent_id,
            'status' => 1
        );
        return $member->getCount($condition);
    }

    public function getValidMemberCountSecond($agent_id) {
        $agent = $this->getAgentListByParentId($agent_id,  $status='', $field='ag.id');
        $count = 0;
        foreach ($agent as $k => $v) {
            $member = new MemberUserModel();
            $condition = array(
                'agent_id' => $v['id'],
                'status' => 1
            );
            $count = $count + $member->getCount($condition);
        }
        return $count;
    }


    public function getAgentStatusNameById($status_id) {
        $status_name = '';
        if ($status_id == 0) {
            $status_name ='未完善';
        } else if  ($status_id == 1) {
            $status_name = '未审核';
        } else if  ($status_id == 2) {
            $status_name = '正常';
        } else if  ($status_id == 3) {
            $status_name = '未通过';
        }  else if  ($status_id == 4) {
            $status_name = '暂停';
        } else if  ($status_id == 5) {
            $status_name = '无效';
        } else if  ($status_id == -1) {
            $status_name = '删除';
        }

        return $status_name;
    }

    public function getAgentListWithAccount($page_index = 1, $page_size = 0, $condition = '' ){
        $agent_view = new AgentViewModel();
         $field = 'ag.*, au.user_name, sp.province_name, sc.city_name,
            sd.district_name';
         $order = 'ag.sort desc, ag.id asc';

        $result =  $agent_view->getAgentViewList($page_index,$page_size,$condition, $order,$field);

        foreach ($result['data'] as $k => $v) {
            $p_agent = $this->getAgentById($v['p_agent_id']);
            $result['data'][$k]['p_agent_name'] =$p_agent['agent_name']; // $this->getAgentNameById($v['p_agent_id']);
            $result['data'][$k]['p_agent_code'] = $p_agent['identify_code'];
          //  getAgentNameById($v['p_agent_id']);
            /*
            if ($v['agent_status'] == 0) {
                $result['data'][$k]['status_name'] ='未完善';
            } else if  ($v['agent_status'] == 1) {
                $result['data'][$k]['status_name'] = '未审核';
            } else if  ($v['agent_status'] == 2) {
                $result['data'][$k]['status_name'] = '正常';
            } else if  ($v['agent_status'] == 3) {
                $result['data'][$k]['status_name'] = '未通过';
            }  else if  ($v['agent_status'] == 4) {
                $result['data'][$k]['status_name'] = '暂停';
            } else if  ($v['agent_status'] == 5) {
                $result['data'][$k]['status_name'] = '无效';
            }
            */
            $result['data'][$k]['status_name'] = $this->getAgentStatusNameById($v['status']);



            $result['data'][$k]['agent_type_name'] = $this->getAgentTypeNameById($v['agent_type']);
            $account_model = new AgentAccountModel();
            $account = $account_model->get(['agent_id' => $v['id']]);
            if (!empty($account)) {
                $result['data'][$k]['order_count_first'] = $account['order_total_count_first'];
                $result['data'][$k]['order_count_second'] = $account['order_total_count_second'];

                $sales_money_first = $account['order_goods_total_money_first'] - $account['order_promotion_total_money_first']
                      - $account['order_coupon_total_money_first'] - $account['order_point_total_money_first']
                      - $account['order_refund_total_money_first'];
                if ($sales_money_first < 0) {
                    $sales_money_first = 0;
                }

                $sales_money_second = $account['order_goods_total_money_second'] - $account['order_promotion_total_money_second']
                    - $account['order_coupon_total_money_second'] - $account['order_point_total_money_second']
                    - $account['order_refund_total_money_second'];

               if ($sales_money_second < 0) {
                   $sales_money_second = 0;
               }

                $result['data'][$k]['sales_money_first'] = $sales_money_first;
                $result['data'][$k]['sales_money_second'] = $sales_money_second;

                $result['data'][$k]['commission_money_first'] = $account['commission_total_money_first'];
                $result['data'][$k]['commission_money_second'] = $account['commission_total_money_second'];
                $result['data'][$k]['commission_adjust_money'] = $account['commission_adjust_total_money'];
                $result['data'][$k]['commission_rate_first'] = $account['commission_rate_first'];
                $result['data'][$k]['commission_rate_second'] = $account['commission_rate_second'];


                $proceeds_money_total = $account['commission_total_money_first']
                                         + $account['commission_total_money_second']
                                         + $account['commission_adjust_total_money'];

                $result['data'][$k]['proceeds_money_total'] = $proceeds_money_total;
                $proceeds_withdraw_amount = $account['total_withdraw_amount'];
                $result['data'][$k]['proceeds_withdraw_amount'] = $proceeds_withdraw_amount;
                $proceeds_money_remain = $proceeds_money_total - $proceeds_withdraw_amount;
                $result['data'][$k]['proceeds_money_remain'] = $proceeds_money_remain;
            } else {
                $result['data'][$k]['order_count_first'] = 0;
                $result['data'][$k]['order_count_second'] = 0;
                $result['data'][$k]['sales_money_first'] = 0;
                $result['data'][$k]['sales_money_second'] = 0;

                $result['data'][$k]['commission_money_first'] = 0;
                $result['data'][$k]['commission_money_second'] = 0;
                $result['data'][$k]['commission_adjust_money'] = 0;


                $result['data'][$k]['proceeds_money_total']=0;
                $result['data'][$k]['proceeds_withdraw_amount'] = 0;
                $result['data'][$k]['proceeds_money_remain'] = 0;
                $config = new ConfigHandle();
                $commission_rate_first = 0;
                $commission_rate_second = 0;
                if ($v['agent_type'] == 1) {
                    $config_rate = $config->getValidConfig(0, 'AGENT_COMMISSION_RATE_FIRST');
                    $commission_rate_first = (float)$config_rate['value']; //$config->getValidConfig(0, 'AGENT_COMMISSION_RATE_FIRST');
                    $config_rate = $config->getValidConfig(0, 'AGENT_COMMISSION_RATE_SECOND');
                    $commission_rate_second = (float)$config_rate['value']; //$config->getValidConfig(0, 'AGENT_COMMISSION_RATE_SECOND');
                } else if ($v['agent_type'] == 2) {
                    $config_rate = $config->getValidConfig(0, 'AGENT_SON_COMMISSION_RATE_FIRST');
                    $commission_rate_first = (float)$config_rate['value']; //$config->getValidConfig(0, 'AGENT_SON_COMMISSION_RATE_FIRST');
                    $config_rate = $config->getValidConfig(0, 'AGENT_SON_COMMISSION_RATE_SECOND');
                    $commission_rate_second = (float)$config_rate['value'];// $config->getValidConfig(0, 'AGENT_SON_COMMISSION_RATE_SECOND');
                }
                $result['data'][$k]['commission_rate_first'] = $commission_rate_first;
                $result['data'][$k]['commission_rate_second'] = $commission_rate_second;
            }
            $result['data'][$k]['member_count_first'] = $this->getValidMemberCountFirst($v['id']);
            $result['data'][$k]['member_count_second'] = $this->getValidMemberCountSecond($v['id']);

         }

        return $result;
    }



    /**
     * 设置代理商的佣金率
     * @param $agent_id
     * @param $rate_first
     * @param $rate_second
     * @return bool
     */
    public function setAgentCommissionRate($agent_id, $rate_first, $rate_second) {
        $platform_agent = $this->getPlatformAgent();
        if ($agent_id == $platform_agent['id']) {
            $this->error = '平台不可设置!';
            return false;
        }

        $agent_model = new AgentModel();
        $agent = $agent_model->get($agent_id);
        if (empty($agent)) {
            $this->error = '指定代理商不存在!';
            return false;
        }

        if ($agent['agent_type'] == 2) {
            $rate_second = 0;
        }

        $this->startTrans();
        try {
            $agent_rate_records_model = new AgentAccountRateRecordsModel();
         //   $agent_rate = $agent_rate_records_model->get(['agent_id' => $agent_id]);
            $data = array(
                'serial_no' => getSerialNo(),
                'operation_id' => 25,
                'agent_id' => $agent_id,
                'commission_rate_first' => $rate_first,
                'commission_rate_second' => $rate_second,
                'account_type' => 2,
                'remark' => '平台手工调整代理商佣金率'
            );
            $res = $agent_rate_records_model->save($data);
            if (empty($res)) {
                    $this->rollback();
                    Log::write('agent_rate_records->save(data) 出错');
                    return false;
            }

            $agent_account_model = new AgentAccountModel();
            $agent_account = $agent_account_model->get(['agent_id' => $agent_id]);
            $data = array(
                'commission_rate_first' => $rate_first,
                'commission_rate_second' => $rate_second,
            );

            if (empty($agent_account)) {
                $data['agent_id'] = $agent_id;
                $res = $agent_account_model->save($data);
                if (empty($res)) {
                    $this->rollback();
                    Log::write('agent_account_mode->save(data) 出错');
                    return false;
                }
            } else {
                $res = $agent_account_model->save($data, ['id' => $agent_account['id']]);
                if ($res === false) {
                    $this->rollback();
                    Log::write('agent_rate_records->save(data,[\'id\'=>agent_rate[\'id\'] ] 出错');
                    return false;
                }
            }
            $this->commit();
            return true;
        } catch (\Exception $e) {
                $this->rollback();
                $this->error ="操作时出现异常:".$e->getMessage();
                return false;
        }
    }

    /**
     * 调整代理商的佣金
     * @param $agent_id
     * @param $rate_first
     * @param $rate_second
     * @return bool
     */
    public function adjustAgentCommission($agent_id, $adjust_money) {
        $platform_agent = $this->getPlatformAgent();
        if ($agent_id == $platform_agent['id']) {
            $this->error = '平台不可操作!';
            return false;
        }

        $agent_model = new AgentModel();
        $agent = $agent_model->get($agent_id);
        if (empty($agent)) {
            $this->error = '指定代理商不存在!';
            return false;
        }

        $this->startTrans();
        try {
            $agent_commission_records_model = new AgentAccountCommissionRecordsModel();

            $data = array(
                'serial_no' => getSerialNo(),
                'operation_id' => 26,
                'agent_id' => $agent_id,
                'order_id' => 0,
                'from_level'=> 0,
                'commission_money' => $adjust_money,
              //  'commission_rate_second' => $rate_second,
                'sign'=>2,
                'account_type' => 3,

                'remark' => '平台手工调整代理商佣金代理商记录'
            );

            $res = $agent_commission_records_model->save($data);
            if (empty($res)) {
                    $this->rollback();
                    Log::write('agent_commission_records_model->save(data) 出错');
                    return false;
            }

            $agent_account_model = new AgentAccountModel();
            $agent_account = $agent_account_model->get(['agent_id' => $agent_id]);


            if (empty($agent_account)) {
                $data = array(
                    'agent_id'=>$agent_id,
                    'commission_adjust_total_money' => $adjust_money
                );
                $res = $agent_account_model->save($data);
                if (empty($res)) {
                    $this->rollback();
                    Log::write('agent_account_mode->save(data) 出错');
                    return false;
                }
            } else {
                $data = array(
                    'commission_adjust_total_money' =>$agent_account['commission_adjust_total_money'] + $adjust_money,
                );
                $res = $agent_account_model->save($data, ['id' => $agent_account['id']]);
                if ($res === false) {
                    $this->rollback();
                    Log::write('agent_account_model->save(data, [\'id\' => agent_account[\'id\']]) 出错');
                    return false;
                }
            }

            /** 调整平台佣金 */
            $commission_records_model = new AccountCommissionRecordsModel();

            $data = array(
                'serial_no' => getSerialNo(),
                'operation_id' => 6,
                'agent_id' => $agent_id,
                'order_id' => 0,
                'from_level'=> 0,
                'commission_money' => $adjust_money,
                //  'commission_rate_second' => $rate_second,
                'sign'=>2,
                'account_type' => 3,

                'remark' => '平台手工调整代理商佣金'
            );

            $res = $commission_records_model->save($data);
            if (empty($res)) {
                $this->rollback();
                Log::write('commission_records_model->save(data) 出错');
                return false;
            }

            $account_model = new AccountModel();
            $account = $account_model-> getInfo($condition = '', $field = '*');


            if (empty($account)) {
                $data = array(
                    'agent_commission_adjust_total_money' => $adjust_money,
                );
                $res = $account_model->save($data);
                if (empty($res)) {
                    $this->rollback();
                    Log::write('account_mode->save(data) 出错');
                    return false;
                }
            } else {
                $data = array(
                    'agent_commission_adjust_total_money' =>$account['agent_commission_adjust_total_money'] + $adjust_money,
                );
                $res = $account_model->save($data, ['id' => $account['id']]);
                if ($res === false) {
                    $this->rollback();
                    Log::write(' account_model->save($data, [\'id\' => account[\'id\']]) 出错');
                    return false;
                }
            }

            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error ="操作时出现异常:".$e->getMessage();
            return false;
        }
    }


    /**
     * 得到代理商收益
     * @param $agent_id
     * @return int
     */
    public function getAgentProceeds($agent_id) {
        $account_model = new AgentAccountModel();
        $account = $account_model->get(['agent_id' => $agent_id]);
        $proceeds_money_total = $account['commission_total_money_first']
            + $account['commission_total_money_second']
            + $account['commission_adjust_total_money'];
        if ($proceeds_money_total < 0) {
            $proceeds_money_total = 0;
        }
        $proceeds_withdraw_amount = $account['total_withdraw_amount'];
        if ($proceeds_withdraw_amount < 0) {
            $proceeds_withdraw_amount = 0;
        }
        $proceeds_money_remain = $proceeds_money_total - $proceeds_withdraw_amount;
        if ($proceeds_money_remain < 0) {
            $proceeds_money_remain = 0;
        }

         $data = array (
            'proceeds_money_total'=> $proceeds_money_total,
            'proceeds_money_withdraw'=> $proceeds_withdraw_amount,
            'proceeds_money_remain'=> $proceeds_money_remain,
        );

        return $data;

    }

    /**
     * 代理商的提现
     */
    public function agentWithdraw($agent_id, $withdraw_money,$operator,$withdraw_time) {
        $platform_agent = $this->getPlatformAgent();
        if ($agent_id == $platform_agent['id']) {
            $this->error = '平台不可操作!';
            return false;
        }

        $agent_model = new AgentModel();
        $agent = $agent_model->get($agent_id);
        if (empty($agent)) {
            $this->error = '指定代理商不存在!';
            return false;
        }
        $agent_account_model = new AgentAccountModel();
        $agent_account = $agent_account_model->get(['agent_id' => $agent_id]);
        if (empty($agent_account)) {
            $this->error = '此代理商帐户余额为空!';
            return false;
        }

        $proceeds = $this->getAgentProceeds($agent_id);
        $proceeds_money_remain = $proceeds['proceeds_money_remain'];

        if ($proceeds_money_remain <= 0) {
            $this->error = '余额为0，不可操作!';
            return false;
        }

        if ($withdraw_money > $proceeds_money_remain ) {
            $this->error = '提取额超过余额，不可操作!';
            return false;
        }

        $this->startTrans();
        try {
            //提现记录
            $agent_withdraw_records_model = new AgentAccountWithdrawRecordsModel();

            $data = array(
                'serial_no' => getSerialNo(),
                'operation_id' =>27 ,
                'agent_id' => $agent_id,
                'withdraw_money' => $withdraw_money,
                'account_type' =>1 ,
                'operator'=>$operator,
                'withdraw_time'=> getTimeTurnTimeStamp($withdraw_time),
                'remark' => '代理商收益提取录入'
            );

            $res = $agent_withdraw_records_model->save($data);
            if (empty($res)) {
                $this->rollback();
                Log::write('agent_withdraw_records_model->save(data) 出错');
                return false;
            }



            //将提取额记录到代理商帐户
            $agent_account_model = new AgentAccountModel();
            $agent_account = $agent_account_model->get(['agent_id' => $agent_id]);


            $data = array(
                'total_withdraw_amount' =>$agent_account['total_withdraw_amount'] + $withdraw_money,
            );
            $res = $agent_account_model->save($data, ['id' => $agent_account['id']]);
            if ($res === false) {
                $this->rollback();
                    Log::write('agent_account_model->save(data, [\'id\' => agent_account[\'id\']]) 出错');
                    return false;
            }

            //将提取额记录到平台帐户


            $account_model = new AccountModel();
            $account = $account_model-> getInfo($condition = '', $field = '*');

            $data = array(
                    'agent_withdraw_total_money' =>$account['agent_withdraw_total_money'] + $withdraw_money,
            );
            $res = $account_model->save($data, ['id' => $account['id']]);
            if ($res === false) {
                $this->rollback();
                Log::write(' account_model->save(data, [\'id\' => account[\'id\']]) 出错');
                return false;
            }

            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error ="操作时出现异常:".$e->getMessage();
            return false;
        }
    }

    /**
     * 获取商品分类的子分类
     * @param  $pid
     */
    /*
     *  return array(
	        'data' => $list,
	        'total_count' => $count,
	        'page_count' => $page_count
	    );
     */
    public function getAgentListByParentId($pid,  $status='', $field='')
    {
        if ($status === '') {
            $list = $this->getAgentList(1, 0, 'ag.p_agent_id=' . $pid, 'ag.p_agent_id, ag.id', $field);
        } else {
            $condition = array(
                'ag.p_agent_id'=>  $pid,
                'ag.status' => $status
            );
            $list = $this->getAgentList(1, 0, $condition, 'ag.p_agent_id, ag.id', $field);
        }
      //  $list = $this->getGoodsCategoryList(1, 0, 'p_agent_id='.$pid, 'pid,sort');
        if(!empty($list)){
            for($i=0; $i<count($list['data']); $i++){
                $parent_id=$list['data'][$i]['id'];
                if ($status === '') {
                    $child_list = $this->getAgentList(1, 1, 'ag.p_agent_id=' . $parent_id, 'ag.p_agent_id,ag.id', $field);
                } else {
                    $condition = array(
                        'ag.p_agent_id'=>  $parent_id,
                        'ag.status' => $status
                    );
                    $child_list = $this->getAgentList(1, 1, $condition, 'ag.p_agent_id,ag.id', $field);
                }
                if(!empty($child_list) && $child_list['total_count']>0){
                    $list['data'][$i]["is_parent"]=1;
                }else{
                    $list['data'][$i]["is_parent"]=0;
                }
            }
        }
        return $list['data'];
    }
    /**
     * 获取格式化后的代理商，得到一个三级分类
     */
    public function getFormatAgentList($status='', $field='', $root_agnet=1){

        $one_list = $this->getAgentListByParentId($root_agnet, $status, $field);
        if (! empty($one_list)) {
            foreach ($one_list as $k => $v) {
                $two_list = array();
                $two_list = $this->getAgentListByParentId($v['id'],$status, $field);
               // $v['agent_child_list'] = $two_list;
                $one_list[$k]['agent_child_list'] = $two_list;
                $one_list[$k]['status_name'] = $this->getAgentStatusNameById($v['status']);
                if (! empty($two_list)) {
                    foreach ($two_list as $k1 => $v1) {
                        $three_list = array();
                        $three_list = $this->getAgentListByParentId($v1['id'], $status, $field);
                       // $v1['agent_child_child_list'] = $three_list;
                        $two_list[$k1]['agent_child_list'] = $three_list;
                        $two_list[$k1]['status_name'] = $this->getAgentStatusNameById($v1['status']);
                    }
                }
            }
        }
        return $one_list;
    }

    /**
     * ok-2ok
     * 得到代理商树
     * @param string $status
     * @param string $field
     * @param int $root_agent
     * @return array
     */
    public function getAgentTree($status='', $field='', $root_agent=1) {

        $child_list = $this->getFormatAgentList($status, $field, $root_agent );
        if(!empty($child_list)){
            $is_parent =1;
        }else{
            $is_parent =0;
        }
        $list = array();
        if ($root_agent > 1) {
            $agent = $this->getAgentById($root_agent);
            if (!empty($agent)) {
                $agent["is_parent"] = $is_parent;
                $agent["agent_child_list"] = $child_list;

                $list = array(
                    'root_agent' => $agent
                );
            }

        } else {
            $platform = $this->getPlatformAgent();
            $platform["is_parent"] = $is_parent;
            $platform["agent_list"] = $child_list;

            $list = array(
                'platform' => $platform
            );
        }
        return  $list;
    }


    public function getAgentUserInfo($agent_id) {
        $platform_agent = $this->getPlatformAgent();
        if ($agent_id == $platform_agent['id']) {
            return '';
        }
        $agent_user = new AgentUserModel();

        $user_info = $agent_user->get(['agent_id'=>$agent_id]);
       // unset($user_info['password']);

        return $user_info;
    }

    /**
     * 重置代理商用户密码
     * @param $user_id
     * @param $password
     * @return bool
     */
    public function setAgentUserPassword($user_id, $password) {
        $agent_user = new AgentUserHandle();
        $res = $agent_user->setPassword($user_id, $password);
        if ($res === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 得到代理商佣金记录
     * @param int $page_index
     * @param int $page_size
     * @param $agent_id
     * @return array
     */
    public function getCommssionRecordsByAgent($page_index=1,$page_size=0,  $agent_id) {
        $agent_records = new AgentAccountCommissionRecordsModel();
        $condition = array(
            'agent_id' => $agent_id
        );

        $list = $agent_records-> pageQuery($page_index, $page_size, $condition, 'id desc', '*');
        $order_model = new OrderModel();
        if (!empty($list['data'])) {
            foreach ($list['data'] as $k => $v) {
                if ($v['operation_id'] == 21 ) {
                    $list['data'][$k]['operation']='增加直接佣金';
                } else if ($v['operation_id'] == 22 ) {
                    $list['data'][$k]['operation']='增加间接佣金';
                } else if ($v['operation_id'] == 26 ) {
                    $list['data'][$k]['operation']='调整佣金';
                }
                if (!empty($v['order_id'])) {
                    $order = $order_model->get($v['order_id']);
                    $list['data'][$k]['order_no'] = $order['order_no'];
                }


            }
        }

        return $list;

    }

    /**
     * 得到代理商提现记录
     * @param int $page_index
     * @param int $page_size
     * @param $agent_id
     * @return array
     */
    public function getWithdrawRecordsByAgent($page_index=1,$page_size=0,  $agent_id) {
        $agent_records = new AgentAccountWithdrawRecordsModel();
        $condition = array(
            'agent_id' => $agent_id
        );

        $list = $agent_records-> pageQuery($page_index, $page_size, $condition, 'id desc', '*');
        if (!empty($list['data'])) {
            foreach ($list['data'] as $k => $v) {
                if ($v['operation_id'] == 27 ) {
                    $list['data'][$k]['operation']='收益提取';
                }
            }
        }
        return $list;
    }

    /**
     * 设置默认佣金率
     * @param $first_rate
     * @param $second_rate
     * @param $son_first_rate
     * @param $son_second_rate
     * @return bool
     */
    public function setDefaultCommissionRate($first_rate, $second_rate, $son_first_rate, $son_second_rate) {
        $config = new ConfigHandle();
        $res = $config->setDefaultCommissionRate('AGENT_COMMISSION_RATE_FIRST', $first_rate);
        if (empty($res)) {
            return false;
        }
        $res = $config->setDefaultCommissionRate('AGENT_COMMISSION_RATE_SECOND', $second_rate);
        if (empty($res)) {
            return false;
        }
        $res = $config->setDefaultCommissionRate('AGENT_SON_COMMISSION_RATE_FIRST', $son_first_rate);
        if (empty($res)) {
            return false;
        }
        $res = $config->setDefaultCommissionRate('AGENT_SON_COMMISSION_RATE_SECOND', $son_second_rate);
        if (empty($res)) {
            return false;
        }
        return true;
    }

    /**
     * 得到默认佣金率
     * @return array
     */
    public function getDefaultCommissionRate() {
        $config_handle = new ConfigHandle();
        $config = $config_handle->getValidConfig(0, 'AGENT_COMMISSION_RATE_FIRST');
        if (empty($config)) {
             $config_handle->setDefaultCommissionRate('AGENT_COMMISSION_RATE_FIRST', 0);
            $first_rate = 0;
        } else {
            $first_rate = (float)$config['value'];
        }
        $config = $config_handle->getValidConfig(0, 'AGENT_COMMISSION_RATE_SECOND');
        if (empty($config)) {
            $config_handle->setDefaultCommissionRate('AGENT_COMMISSION_RATE_SECOND', 0);
            $second_rate = 0;
        } else {
            $second_rate = (float)$config['value'];
        }

       $config = $config_handle->getValidConfig(0, 'AGENT_SON_COMMISSION_RATE_FIRST');
        if (empty($config)) {
            $config_handle->setDefaultCommissionRate('AGENT_SON_COMMISSION_RATE_FIRST', 0);
            $son_first_rate = 0;
        } else {
            $son_first_rate = (float)$config['value'];
        }

       $config = $config_handle->getValidConfig(0, 'AGENT_SON_COMMISSION_RATE_SECOND');
        if (empty($config)) {
            $config_handle->setDefaultCommissionRate('AGENT_SON_COMMISSION_RATE_SECOND', 0);
            $son_second_rate = 0;
        } else {
            $son_second_rate = (float)$config['value'];
        }

        $data = array(
            'first_rate'=>$first_rate,
            'second_rate'=>$second_rate,
            'son_first_rate'=>$son_first_rate,
            'son_second_rate'=>$son_second_rate,
        );

        return $data;
    }

    /**
     * 代理商帐户列表
     * @param int $page_index
     * @param int $page_size
     * @param string $condition
     * @return array
     */
    public function getAgentAccountList($page_index=1,$page_size=0,$condition='') {
        $agent_acount = new AgentAccountModel();
        $agent_model = new AgentModel();
        $address = new AddressHandle();
        $fields = "*";
        $list = $agent_acount->pageQuery($page_index, $page_size, $condition, 'id desc', $fields);
        if (!empty($list['data'])) {
            foreach ($list['data'] as $k => $v) {
                $agent = $agent_model->get($v['agent_id']);
                $list['data'][$k]['agent_name']=$agent['agent_name'];
                $list['data'][$k]['identify_code']=$agent['identify_code'];
                $list['data'][$k]['status']=$this->getAgentStatusNameById($agent['status']);
                $list['data'][$k]['agent_type']=$this->getAgentTypeNameById($agent['agent_type']);
                $list['data'][$k]['province_name'] = $address->getProvinceName($agent['province_id']);
                $list['data'][$k]['city_name'] = $address->getCityName($agent['city_id']);
                $list['data'][$k]['district_name'] = $address->getDistrictName($agent['district_id']);
                $list['data'][$k]['p_agent_name'] = $this->getAgentNameById($agent['p_agent_id']);

                $sales_money_first = $v['order_goods_total_money_first'] - $v['order_promotion_total_money_first']
                    - $v['order_coupon_total_money_first'] - $v['order_point_total_money_first'] - $v['order_refund_total_money_first'];
                if ($sales_money_first < 0) {
                    $sales_money_first = 0;
                }

                $list['data'][$k]['sales_money_first']= $sales_money_first;
                $sales_money_second = $v['order_goods_total_money_second'] - $v['order_promotion_total_money_second']
                    - $v['order_coupon_total_money_second'] - $v['order_point_total_money_second'] - $v['order_refund_total_money_second'];
                if ($sales_money_second < 0) {
                    $sales_money_second = 0;
                }
                $list['data'][$k]['sales_money_second']= $sales_money_second;

                $proceeds_money_total = $v['commission_total_money_first']
                    + $v['commission_total_money_second']
                    + $v['commission_adjust_total_money'];
                if ($proceeds_money_total < 0) {
                    $proceeds_money_total = 0;
                }
                $proceeds_money_remain = $proceeds_money_total - $v['total_withdraw_amount'];
                if ($proceeds_money_remain < 0) {
                    $proceeds_money_remain = 0;
                }
                $list['data'][$k]['proceeds_money_total'] = $proceeds_money_total;
                $list['data'][$k]['proceeds_money_remain'] = $proceeds_money_remain;



            }
        }
        return $list;
    }

    /**
     * ok-2ok
     * 得到代理商的营销帐户
     * @param $agent_id
     * @return string
     */
    public function getAgentMarketingAccount($agent_id) {
        $condition = array(
            'agent_id'=>$agent_id
        );
        $list = $this->getAgentAccountList(1,0,$condition);
        if (!empty($list)) {
            if (!empty($list['data'])) {
                return $list['data'][0];
            }
        }
        return '';
    }



    /**
     * 代理商佣金记录列表
     * @param int $page_index
     * @param int $page_size
     * @param string $condition
     * @return array
     */
    public function getAgentCommissionRecordsList($page_index=1,$page_size=0,$condition='') {
        $agent_acount_records = new AgentAccountCommissionRecordsModel();
        $agent_model = new AgentModel();
        $address = new AddressHandle();
        $order_model = new OrderModel();
        $fields = "*";
        $list = $agent_acount_records-> pageQuery($page_index, $page_size, $condition, 'id desc', $fields);
        if (!empty($list['data'])) {
            foreach ($list['data'] as $k => $v) {
                $agent = $agent_model->get($v['agent_id']);
                $list['data'][$k]['agent_name']=$agent['agent_name'];
                $list['data'][$k]['identify_code']=$agent['identify_code'];
                $list['data'][$k]['status']=$this->getAgentStatusNameById($agent['status']);
                $list['data'][$k]['agent_type']=$this->getAgentTypeNameById($agent['agent_type']);
                $list['data'][$k]['province_name'] = $address->getProvinceName($agent['province_id']);
                $list['data'][$k]['city_name'] = $address->getCityName($agent['city_id']);
                $list['data'][$k]['district_name'] = $address->getDistrictName($agent['district_id']);
                $list['data'][$k]['p_agent_name'] = $this->getAgentNameById($agent['p_agent_id']);


                if ($v['operation_id'] == 21 ) {
                    $list['data'][$k]['operation']='增加直接佣金';
                } else if ($v['operation_id'] == 22 ) {
                    $list['data'][$k]['operation']='增加间接佣金';
                } else if ($v['operation_id'] == 26 ) {
                    $list['data'][$k]['operation']='调整佣金';
                } else if ($v['operation_id'] == 35 ) {
                    $list['data'][$k]['operation']='退款减少直接佣金';
                }  else if ($v['operation_id'] == 36 ) {
                    $list['data'][$k]['operation']='退款减少间接佣金';
                }



                if (!empty($v['order_id'])) {
                    $order = $order_model->get($v['order_id']);
                    $list['data'][$k]['order_no'] = $order['order_no'];
                }

            }
        }
        return $list;
    }

    /**
     * 代理商提现记录列表
     * @param int $page_index
     * @param int $page_size
     * @param string $condition
     * @return array
     */
    public function getAgentWithdrawRecordsList($page_index=1,$page_size=0,$condition='') {
        $agent_acount_records =  new AgentAccountWithdrawRecordsModel(); //  new AgentAccountWithdrawRecordsModel();
        $agent_model = new AgentModel();
        $address = new AddressHandle();
        $fields = "*";
        $list = $agent_acount_records-> pageQuery($page_index, $page_size, $condition, 'id desc', $fields);
        if (!empty($list['data'])) {
            foreach ($list['data'] as $k => $v) {
                $agent = $agent_model->get($v['agent_id']);
                $list['data'][$k]['agent_name']=$agent['agent_name'];
                $list['data'][$k]['identify_code']=$agent['identify_code'];
                $list['data'][$k]['status']=$this->getAgentStatusNameById($agent['status']);
                $list['data'][$k]['agent_type']=$this->getAgentTypeNameById($agent['agent_type']);
                $list['data'][$k]['province_name'] = $address->getProvinceName($agent['province_id']);
                $list['data'][$k]['city_name'] = $address->getCityName($agent['city_id']);
                $list['data'][$k]['district_name'] = $address->getDistrictName($agent['district_id']);
                $list['data'][$k]['p_agent_name'] = $this->getAgentNameById($agent['p_agent_id']);


                if ($v['operation_id'] == 27 ) {
                    $list['data'][$k]['operation']='收益提取';
                }

            }
        }
        return $list;
    }


    /**
     * ok-2ok
     * 代理商总数据
     */
    public function agentTotalData($root_agent=0) {
        $agent = new AgentModel();
        if ($root_agent > 0) {
            $condition = array (
                'id | p_agent_id'=>$root_agent,
                'status'=>2
            );
        } else {
            $condition = array (
                'status'=>2
            );
        }
        $agent_valid_count = $agent->getCount($condition);

        if ($root_agent > 0) {
            $condition = array (
                'id | p_agent_id'=>$root_agent,
                'status'=>2,
                'agent_type'=>1
            );
        } else {
            $condition = array (
                'status'=>2,
                'agent_type'=>1
            );
        }

        $agent_first_valid_count = $agent->getCount($condition);         //getCount(['status'=>2, 'agent_type'=>1]);


        if ($root_agent > 0) {
            $condition = array (
                'id | p_agent_id'=>$root_agent,
                'status'=>2,
                'agent_type'=>2
            );
        } else {
            $condition = array (
                'status'=>2,
                'agent_type'=>2
            );
        }
        $agent_second_valid_count = $agent->getCount($condition);          //getCount(['status'=>2, 'agent_type'=>2]);
        $agent_acount_model = new AgentAccountModel();

        $agent_ids = '';
        if ($root_agent > 0) {
            $agent_ids = (string)$root_agent;
            /*
            $value= array(
                ['=', $root_agent]
            );
            */

            $agent_model = new AgentModel();
            $child_agent_list = $agent_model->getInfo(['p_agent_id'=>$root_agent],  'id');

            if (!empty($child_agent_list)) {
                foreach ($child_agent_list as $k => $child_agent) {
                    $agent_ids = $agent_ids.','.$child_agent['id'];
                   // array_push($value, ['=',$agent_ids ]);
                }
             //   array_push($value, 'or');
            }

            $condition = array (
                'agent_id'=> array('in', $agent_ids)
            );

        } else {
            $condition = '';
        }

        $order_total_first = $agent_acount_model->getSum($condition, 'order_total_count_first');          //getSum('', 'order_total_count_first');

        $order_goods_total_money_first =  $agent_acount_model->getSum($condition, 'order_goods_total_money_first');   // getSum('', 'order_goods_total_money_first');
        $order_promotion_total_money_first =  $agent_acount_model->getSum($condition, 'order_promotion_total_money_first');  //getSum('', 'order_promotion_total_money_first');
        $order_coupon_total_money_first =  $agent_acount_model->getSum($condition, 'order_coupon_total_money_first'); //getSum('', 'order_coupon_total_money_first');
        $order_point_total_money_first =  $agent_acount_model->getSum($condition, 'order_point_total_money_first');   //getSum('', 'order_point_total_money_first');
        $order_refund_total_money_first =  $agent_acount_model->getSum($condition, 'order_refund_total_money_first');   //getSum('', 'order_refund_total_money_first');

        $sales_money_total = $order_goods_total_money_first - $order_promotion_total_money_first
                            - $order_coupon_total_money_first - $order_point_total_money_first
                            - $order_refund_total_money_first;
        if ($sales_money_total < 0) {
            $sales_money_total = 0;
        }


        $commission_total_money_first =  $agent_acount_model->getSum($condition, 'commission_total_money_first'); //getSum('', 'commission_total_money_first');
        $commission_total_money_second =  $agent_acount_model->getSum($condition, 'commission_total_money_second'); //getSum('', 'commission_total_money_second');
        $commission_adjust_total_money =  $agent_acount_model->getSum($condition, 'commission_adjust_total_money');  //getSum('', 'commission_adjust_total_money');
        $commission_total = $commission_total_money_first + $commission_total_money_second + $commission_adjust_total_money;
        $total_withdraw_amount =  $agent_acount_model->getSum($condition, 'total_withdraw_amount');     //getSum('', 'total_withdraw_amount');
        $commission_remain = $commission_total - $total_withdraw_amount;
        if ($commission_total < 0) {
            $commission_total = 0;
        }

        if ($commission_remain < 0) {
            $commission_remain = 0;
        }

      //  $order_total_second = $agent_acount_model->getSum('', 'order_total_count_second');
        $member = new MemberUserModel();
        if ($root_agent > 0) {
            $member_valid_count = $member->getCount(
                array(
                    'status'=>1,
                    'agent_id'=>$root_agent
                )
            );
            $agent_model = new AgentModel();
            $child_agent_list = $agent_model->getInfo(['p_agent_id'=>$root_agent],  'id');

            if (!empty($child_agent_list)) {
                foreach ($child_agent_list as $k => $child_agent)  {
                    $member_valid_count_x = $member->getCount(
                        array(
                            'status'=>1,
                            'agent_id'=>$child_agent['id']
                        )
                    );
                    $member_valid_count = $member_valid_count + $member_valid_count_x;
                }

            }

        } else {
            $platform_agent_id = $this->getPlatformAgentId();
            $member_valid_count = $member->getCount(
                array(
                    'status' => 1,
                    'agent_id' => array(
                        '<>', $platform_agent_id
                    )
                )
            );
        }

        $data= array(
            'agent_validcount'=>$agent_valid_count,
            'agent_first_validcount'=>$agent_first_valid_count,
            'agent_second_validcount'=>$agent_second_valid_count,
            'memeber_validcount'=>$member_valid_count,
            'order_validcount'=>$order_total_first,
            'sales_total_money'=>$sales_money_total,
            'commission_first_total_money'=>$commission_total_money_first,
            'commission_second_total_money'=>$commission_total_money_second,
            'commission_adjust_total_money'=>$commission_adjust_total_money,
            'commission_total_money'=>$commission_total,
            'withdraw_total_money'=>$total_withdraw_amount,
            'commission_remain_total_money'=>$commission_remain,

        );
        return $data;
    }
    /**
     * 自提点添加
     */
    public function addPickupPoint($agent_id, $name,$status, $address, $contact, $phone, $province_id, $city_id, $district_id, $longitude, $latitude)
    {
        $pickup_point_model = new PickupPointModel();
        $data = array(
            "agent_id" => $agent_id,
            "name" => $name,
            "status"=>$status,
            'status' =>1,
            "address" => $address,
            "contact" => $contact,
            "phone" => $phone,
            "province_id" => $province_id,
            'city_id' => $city_id,
            'district_id' => $district_id,
            'longitude' => $longitude,
            'latitude'=>$latitude
          //  "create_time" => time()
        );
        $res = $pickup_point_model->save($data);
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
       // return $pickup_point_model->id;
    }

    /**
     *
     * 自提点修改
     */
    public function updatePickupPoint($id, $agent_id, $name, $status, $address, $contact, $phone, $province_id, $city_id, $district_id, $longitude, $latitude)
    {
        $pickup_point_model = new PickupPointModel();
        $data = array(
            "agent_id" => $agent_id,
            "name" => $name,
            'status'=> $status,
            "address" => $address,
            "contact" => $contact,
            "phone" => $phone,
            "province_id" => $province_id,
            'city_id' => $city_id,
            'district_id' => $district_id,
            'longitude' => $longitude,
            'latitude'=>$latitude
        );
        $retval = $pickup_point_model->save($data, [
            'id' => $id
        ]);
        if ($retval === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
    *  自提点列表
     */
    public function getPickupPointList($page_index = 1, $page_size = 0, $where = '', $order = '')
    {
        $pickup_point_model = new PickupPointModel();
        $list = $pickup_point_model->pageQuery($page_index, $page_size, $where, $order, '*');
        if (! empty($list)) {
            $address = new AddressHandle();
            $agent = new AgentHandle();
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['province_name'] = $address->getProvinceName($v['province_id']);
                $list['data'][$k]['city_name'] = $address->getCityName($v['city_id']);
                $list['data'][$k]['dictrict_name'] = $address->getDistrictName($v['district_id']);
                $list['data'][$k]['agent_name'] = $agent->getAgentNameById($v['agent_id']);

            }
        }
        return $list;
    }

    /**
     * 自提点删除
     */
    public function deletePickupPoint($pickip_id)
    {
        $pickup_point_model = new PickupPointModel();

        $condition = array(
            'id' => array(
                    "in",
                $pickip_id
            )
        );
        $retval = $pickup_point_model->destroy($condition);
        if ($retval > 0) {
            return true;
        } else {
            return false;
        }
     //   return $retval;
    }

    /**
     *得到自提点详情
     */
    public function getPickupPointDetail($pickip_id){
        $pickup_point_model = new PickupPointModel();
        $pickup_point_detail = $pickup_point_model->get($pickip_id);
        return $pickup_point_detail;
    }

    public function isAgentChild($p_agent_id, $agent_id) {
        $agent_model = new AgentModel();
        $agent = $agent_model->get(['id'=>$agent_id, 'p_agent_id'=>$p_agent_id]);
        if (empty($agent)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 得到代理商数
     * @param $conditioin
     */
    public function getAgentCount($condition) {
        $agent = new AgentModel();
        $count = $agent->getCount($condition);
        return $count;
    }

    /**
     * ok-2ok
     * 得到指定代理商的银行账号列表
     * @see \data\api\IMember::memberBankAccount()
     */
    public function getBankAccountByAgent($agent_id, $is_default = 0)
    {
        $agent_bank_account = new AgentBankAccountModel();
        $bank_account_list = '';
        if (empty($is_default)) {
            $bank_account_list = $agent_bank_account->getConditionQuery([
                'agent_id' => $agent_id
            ], '*', 'is_default desc, id desc');
        } else {
            $bank_account_list = $agent_bank_account->getConditionQuery([
                'agent_id' => $agent_id,
                'is_default' => 1
            ], '*', '');
        }

        foreach ($bank_account_list as $k=>$v) {
            $agent = $this->getAgentById($v['agent_id']);
            $agent_user_handle = new AgentUserHandle();
            $agent_user = $agent_user_handle->getAgentUserById($v['agent_user_id']);

            $bank_account_list[$k]['agent_name']=$agent['agent_name'];
            $bank_account_list[$k]['agent_identify_code']=$agent['identify_code'];
            $bank_account_list[$k]['agent_user_name']=$agent_user['user_name'];
            $bank_account_list[$k]['bank_type_name']=$this->getBankAccountTypeName($v['bank_type']);
        }
        return $bank_account_list;
    }

    /**
     * ok-2ok
     * 得到代理商的银行账号列表
     * @see \data\api\IMember::memberBankAccount()
     */
    public function getAgentBankAccountList($page_index=1,$page_size=0, $condition, $order='agent_id asc, is_default desc, id desc',  $field='*')
    {
        $agent_bank_account = new AgentBankAccountModel();
        $bank_account_list = $agent_bank_account->pageQuery($page_index, $page_size, $condition, $order, $field);
        foreach ($bank_account_list['data'] as $k=>$v) {
            $agent = $this->getAgentById($v['agent_id']);
            $agent_user_handle = new AgentUserHandle();
            $agent_user = $agent_user_handle->getAgentUserById($v['agent_user_id']);

            $bank_account_list['data'][$k]['agent_name']=$agent['agent_name'];
            $bank_account_list['data'][$k]['agent_identify_code']=$agent['identify_code'];
            $bank_account_list['data'][$k]['agent_user_name']=$agent_user['user_name'];
            $bank_account_list['data'][$k]['bank_type_name']=$this->getBankAccountTypeName($v['bank_type']);

        }
        return $bank_account_list;
    }

    /**
     * ok-2ok
     * 得到银行帐户类型名
     * @param $accountType
     * @return string
     */
    private function getBankAccountTypeName($accountType) {
        if ($accountType == 1) {
            return '对公帐户';
        } else if ($accountType == 2) {
            return '个人帐户';
        } else if ($accountType == 3) {
            return '银行卡';
        } else if ($accountType == 4) {
            return '支付宝';
        } else if ($accountType == 5) {
            return '微信';
        } else{
            return $accountType;
        }
    }

    /**
     * ok-2ok
     * 添加代理商银行账号
     */
    public function addAgentBankAccount($agent_id, $agent_user_id, $bank_type, $branch_bank_name, $realname, $account_number, $mobile)
    {
        $agent_bank_account = new AgentBankAccountModel();
        $this->startTrans();
        try {
            $data = array(
                'agent_id'=>$agent_id,
                'agent_user_id' => $agent_user_id,
                'bank_type' => $bank_type,
                'branch_bank_name' => $branch_bank_name,
                'realname' => $realname,
                'account_number' => $account_number,
                'mobile' => $mobile
              //  'memo'=>$memo,
            );

           $ret = $agent_bank_account->save($data);
            if (empty($ret)) {
                $this->rollback();
                $this->error = '数据库操作失败';
                return false;
            }
            $account_id = $agent_bank_account->id;
            $retval = $this->setAgentBankAccountDefault($agent_id, $account_id);
            $this->commit();
         //   return $account_id;
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * ok-2ok
     * 修改代理商银行账号
     */
    public function updateAgentBankAccount($account_id, $agent_id, $agent_user_id,$bank_type,  $branch_bank_name, $realname, $account_number, $mobile)
    {
        $agent_bank_account = new AgentBankAccountModel();
        $this->startTrans();
        try {

            $data = array(
                'agent_user_id'=>$agent_user_id,
                'bank_type'=> $bank_type,
                'branch_bank_name' => $branch_bank_name,
                'realname' => $realname,
                'account_number' => $account_number,
                'mobile' => $mobile,
                'update_time' => time()
            );
           $ret =  $agent_bank_account->save($data, [
                'id' => $account_id,
                'agent_id'=>$agent_id
            ]);
            if (empty($ret)) {
                $this->rollback();
                $this->error = '数据库操作失败';
                return false;
            }
            $retval = $this->setAgentBankAccountDefault($agent_id, $account_id);
            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * ok-2ok
     * 删除代理商银行账号
     * @see \data\api\IMember::delMemberBankAccount()
     */
    public function delAgentBankAccount($agent_id, $account_id)
    {
        $agent_bank_account = new AgentBankAccountModel();
        $retval = $agent_bank_account->destroy([
            'agent_id' => $agent_id,
            'id' => $account_id
        ]);
        if (empty($retval)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 设定代理商默认银行账户
     *
     * @see \data\api\IMember::setMemberBankAccountDefault()
     */
    public function setAgentBankAccountDefault($agent_id, $account_id)
    {
        $agent_bank_account = new AgentBankAccountModel();
        $this->startTrans();
        try {
            $ret = $agent_bank_account->update([
                'is_default' => 0,
                'update_time' => time()
            ], [
                'agent_id' => $agent_id,
                'is_default' => 1
            ]);
            if (empty($ret)) {
                $this->rollback();
                return false;
            }
            $ret = $agent_bank_account->update([
                'is_default' => 1,
                'update_time' => time()
            ], [
                'agent_id' => $agent_id,
                'id' => $account_id
            ]);
            if (empty($ret)) {
                $this->rollback();
                return false;
            }
            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * ok-2ok
     * 设定代理商银行账户备注
     *
     * @see \data\api\IMember::setMemberBankAccountDefault()
     */
    public function setAgentBankAccountMemo($agent_id, $account_id, $memo)
    {
        $agent_bank_account = new AgentBankAccountModel();

        $ret = $agent_bank_account->update([
            'memo' => $memo,
            'update_time' => time()
        ], [
            'agent_id' => $agent_id,
            'id' => $account_id
        ]);
        if (empty($ret)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 获取代理商银行账号详情信息
     */
    public function getAgentBankAccountDetail($agent_id, $account_id)
    {
        $agent_bank_account = new AgentBankAccountModel();
        $bank_account_info = $agent_bank_account->getInfo([
            'id' => $account_id,
            'agent_id' => $agent_id
        ], '*');
        return $bank_account_info;
    }

    /**
     * 获取代理商银行账号备注
     */
    public function getAgentBankAccountMemo($agent_id, $account_id)
    {
        $agent_bank_account = new AgentBankAccountModel();
        $bank_account_info = $agent_bank_account->getInfo([
            'id' => $account_id,
            'agent_id' => $agent_id
        ], 'memo');
        return $bank_account_info['memo'];
    }

    /**
     * ok-2ok
     * 获取提现记录
     *
     * @see \data\api\niufenxiao\INfxUser::getMemberBalanceWithdraw()
     */
    public function getAgentBalanceWithdraw($page_index = 1, $page_size = 0, $condition = '', $order = 'id desc')
    {
        $agent_balance_withdraw = new AgentWithdrawModel();
        $list = $agent_balance_withdraw->pageQuery($page_index, $page_size, $condition, $order, '*');
        if (! empty($list['data'])) {
            foreach ($list['data'] as $k => $v) {
                $agent =$this->getAgentById($v['agent_id']);
                $agent_user = $this->getAgentUserByUserId($v['agent_user_id']);
                $list['data'][$k]['agent_name'] = $agent['agent_name'];
                $list['data'][$k]['agent_identify_code'] = $agent['identify_code'];
                $list['data'][$k]['agent_user_name'] = $agent_user['user_name'];
                $list['data'][$k]['status_name'] = $this->getAgentWithdrawStatusName($v['status']);
                $list['data'][$k]['ask_for_date'] = getTimeStampTurnTime($v['ask_for_date']);
                $list['data'][$k]['audit_date'] = getTimeStampTurnTime($v['audit_date']);
                $list['data'][$k]['payment_date'] = getTimeStampTurnTime($v['payment_date']);
            }
        }
        return $list;
    }

    /**
     * ok-2ok
     * 获取代理商提现审核数量
     */
    public function getAgentBalanceWithdrawCount($condition = '')
    {
        $agent_balance_withdraw = new AgentWithdrawModel();
        $count = $agent_balance_withdraw->getCount($condition);
        return $count;
    }

    /**
     * ok-2ok
     * 代理商申请提现
     * @see \data\api\niufenxiao\INfxUser::addMemberBalanceWithdraw()
     */
    public function addAgentBalanceWithdraw($agent_id,$agent_user_id, $withdraw_no,$bank_account_id, $cash)
    {
        // 得到本店的提线设置
        $config = new ConfigHandle();
        $withdraw_info = $config->getAgentBalanceWithdrawConfig();
        // 判断是否余额提现设置是否为空 是否启用
        if (empty($withdraw_info) || $withdraw_info['is_use'] == 0) {
            $this->error = '代理商提现暂不可用';
            return false;
           // return USER_WITHDRAW_NO_USE;
        }
        // 提现倍数判断
        if ($withdraw_info['value']["agent_withdraw_multiple"] != 0) {
            $mod = $cash % $withdraw_info['value']["agent_withdraw_multiple"];
            if ($mod != 0) {
                $this->error = '所提取金额不是所规定的值';
               // return USER_WITHDRAW_BEISHU;
                return false;
            }
        }
        // 最小提现额判断
        if ($cash < $withdraw_info['value']["agent_withdraw_cash_min"]) {
            $this->error = '所提取金额低于最低提现额';
            return false;
           // return USER_WITHDRAW_MIN;
        }
        // 判断会员当前余额
        $agent_account = new AgentAccountHandle();

        $balance = $agent_account->getAgentBalance($agent_id);
        if ($balance <= 0) {
            $this->error = '余额不足';
            return false;
          //  return ORDER_CREATE_LOW_PLATFORM_MONEY;
        }

       // $poundage = $withdraw_info['value']['agent_withdraw_poundage'];
        if ($balance < $cash || $cash <= 0) {
            $this->error = '余额不足';
            return false;
           // return ORDER_CREATE_LOW_PLATFORM_MONEY;
        }
        // 获取 提现账户
        $agent_bank_account = new AgentBankAccountModel();
        $bank_account_info = $agent_bank_account->getInfo([
            'id' => $bank_account_id,
            'agent_id'=>$agent_id
        ], '*');

        if (empty($bank_account_info)) {
            $this->error = '指定银行帐户不存在';
            return false;
        }
        $poundage = $withdraw_info['value']['agent_withdraw_poundage'];
        if (empty($poundage)) {
            $poundage = 0;
        }
        if ($poundage > 1) {
            $poundage = 0;
        }
        $real_cash = $cash * (1 - $poundage);
        // 添加提现记录
        $balance_withdraw = new AgentWithdrawModel();
        $data = array(
            'withdraw_no' => $withdraw_no,
            'agent_id' => $agent_id,
            'agent_user_id'=>$agent_user_id,
            'bank_name' => $bank_account_info['branch_bank_name'],
            'account_number' => $bank_account_info['account_number'],
            'realname' => $bank_account_info['realname'],
            'mobile' => $bank_account_info['mobile'],
            'cash' => $cash,
            'real_cash'=>$real_cash,
            'ask_for_date' => time(),
            'status' => 0,
        );
       $res = $balance_withdraw->save($data);
        // 添加账户流水
       // $member_account->addMemberAccountData($shop_id, 2, $uid, 0, - $cash, 8, $balance_withdraw->id, "会员余额提现");
        if($balance_withdraw->id){
            $data['id'] = $balance_withdraw->id;
           // hook("memberWithdrawApplyCreateSuccess", $data);
        }
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 代理商提现审核
     * @see \data\api\niufenxiao\INfxUser::MemberBalanceWithdrawAudit()
     */
    public function agentBalanceWithdrawAudit($withdraw_id, $status, $audit_name, $memo='')
    {
        $agent_balance_withdraw = new AgentWithdrawModel();
        $retval = $agent_balance_withdraw->where(array(
            "id" => $withdraw_id
        ))->update(array(
            "status" => $status,
            'audit_name'=>$audit_name,
            'memo'=>$memo,
            'audit_date'=>time(),
            'update_time'=>time()
        ));

        if ($retval > 0 && $status == - 1) {
          //  $member_account->addMemberAccountData($shop_id, 2, $member_balance_withdraw_info['uid'], 1, $member_balance_withdraw_info["cash"], 9, $id, "会员余额提现退回");
        }
        if($retval > 0 && $status == 1){
            //会员提现审核通过钩子
           // hook('memberWithdrawAuditAgree', ['id' => $id]);
        }
        if (empty($retval)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 代理商提现计入
     * @see \data\api\niufenxiao\INfxUser::MemberBalanceWithdrawAudit()
     */
    public function agentBalanceWithdrawWrite($withdraw_id, $operator, $payment_date)
    {

        $agent_balance_withdraw = new AgentWithdrawModel();
        $agent_withdraw = $agent_balance_withdraw->get($withdraw_id);
        if (empty($agent_withdraw)) {
            $this->error = '未获取到数据';
            return false;
        }
        if ($agent_withdraw['status'] != 1) {
            $this->error = '不可进行提现计入操作';
            return false;
        }
        $this->startTrans();
        try {
            $retval = $agent_balance_withdraw->where(array(
                "id" => $withdraw_id,
                'status'=> 1
            ))->update(array(
                "status" => 2,
                'operator'=>$operator,
                'payment_date'=>getTimeTurnTimeStamp($payment_date),
                'update_time'=>time()
            ));

            if (empty($retval)) {
                $this->rollback();
                Log::write('agent_balance_withdraw->where 出错');
                return false;
            }

            $agent_id = $agent_withdraw['agent_id'];
            $withdraw_money = $agent_withdraw['cash'];
            $withdraw_time = $payment_date;

            $retval = $this->agentWithdraw($agent_id, $withdraw_money,$operator,$withdraw_time);

            if (empty($retval)) {
                $this->rollback();
                Log::write('this->agentWithdraw 出错');
                return false;
            }
            $this->commit();
            return true;

        } catch (Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 拒绝用户提现申请
     * @see \data\api\niufenxiao\INfxUser::MemberBalanceWithdrawRefuse()
     */
    public function userCommissionWithdrawRefuse($shop_id, $id, $status, $remark)
    {
        // TODO Auto-generated method stub
        $member_balance_withdraw = new NsMemberBalanceWithdrawModel();
        $member_account = new MemberAccount();
        $retval = $member_balance_withdraw->where(array(
            "shop_id" => $shop_id,
            "id" => $id
        ))->update(array(
            "status" => $status,
            "memo" => $remark
        ));
        $member_balance_withdraw = new NsMemberBalanceWithdrawModel();
        $member_balance_withdraw_info = $member_balance_withdraw->getInfo([
            'id' => $id
        ], '*');
        if ($retval > 0 && $status == - 1) {
            $member_account->addMemberAccountData($shop_id, 2, $member_balance_withdraw_info['uid'], 1, $member_balance_withdraw_info["cash"], 9, $id, "会员余额提现退回");
        }
        return $retval;
    }

    /**
     * ok-2ok
     * 得到代理商提现状态名
     * @param $status
     * @return string
     */
    private function getAgentWithdrawStatusName($status) {
        if ($status == 0) {
            return '等待审核';
        } else if ($status == 1) {
            return '已通过';
        } else if ($status == -1) {
            return '已拒绝';
        } else if ($status == 2) {
            return '已完成';
        } else {
            return $status;
        }
    }

    /**
     * ok-2ok
     * 获取代理商提现详情
     *
     * @see \data\api\niufenxiao\INfxUser::getMemberWithdrawalsDetails()
     */
    public function getAgentWithdrawDetails($withdraw_id)
    {
        $agent_balance_withdraw = new AgentWithdrawModel();
        $retval = $agent_balance_withdraw->getInfo([
            'id' => $withdraw_id
        ], '*');
        if (! empty($retval)) {
            $agent =$this->getAgentById($retval['agent_id']);
            $agent_user = $this->getAgentUserByUserId($retval['agent_user_id']);
            $retval['agent_name'] = $agent['agent_name'];
            $retval['agent_identify_code'] = $agent['identify_code'];
            $retval['agent_user_name'] = $agent_user['user_name'];
            $retval['status_name'] =$this->getAgentWithdrawStatusName($retval['status']);
            $retval['ask_for_date'] = getTimeStampTurnTime($retval['ask_for_date']);
            $retval['audit_date'] = getTimeStampTurnTime($retval['audit_date']);
            $retval['payment_date'] = getTimeStampTurnTime($retval['payment_date']);
        }
        return $retval;
    }

    /*************************** 代理商体验店相关***************************************/
    /**
     * ok-2ok
     * 新增代理商体验店
     */
    public function addAgentShop($agent_id,$title, $keyword,$shop_name, $shop_banner, $shop_address,
                                 $shop_lng, $shop_lat, $shop_phone, $contacts, $business_begin,$business_end,
                                 $shop_introduce, $shop_content, $status)
    {
       $agent_shop_model = new AgentShopModel();
        $agent_shop = $agent_shop_model->get(['agent_id'=> $agent_id]);
        if (!empty($agent_shop)) {
            $this->error = "此代理商已存在体验店";
            return false;
        }

        $data = array(
            'agent_id' => $agent_id,
            'title' => $title,
            'keyword'=>$keyword,
            'shop_name' => $shop_name,
            'shop_banner' => $shop_banner,
            'shop_address' => $shop_address,
            'shop_lng' => $shop_lng,
            'shop_lat' => $shop_lat,
            'shop_phone'=>$shop_phone,
            'contacts' => $contacts,
            'business_begin' =>$business_begin,
            'business_end' =>$business_end,
            'shop_introduce' =>$shop_introduce,
            'shop_content' =>$shop_content,
            'status' =>$status,

        );
        $res = $agent_shop_model->save($data);

        if($agent_shop_model->id){
            $data['id'] = $agent_shop_model->id;
            // hook("memberWithdrawApplyCreateSuccess", $data);
        }
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 更新代理商体验店
     */
    public function updateAgentShop($agent_shop_id, $agent_id,$title, $keyword,$shop_name, $shop_banner, $shop_address,
                                 $shop_lng, $shop_lat, $shop_phone, $contacts, $business_begin,$business_end,
                                 $shop_introduce, $shop_content, $status)
    {
        $agent_shop_model = new AgentShopModel();

        $data = array(
            'title' => $title,
            'keyword'=>$keyword,
            'shop_name' => $shop_name,
            'shop_banner' => $shop_banner,
            'shop_address' => $shop_address,
            'shop_lng' => $shop_lng,
            'shop_lat' => $shop_lat,
            'shop_phone'=>$shop_phone,
            'contacts' => $contacts,
            'business_begin' =>$business_begin,
            'business_end' =>$business_end,
            'shop_introduce' =>$shop_introduce,
            'shop_content' =>$shop_content,
            'status' =>$status,
            'update_time'=>time()

        );

        $res = $agent_shop_model->save($data, [
                'id'=>$agent_shop_id,
                'agent_id'=>$agent_id
            ]);

        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 更新代理商体验店状态
     */
    public function updateAgentShopStatus($agent_id, $agent_shop_id,  $status)
    {
        $agent_shop_model = new AgentShopModel();
        $data = array(
            'status' =>$status,
            'update_time'=>time()
        );
        $res = $agent_shop_model->save($data, [
            'id'=>$agent_shop_id,
            'agent_id'=>$agent_id
        ]);

        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * ok-2ok
     * 得到代理商体验店祥情
     * @param $condition
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function getAgentShopDetails($condition) {
        $agent_shop_model = new AgentShopModel();
        $agent_shop =  $agent_shop_model->getInfo($condition);
        if (!empty($agent_shop)) {
            $agent =$this->getAgentById($agent_shop['agent_id']);
            $agent_shop['agent_name'] = $agent['agent_name'];
            $agent_shop['agent_identify_code'] = $agent['identify_code'];
            $agent_shop['status_name'] = $this->agentShopStatusName($agent_shop['status']);
        }
        return $agent_shop;
    }

    /**
     * ok-2ok
     * 得到代理商体验店列表
     * @param int $page_index
     * @param int $page_size
     * @param string $condition
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getAgentShopList($page_index=1, $page_size=0, $condition='', $order='id desc',$field='*') {
        $agent_shop_model = new AgentShopModel();
        $agent_shop_list =  $agent_shop_model->pageQuery($page_index, $page_size, $condition, $order, $field);

        if (! empty($agent_shop_list['data'])) {
            foreach ($agent_shop_list['data'] as $k => $v) {
                $agent =$this->getAgentById($v['agent_id']);
                $agent_shop_list['data'][$k]['agent_name'] = $agent['agent_name'];
                $agent_shop_list['data'][$k]['agent_identify_code'] = $agent['identify_code'];
                $agent_shop_list['data'][$k]['status_name'] = $this->agentShopStatusName($v['status']);

            }
        }
        return $agent_shop_list;
    }

    /**
     * ok-2ok
     * 删除指定条件的代理商体验店
     * @param $agent_id
     * @param $agent_shop_id
     * @return bool
     */
    public function delAgentShop($agent_id, $agent_shop_id) {
        $agent_shop_model = new AgentShopModel();
        $condition = array (
            'id'=>$agent_shop_id,
            'agent_id'=>$agent_id
        );

        $retval = $agent_shop_model->destroy($condition);

        if (empty($retval)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 得到代理商体验店状态名
     * @param $status
     * @return string
     */
    private function agentShopStatusName($status) {
        if ($status == 0) {
            return '待审核';
        } else if ($status == 1) {
            return '正常';
        } else if ($status == 2) {
            return '未通过';
        } else if ($status == 3) {
            return '关闭';
        }
    }


    /**
     * ok-2ok
     * 加盟店升级
     * @param $agent_id
     * @return bool
     */
    public function agentUpgrade($agent_id) {
        $agent = $this->getAgentById($agent_id);
        if (empty($agent)) {
            $this->error = '指定的代理商不存在';
            return false;
        }

        $agent_type = $agent['agent_type'];
        if ($agent_type != 2) {
            $this->error = '非加盟店不可操作';
            return false;
        }

        $data = array(
            'agent_type'=>1,
            'p_agent_id'=> $this->getPlatformAgentId(),
            'update_time'=>time()
        );
        $agent_model = new AgentModel();
        $ret = $agent_model->save($data, ['id'=>$agent_id, 'agent_type'=>2]);
        if (empty($ret)) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * ok-2ok
     * 直接代理商降级
     * @param $agent_id
     * @param $p_agent_id
     * @return bool
     */
    public function agentDowngrade($agent_id, $p_agent_id) {
        $agent = $this->getAgentById($agent_id);
        if (empty($agent)) {
            $this->error = '指定的代理商不存在';
            return false;
        }

        $agent_type = $agent['agent_type'];
        if ($agent_type != 1) {
            $this->error = '非直接代理商,不可操作';
            return false;
        }

        $p_agent =  $this->getAgentById($p_agent_id);
        if (empty($p_agent)) {
            $this->error = '指定的上级代理商不存在';
            return false;
        }

        $p_agent_type = $p_agent['agent_type'];
        if ($p_agent_type != 1) {
            $this->error = '上级代理商不是直接代理商,不可操作';
            return false;
        }

        $this->startTrans();
        try {
            $data = array(
                'agent_type'=>2,
                'update_time'=>time()
            );
            $agent_model = new AgentModel();
            $ret = $agent_model->save($data, ['id'=>$agent_id, 'agent_type'=>1]);
            if (empty($ret)) {
                $this->rollback();
                Log::write('agent_model->save($data, [\'id\'=>$agent_id, \'agent_type\'=>1]) 出错');
                $this->error ='数据库不可操作';
                return false;
            }

            $data = array (
                'p_agent_id'=>$p_agent_id,
                'update_time'=>time()
            );

            $ids = $this->getAgentIdList(['p_agent_id'=>$agent_id]);
            if (empty($ids)) {
                $ids = (string)$agent_id;
            } else {
                $ids = $ids.','.$agent_id;
            }
            $ret = $agent_model->save($data, ['id'=>['in', $ids]]);

            if ($ret === false) {
                $this->rollback();
                Log::write('agent_model->save($data, [\'p_agent_id\'=>$agent_id]) 出错');
                $this->error ='数据库操作出错';
                return false;
            }

            $this->commit();
            return true;
        } catch (Exception $ex) {
            $this->rollback();
            $this->error = $ex->getMessage();
            return false;
        }
    }

    /**
     * ok-2ok
     * 更换加盟店的父级
     * @param $agent_id
     * @param $p_agent_id
     * @return bool
     */
    public function updatePAgent($agent_id, $p_agent_id) {
        $agent = $this->getAgentById($agent_id);
        if (empty($agent)) {
            $this->error = '指定的加盟店不存在';
            return false;
        }

        $agent_type = $agent['agent_type'];
        if ($agent_type != 2) {
            $this->error = '非加盟店,不可作此操作';
            return false;
        }

        $p_agent =  $this->getAgentById($p_agent_id);
        if (empty($p_agent)) {
            $this->error = '指定的上级代理商不存在';
            return false;
        }

        $p_agent_type = $p_agent['agent_type'];
        if ($p_agent_type != 1) {
            $this->error = '上级代理商不是直接代理商,不可作此操作';
            return false;
        }

        $data = array(
            'p_agent_id'=>$p_agent_id,
            'update_time'=>time()
        );
        $agent_model = new AgentModel();
        $ret = $agent_model->save($data, ['id'=>$agent_id, 'agent_type'=>2]);

        if (empty($ret)) {
            return false;
        } else {
            return true;
        }
    }


}