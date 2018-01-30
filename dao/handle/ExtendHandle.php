<?php
/**
 * ExpressHandle.php
 * @date : 2018.1.17
 * @version : v1.0.0.0
 */
namespace dao\handle;

/**
 * 扩展（插件与钩子）
 */
use dao\handle\BaseHandle as BaseHandle;
use dao\model\Addons as AddonsModel;
use dao\model\Hooks as HooksModel;
use dao\model\BaseModel;
use think\Db;
use think\Log;


class ExtendHandle extends BaseHandle
{

    /**
     * ok-2ok
     * 获取插件列表
     * @param int $page_index
     * @param mixed $page_size
     * @param string $condition
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getAddonsList($page_index = 1, $page_size = PAGESIZE, $condition = '', $order = 'create_time desc', $field = '*'){
        $sys_addons = new AddonsModel();
        if($page_size == 0){
            $page_size = PAGESIZE;
        }
        $addon_dir = "";
        $data = "";
        if (!$addon_dir)
            $addon_dir = ADDON_PATH;
        $dirs = array_map ( 'basename', glob ( $addon_dir . '*', GLOB_ONLYDIR ) );
        Log::write("dirs:".json_encode($dirs));

        if ($dirs === FALSE || ! file_exists ( $addon_dir )) {
            $this->error = '插件目录不可读或者不存在';
            return FALSE;
        }
        $addons = array ();

        //$where ['name'] = array ('in', $dirs);
        $condition ['name'] = array ('in', $dirs);

        $list = $sys_addons->getConditionQuery($condition, $field, $order);

        foreach ( $list as $key => $value ) {
            $list [$key] = $value->toArray ();  //对象转数组
        }
        foreach ( $list as $addon ) {
            $addon ['uninstall'] = 0;
            $addons [$addon ['name']] = $addon;
        }
        
        foreach ( $dirs as $value ) {
            if (! isset ( $addons [$value] )) {
                Log::write("value:".$value);
                $class = get_addon_class ( $value );
                Log::write("class:".$class);

                if (! class_exists ( $class )) { // 实例化插件失败忽略执行
                    trace($class);
                    \think\Log::record ( '插件' . $value . '的入口文件不存在！' );
                    continue;
                }

                $obj = new $class ();
                $addons [$value] = $obj->info;
                if ($addons [$value]) {
                    $addons [$value] ['uninstall'] = 1;
                    unset ( $addons [$value] ['status'] );
                }
            }
        }
        $addons = $this->list_sort_by ( $addons, 'uninstall', 'desc' );

        $new_array = [];
        //总条数
        $total_count = count($addons);
        //总页数
        $page_count = ceil($total_count/$page_size);
        //获取当前数组键值开始与结束
        $key_start = ($page_index-1) * $page_size;
        $key_end = $page_index * $page_size - 1;

        for ($i = $key_start; $i <= $key_end; $i++){
            if(!empty($addons[$i])){
                $data[$i] = $addons[$i];
              //  Log::write("data:".json_encode($data[$i]));
            }
        }
        $new_array['data'] = $data;
        $new_array['total_count'] = $total_count;
        $new_array['page_count'] = $page_count;
        return $new_array;
    }

    /**
     * ok-2ok
     * 安装插件
     * @param $name
     * @param $title
     * @param $description
     * @param $status
     * @param $config
     * @param $author
     * @param $version
     * @param $has_adminlist
     * @param $has_addonslist
     * @param $config_hook
     * @param $content
     * @return bool
     */
    public function addAddons($name, $title, $description, $status, $config, $author, $version, $has_adminlist, $has_addonslist, $config_hook, $content){
        $sys_addons = new AddonsModel();
        $data = array(
            'name' => $name, 
            'title' => $title, 
            'description' => $description, 
            'status' => $status, 
            'config' => $config, 
            'author' => $author, 
            'version' => $version, 
            'has_adminlist' => $has_adminlist, 
            'has_addonslist' => $has_addonslist,
            'config_hook' => $config_hook,
            'content' => $content,
            'create_time' => time(),
            'update_time'=> time()
        );
        $res = $sys_addons->save($data);

        if ($res == 1) {
            return true;
        } else {
            return false;
        }
       // return $sys_addons->id;
    }

