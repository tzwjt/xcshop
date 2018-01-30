<?php
/**
 * MemberAccount.php--ok
 * 会员流水账户
 */

namespace dao\handle\member;
use dao\handle\BaseHandle as BaseHandle;
use dao\model\MemberAccountRecords as MemberAccountRecordsModel;
use dao\model\MemberAccount as MemberAccountModel;
use dao\model\PointConfig as PointConfigModel;
use dao\handle\member\MemberUserHandle as MemberUserHandle;
use think\Log;

class MemberAccountHandle extends BaseHandle
{ 
    function __construct(){
        parent::__construct();
    }

    /**
     * 添加会员消费 ok-2ok
     */
    public function addMmemberConsum($user_id, $consum){
        $account_statistics = new MemberAccountModel();
        $acount_info = $account_statistics->getInfo(['user_id'=> $user_id], '*');
        $data = array(
            'member_cunsum' => $acount_info['member_cunsum'] + $consum //会员消费金额
        );
        $retval = $account_statistics->save($data, ['user_id'=> $user_id]);
        if ($retval === false) {
            return false;
        } else {
            return true;
        }
        //return $retval;
    }


	/**
     * 添加账户流水--ok
     * 添加账户流水--ookkkk
     * @param  $account_type
     * @param  $user_id
     * @param  $sign
     * @param  $number(+-)
     * @param  $from_type
     * @param  $data_id
     * @param  $text
     */
//$account_flow->addMemberAccountData( 1, $order_info['buyer_id'], 1, $order_info['point'], 2, $orderid, '订单关闭返还积分');
    public function addMemberAccountData($account_type, $user_id, $sign, $number, $from_type, $data_id,$text,$operation_id=0)
    {
         if($account_type == 1)
        {
            $point_config = new PointConfigModel();
            $config_info = $point_config->getInfo('', 'is_open');
            /* if($config_info['is_open'] == 0)
            {
                //店铺关闭了积分兑换余额功能
                return CLOSE_POINT;
            } */
            
        } 

        if (empty($user_id)) {
            $this->error = "未指定会员用户";
            return false;
        }
        
        $member_account = new MemberAccountRecordsModel();
        $this->startTrans();
        try{
            //前期检测
            $account_statistics = new MemberAccountModel();
            $all_info = $account_statistics->getInfo(['user_id'=> $user_id], '*');
            if(empty($all_info))
            {
                $member_all_point = 0;
            }else{
                $member_all_point = $all_info['point'];
            }
            if(empty($all_info))
            {
                $member_all_balance = 0;
            }else{
                $member_all_balance = $all_info['balance'];
            }

            if(empty($all_info))
            {
                $member_all_coin = 0;
            }else{
                $member_all_coin = $all_info['coin'];
            }
            if($number < 0) //支出
            {

                if($account_type == 1){

                    if($number + $member_all_point < 0)
                    {
                        $this->error="帐户积分不足";
                        return false;
                       // return LOW_POINT;
                    }
                }elseif($account_type == 2){

                    if($number + $member_all_balance < 0)
                    {
                        $this->error="帐户余额不足";
                        return false;
                       // return LOW_BALANCE;
                    }
                } elseif($account_type == 3){

                    if($number + $member_all_coin < 0)
                    {
                        $this->error="购物币余额不足";
                        return false;
                        // return LOW_BALANCE;
                    }
                }
            }
            $data = array(
                 'serial_no'=> getSerialNo(), //生流水号
                 'operation_id' => $operation_id,
                 'account_type' => $account_type,  //'账户类型1.积分2.余额3.购物币 4,有效订单数',
                 'user_id' => $user_id,
                 'sign' => $sign,
                 'number' => $number,
                 'from_type' => $from_type, //'产生方式1.商城订单2.订单退还3.兑换4.充值5.签到6.分享7.注册8.提现9提现退还'
                'data_id' => $data_id,
                'remark' => $text,
            //  'create_time' => time()
             );
            $retval = $member_account->save($data);
        //更新对应会员账户
            if($account_type == 1)
            {
                //积分
                $account_statistics = new MemberAccountModel();
                $count = $account_statistics->where(['user_id'=> $user_id])->count();
                if($count == 0)
                {
                    $data = array(
                        'user_id' => $user_id,
                         'point' => $number,
                         'member_sum_point' => $number
                    );
                    $account_statistics->save($data);
                }else{
                   // $all_info = $account_statistics->getInfo(['user_id'=> $user_id], '*');
                    $data_member = array(
                        'point' =>$member_all_point + $number,
                       
                    );
                    if($number > 0)
                    {
                        //计算会员累计积分
                        $data_member['member_sum_point'] = $member_all_point + $number;         //$all_info['member_sum_point'] + $number;
                    }
                    $account_statistics->save($data_member,['user_id'=> $user_id]);
                }
                try {
                    $user_handle = new MemberUserHandle();
                    $user_handle->updateUserLevel($user_id); //升级用户等级
                } catch (\Exception $e) {
                    Log::write($e->getMessage());
                }
            }
            if($account_type == 2)
            {
                //余额
                $account_statistics = new MemberAccountModel();
                $count = $account_statistics->where(['user_id'=> $user_id])->count();
                if($count == 0)  //新增
                {
                    $data = array(
                        'user_id' => $user_id,
                       //  'shop_id' => 0,
                         'balance' => $number
                    );
                    $account_statistics->save($data);
                }else{
                    $data_member = array(
                        'balance' => $member_all_balance + $number
                    );
                    $account_statistics->save($data_member,['user_id'=> $user_id]);
                }
            }

            if($account_type == 3)
            {
                //购物币
                $account_statistics = new MemberAccountModel();
                $count = $account_statistics->where(['user_id'=> $user_id])->count();
                if($count == 0)  //新增
                {
                    $data = array(
                        'user_id' => $user_id,
                        //  'shop_id' => 0,
                        'coin' => $number
                    );
                    $account_statistics->save($data);
                }else{
                    $data_member = array(
                        'coin' => $member_all_coin + $number
                    );
                    $account_statistics->save($data_member,['user_id'=> $user_id]);
                }

            }


            if($account_type == 4)
            {
                //订单数
                $account_statistics = new MemberAccountModel();
                $count = $account_statistics->where(['user_id'=> $user_id])->count();
                if($count == 0)  //新增
                {
                    $xNumber = $number;
                    if ($xNumber < 0) {
                        $xNumber = 0;
                    }
                    $data = array(
                        'user_id' => $user_id,
                        //  'shop_id' => 0,
                        'member_sum_order' => $xNumber
                    );
                    $account_statistics->save($data);
                }else{
                    $xNumber =  $all_info['member_sum_order'] + $number;
                    if ($xNumber < 0) {
                        $xNumber = 0;
                    }
                    $data_member = array(
                        'member_sum_order' => $xNumber
                    );
                    $account_statistics->save($data_member,['user_id'=> $user_id]);
                }
            }
            $this->commit();
            return true;
        } catch (\Exception $e)
        {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
        }

    }

