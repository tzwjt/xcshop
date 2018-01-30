<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-10-07
 * Time: 16:52
 */

namespace dao\model;


use dao\model\BaseModel;


class AgentView extends BaseModel
{
    protected $name = "agent";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;

    /**
     * 获取列表返回数据格式
     */
    public function getAgentViewList($page_index, $page_size, $condition, $order,$field=''){

        $queryList = $this->getAgentViewQuery($page_index, $page_size, $condition, $order, $field);
        $queryCount = $this->getAgentViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }

    /**
     * 获取舍员列表
     */
    public function getAgentViewQuery($page_index, $page_size, $condition,  $order, $field='')
    {
        //设置查询视图
        if (empty($field) || $field == '*') {
            $field = 'ag.*, au.user_name, sp.province_name, sc.city_name, sd.district_name';
        }
        $viewObj = $this->alias('ag')
            ->join('xcshop_agent_user au','ag.id = au.agent_id','left')
         //   ->join('xcshop_agent_account aa','ag.id = aa.agent_id','left')
            ->join('xcshop_system_province sp',"ag.province_id = sp.id", 'left' )
            ->join('xcshop_system_city sc',"ag.city_id = sc.id", 'left' )
            ->join('xcshop_system_district sd',"ag.district_id = sd.id", 'left' )
            ->field($field);

        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }

    /**
     * 获取列表数量
     */
    public function getAgentViewCount($condition)
    {
        $viewObj = $this->alias('ag')
            ->join('xcshop_agent_user au','ag.id = au.agent_id','left')

            ->join('xcshop_system_province sp',"ag.province_id = sp.id", 'left' )
            ->join('xcshop_system_city sc',"ag.city_id = sc.id", 'left' )
            ->join('xcshop_system_district sd',"ag.district_id = sd.id", 'left' )
            ->field('ag.id');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }



}