<?php
/**
 * Extend.php
 * @date : 2018.1.17
 * @version : v1.0.0.0
 */
namespace app\platform\controller;

use dao\model\Module as ModuleModel;
use dao\handle\ExtendHandle as ExtendHandle;
use dao\handle\SiteHandle as SiteHandle;
use think\Db;
use think\Request;

/**
 * 扩展模块控制器
 *
 * @author Administrator
 *        
 */
class Extend extends BaseController
{

    protected $extend;

    public function __construct()
    {
        $this->extend = new ExtendHandle();
        parent::__construct();
    }

    /**
     * ok-2ok
     * 插件管理
     */
    public function addonsList()
    {
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", PAGESIZE);
        $list = $this->extend->getAddonsList($page_index, $page_size);
        return json(resultArray(0, "操作成功", $list));
    }

    /**
     * 添加插件
     */
    /**
    public function addAddons()
    {
       // return view($this->style . "Extend/addAddons");
    }
*/
    /**
     * ok-2ok
     * 钩子管理
     */
    public function hooksList()
    {
        $page_index = request()->post('page_index', 1);
        $page_size = request()->post('page_size', 0);
        $list = $this->extend->getHooksList($page_index, $page_size);
        return json(resultArray(0, "操作成功", $list));
    }

    /**
     * 添加钩子
     */
    public function addHooks()
    {
        $name = request()->post('name');
        $desc = request()->post('desc');
        $type = request()->post('type');
        $res = $this->extend->addHooks($name, $desc, $type);

        if (empty($res)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 得到钩子详情用于修改
     */
    public function getHooksDetails()
    {
        $id = request()->post('id');
        $info = $this->extend->getHoodsInfo([
            'id' => $id
        ]);
        if (!empty($info['addons'])) {
            $info['addons'] = explode(',', $info['addons']);
        }
        return json(resultArray(0, "操作成功",$info));
    }

    /**
     * 修改钩子
     */
    public function updateHooks() {
        $id = request()->post('id');
        $name = request()->post('name');
        $desc = request()->post('desc');
        $type = request()->post('type');
        $addons = request()->post('addons','');

        if (empty($id)) {
            return json(resultArray(2, "未指定id"));
        }
        $res = $this->extend->editHooks($id, $name, $desc, $type, $addons);

        if (empty($res)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 删除 钩子
     */
    public function deleteHooks()
    {
        $id = request()->post('id');

        if (empty($id)) {
            return json(resultArray(2, "未指定id"));
        }

        $res = $this->extend->deleteHooks($id);
        if (empty($res)) {
            return json(resultArray(2, "删除失败"));
        } else {
            return json(resultArray(0, "删除成功"));
        }
    }

    /**
     * ok-2ok
     * 插件列表（某插件类型下的）
     */
    public function pluginList()
    {
        $id = request()->post('id');
        $list = $this->extend->getPluginList($id);

        if ($list === false) {
            return json(resultArray(2, "操作失败 ".$this->extend->getError(), ''));
        } else {
            return json(resultArray(0, "操作成功", $list));
        }
    }

    /**
     * ok-2ok
     * 安装插件
     */
    public function install()
    {
        $addon_name = trim(request()->post('addon_name'));
        $class = get_addon_class($addon_name);
        if (!class_exists($class)) {
            return json(resultArray(2, "插件不存在"));
        }

        $addons = new $class();
        $info = $addons->info;

        if (!$info) {  // 检测信息的正确性
            return json(resultArray(2, "插件信息缺失"));
        }

        session('addons_install_error', null);
        $install_flag = $addons->install();
        if (! $install_flag) {
            return json(resultArray(2, '执行插件预安装操作失败' . session('addons_install_error')));
        }
        
        // 判断是否有后台列表
        if (is_array($addons->admin_list) && $addons->admin_list !== array()) {
            $info['has_adminlist'] = 1;
        } else {
            $info['has_adminlist'] = 0;
        }
        // 获取菜单 如果有菜单配置则安装菜单
        if (is_array($addons->menu_info) && $addons->menu_info !== array()) {
            $menu = $addons->menu_info;
            $website = new SiteHandle();
            $module_model = new ModuleModel();
            foreach ($menu as $k => $v) {
                $parent_module_info = $module_model->getInfo([
                    'module_name' => $v['parent_module_name']
                ], 'id, controller');
                if (empty($parent_module_info)) {
                    // $addons->uninstall();
                    // $this->error('上级菜单不存在，请检查菜单配置');
                    // break;
                    $controller = $v['parent_module_name'];
                } else {
                    $controller = $parent_module_info['controller'];
                }
                
                $method = 'addonmenu';
                $url = $controller . '/menu_' . $controller . '?addons=' . $v['hook_name'];
                $last_module_id = $module_model->getInfo([
                    'module_name' => $v['last_module_name']
                ], 'id, sort');
                $res = $website->addSytemModule($v['module_name'], $controller, $method, $parent_module_info['module_id'], $url, $v['is_menu'], $v['is_dev'], $last_module_id['sort'], $v['module_picture'], $v['desc'], $v['icon_class'], $v['is_control_auth']);

                if (empty($res)) {
                    $addons->uninstall();
                    return json(resultArray(2, '安装菜单操作失败，请检查菜单配置' ));
                    break;
                }
            }
        }
        // 获取配置文件
        $info['config'] = json_encode($addons->getOneConfig());
        // 插件添加到库
        $res = $this->extend->addAddons($info['name'], $info['title'], $info['description'], $info['status'], $info['config'], $info['author'], $info['version'], $info['has_adminlist'], $info['has_addonslist'], $info['config_hook'], $info['content']);

        if (empty($res)) {
            return json(resultArray(2, '写入插件数据失败' ));
        } else {
            $hooks_update = $this->extend->updateHooks($addon_name);

            if (empty($hooks_update)) {
                $this->extend->deleteAddons([
                    'name' => $addon_name
                ]);
                return json(resultArray(2, '更新钩子处插件失败,请卸载后尝试重新安装' ));

            } else {
                cache('hooks', null);
                return json(resultArray(0, "安装成功"));
            }
        }
    }

    /**
     * ok-2ok
     * 卸载插件
     */
    public function uninstall()
    {
        $id = trim(request()->post('id'));
        $db_addons = $this->extend->getAddonsInfo([
            'id' => $id
        ], '*');
        $class = get_addon_class($db_addons['name']);
        // $this->assign ( 'jumpUrl', url ( 'index' ) );
        if (! $db_addons || ! class_exists($class)) {
            return json(resultArray(2, '插件不存在' ));
        }
        session('addons_uninstall_error', null);
        $addons = new $class();
        $uninstall_flag = $addons->uninstall();
        if (! $uninstall_flag) {
            return json(resultArray(2, '执行插件预卸载操作失败' . session('addons_uninstall_error')));
        }

            // 判断是否有菜单，有的话需要删除
        if (is_array($addons->menu_info) && $addons->menu_info !== array()) {
            $menu = $addons->menu_info;
            $module_model = new ModuleModel();
            foreach ($menu as $k => $v) {
                $method = 'addonmenu';
                $module_model->destroy([
                    'module_name' => $v['module_name'],
                    'method' => $method
                ]);
            }
        }
        $hooks_update = $this->extend->removeHooks($db_addons['name']);
        if ($hooks_update === false) {
            return json(resultArray(2, '卸载插件所挂载的钩子数据失败' ));
        }
        cache('hooks', null);
        $delete = $this->extend->deleteAddons([
            'name' => $db_addons['name']
        ]);
        if ($delete === false) {
            return json(resultArray(2, '卸载插件失败' ));
        } else {
            // 删除移动的资源文件
            // $File = new \com\File();
            // $File->del_dir('./static/addons/'.$db_addons ['name']);
            return json(resultArray(2, '卸载成功' ));
        }
    }

    /**
     * ok-2ok
     * 启用插件
     */
    public function enable()
    {
        $id = request()->post('id');
        cache('hooks', null);
        $res = $this->extend->updateAddonsStatus($id, 1);
        if (empty($res)) {
            return json(resultArray(2, '启用失败' ));
        } else {
            return json(resultArray(0, '启用成功' ));
        }
    }

    /**
     * ok-2ok
     * 禁用插件
     */
    public function disable()
    {
        $id = request()->post('id');
        cache('hooks', null);
        $res = $this->extend->updateAddonsStatus($id, 0);

        if (empty($res)) {
            return json(resultArray(2, '禁用失败' ));
        } else {
            return json(resultArray(0, '禁用成功' ));
        }
    }

    /**
     * ok-2ok
     * 安装插件 （某插件类型下）
     */
    public function installPlugin()
    {
        $id = request()->post('id', 0);
        $addon_name = $this->extend->getAddonsInfo([
            'id' => $id
        ], 'name');
        $addon_name = $addon_name['name'];
        $plugin_name = trim(request()->post('plugin_name', ''));
        if ($id == 0 || empty($addon_name) || $plugin_name == '') {
            return json(resultArray(2, '安装失败，参数错误' ));
        }
        $class = get_addon_class($addon_name);
        if (! class_exists($class)) {
            return json(resultArray(2, '插件不存在' ));
        }
        $addons = new $class();
        $table = $addons->table;
        $config_file = ADDON_PATH . $addon_name . '/' . $plugin_name . '/config.php';
        if (is_file($config_file)) {
            $temp_arr = include $config_file;
            $config_arr = array();
            foreach ($temp_arr['config'] as $key => $value) {
                $config_arr[$key] = $value['value'];
            }
            $data = [
                'name' => $temp_arr['name'],
                'title' => $temp_arr['title'],
                'config' => json_encode($config_arr),
                'status' => 1,
                'desc' => $temp_arr['desc'],
                'author' => $temp_arr['author'],
                'version' => $temp_arr['version'],
                'create_time' => time()
            ];
            $res = Db::table("$table")->insert($data);
            if ($res) {
                return json(resultArray(0, '安装成功' ));
            } else {
                return json(resultArray(2, '安装失败' ));
            }
        } else {
            return json(resultArray(2, '配置文件不存在' ));
        }
    }

    /**
     * ok-2ok
     * 卸载插件 （某插件类型下）
     */
    public function uninstallPlugin()
    {
        $addons_id = trim(request()->post('addons_id', 0));
        $plugin_id = trim(request()->post('plugin_id', 0));
        $addon_name = $this->extend->getAddonsInfo([
            'id' => $addons_id
        ], 'name');
        $addon_name = $addon_name['name'];
        
        if ($addons_id == 0 || empty($addon_name) || $plugin_id == 0) {
            return json(resultArray(2, '卸载失败，参数错误' ));
        }
        $class = get_addon_class($addon_name);
        if (! class_exists($class)) {
            return json(resultArray(2, '插件不存在' ));

        }

        $addons = new $class();
        $table = $addons->table;
        $res = Db::table("$table")->where('id', $plugin_id)->delete();
        if ($res) {
            return json(resultArray(0, '卸载成功' ));
        } else {
            return json(resultArray(2, '卸载失败' ));
        }
    }

    /**
     * ok-2ok
     * 启用插件 （某插件类型下）
     */
    public function enablePlugin()
    {
        $addons_id = trim(request()->post('addons_id', 0));
        $plugin_id = trim(request()->post('plugin_id', 0));
        $addon_name = $this->extend->getAddonsInfo([
            'id' => $addons_id
        ], 'name');
        $addon_name = $addon_name['name'];
        
        if ($addons_id == 0 || empty($addon_name) || $plugin_id == 0) {
            return json(resultArray(2, '启用失败，参数错误' ));
        }
        $class = get_addon_class($addon_name);
        if (! class_exists($class)) {
            return json(resultArray(2, '插件不存在' ));
        }


        $addons = new $class();
        $table = $addons->table;
        $res = Db::table("$table")->where('id', $plugin_id)->update([
            'status' => 1
        ]);
        if ($res) {
            return json(resultArray(0, '启用成功' ));
        } else {
            return json(resultArray(2, '启用失败' ));
        }
    }

    /**
     * ok-2ok
     * 禁用插件 （某插件类型下）
     */
    public function disablePlugin()
    {
        $addons_id = trim(request()->post('addons_id', 0));
        $plugin_id = trim(request()->post('plugin_id', 0));
        $addon_name = $this->extend->getAddonsInfo([
            'id' => $addons_id
        ], 'name');
        $addon_name = $addon_name['name'];
        
        if ($addons_id == 0 || empty($addon_name) || $plugin_id == 0) {
            return json(resultArray(2, '禁用失败，参数错误' ));
        }
        $class = get_addon_class($addon_name);
        if (! class_exists($class)) {
            return json(resultArray(2, '插件不存在' ));
        }

        $addons = new $class();
        $table = $addons->table;
        $res = Db::table("$table")->where('id', $plugin_id)->update([
            'status' => 0
        ]);
        if ($res) {
            return json(resultArray(0, '禁用成功' ));
        } else {
            return json(resultArray(2, '禁用失败' ));
        }
    }

    /**
     * ok-2ok
     * 得到配置信息
     * @return \think\response\Json
     */
    public function addonsConfig()
    {
        $addons_id = trim(request()->post('id', 0));
        $addon_info = $this->extend->getAddonsInfo([
            'id' => $addons_id
        ], '*');
        
        $addon_title = $addon_info['title'];
        $addon_name = $addon_info['name'];
        $config_book = $addon_info['config_hook'];

        if ($addons_id == 0 || empty($addon_name)) {
            return json(resultArray(2, '参数错误' ));
        }
        $config_file = ADDON_PATH . $addon_name . '/config.php';
        if (! is_file($config_file)) {
            return json(resultArray(2, '配置文件不存在' ));
        }
        $temp_arr = include $config_file;
        $db_config = $addon_info['config'];
        if ($db_config) {
            $db_config = json_decode($db_config, true);
            if ($db_config) {
                foreach ($temp_arr['config'] as $key => $value) {
                    $temp_arr['config'][$key]['value'] = $db_config[$key];
                }
            }
        }

        $data = array(
            'addons_id'=> $addons_id,
            'addon_title'=> $addon_title,
            'config_hook'=> $config_book,
            'data'=> $temp_arr
        );

        return json(resultArray(0, '操作成功', $data ));
    }

    /**
     * ok-2ok
     * 插件配置 （某插件类型下）
     */
    public function pluginConfig()
    {
        $addons_id = trim(request()->post('addons_id', 0));
        $plugin_id = trim(request()->post('plugin_id', 0));
        $addon_info = $this->extend->getAddonsInfo([
            'id' => $addons_id
        ], 'name, title');
        $addon_title = $addon_info['title'];
        $addon_name = $addon_info['name'];
        if ($addons_id == 0 || empty($addon_name) || $plugin_id == 0) {
            return json(resultArray(2, '参数错误' ));
        }

        $class = get_addon_class($addon_name);
        if (! class_exists($class)) {
            return json(resultArray(2, '插件不存在' ));
        }

        $addons = new $class();
        $table = $addons->table;
        $config_info = Db::table("$table")->where('id', $plugin_id)->find();
        if (empty($config_info)) {
            return json(resultArray(2, '该插件失效，请重新安装' ));
        }
        $config_file = ADDON_PATH . $addon_name . '/' . $config_info['name'] . '/config.php';
        $temp_arr = include $config_file;
        $db_config = $config_info['config'];
        if ($db_config) {
            $db_config = json_decode($db_config, true);
            foreach ($temp_arr['config'] as $key => $value) {
                $temp_arr['config'][$key]['value'] = $db_config[$key];
            }
        }

        $data = array(
            'addons_id' => $addons_id,
            'plugin_id' => $plugin_id,
            'addon_title' => $addon_title,
            'plugin_title' => $config_info['title'],
            'data' => $temp_arr
        );
        return json(resultArray(0, '操作成功', $data ));
    }

    /**
     * ok-2ok
     * 修改插件配置 表单提交
     */
    public function saveAddonsConfig()
    {
        $post = request()->post();
        $addons_id = $post['addons_id'];
        $config = json_encode($post['config']);
        $res = $this->extend->updateAddonsConfig([
            'id' => $addons_id
        ], $config);

        if (empty($res)) {
            return json(resultArray(2, '保存失败'));
        } else {
            return json(resultArray(0, '保存成功'));
        }
    }

    /**
     * ok-2ok
     * 修改插件配置 (某插件类型下) 表单提交
     */
    public function savePluginConfig()
    {
        $post = request()->post();
        $addons_id = $post['addons_id'];
        $plugin_id = $post['plugin_id'];
        $config = json_encode($post['config']);
        $addon_info = $this->extend->getAddonsInfo([
            'id' => $addons_id
        ], 'name, title');
        $addon_name = $addon_info['name'];
        if ($addons_id == 0 || empty($addon_name) || $plugin_id == 0) {
            return json(resultArray(2, '参数错误'));
        }
        $class = get_addon_class($addon_name);
        if (! class_exists($class)) {
            return json(resultArray(2, '插件不存在'));
        }
        $addons = new $class();
        $table = $addons->table;
        $res = Db::table("$table")->where('id', $plugin_id)->update([
            'config' => $config
        ]);
        if ($res) {
            return json(resultArray(0, '保存成功'));
        } else {
            return json(resultArray(2, '保存失败'));
        }
    }

    /**
     * ok-2ok
     * 获取插件详情
     */
    public function getAddonsDetail()
    {
        $id = request()->post('id', 0);
        if (empty($id)) {
            return json(resultArray(2, '参数错误'));
        }
        $extend = new ExtendHandle();
        $info = $extend->getAddonsInfo([
            'id' => $id
        ], '*');
        return json(resultArray(0, '操作成功',$info));
    }
}