	/**
     * 获取会员在一段时间之内的账户 ok
     */
    public function getMemberAccount($user_id, $account_type, $start_time='', $end_time='')
    {
        $start_time = ($start_time == '') ? '2010-1-1' : $start_time;
        $end_time = ($end_time == '') ? 'now' : $end_time;
        $condition = array(
            'create_time' => array('EGT', $this->getTimeTurnTimeStamp($start_time)),
            'create_time' => array('LT', $this->getTimeTurnTimeStamp($end_time)),
            'account_type'=> $account_type,
            'user_id'         => $user_id,
        );
        $member_account = new MemberAccountRecordsModel();
        $account = $member_account->where($condition)->sum('number');
        if(empty($account))
        {
            $account = 0;
        }
        return $account;
        
    }

	 /**
     * 获取在一段时间之内用户的账户流水 -ok
     */
    public function getMemberAccountList($user_id, $account_type, $start_time, $end_time)
    {
        $start_time = ($start_time == '') ? '2010-1-1' : $start_time;
        $end_time = ($end_time == '') ? 'now' : $end_time;
        $condition = array(
            'create_time' => array('EGT', $start_time),
            'create_time' => array('LT', $end_time),
            'account_type'=> $account_type,
            'user_id'         => $user_id,
          //  'shop_id'     => $shop_id
        );
        $member_account = new MemberAccountRecordsModel();
        $list = $member_account->getConditionQuery($condition, '*', 'create_time desc');
        return $list;
    }