    /**
     * ok-2ok
     * 更新插件里的所有钩子对应的插件
     * @param $addons_name
     * @return bool
     */
    public function updateHooks($addons_name){
        $sys_hooks = new HooksModel();
        $addons_class = get_addon_class($addons_name);//获取插件名
        if(!class_exists($addons_class)){
            $this->error = "未实现{$addons_name}插件的入口文件";
            return false;
        }
        $methods = get_class_methods($addons_class);
        $hooks = $sys_hooks->column('name');
        $common = array_intersect($hooks, $methods);//对比返回交集
        if(!empty($common)){
            foreach ($common as $hook) {
                $flag = $this->updateAddons($hook, array($addons_name));
                if(false === $flag){
                    $this->removeHooks($addons_name);
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * ok-2ok
     * 更新单个钩子处的插件
     * @param $hook_name
     * @param $addons_name
     * @return bool
     */
    public function updateAddons($hook_name, $addons_name){
        $sys_hooks = new HooksModel();
        $hooks_info = $sys_hooks->getInfo(['name' => $hook_name], 'addons');

        $o_addons = $hooks_info['addons'];
        if($o_addons){
            $o_addons = explode(',', $o_addons);
        }
        if($o_addons){
            $addons = array_merge($o_addons, $addons_name);
            $addons = array_unique($addons);
        }else{
            $addons = $addons_name;
        }
        $addons = implode(',', $addons);
        if($o_addons){
            $o_addons = implode(',', $o_addons);
        }
        $res = $sys_hooks->save(['addons' => $addons, 'update_time'=>time()], ['name' => $hook_name]);
        if(false === $res){
            $sys_hooks->save(['addons' => $o_addons, 'update_time'=>time()], ['name' => $hook_name]);
        }

        if ($res > 0) {
            return true;
        } else {
            return false;
        }
      //  return $res;
    }

    /**
     * ok-2ok
     * 去除插件所有钩子里对应的插件数据
     * @param $addons_name
     * @return bool
     */
    public function removeHooks($addons_name){
        $sys_hooks = new HooksModel();
        $addons_class = get_addon_class($addons_name);
        if(!class_exists($addons_class)){
            return false;
        }
        $methods = get_class_methods($addons_class);
        $hooks = $sys_hooks->column('name');
        $common = array_intersect($hooks, $methods);
        if($common){
            foreach ($common as $hook) {
                $flag = $this->removeAddons($hook, array($addons_name));
                if(false === $flag){
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * ok-2ok
     * 去除单个钩子里对应的插件数据
     * @param $hook_name
     * @param $addons_name
     * @return bool
     */
    public function removeAddons($hook_name, $addons_name){
        $sys_hooks = new HooksModel();
        $hooks_info = $sys_hooks->getInfo(['name' => $hook_name], 'addons');

        $o_addons = explode(',', $hooks_info['addons']);
        if($o_addons){
            $addons = array_diff($o_addons, $addons_name);
        }else{
            return true;
        }
        $addons = implode(',', $addons);
        $o_addons = implode(',', $o_addons);
        $flag = $sys_hooks->save(['addons' => $addons, 'update_time'=>time()], ['name' => $hook_name]);
        if(false === $flag){
             $sys_hooks->save(['addons' => $o_addons, 'update_time'=>time()], ['name' => $hook_name]);
        }
        if ($flag > 0) {
            return true;
        } else {
            return false;
        }

     //   return $flag;
    }

    /**
     * ok-2ok
     * 删除插件
     * @param $condition
     * @return bool
     */
    public function deleteAddons($condition){
        $sys_addons = new AddonsModel();
        $res = $sys_addons->destroy($condition);

        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 获取单条插件信息
     * @param $condition
     * @param string $field
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function getAddonsInfo($condition, $field = '*'){
        $sys_addons = new AddonsModel();
        return $sys_addons->getInfo($condition, $field);
    }

    /**
     * ok-2ok
     * 修改插件状态
     * @param $id
     * @param $status
     * @return bool
     */
    public function updateAddonsStatus($id, $status){
        $sys_addons = new AddonsModel();
        $res = $sys_addons->save(['status' => $status, 'update_time'=>time()], ['id' => $id]);
        if ($res == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 获取某插件类型下 的插件列表
     * @param $id
     * @return array|bool
     */
    public function getPluginList($id){
        $sys_addons = new AddonsModel();
        $addons_info = $sys_addons->getInfo(['id' => $id], 'name');
        
        $addon_name = $addons_info['name'];
        
        $addon_dir = ADDON_PATH . $addon_name . '/';
        
        $dirs = array_map ( 'basename', glob ( $addon_dir . '*', GLOB_ONLYDIR ) );
        if ($dirs === FALSE || ! file_exists ( $addon_dir )) {
            $this->error = '插件目录不可读或者不存在';
            return FALSE;
        }
        $addon_type_class = get_addon_class($addon_name);
        if (! class_exists ( $addon_type_class )) { // 实例化插件失败忽略执行
            trace($addon_type_class);
            \think\Log::record ( '插件' . $addon_name . '的入口文件不存在！' );
           // \think\Log::record ( '插件' . $value . '的入口文件不存在！' );
            return false;
//             continue;
        }
        $obj = new $addon_type_class ();
        $table = $obj->table;
        
        $addons = array (); //已安装的数组
//         var_dump($dirs);
        $where ['name'] = array ('in', $dirs);
        $list = Db::table("$table")->where($where)->select();
        foreach ( $list as $addon ) {
            $addon ['uninstall'] = 0;
            $addons [$addon ['name']] = $addon;
        }
        
        foreach ( $dirs as $value ) {
            if (! isset ( $addons [$value] ) && ($value != 'core')) {
                //不在已安装插件数组中
                //读取配置文件
                $temp_arr = array();
                if (is_file($addon_dir.$value.'/config.php')) {
                    $temp_arr = include $addon_dir.$value.'/config.php';
                }
                $addons [$value] = $temp_arr;
            }
        }
        $addons = $this->list_sort_by($addons, 'id');
        return $addons;
    }

    /**
     * ok-2ok
     * 获取钩子列表
     * @param int $page_index
     * @param int $page_size
     * @param string $condition
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getHooksList($page_index = 1, $page_size = 0, $condition = '', $order = '', $field = '*'){
        $sys_hooks = new HooksModel();
        return $sys_hooks->pageQuery($page_index, $page_size, $condition, $order, $field);
    }

    /**
     * ok-2ok
     * 获取钩子详情
     * @param $condition
     * @param string $field
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function getHoodsInfo($condition, $field = '*'){
        $sys_hooks = new HooksModel();
        $info = $sys_hooks->getInfo($condition, $field);
        return $info;
    }

    /**
     * ok-2ok
     * 添加钩子
     * @param $name
     * @param $description
     * @param $type
     * @return bool
     */
    public function addHooks($name, $description, $type){
        $sys_hooks = new HooksModel();
        $data = array(
            'name' => $name,
            'description' => $description,
            'type' => $type,
            'create_time' => time(),
            'update_time' => time(),
        );
       $res =  $sys_hooks->save($data);

        if ($res == 1) {
            return true;
        } else {
            return false;
        }
      //  return $sys_hooks->id;
    }

    /**
     * ok-2ok
     * 修改钩子
     * @param $id
     * @param $name
     * @param $description
     * @param $type
     * @param $addons
     * @return bool
     */
    public function editHooks($id, $name, $description, $type, $addons){
        $sys_hooks = new HooksModel();
        $data = array(
            'name' => $name,
            'description' => $description,
            'type' => $type,
//             'addons' => $addons,
            'update_time' => time(),
        );
        $res = $sys_hooks->save($data, ['id' => $id]);

        if ($res == 1) {
            return true;
        } else {
            return false;
        }
        //return $res;
    }

    /**
     * ok-2ok
     * 删除钩子
     * @param $id
     * @return bool
     */
    public function deleteHooks($id){
        $sys_hooks = new HooksModel();
        $res =  $sys_hooks->destroy($id);
        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 修改插件配置
     * @param $condition
     * @param $config
     * @return bool
     */
    public function updateAddonsConfig($condition, $config){
        $sys_addons = new AddonsModel();
        $res =$sys_addons->save(['config' => $config, 'update_time' => time()], $condition);
        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 重新排序
     * @param  $list
     * @param  $field
     * @param string $sortby
     */
    protected function list_sort_by($list,$field, $sortby='asc') {
        if(is_array($list)){
            $refer = $resultSet = array();
            foreach ($list as $i => $data)
                $refer[$i] = &$data[$field];
            switch ($sortby) {
                case 'asc': // 正向排序
                    asort($refer);
                    break;
                case 'desc':// 逆向排序
                    arsort($refer);
                    break;
                case 'nat': // 自然排序
                    natcasesort($refer);
                    break;
            }
            foreach ( $refer as $key=> $val)
                $resultSet[] = &$list[$key];
            return $resultSet;
        }
        return false;
    }
}