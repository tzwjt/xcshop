<?php
/**
 * 处理站点信息
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-11-25
 * Time: 19:08
 */

namespace dao\handle;

use dao\handle\BaseHandle;
use dao\model\SiteInfo as SiteInfoModel;
use dao\model\Module as ModuleModel;
use think\Session;



class SiteHandle extends BaseHandle
{
    private $site;

    private $module;

    function __construct()
    {
        parent::__construct();

        $this->site = new SiteInfoModel();
        $this->module = new ModuleModel();
    }

    /**
     * ok-2ok
     * 获取站点信息
     */
    public function getSiteInfo()
    {
        if (cache("SITEINFO")) {
            return cache("SITEINFO");
        } else {
            $info = $this->site->getInfo('');
            cache("SITEINFO", $info);
        }

        return cache("SITEINFO");
    }

    /**
     * ok-2ok
     * * 修改站点信息
     */
    public function updateSite($platform_site_name, $agent_site_name, $web_shop_site_name, $wap_shop_site_name,$platform_title,$agent_title,$web_shop_title,$wap_shop_title,
                        $logo, $web_desc,$shop_key_words,$web_icp,$web_icp_link,
                        $web_shop_style_id,$wap_shop_style_id,$web_address,$url_type,
                        $web_qrcode,$web_url, $web_shop_url,$wap_shop_url, $web_email,
                        $web_phone,$web_qq,$web_weixin,$third_count,$close_reason, $web_status,
                        $wap_shop_status, $web_shop_status,$style_id_admin,$url_type,$web_popup_title)    {
        $data = array(
            'platform_site_name'=>$platform_site_name,
            'agent_site_name'=>$agent_site_name,
            'web_shop_site_name'=>$web_shop_site_name,
            'wap_shop_site_name'=>$wap_shop_site_name,
            'platform_title' => $platform_title,
            'agent_title' => $agent_title,
            'web_shop_title' => $web_shop_title,
            'wap_shop_title' => $wap_shop_title,
            'logo' => $logo,
            'web_desc' => $web_desc,
            'shop_key_words' => $shop_key_words,
            'web_icp' => $web_icp,
            'web_icp_link'=>$web_icp_link,
            'web_shop_style_id' => $web_shop_style_id,
            'wap_shop_style_id' => $wap_shop_style_id,
            'web_address' => $web_address,
            'web_qrcode' => $web_qrcode,
            'web_url' => $web_url,
            'web_shop_url' => $web_shop_url,
            'wap_shop_url' => $wap_shop_url,
            'web_email' => $web_email,
            'web_phone' => $web_phone,
            'web_qq' => $web_qq,
            'web_weixin' => $web_weixin,
            'third_count' => $third_count,
            'close_reason' => $close_reason,
            'web_status' => $web_status,
            'wap_shop_status' => $wap_shop_status,
            'web_shop_status' => $web_shop_status,
            'style_id_admin' => $style_id_admin,
            'url_type' => $url_type,
             'web_popup_title' => $web_popup_title,
             'update_time'=>time()
        );
        $this->site = new SiteInfoModel();
        $site = $this->site->getInfo('');
        if (empty($site)) {
            $data['create_time']=time();
            $res = $this->site->save($data);
        } else {
            $res = $this->site->save($data, [
                "id" => $site['id']
            ]);
        }

        if ($res > 0) {
            cache("SITEINFO", null);
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 添加系统模块
     *
     * @see \data\api\IWebsite::addSytemModule()
     */
    public function addSytemModule($module_name, $controller, $method, $pid, $url, $is_menu, $is_dev, $sort, $module_picture, $desc, $icon_class, $is_control_auth)
    {
        // 查询level
        if ($pid == 0) {
            $level = 1;
        } else {
            $level = $this->getSystemModuleInfo($pid, $field = 'level')['level'] + 1;
        }
        $data = array(
            'module_name' => $module_name,
            'module' => \think\Request::instance()->module(),
            'controller' => $controller,
            'method' => $method,
            'pid' => $pid,
            'level' => $level,
            'url' => $url,
            'is_menu' => $is_menu,
            "is_control_auth" => $is_control_auth,
            'is_dev' => $is_dev,
            'sort' => $sort,
            'module_picture' => $module_picture,
            'desc' => $desc,
            'create_time' => time(),
            'icon_class' => $icon_class
        );
        $mod = new ModuleModel();
        $res = $mod->save($data);
        if (empty($res)) {
            return false;
        } else {
            $this->updateUserModule();
            return true;
        }
    }

    /**
     * ok-2ok
     * 修改系统模块
     */
    public function updateSystemModule($module_id, $module_name, $controller, $method, $pid, $url, $is_menu, $is_dev, $sort, $module_picture, $desc, $icon_class, $is_control_auth)
    {
        // 查询level
        if ($pid == 0) {
            $level = 1;
        } else {
            $level = $this->getSystemModuleInfo($pid, $field = 'level')['level'] + 1;
        }
        $data = array(
            'module_name' => $module_name,
            'module' => \think\Request::instance()->module(),
            'controller' => $controller,
            'method' => $method,
            'pid' => $pid,
            'level' => $level,
            'url' => $url,
            'is_menu' => $is_menu,
            "is_control_auth" => $is_control_auth,
            'is_dev' => $is_dev,
            'sort' => $sort,
            'module_picture' => $module_picture,
            'desc' => $desc,
            'update_time' => time(),
            'icon_class' => $icon_class
        );
        $mod = new ModuleModel();
        $res = $mod->allowField(true)->save($data, [
            'id' => $module_id
        ]);
        if (empty($res)) {
            return false;
        } else {
            $this->updateUserModule();
            return true;
        }
    }

    /**
     * ok-2ok
     * 删除系统模块
     */
    public function deleteSystemModule($module_id_array)
    {
        $sub_list = $this->getModuleListByParentId($module_id_array);
        if (! empty($sub_list)) {
            $this->error = '存在下级模块不可删除';
            return false;
           // $res = SYSTEM_DELETE_FAIL;
        } else {
            $res = $this->module->destroy($module_id_array);
        }
        if (empty($res)) {
            return false;
        } else {
            $this->updateUserModule();
            return true;
        }
    }

    /**
     * ok-2ok
     * 清除菜单
     */
    private function updateUserModule(){
        $module = request()->module();
        Session::set('module_list.'.$module.'module_list_0', '');
        $mod = new ModuleModel();
        $module_id_list = $mod->getConditionQuery('', 'id', '');

        foreach ($module_id_list as $k => $v)
        {
            Session::set('module_list.'.$module.'module_list_' . $v['id'], '');
        }
    }

    /**
     * ok-2ok
     * 获取系统模块
     *
     * @param unknown $module_id
     */
    public function getSystemModuleInfo($module_id, $field = '*')
    {
        $res = $this->module->getInfo(array(
            'id' => $module_id
        ), $field);
        return $res;
    }

    /**
     * ok-2ok
     * 修改系统模块 单个字段
     */
    public function modifyModuleField($module_id, $field_name, $field_value)
    {
        $res = $this->module->modifyTableField('id', $module_id, $field_name, $field_value);

        if (empty($res)) {
            return false;
        } else {
            $this->updateUserModule();
            return true;
        }
    }

    /**
     * ok-2ok
     * 获取系统模块列表
     */
    public function getSystemModuleList($page_index = 1, $page_size = 0, $condition = '', $order = '', $field = '*')
    {
        // 针对开发者模式处理
        if (! config('app_debug')) {
            if (is_array($condition)) {
                $condition = array_merge($condition, [
                    'is_dev' => 0
                ]);
            } else {
                if (! empty($condition)) {
                    $condition = $condition . ' and is_dev=0 ';
                } else {
                    $condition = 'is_dev=0';
                }
            }
        }
        $res = $this->module->pageQuery($page_index, $page_size, $condition, $order, $field);
        return $res;
    }

    /**
     * ok-2ok
     * 根据当前实例查询权限列表
     */
    public function getInstanceModuleQuery()
    {
        // 单用户查询全部
        $condition_module = array(
            'module' => \think\Request::instance()->module(),
            'is_control_auth' => 1
        );

        $field = 'id,sort, module_name,pid, level';
        $moduelList = $this->getSystemModuleList(1, 0, $condition_module, 'pid,sort,id', $field);
        return $moduelList['data'];
    }


    /**
     * (non-PHPdoc)
     *
     * @see \ata\api\IWebsite::addSystemInstance()
     */
    /*
    public function addSystemInstance($uid, $instance_name, $type)
    {
        $instance = new InstanceModel();
        $instance->startTrans();
        try {
            $instance_model = new InstanceModel();
            // 创建实例
            $data_instance = array(
                'instance_name' => $instance_name,
                'instance_typeid' => $type,
                'create_time' => time()
            );
            $instance_model->save($data_instance);
            $instance_id = $instance_model->instance_id;
            // 查询实例权限
            $instance_type_model = new InstanceTypeModel();
            $instance_type_info = $instance_type_model->get($type);
            // 创建管理员组
            $data_group = array(
                'instance_id' => $instance_id,
                'group_name' => '管理员组',
                'is_system' => 1,
                'module_id_array' => $instance_type_info['type_module_array'],
                'create_time' => time()
            );
            $user_group = new AuthGroupModel();
            $user_group->save($data_group);
            // 调整用户属性
            $user = new UserModel();
            $user->save([
                'is_system' => 1,
                'instance_id' => $instance_id
            ], [
                'uid' => $uid
            ]);
            // 添加后台用户
            $user_admin = new AdminUserModel();
            $data_admin = array(
                'uid' => $uid,
                'admin_name' => '',
                'group_id_array' => $user_group->group_id
            );
            $user_admin->save($data_admin);
            $instance->commit();
            return $instance_id;
        } catch (\Exception $e) {
            $instance->rollback();
            return $e->getMessage();
        }
    }

*/

    /**
     * 修改系统实例
     */
    /**
    public function updateSystemInstance()
    {}
**/
    /**
     * 获取系统实例
     *
     * @param unknown $instance_id
     */
    /**
    public function getSystemInstance($instance_id)
    {
        $instance = new InstanceModel();
        $info = $instance->get($instance_id);
        return $info;
    }
**/
    /**
     * 查询系统实例列表
     *
     * @param unknown $where
     * @param unknown $order
     * @param unknown $page_size
     * @param unknown $page_index
     */
    /**
    public function getSystemInstanceList($page_index = 1, $page_size = 0, $condition = '', $order = '', $field = '*')
    {
        $instance = new InstanceModel();
        $instance_list = $instance->pageQuery($page_index, $page_size, $condition, $order, $field);
        if (! empty($instance_list['data'])) {
            foreach ($instance_list['data'] as $k => $v) {
                $instance_type = new InstanceTypeModel();
                $type_name = $instance_type->getInfo([
                    'instance_typeid' => $v['instance_typeid']
                ], 'type_name');
                if (! empty($type_name['type_name'])) {
                    $v['type_name'] = $type_name['type_name'];
                } else {
                    $v['type_name'] = '';
                }
                $instance_list['data'][$k] = $v;
            }
        }
        return $instance_list;
    }
  **/

    /**
     * ok-2ok
     * 通过模块和方法查询权限(non-PHPdoc)
     */
    public function getModuleIdByModule($controller, $action)
    {
        $res = $this->module->getModuleIdByModule($controller, $action);
        return $res;
    }

    /**
     * ok-2ok
     * 查询权限节点的根节点
     *
     * @param unknown $module_id
     */
    public function getModuleRoot($module_id)
    {
        $root_id = $this->module->getModuleRoot($module_id);
        return $root_id;
    }

    /**
     * ok-2ok
     * 获取系统模块列表
     *
     * @param string $tpye
     *            0 debug模式 1 部署模式
     */
    public function getModuleListTree($type = 0)
    {
        $list = $this->module->order('pid,sort')->select();
        $new_list = $this->list_tree($list);

        return $new_list;
    }

    /**
     * ok-2ok
     * 数组转化为树
     */
    private function list_tree($list, $p_id = '0')
    {
        $tree = array();
        foreach ($list as $row) {
            if ($row['pid'] == $p_id) {
                $tmp = $this->list_tree($list, $row['id']);
                if ($tmp) {
                    $row['sub_menu'] = $tmp;
                } else {
                    $row['leaf'] = true;
                }
                $tree[] = $row;
            }
        }
        return $tree;
    }

    /**
     * ok-2ok
     * 获取下级列表子项
     */
    public function getModuleListByParentId($pid)
    {
        $list = $this->getSystemModuleList(1, 0, 'pid=' . $pid);
        return $list['data'];
    }

    /**
     * ok-2ok
     * 获取当前节点的根节点以及二级节点项(non-PHPdoc)
     */
    public function getModuleRootAndSecondMenu($module_id)
    {
        $count = $this->module->where([
            'id' => $module_id,
            'module' => \think\Request::instance()->module()
        ])->count();
        if ($count == 0) {
            return array(
                0,
                0
            );
        }
        $info = $this->module->getInfo([
            'id' => $module_id,
            'module' => \think\Request::instance()->module(),
            'pid' => array(
                'neq',
                0
            )
        ], 'pid, level');
        if (empty($info)) {
            return array(
                $module_id,
                0
            );
        } else {
            if ($info['level'] == 2) {
                return array(
                    $info['pid'],
                    $module_id
                );
            } else {
                $pid = $info['pid'];
                while ($pid != 0) {
                    $module = $this->module->getInfo([
                        'id' => $pid,
                        'module' => \think\Request::instance()->module(),
                        'pid' => array(
                            'neq',
                            0
                        )
                    ], 'pid, id, level');
                    if ($module['level'] == 2) {
                        $pid = 0;
                        return array(
                            $module['pid'],
                            $module['id']
                        );
                    } else {
                        $pid = $module['pid'];
                    }
                }
            }
        }
    }

    /**
     * 获取模板样式(non-PHPdoc)
     *
     * @see \ata\api\IWebsite::getWebStyle()
     */
    /**
    public function getWebStyle()
    {
        $config_style = ''; // 根据用户实例从数据库中获取样式，以及项目
        $style = \think\Request::instance()->module() . '/' . $config_style;
        return $style;
    }
**/
    /**
    public function getWebStyleList($condition)
    {
        $webstyle = new WebStyleModel();
        $style_list = $webstyle->getQuery($condition, '*', '');
        return $style_list;
    }
     * **/

    /**
     * ok-2ok
     * 获取站点信息
     */
    public function getSiteDetail()
    {
        $web_info = $this->site->getInfo(array());
        return $web_info;
    }

    /**
     * 获取伪静态配置列表
     */
    /**
    public function getUrlRouteList($page_index = 1, $page_size = 0, $condition = '', $order = '')
    {
        $url_route_model = new SysUrlRouteModel();
        $route_list = $url_route_model->pageQuery($page_index, $page_size, $condition, $order, '*');
        return $route_list;
    }
     * **/
    /**
     * 获取路由
     * @return Ambigous <mixed, \think\cache\Driver, boolean>
     */
    /**
    public function getUrlRoute(){
        $cache = Cache::get("url_route");
        if ($cache) {
            return $cache;
        } else {
            $url_route_model = new SysUrlRouteModel();
            $route_list = $url_route_model->pageQuery(1, 0, ['is_open' => 1], '', 'rule,route');
            Cache::set("url_route", $route_list);
            return $route_list;
        }


    }
     * **/
    /**
     * (non-PHPdoc)
     * @see \data\api\IWebsite::addUrlRoute()
     */
    /**
    public function addUrlRoute($rule, $route, $is_open,$route_model = 1)
    {
        cache("url_route", null);
    }
     * **/
    /**
     * (non-PHPdoc)
     * @see \data\api\IWebsite::updateUrlRoute()
     */
    /**
    public function updateUrlRoute($routeid, $rule, $route, $is_open, $route_model = 1)
    {
        cache("url_route", null);
    }
     * **/
}