<?php

namespace app\behavior;
// 注意应用或模块的不同命名空间
\think\Loader::addNamespace('dao', 'dao/');
use dao\model\Addons as AddonsModel;
use dao\model\Hooks as HooksModel;
use think\cache;
use think\hook;

class InitHook
{

    /**
     * ok-2ok
     * @param array $param
     */
    public function run(&$param = [])
    {
        if (defined('BIND_MODULE') && BIND_MODULE === 'Install')
            return;
            // 动态加入命名空间
        \think\Loader::addNamespace('addons', 'addons');
        // 获取钩子数据
        $data = cache('hooks');
        if (! $data) {
            $addons_model = new AddonsModel();
            $hooks_model = new HooksModel();
            $hooks = $hooks_model->column('addons', 'name');
            // 获取钩子的实现插件信息
            foreach ($hooks as $key => $value) {
                if ($value) {
                    $map['status'] = 1;
                    $names = explode(',', $value);
                    $map['name'] = [
                        'IN',
                        $names
                    ];
                    $data = $addons_model->where($map)->column('name', 'id');
                    if ($data) {
                        $addons = array_intersect($names, $data);
                        Hook::add($key, array_map('get_addon_class', $addons));
                    }
                }
            }
            cache('hooks', Hook::get());
        } else {
            Hook::import($data, false);
        }
    }
}
