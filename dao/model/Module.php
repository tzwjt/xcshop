<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-11-25
 * Time: 19:05
 */

namespace dao\model;

use dao\model\BaseModel;


class Module extends BaseModel
{
    protected $name = "system_module";

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;

    /**
     * ok-2ok
     * 通过模块方法查询权限id
     */
    public function getModuleIdByModule($controller, $action)
    {
        $condition = array(
            'controller' => $controller,
            'method' => $action,
            'module' => \think\Request::instance()->module()
        );
        $count = $this->where($condition)->count('id');
        if($count > 1)
        {
            $condition = array(
                'module' => \think\Request::instance()->module(),
                'controller' => $controller,
                'method' => $action,
                'pid' => array('<>', 0)
            );
        }
        $res = $this->where($condition)->find();
        return $res;
    }

    /**
     * ok-2ok
     * 查询权限节点的根节点
     * @param unknown $module_id
     */
    public function getModuleRoot($module_id)
    {
        $root_id = $module_id;
        $pid = $this->getInfo(['id' => $module_id], 'pid');
        $pid = $pid['pid'];
        if(empty($pid))
        {
            return 0;
        }
        while($pid != 0){
            $module= $this->getInfo(['id' =>$pid], 'pid, id');
            $root_id = $module['id'];
            $pid = $module['pid'];

        }
        return $root_id;
    }

    /**
     * ok-2ok
     * 通过权限id组查询权限列表
     * @param unknown $list_id_arr
     */
    public function getAuthList($pid)
    {
        $contdition = array(
            'pid' => $pid,
            'is_menu' => 1,
            'module'  => \think\Request::instance()->module()
        );
        $list = $this->where($contdition)->order("sort")->column('id,module_name,controller,method,pid,url,is_menu,is_dev,icon_class,is_control_auth');
        return $list;
    }


    /**
     * ok-2ok
     * 查询当前模块的上级ID
     * @param unknown $module_id
     */
    public function getModulePid($module_id)
    {
        $pid = $this->get($module_id);
        return $pid['pid'];
    }
}
