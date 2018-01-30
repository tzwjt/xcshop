<?php
/**
 * 系统控制器
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-11-29
 * Time: 14:57
 */

namespace app\platform\controller;
use app\platform\controller\BaseController;
use dao\handle\SiteHandle;
use dao\handle\ShopSiteSettingHandle;
use think\exception\Handle;



class System extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * ok-2ok
     * 模块列表
     */
    public function moduleList()
    {
        $condition = array(
            'pid' => 0,
            'module' => $this->module
        );
        $site = new SiteHandle();
        $field = 'id,sort, module_name,pid, level, is_menu,is_dev,url';
        $frist_list = $site->getSystemModuleList(1, 0, $condition, 'pid,sort,id',$field);
        $frist_list = $frist_list['data'];
        $list = array();
        foreach ($frist_list as $k => $v) {
            $submenu = $site->getSystemModuleList(1, 0, 'pid=' . $v['id'], 'pid,sort,id',$field);
            $v['sub_menu'] = $submenu['data'];
            if (! empty($submenu['data'])) {
                foreach ($submenu['data'] as $ks => $vs) {
                    $sub_sub_menu = $site->getSystemModuleList(1, 0, 'pid=' . $vs['id'], 'pid,sort,id',$field);
                    $vs['sub_menu'] = $sub_sub_menu['data'];
                    if (! empty($sub_sub_menu['data'])) {
                        foreach ($sub_sub_menu['data'] as $kss => $vss) {
                            $sub_sub_sub_menu = $site->getSystemModuleList(1, 0, 'pid=' . $vss['id'], 'pid,sort,id',$field);
                            $vss['sub_menu'] = $sub_sub_sub_menu['data'];
                            if (! empty($sub_sub_sub_menu['data'])) {
                                foreach ($sub_sub_sub_menu['data'] as $ksss => $vsss) {
                                    $sub_sub_sub_sub_menu = $site->getSystemModuleList(1, 0, 'pid=' . $vsss['id'], 'pid,sort,id',$field);
                                    $vsss['sub_menu'] = $sub_sub_sub_sub_menu['data'];
                                }
                            }
                        }
                    }
                }
            }
        }
        $list = $frist_list;
        return json(resultArray(0,"操作成功",$list));
    }

    /**
     * ok-2ok
     * 添加模块
     */
    public function addModule()
    {
        $module_name = request()->post('module_name');
        $controller = request()->post('controller');
        $method = request()->post('method');
        $pid = request()->post('pid', '');
        $url = request()->post('url');
        $is_menu = request()->post('is_menu', 0);
        $is_control_auth = request()->post('is_control_auth', 0);
        $is_dev = request()->post('is_dev', 0);
        $sort = request()->post('sort', 0);
        $module_picture = request()->post('module_picture', '');
        $desc = request()->post('desc', '');
        $icon_class = '';
        $site = new SiteHandle();
        $retval = $site->addSytemModule($module_name, $controller, $method, $pid, $url, $is_menu, $is_dev, $sort, $module_picture, $desc, $icon_class, $is_control_auth);

        if (empty($retval)) {
            return json(resultArray(2,"操作失败 ".$site->getError()));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }


    /**
     * ok-2ok
     * 得到格式化的模块列表
     * @return \think\response\Json
     */
    public function getFormatModuleList() {
        $condition = array(
            'pid' => 0,
            'module' => $this->module
        );
        $site = new SiteHandle();
        $field = 'id,sort, module_name,pid, level';
        $frist_list = $site->getSystemModuleList(1, 0, $condition, 'pid,sort,id', $field);
        $frist_list = $frist_list['data'];
        $list = array();
        foreach ($frist_list as $k => $v) {
            $submenu = $site->getSystemModuleList(1, 0, 'pid=' . $v['id'], 'pid,sort,id', $field);
            $list[$k]['data'] = $v;
            $list[$k]['sub_menu'] = $submenu['data'];
        }

        return json(resultArray(0,"操作成功",$list));
    }

    /**
     * ok-2ok
     * 修改模块
     */
    public function editModule()
    {
        $module_id = request()->post('module_id');
        $module_name = request()->post('module_name');
        $controller = request()->post('controller');
        $method = request()->post('method');
        $pid = request()->post('pid');
        $url = request()->post('url');
        $is_menu = request()->post('is_menu');
        $is_control_auth = request()->post('is_control_auth');
        $is_dev = request()->post('is_dev');
        $sort = request()->post('sort');
        $module_picture = request()->post('module_picture');
        $desc = request()->post('desc');
        $icon_class = '';
        $site = new SiteHandle();

        $retval = $site->updateSystemModule($module_id, $module_name, $controller, $method, $pid, $url, $is_menu, $is_dev, $sort, $module_picture, $desc, $icon_class, $is_control_auth);

        if (empty($retval)) {
            return json(resultArray(2,"操作失败 ".$site->getError()));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }


    /**
     * ok-2ok
     * 得到模块详情用于修改
     * @return \think\response\Json
     */
    public function moduleDetails() {
        $module_id = request()->post('module_id', '');
        if (empty($module_id)) {
            return json(resultArray(2,"未指定模块"));
        }
        if (! is_numeric($module_id)) {
            return json(resultArray(2,"未获取到指定信息 "));
        }
        $site = new SiteHandle();
        $module_info = $site->getSystemModuleInfo($module_id);

        $condition = array(
            'pid' => 0,
            'module' => $this->module
        );
        $field = 'id, sort, module_name,pid, level';
        if ($module_info['level'] == 1) {
            $list = array();
        } else if ($module_info['level'] == 2) {
            $frist_list = $site->getSystemModuleList(1, 0, $condition, 'pid,sort,id',$field);
            $list = array();
            foreach ($frist_list['data'] as $k => $v) {
                $list[$k]['data'] = $v;
                $list[$k]['sub_menu'] = array();
            }
        } else if ($module_info['level'] == 3) {
            $frist_list = $site->getSystemModuleList(1, 0, $condition, 'pid,sort,id', $field);
            $frist_list = $frist_list['data'];
            $list = array();
            foreach ($frist_list as $k => $v) {
                $submenu = $site->getSystemModuleList(1, 0, 'pid=' . $v['id'], 'pid,sort,id',$field);
                $list[$k]['data'] = $v;
                $list[$k]['sub_menu'] = $submenu['data'];
            }
        }

        $data = array (
            'module_info'=>$module_info,
            'list'=>$list
        );

        return json(resultArray(0,"操作成功",$data));
    }

    /**
     * ok-2ok
     * 删除模块
     */
    public function delModule()
    {
        $module_id = request()->post('module_id');
        if (empty($module_id)) {
            return json(resultArray(2,"未指定模块"));
        }
        $site = new SiteHandle();
        $retval = $site->deleteSystemModule($module_id);
        if (empty($retval)) {
            return json(resultArray(2,"删除失败 ".$site->getError()));
        } else {
            return json(resultArray(0,"删除成功"));
        }
    }

    /**
     * ok-2ok
     * 获取指定模块的根菜单以及二级菜单
     * @return \think\response\Json
     */
    public function getModuleRootAndSecondMenu() {
        $module_id = request()->post('module_id');
        if (empty($module_id)) {
            return json(resultArray(2,"未指定模块"));
        }
        $site = new SiteHandle();
        $root_array = $site->getModuleRootAndSecondMenu($module_id);

        return json(resultArray(0,"操作成功",$root_array));
    }

    /**
     * ok-2ok
     * 得到指定模块的下级模块列表
     * @return \think\response\Json
     */
     public function getSonModuleList() {
         $module_id = request()->post('module_id');
         if (empty($module_id)) {
             return json(resultArray(2,"未指定模块"));
         }
         $site = new SiteHandle();
         $data = $site->getModuleListByParentId($module_id);
         return json(resultArray(0,"操作成功",$data));

     }

    /**
     * ok-2ok
     * 根据指定的controller和action得到相应的模块
     * @return \think\response\Json
     */
    public function getModuleIdByModule() {
        $controller = request()->post("controller");
        $action =  request()->post("action");

        $site = new SiteHandle();
        $data = $site->getModuleIdByModule($controller, $action);
        return json(resultArray(0,"操作成功",$data));
    }


    /**
     * ok-2ok
     * 新增商城首页层
     * @return \think\response\Json
     */
    public function addShopIndexBlock() {
        $request = request()->post();

        $sort = $request['sort'];//request()->post("sort");
        $position = $request['position'];//request()->post("position");
        $title = $request['title']; //request()->post("title");
        $type = $request['type']; //request()->post("type");
        $is_use = $request['is_use']; //request()->post("is_use");
        $content = $request['content']; //request()->post("content");

        $setting = new ShopSiteSettingHandle();
        $res = $setting->addShopIndexBlock($sort, $position, $title, $type, $is_use, $content);

        if (empty($res)) {
            return json(resultArray(2,"操作失败"));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * ok-2ok
     * 更新商城首页层
     * @return \think\response\Json
     */
    public function updateShopIndexBlock() {
        $request = request()->post();
        $block_id = $request['block_id']; //request()->post("block_id");
        $sort = $request['sort']; //request()->post("sort");
        $position = $request['position']; //request()->post("position");
        $title = $request['title']; //request()->post("title");
        $type = $request['type']; // request()->post("type");
        $is_use = $request['is_use']; // request()->post("is_use");
        $content = $request['content']; //request()->post("content");

        $setting = new ShopSiteSettingHandle();
        $res = $setting->updateShopIndexBlock($block_id, $sort, $position, $title, $type, $is_use, $content);

        if (empty($res)) {
            return json(resultArray(2,"操作失败"));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * ok-2ok
     * 得到首页层列表
     * @return \think\response\Json
     */
    public function shopIndexBlockList() {
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", PAGESIZE);
        $title = request()->post('title', '');
        $sort = request()->post('sort', '');

        $condition = array();
        if (!empty($title)) {
            $condition['title'] = ['like', '%'.$title.'%'];
        }
        if (!empty($sort)) {
            $condition['sort'] = $sort;
        }

        $setting = new ShopSiteSettingHandle();
        $list = $setting->getShopIndexBlockList($page_index, $page_size, $condition);

        return json(resultArray(0,"操作成功", $list));
    }

    /**
     * ok-2ok
     * 删除指定商城的首页层
     * @return \think\response\Json
     */
    public function delShopIndexBlock() {
        $block_id =  request()->post("block_id");
        if (empty($block_id)) {
            return json(resultArray(2,"未得到相关数据"));
        }

        $setting = new ShopSiteSettingHandle();
        $res = $setting->delShopIndexBlock($block_id);

        if (empty($res)) {
            return json(resultArray(2,"删除失败"));
        } else {
            return json(resultArray(0,"删除成功"));
        }
    }

    /**
     * ok-2ok
     * 得到指定商城首页层详情
     * @return \think\response\Json
     */
    public function shopIndexBlockDetails() {
        $block_id =  request()->post("block_id");
        if (empty($block_id)) {
            return json(resultArray(2,"未得到相关数据"));
        }
        $setting = new ShopSiteSettingHandle();
        $info = $setting->getShopIndexBlock($block_id);

        return json(resultArray(0,"操作成功", $info));
    }

    /**
     * ok-2ok
     * 设置商城首页层是否可用
     * @return \think\response\Json
     */
    public function setShopIndexBlockUse() {
        $block_id =  request()->post("block_id");
        if (empty($block_id)) {
            return json(resultArray(2,"未得到相关数据"));
        }

        $is_use = request()->post("is_use", 1);
        $setting = new ShopSiteSettingHandle();
        $res = $setting->setShopIndexBlockUse($block_id, $is_use);

        if (empty($res)) {
            return json(resultArray(2,"操作失败"));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * ok-2ok
     * 更改商城首页层的位置
     * @return \think\response\Json
     */
    public function updateShopIndexBlockPosition() {
        $block_id =  request()->post("block_id");
        if (empty($block_id)) {
            return json(resultArray(2,"未得到相关数据"));
        }

        $position = request()->post("position", '');

        if ($position === '') {
            return json(resultArray(2,"未指定位置"));
        }

        $setting = new ShopSiteSettingHandle();
        $res = $setting->updateShopIndexBlockPosition($block_id, $position);

        if (empty($res)) {
            return json(resultArray(2,"操作失败"));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * ok-2ok
     * 新增/编辑商城介绍
     * @return \think\response\Json
     */
    public function addAndEditShopIntroduce() {
        $introduce_id = request()->post("introduce_id", '');
       // $sort = request()->post("sort");
        $sort = 3;
        $title = request()->post("title");
        $pic = request()->post("pic");
        $is_use = request()->post("is_use");
        $content = request()->post("content");

        $setting = new ShopSiteSettingHandle();
        if (empty($introduce_id)) {
            $res = $setting->addShopIntroduce($sort, $title, $pic, $is_use, $content);
        } else {
            $res = $setting->updateShopIntroduce($introduce_id, $sort, $title, $pic, $is_use, $content);
        }

        if (empty($res)) {
            return json(resultArray(2,"操作失败"));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * ok-2ok
     * 得到商城介绍
     * @return \think\response\Json
     */
    public function shopIntroduce() {
        $setting = new ShopSiteSettingHandle();
        $condition = array(
            'sort'=>3
        );
        $shop_introduce = $setting->getShopIntroduce($condition, '*');
        if (empty($shop_introduce)) {
            $data = array(
                'sort' => '',
                'sort_name'=>'',
                'title'=>'',
                'pic'=>'',
                'content'=>'',
                'is_use'=>0
            );
        } else {
            $data = $shop_introduce;
        }
        return json(resultArray(0,"操作成功", $data));

    }

    /**
     * ok-2ok
     * 新增商城菜单
     * @return \think\response\Json
     */
    public function addShopMenu() {
        $sort = request()->post("sort");
        $position = request()->post("position");
        $menu_name = request()->post("menu_name");
        $menu_logo = request()->post("menu_logo");
        $menu_url = request()->post("menu_url");
        $is_use = request()->post("is_use");

        $setting = new ShopSiteSettingHandle();
        $res = $setting->addShopMenu($sort, $position, $menu_name,$menu_logo, $menu_url, $is_use);

        if (empty($res)) {
            return json(resultArray(2,"操作失败"));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * ok-2ok
     * 更新商城菜单
     * @return \think\response\Json
     */
    public function updateShopMenu() {
        $menu_id= request()->post("menu_id");
        $sort = request()->post("sort");
        $position = request()->post("position");
        $menu_name = request()->post("menu_name");
        $menu_logo = request()->post("menu_logo");
        $menu_url = request()->post("menu_url");
        $is_use = request()->post("is_use");

        $setting = new ShopSiteSettingHandle();
        $res = $setting->updateShopMenu($menu_id, $sort, $position, $menu_name,$menu_logo, $menu_url, $is_use);

        if (empty($res)) {
            return json(resultArray(2,"操作失败"));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * ok-2ok
     * 得到菜单列表
     * @return \think\response\Json
     */
    public function shopMenuList() {
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", PAGESIZE);
        $sort = request()->post('sort', '');

        $condition = array();
        if (!empty($sort)) {
            $condition['sort'] = $sort;
        }

        $setting = new ShopSiteSettingHandle();
        $order = 'sort asc, position asc';
        $list = $setting->getShopMenuList($page_index, $page_size, $condition, "*",  $order);

        return json(resultArray(0,"操作成功", $list));
    }

    /**
     * ok-2ok
     * 删除指定商城的菜单
     * @return \think\response\Json
     */
    public function delShopMenu() {
        $menu_id =  request()->post("menu_id");
        if (empty($menu_id)) {
            return json(resultArray(2,"未得到相关数据"));
        }

        $setting = new ShopSiteSettingHandle();
        $res = $setting->delShopMenu($menu_id);

        if (empty($res)) {
            return json(resultArray(2,"删除失败"));
        } else {
            return json(resultArray(0,"删除成功"));
        }
    }

    /**
     * ok-2ok
     * 得到指定商城菜单详情
     * @return \think\response\Json
     */
    public function shopMenuDetails() {
        $menu_id =  request()->post("menu_id");
        if (empty($menu_id)) {
            return json(resultArray(2,"未得到相关数据"));
        }
        $setting = new ShopSiteSettingHandle();
        $info = $setting->getShopMenu($menu_id);

        return json(resultArray(0,"操作成功", $info));
    }

    /**
     * ok-2ok
     * 设置商城菜单是否可用
     * @return \think\response\Json
     */
    public function setShopMenuUse() {
        $menu_id =  request()->post("menu_id");
        if (empty($menu_id)) {
            return json(resultArray(2,"未得到相关数据"));
        }

        $is_use = request()->post("is_use", 1);
        $setting = new ShopSiteSettingHandle();
        $res = $setting->setShopMenuUse($menu_id, $is_use);

        if (empty($res)) {
            return json(resultArray(2,"操作失败"));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }

    /**
     * ok-2ok
     * 更改商城菜单的位置
     * @return \think\response\Json
     */
    public function updateShopMenuPosition() {
        $menu_id =  request()->post("menu_id");
        if (empty($menu_id)) {
            return json(resultArray(2,"未得到相关数据"));
        }

        $position = request()->post("position");

        if (empty($position)) {
            return json(resultArray(2,"未指定位置"));
        }

        $setting = new ShopSiteSettingHandle();
        $res = $setting->updateShopMenuPosition($menu_id, $position);

        if (empty($res)) {
            return json(resultArray(2,"操作失败"));
        } else {
            return json(resultArray(0,"操作成功"));
        }
    }
}