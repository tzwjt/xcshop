<?php
/**
 * 会员管理
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-28
 * Time: 21:59
 */

namespace app\platform\controller;

use dao\handle\AgentHandle;
use dao\handle\MemberHandle;
use dao\handle\member\MemberUserHandle;
use dao\handle\OrderHandle as OrderHandle;




class Member extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 会员列表主页
     */
    public function memberList()
    {
        $memberHandle = new MemberHandle();
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $search_text = request()->post('search_text', '');
        $level_id = request()->post('level_id', -1);
        $condition = [
            'mu.login_phone|mu.email|mi.nickname' => array(
                'like',
                '%' . $search_text . '%'
            )
        ];
        if ($level_id != -1) {
            $condition['ml.id'] = $level_id;
        }
        $list = $memberHandle->getMemberList($page_index, $page_size, $condition, 'mu.create_time desc');

        return json(resultArray(0, "操作成功", $list));

    }

    /**
     * 会员等级列表
     */
    public function getMemberLevelList()
    {
        $memberHandle = new MemberHandle();
        $list = $memberHandle->getMemberLevelList(1, 0);
        return json(resultArray(0, "操作成功", $list));
    }


    /**
     * 会员等级列表
     */
    /*
    public function memberLevelList()
    {
        $member = new MemberHandle();
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $list = $member->getMemberLevelList($page_index, $page_size);
        return json(resultArray(0, "操作成功", $list));
    }
*/
    /**
     * 查询单个 会员
     */
    public function getMemberDetail()
    {
        $memberHandle = new MemberHandle();
        $uid = request()->post("user_id", 0);
        $info = $memberHandle->getMemberDetail($uid);
        return json(resultArray(0, "操作成功", $info));

    }

    /**
     * 会员积分管理
     */
    public function pointList()
    {

        $page_index = request()->post("page_index", 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $search_text = request()->post('search_text', '');
        $form_type = request()->post('form_type', '');
        $start_date = request()->post('start_date') == "" ? 0 : request()->post('start_date');
        $end_date = request()->post('end_date') == "" ? 0 : request()->post('end_date');
        $condition['mar.account_type'] = 1;
        if ($form_type != '') {
            $condition['mar.from_type'] = $form_type;
        }
        if($start_date != 0 && $end_date != 0){
            $condition["mar.create_time"] = [
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
                $condition["mar.create_time"] = [
                    [
                        ">",
                        getTimeTurnTimeStamp($start_date)
                    ]
                ];
        }elseif($start_date == 0 && $end_date != 0){
                $condition["mar.create_time"] = [
                    [
                        "<",
                        getTimeTurnTimeStamp($end_date)
                    ]
                ];
        }
        $condition['mu.login_phone'] = [
                'like',
                '%' . $search_text . '%'
            ];

        $member = new MemberHandle();
        $list = $member->getPointList($page_index, $page_size, $condition, $order = '', $field = '*');
        return json(resultArray(0, "操作成功", $list));
    }

    /**
     * 会员积分明细
     */
    public function pointDetail()
    {

        $page_index = request()->post("page_index", 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $member_id = request()->post('member_id');
        $search_text = request()->post('search_text');
        $start_date = request()->post('start_date') == "" ? 0 : request()->post('start_date');
        $end_date = request()->post('end_date') == "" ? 0 : request()->post('end_date');
        $condition['mar.user_id'] = $member_id;
        $condition['mar.account_type'] = 1;
        if($start_date != 0 && $end_date != 0){
            $condition["mar.create_time"] = [
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
            $condition["mar.create_time"] = [
                    [
                        ">",
                        getTimeTurnTimeStamp($start_date)
                    ]
                ];
        }elseif($start_date == 0 && $end_date != 0){
            $condition["mar.create_time"] = [
                    [
                        "<",
                        getTimeTurnTimeStamp($end_date)
                    ]
                ];
        }
        $condition['mu.login_phone'] = [
                'like',
                '%' . $search_text . '%'
            ];

        $member = new MemberHandle();
        $list = $member->getPointList($page_index, $page_size, $condition, $order = '', $field = '*');
        return json(resultArray(0, "操作成功", $list));
    }

    /**
     * 会员余额明细
     */
    public function balanceDetail()
    {
        $member = new MemberHandle();
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $member_id = request()->post('member_id');
        $search_text = request()->post('search_text');
        $start_date = request()->post('start_date') == "" ? 0 : request()->post('start_date');
        $end_date = request()->post('end_date') == "" ? 0 : request()->post('end_date');
        $condition['mar.user_id'] = $member_id;
        $condition['mar.account_type'] = 2;
        if($start_date != 0 && $end_date != 0){
            $condition["mar.create_time"] = [
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
            $condition["mar.create_time"] = [
                    [
                        ">",
                        getTimeTurnTimeStamp($start_date)
                    ]
                ];
        }elseif($start_date == 0 && $end_date != 0){
            $condition["mar.create_time"] = [
                    [
                        "<",
                        getTimeTurnTimeStamp($end_date)
                    ]
                ];
        }
        $condition['mu.login_phone'] = [
                'like',
                '%' . $search_text . '%'
            ];


        $list = $member->getAccountList($page_index, $page_size, $condition, $order = '', $field = '*');
        return json(resultArray(0, "操作成功", $list));

    }

    /**
     * 会员余额管理
     */
    public function balanceList()
    {

        $member = new MemberHandle();
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $search_text = request()->post('search_text', '');
        $form_type = request()->post('form_type', '');
        $start_date = request()->post('start_date') == "" ? 0 : request()->post('start_date');
        $end_date = request()->post('end_date') == "" ? 0 : request()->post('end_date');

        $condition['mar.account_type'] = 2;
        $condition['mu.login_phone'] = [
                'like',
                '%' . $search_text . '%'
        ];
        if($start_date != 0 && $end_date != 0){
            $condition["mar.create_time"] = [
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
            $condition["mar.create_time"] = [
                    [
                        ">",
                        getTimeTurnTimeStamp($start_date)
                    ]
                ];
        }elseif($start_date == 0 && $end_date != 0){
                $condition["mar.create_time"] = [
                    [
                        "<",
                        getTimeTurnTimeStamp($end_date)
                    ]
                ];
        }
        if ($form_type != '') {
            $condition['mar.from_type'] = $form_type;
        }
        $list = $member->getAccountList($page_index, $page_size, $condition, $order = '', $field = '*');
        return json(resultArray(0, "操作成功", $list));
    }

    /**
     * 用户无效
     */
    public function memberInvalid()
    {
        $user_id = request()->post('user_id');
        $retval = false;
        if (! empty($user_id)) {
            $member = new MemberHandle();
            $retval = $member->userInvalid($user_id);
        }
        if (empty($retval)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * 用户有效
     */
    public function memberValid()
    {
        $user_id = request()->post('user_id');
        $retval = false;
        if (! empty($user_id)) {
            $member = new MemberHandle();
            $retval = $member->userValid($user_id);
        }
        if (empty($retval)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * 积分、余额调整
     */
    public function adjustMemberAccount()
    {
        $member = new MemberHandle();
        $user_id = request()->post('user_id');
        $type = request()->post('type');
        $num = request()->post('num');
        $text = request()->post('text','');
        $retval = $member->addMemberAccount( $type, $user_id, $num, $text);
        if (empty($retval)) {
            return json(resultArray(2, "操作失败 ").$member->getError());
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * 会员等级列表,进入会员等级页面
     */
    public function memberLevelList()
    {
        $member = new MemberHandle();
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $list = $member->getMemberLevelList($page_index, $page_size);
        return json(resultArray(0, "操作成功",$list));

    }

    /**
     * 添加会员等级
     */
    public function addMemberLevel()
    {
        $member = new MemberHandle();
        $level_name = request()->post("level_name");
        $min_integral = request()->post("min_integral", 0);
        $quota = request()->post("quota", 0);
        $upgrade = request()->post("upgrade");
        $goods_discount = request()->post("goods_discount", 100);
        $goods_discount = $goods_discount / 100;
        $desc = request()->post("desc", '');
        $relation = request()->post("relation", '');

           // addMemberLevel( $level_name, $min_integral, $quota, $upgrade, $goods_discount, $desc, $relation)
        $retval = $member->addMemberLevel( $level_name, $min_integral, $quota, $upgrade, $goods_discount, $desc, $relation);
        if (empty($retval)) {
            return json(resultArray(2, "操作失败 ").$member->getError());
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * 修改会员等级
     */
    public function updateMemberLevel()
    {
        $member = new MemberHandle();
        $level_id = request()->post("level_id");
        $level_name = request()->post("level_name");
        $min_integral = request()->post("min_integral");
        $quota = request()->post("quota");
        $upgrade = request()->post("upgrade");
        $goods_discount = request()->post("goods_discount");
        $goods_discount = $goods_discount / 100;
        $desc = request()->post("desc");
        $relation = request()->post("relation");
        // updateMemberLevel($level_id,  $level_name, $min_integral, $quota, $upgrade, $goods_discount, $desc, $relation)
        $retval = $member->updateMemberLevel($level_id, $level_name, $min_integral, $quota, $upgrade, $goods_discount, $desc, $relation);
        if (empty($retval)) {
            return json(resultArray(2, "操作失败 ") . $member->getError());
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * 得到会员等级详情用于修改
     * @return \think\response\Json|\think\response\View
     */
    public function memberLevelDetail() {
        $level_id = request()->post("level_id");
        $member = new MemberHandle();
        $info = $member->getMemberLevelDetail($level_id);
        $info['goods_discount'] = $info['goods_discount'] * 100;
        return json(resultArray(0, "操作成功", $info));
    }

    /**
     * 删除 会员等级
     */
    public function deleteMemberLevel()
    {
        $member = new MemberHandle();
        $level_id = request()->post("level_id");
        $retval = $member->deleteMemberLevel($level_id);
        if (empty($retval)) {
            return json(resultArray(2, "删除失败 ") . $member->getError());
        } else {
            return json(resultArray(0, "删除成功"));
        }
    }

    /**
     * 修改 会员等级 单个字段
     */
    public function modityMemberLevelField()
    {
        $member = new MemberHandle();
        $level_id = request()->post("level_id");
        $field_name = request()->post("field_name");
        $field_value = request()->post("field_value");
      //  modifyMemberLevelField($level_id, $field_name, $field_value)
        $res = $member->modifyMemberLevelField($level_id, $field_name, $field_value);
        if (empty($retval)) {
            return json(resultArray(2, "删除失败 ") . $member->getError());
        } else {
            return json(resultArray(0, "删除成功"));
        }
    }

    /**
     * 得到会员的订单列表
     */
    public function getOrderListByMember() {
        $member_id = request()->post("member_id");
        $page_index = request()->post("page_index",1);
        $page_size = request()->post("page_size",PAGESIZE);
        $condition = ['buyer_id' => $member_id];
        $order = new OrderHandle();
        $order_list = $order->getOrderList($page_index, $page_size, $condition, 'id desc ' );
        return json(resultArray(0, "操作成功", $order_list));
    }

    /**
     * ok-2ok
     * 得到会员统计数据
     * @return \think\response\Json
     */
    public function getMemeberCount() {
        $member_handle = new MemberHandle();
        $condition = "";
        $total_count = $member_handle->getMemberCount($condition);
        $condition=array(
            'status'=>1
        );
        $valid_total_count = $member_handle->getMemberCount($condition);
        /**
        $condition=array(
            'status'=>0
        );
        $invalid_total_count = $member_handle->getMemberCount($condition);
        **/



        $agent_handle = new AgentHandle();

        $condition=array(
            'status'=>1,
            'agent_id'=> $agent_handle->getPlatformAgentId()
        );
        $first_valid_total_count = $member_handle->getMemberCount($condition);
        $condition=array(
            'status'=>1,
            'agent_id'=>['<>', $agent_handle->getPlatformAgentId()]
        );
        $second_valid_total_count = $member_handle->getMemberCount($condition);

        $data = array (
            'member_total_count'=>$total_count,
            'member_valid_total_count'=> $valid_total_count,
            'platfor_member_valid_total_count'=> $first_valid_total_count,
            'agent_member_valid_total_count'=> $second_valid_total_count,

        );

        return json(resultArray(0, "操作成功", $data));
    }


}