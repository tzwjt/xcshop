<?php
/**
 * 会员管理视图
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-09-28
 * Time: 22:22
 */

namespace dao\model;

use dao\model\BaseModel;


class MemberView extends BaseModel
{
    protected $name = "member_user";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;

    /**
     * 获取列表返回数据格式
     */
    public function getMemberViewList($page_index, $page_size, $condition, $order, $field=''){

        $queryList = $this->getMemberViewQuery($page_index, $page_size, $condition, $order, $field);
        $queryCount = $this->getMemberViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }

    /**
     * 获取舍员列表
     */
    public function getMemberViewQuery($page_index, $page_size, $condition, $order, $field='')
    {
        //设置查询视图
        if ($field == '' || $field == '*' ) {
            $field = 'mu.id, mu.user_name, mu.login_phone, mu.email,  mu.agent_id,ag.agent_name, mu.user_type,  mu.status, mu.last_login_time,  mu.login_count, mu.create_time,'.
                ' mi.nickname, mi.head_img, mi.mobile, mi.real_name, mi.sex, sp.province_name, sc.city_name, sd.district_name,'.
                '  mu.member_level, ml.level_name, ml.goods_discount';
        }
        $viewObj = $this->alias('mu')
            ->join('xcshop_agent ag','mu.agent_id = ag.id','left')
            ->join('xcshop_member_level ml','mu.member_level = ml.id','left')
            ->join('xcshop_member_info mi','mu.id= mi.user_id','left')
            ->join('xcshop_system_province sp',"mi.province_id = sp.id", 'left' )
            ->join('xcshop_system_city sc',"mi.city_id = sc.id", 'left' )
            ->join('xcshop_system_district sd',"mi.district_id = sd.id", 'left' )
            ->field($field);

        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }

    /**
     * 获取列表数量
     */
    public function getMemberViewCount($condition)
    {
        $viewObj = $this->alias('mu')
            ->join('xcshop_agent ag','mu.agent_id = ag.id','left')
            ->join('xcshop_member_level ml','mu.member_level = ml.id','left')
            ->join('xcshop_member_info mi','mu.id= mi.user_id','left')
            ->join('xcshop_system_province sp',"mi.province_id = sp.id", 'left' )
            ->join('xcshop_system_city sc',"mi.city_id = sc.id", 'left' )
            ->join('xcshop_system_district sd',"mi.district_id = sd.id", 'left' )
            ->field('mu.id');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }



}