    /**
     * 积分转换成余额 ok
     */
    public function pointToBalance($point){
        $point_config = new PointConfigModel();
        $convert_rate = $point_config->getInfo([ 'is_open'=>1],'convert_rate');
        if(!$convert_rate || $convert_rate == ''){
            $convert_rate = 0;
        }
//         var_dump($convert_rate);
        $balance = $point * $convert_rate["convert_rate"];
        return $balance;
    }
    /**
     * 获取兑换比例 ok
     */
    public function getConvertRate(){
        $point_config = new PointConfigModel();
        $convert_rate = $point_config->getInfo([ 'is_open'=>1],'convert_rate');
        return $convert_rate;
    }

    /**
     * 获取购物币余额转化关系 ok
     */
    public function getCoinConvertRate(){
        /*
        $config = new ConfigModel();
        $config_rate = $config->getInfo(['key' => 'COIN_CONFIG'], '*');
        if(empty($config_rate))
        {
            return 1;
        }else{
            $rate = json_decode($config_rate['value'], true);
            return $rate['convert_rate'];
        }
        */
    }
    /**
     * 获取会员余额数 okkk-2ok
     */
    public function getMemberBalance($user_id)
    {
        $member_account = new MemberAccountModel();
        $balance = $member_account->getInfo(['user_id'=> $user_id], 'balance');
        if(!empty($balance))
        {
            return $balance['balance'];
        }else{
            return 0.00;
        }
    }

    /**
     * 获取会员购物币 ok-2okk
     */
    public function getMemberCoin($user_id)
    {
        $member_account = new MemberAccountModel();
        $coin = $member_account->getInfo(['user_id'=> $user_id], 'coin');
        if(!empty($coin))
        {
            return $coin['coin'];
        }else{
            return 0.00;
        }
    }

    /**
     * 获取会员积分 okkk-2ok
     */
    public function getMemberPoint($user_id)
    {
        $member_account = new MemberAccountModel();

            //查询全部积分
        $point = $member_account->where(['user_id'=> $user_id])->sum('point');
        if(!empty($point))
        {
                return $point;
        }else{
                return 0;
        }
      /*
            $point = $member_account->getInfo(['uid'=> $uid, 'shop_id' => $shop_id], 'point');
            if(!empty($point))
            {
                return $point['point'];
            }else{
                return 0;
            }
      */

    }

    /**
     * 获取会员销费总额 okkk-2ok
     */
    public function getMemberCunSum($user_id)
    {
        $member_account = new MemberAccountModel();
        $cunsum = $member_account->getInfo(['user_id'=> $user_id], 'member_cunsum');
        if(!empty($cunsum))
        {
            return $cunsum['member_cunsum'];
        }else{
            return 0.00;
        }
    }

    /**
     * 获取会员有效订单总数 okkk-2ok
     */
    public function getMemberOrderSum($user_id)
    {
        $member_account = new MemberAccountModel();
        $ordersum = $member_account->getInfo(['user_id'=> $user_id], 'member_sum_order');
        if(!empty($ordersum))
        {
            return $ordersum['member_sum_order'];
        }else{
            return 0;
        }
    }

    //ok
    public static function getMemberAccountRecordsName($from_type)
    {
        switch($from_type)
        {
                case 1:
                    $type_name = '商城订单';
                break;
                case 2:
                    $type_name = '订单退还';
                break;
                case 3:
                    $type_name = '兑换';
                    break;
                case 4:
                    $type_name = '充值';
                    break;
                case 5:
                    $type_name = '签到';
                    break;
                    
                case 6:
                    $type_name = '分享';
                    break;
                case 7:
                    $type_name = '注册';
                    break;
                case 8:
                    $type_name = '提现';
                    break;
                case 9:
                    $type_name = '提现退还';
                    break;
                case 10:
                    $type_name = '调整';
                    break;
               case 19:
                    $type_name = '点赞';
                    break;
               case 20:
                    $type_name = '评论';
                    break;
                default:
                    $type_name = '';
                    break;
        }
        return $type_name;

    }

    /**
     * 余额兑换为积分 ok
     * @param  $balance  余额
     */
     public function balanceToPoint($balance){
         /*
        $point_config = new NsPointConfigModel();
        $convert_rate = $point_config->getInfo([ 'is_open'=>1],'convert_rate');
        if(!$convert_rate || $convert_rate == ''){
            $convert_rate = 0;
        }
        //         var_dump($convert_rate);
        $point  = $balance / $convert_rate["convert_rate"];
        return $point;
         */
    }
}