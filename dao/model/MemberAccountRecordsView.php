<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-10-27
 * Time: 16:57
 */

namespace dao\model;

use dao\model\BaseModel;


class MemberAccountRecordsView extends BaseModel
{
    protected $name = "member_account_records";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;

    /**
     * 获取列表返回数据格式
     */
    public function getViewList($page_index, $page_size, $condition, $order){

        $queryList = $this->getViewQuery($page_index, $page_size, $condition, $order);
        $queryCount = $this->getViewCount($condition);
        $list = $this->setReturnList($queryList, $queryCount, $page_size);
        return $list;
    }
    /**
     * 获取列表
     */
    public function getViewQuery($page_index, $page_size, $condition, $order)
    {
        //设置查询视图
        $viewObj = $this->alias('mar')
            ->join('xcshop_member_user mu','mar.user_id = mu.id','left')
            ->join('xcshop_member_info mi','mar.user_id = mi.user_id','left')
            ->field('mar.id, mar.user_id,  mar.account_type, mar.sign, mar.number, mar.from_type, mar.data_id, mar.remark, mar.create_time, mu.user_name, mu.login_phone, mi.nickname,  mu.email, mi.head_img');
        $list = $this->viewPageQuery($viewObj, $page_index, $page_size, $condition, $order);
        return $list;
    }
    /**
     * 获取列表数量
     */
    public function getViewCount($condition)
    {
        $viewObj = $this->alias('mar')
            ->join('xcshop_member_user mu','mar.user_id = mu.id','left')
            ->join('xcshop_member_info mi','mar.user_id = mi.user_id','left')
            ->field('mar.id');
        $count = $this->viewCount($viewObj,$condition);
        return $count;
    }

}