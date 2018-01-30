<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-11-29
 * Time: 19:13
 */

namespace dao\handle;

use dao\handle\BaseHandle;
use dao\model\UserGroup as UserGroupModel;
use dao\model\PlatformUser as PlatformUserModel;


class UserGroupHandle extends BaseHandle
{
    private $usergroup;
    public function __construct(){
        parent::__construct();
        $this->usergroup = new UserGroupModel();
    }

    /**
     * ok-2ok
     * 获取系统用户组
     */
    public function getSystemUserGroupList($page_index=1, $page_size=0, $condition='', $order='', $field = "*"){

        $list = $this->usergroup->pageQuery($page_index, $page_size, $condition, $order, $field);
        return $list;
    }

    /**
     * ok-2ok
     * 添加系统用户组
     */
    public function addSystemUserGroup( $group_name, $is_system, $module_id_array, $desc){

        if (empty($group_name)) {
            $this->error = "用户组名不可为空";
            return false;
        }
        $count = $this->usergroup->getCount(['group_name' => $group_name]);
        if($count > 0)
        {
            $this->error = "用户组名已存在";
            return false;
           // return USER_GROUP_REPEAT;
        }
        $data = array(
            'group_name' => $group_name,
            'instance_id' =>  0, //$this->instance_id,
            'is_system' => $is_system,
            'module_id_array' => $module_id_array,
            'desc' => $desc,
            'create_time' => time()
        );
        $res = $this->usergroup->save($data);
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 修改系统用户组
     * @see \data\api\IAuthGroup::updateSystemUserGroup()
     */
    public function updateSystemUserGroup($group_id, $group_name, $group_status, $is_system, $module_id_array, $desc){
        if (empty($group_name)) {
            $this->error = "用户组名不可为空";
            return false;
        }

        $group_info = $this->usergroup->getInfo(['id' => $group_id], '*');
        if (empty($group_info)) {
            $this->error = "指定的用户组不存在";
            return false;
        }
        if($group_name != $group_info['group_name'])
        {
            $count = $this->usergroup->getCount(['group_name' => $group_name]);
            if($count > 0)
            {
                $this->error = "更新的用户组名已存在";
                return false;
            }
        }

        $data = array(
            'group_name' => $group_name,
            'group_status' => $group_status,
            'is_system' => $is_system,
            'module_id_array' => $module_id_array,
            'desc' => $desc,
            'update_time' => time()
        );
        $res = $this->usergroup->save($data,['id' => $group_id]);
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 修改系统用户组的状态
     */
    public function modifyUserGroupStatus($group_id, $group_status){
        $data = array(
            'group_status' => $group_status,
            'update_time'=>time()
        );
        $res = $this->usergroup->save($data, ['id' => $group_id]);
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 删除系统用户组
     */
    public function deleteSystemUserGroup($group_id){
        $count = $this->getUserGroupIsUse($group_id);
        if($count > 0)
        {
            $this->error ="指定的用户组已被使用，不可删除";
            return false;
        }

        $res = $this->usergroup->where('id',$group_id)->delete();
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 获取权限使用数量（0表示未使用）
     */
    private function getUserGroupIsUse($group_id)
    {
        $user_admin = new PlatformUserModel();
        $count = $user_admin->getCount(['user_group_id' => $group_id]);
        return $count;
    }

    /**
     * ok-2ok
     * 得到系统用户组的详情
     * @see \ata\api\IAuthGroup::getSystemUserGroupDetail()
     */
    public function getSystemUserGroupDetail($group_id){
        return $this->usergroup->get($group_id);
    }

    /**
     * ok-2ok
     * 获取多个主键的数据
     */
    public function getSystemUserGroupAll($where)
    {
        $all = $this->usergroup->all($where);
        return $all;
    }

}