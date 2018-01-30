<?php

namespace app\platform\controller;

use dao\handle\AgentHandle;
use dao\handle\PlatformUserHandle;
use dao\handle\SiteHandle;
use think\Db;
use think\Controller;
use app\platform\controller\Goods;
use app\platform\controller\BaseController;
use dao\handle\ExpressHandle as ExpressHandle;

header("Content-Type: text/html;charset=utf-8");

class Index extends BaseController
{
    public function _initialize()
    {
        $platformInfo = $this->getSiteInfo();
        $this->assign('platformName', $platformInfo['platform_title']);
        $this->assign('platformInfo', $platformInfo);
        $copyInfo = $this->getCopyrightInfo();
        $this->assign('copyInfo', $copyInfo['copyright_desc']);
        if (strtolower(request()->action()) != 'login' && strtolower(request()->action()) != 'geturl') {
            $user_handle = new PlatformUserHandle();
            $auth = $user_handle->getUserAuth();
            $this->assign('is_admin', $auth["is_admin"]);
            $site = new SiteHandle();

            if ($auth["is_admin"] == 1) {
                $contidion = array(
                    'level' => array('eq', 1),
                );
                $module_first = $site->getSystemModuleList(1, 0, $contidion, 'sort desc,id');

            } else {
                $contidion = array(
                    'level' => array('eq', 1),
                    'id' => array('in', $auth['module_id_array'])
                );
                $module_first = $site->getSystemModuleList(1, 0, $contidion, 'sort desc,id');
            }
            $module_first = json_decode(json_encode($module_first['data']), TRUE);
            if ($auth["is_admin"] != 1) {
                for ($i = 0; $i < count($module_first); $i++) {
                    $contidion = array(
                        'level' => array('eq', 2),
                        'is_menu' => array('eq', 1),
                        'pid' => array('eq', $module_first[$i]['id']),
                        'id' => array('in', $auth['module_id_array'])
                    );
                    $module_second_first = $site->getSystemModuleList(1, 1, $contidion, 'sort desc,id');
                    $module_second_first = json_decode(json_encode($module_second_first['data']), TRUE);
                    if (count($module_second_first) > 0) {
                        $module_first[$i]['url'] = $module_second_first[0]['url'];
                    } else {
                        $module_first[$i]['url'] = '';
                    }
                }
            }
            $this->assign("module_first", $module_first);
            $action = strtolower(request()->action());
            if (strtolower($action) != 'index') {
                if ($auth["is_admin"] == 1) {
                    $contidion = array(
                        'level' => array('eq', 2),
                        'method' => array('eq', $action)
                    );
                    $action = $site->getSystemModuleList(1, 1, $contidion);
                    $action = json_decode(json_encode($action['data']), TRUE);
                    if (count($action) != 0) {
                        $contidion = array(
                            'level' => array('eq', 2),
                            'is_menu' => array('eq', 1),
                            'pid' => array('eq', $action[0]['pid'])
                        );
                        $module_second = $site->getSystemModuleList(1, 0, $contidion, 'sort desc,id');
                        $module_second = json_decode(json_encode($module_second['data']), TRUE);
                        for ($i = 0; $i < count($module_second); $i++) {
                            if ($action[0]['id'] == $module_second[$i]['id']) {
                                $module_second[$i]['selected'] = true;
                            } else {
                                $module_second[$i]['selected'] = false;
                            }
                        }
                        $this->assign("module_second", $module_second);
                    }
                } else {
                    $contidion = array(
                        'level' => array('eq', 2),
                        'is_menu' => array('eq', 1),
                        'id' => array('in', $auth['module_id_array']),
                        'method' => array('eq', $action)
                    );
                    $action = $site->getSystemModuleList(1, 1, $contidion);
                    $action = json_decode(json_encode($action['data']), TRUE);
                    if (count($action) == 0) {
                        $this->error('你未登陆或没有权限访问该模块', "index/index");
                    }
                    if (count($action) != 0) {
                        $contidion = array(
                            'level' => array('eq', 2),
                            'is_menu' => array('eq', 1),
                            'id' => array('in', $auth['module_id_array']),
                            'pid' => array('eq', $action[0]['pid'])
                        );
                        $module_second = $site->getSystemModuleList(1, 0, $contidion, 'sort desc,id');
                        $module_second = json_decode(json_encode($module_second['data']), TRUE);
                        for ($i = 0; $i < count($module_second); $i++) {
                            if ($action[0]['id'] == $module_second[$i]['id']) {
                                $module_second[$i]['selected'] = true;
                            } else {
                                $module_second[$i]['selected'] = false;
                            }
                        }
                        $this->assign("module_second", $module_second);
                    }
                }
            }
        }
    }

    public function login()
    {
        return $this->fetch();
    }

    public function index()
    {
        return $this->fetch();
    }

    public function resetpwd()
    {
        return $this->fetch();

    }

    public function addgoods($id = '')
    {
        $category = db('goods_category')->where('level', 3)->field('id,category_name,pid')->order('pid,sort desc')->select();
        $pid = 0;
        $addName = '';
        for ($i = 0; $i < count($category); $i++) {
            if ($category[$i]['pid'] != $pid) {
                $info = db('goods_category')->where('id', $category[$i]['pid'])->field('pid,category_name')->find();
                $addName = $info['category_name'];
                $info = db('goods_category')->where('id', $info['pid'])->field('category_name')->find();
                $addName = $info['category_name'] . "-" . $addName;
                $category[$i]['category_name'] = $addName . "-" . $category[$i]['category_name'];
            } else {
                $category[$i]['category_name'] = $addName . "-" . $category[$i]['category_name'];
            }
        }
        $this->assign('category', $category);
        $this->assign('id', '');
        return $this->fetch();
    }

    public function addgoodscategory()
    {
        $category = db('goods_category')->where('level', 1)->field('id,level,category_name')->order('sort desc')->select();
        for ($i = 0; $i < count($category); $i++) {
            $category[$i]['child_list'] = db('goods_category')->where(array('level' => 2, 'pid' => $category[$i]['id']))->field('id,level,category_name')->order('sort desc')->select();
        }
        $this->assign('id', 0);
        $this->assign('category', $category);
        return $this->fetch();
    }

    public function updategoodscategory($id)
    {
        $category = db('goods_category')->where('level', 1)->field('id,level,category_name')->order('sort desc')->select();
        for ($i = 0; $i < count($category); $i++) {
            $category[$i]['child_list'] = db('goods_category')->where(array('level' => 2, 'pid' => $category[$i]['id']))->field('id,level,category_name')->order('sort desc')->select();
        }
        $this->assign('category', $category);
        $this->assign('id', $id);
        return $this->fetch('index/addgoodscategory');
    }

    public function goodscategorylist()
    {
        return $this->fetch();
    }

    public function goodslist()
    {
        $this->assign('status', 1);
        return $this->fetch('index/goodslist');
    }

    public function goodslist2()
    {
        $this->assign('status', 0);

        return $this->fetch('index/goodslist');

    }

    public function goodslist3()
    {
        $this->assign('status', -1);

        return $this->fetch('index/goodslist');

    }
//    public function goodslist4()
//    {
//        $this->assign('type',4);
//        return $this->fetch('index/goodslist');
//    }


    public function updateGood($id)
    {
        $category = db('goods_category')->where('level', 3)->field('id,category_name,pid')->order('pid,sort desc')->select();
        $pid = 0;
        $addName = '';
        for ($i = 0; $i < count($category); $i++) {
            if ($category[$i]['pid'] != $pid) {
                $info = db('goods_category')->where('id', $category[$i]['pid'])->field('pid,category_name')->find();
                $addName = $info['category_name'];
                $info = db('goods_category')->where('id', $info['pid'])->field('category_name')->find();
                $addName = $info['category_name'] . "-" . $addName;
                $category[$i]['category_name'] = $addName . "-" . $category[$i]['category_name'];
            } else {
                $category[$i]['category_name'] = $addName . "-" . $category[$i]['category_name'];
            }
        }
        $this->assign('category', $category);
        $this->assign('id', $id);
        return $this->fetch('index/addgoods');

    }

    public function getUrl()
    {
        switch (input('post.type')) {
            case "updateCategory":
                return array(
                    "result" => "OK",
                    "url" => url('updategoodscategory', array('id' => input('post.id')))
                );
                break;
            case "updateGood":
                return array(
                    "result" => "OK",
                    "url" => url('updateGood', array('id' => input('post.id')))
                );
                break;
        }
    }

    public function member()
    {
        return $this->fetch();
    }

    public function agency()
    {
        return $this->fetch();
    }

    public function orderlist()
    {
        return $this->fetch('orderlist');
    }

    public function orderdetail($order_id)
    {
        $this->assign("order_id", $order_id);
        return $this->fetch();
    }

    public function coupontypelist()
    {
        return $this->fetch();
    }

    public function pointconfig()
    {
        return $this->fetch();
    }

    public function mansonglist()
    {
        return $this->fetch();
    }

    public function getdiscountlist()
    {
        return $this->fetch();
    }

    public function fullshipping()
    {
        return $this->fetch();
    }

    public function coupontype($coupon_type_id = '')
    {
        $this->assign("coupon_type_id", $coupon_type_id);
        return $this->fetch();
    }

    public function integral()
    {
        return $this->fetch();
    }

    public function mansong($mansong_id = '')
    {
        $this->assign('mansong_id', $mansong_id);
        return $this->fetch();
    }

    public function addDiscount()
    {
        $this->assign('discount_id', '');

        return $this->fetch('index/discount');
    }

    public function updatediscount($discount_id)
    {
        $this->assign('discount_id', $discount_id);
        return $this->fetch('index/discount');
    }


    public function agency_tree()
    {
        /*
//        $agentHandle = new AgentHandle();
        $agentHandle = new AgentHandle();
        $data = $agentHandle->getAgentTree();
        for($i=0;$i<count($data["platform"]["agent_list"]);$i++){
            $data["platform"]["agent_list"][$i]=json_decode((string)$data["platform"]["agent_list"][$i],true);
        }
//        dump($data);
//        dump($this->getLi($data["platform"]["agent_list"]));
        $this->assign("anget_list",$this->getLi($data["platform"]["agent_list"]));
        */
        return $this->fetch();
    }

    private function getLi($item)
    {
        $data = '';
        if (count($item) == 0) {
            return "";
        } else {
            $data .= "<ul>";
            for ($i = 0; $i < count($item); $i++) {
                $data .= "<li>" . $item[$i]["agent_name"];
                if (empty($item[$i]['agent_child_list'])) {
                    $data .= "</li>";
                } else {
                    $data .= $this->getLi($item[$i]['agent_child_list']) . "</li>";
                }
            }
            $data .= "</ul>";
            return $data;
        }
    }

    public function agency_account()
    {
        return $this->fetch();
    }

    public function agency_commission_recond_1()
    {
        return $this->fetch();
    }

    public function agency_commission_recond_2()
    {
        return $this->fetch();
    }

    public function agency_commission_recond_3()
    {
        return $this->fetch();
    }

    public function commission_rate()
    {
        return $this->fetch();
    }

    public function point_detail()
    {
        return $this->fetch();
    }

    public function balance_detail()
    {
        return $this->fetch();
    }

    public function member_level_list()
    {
        return $this->fetch();
    }

    public function add_member_level()
    {
        $this->assign("level_id", 0);
        return $this->fetch();
    }

    public function edit_member_level($level_id)
    {
        $this->assign("level_id", $level_id);
        return $this->fetch('add_member_level');
    }


    public function order_refund_detail($order_goods_id)
    {
        $this->assign("order_goods_id", $order_goods_id);
        return $this->fetch();
    }

    public function userlist()
    {
        return $this->fetch();
    }

    public function agentWithdrawList($status = "all")
    {
        $this->assign("status", $status);
        return $this->fetch();
    }

    public function agentShopList($status = "all")
    {
        $this->assign("status", $status);
        return $this->fetch();
    }

    public function agentShop($agent_id, $agent_shop_id)
    {
        $this->assign("agent_id", $agent_id);
        $this->assign("agent_shop_id", $agent_shop_id);
        return $this->fetch();
    }


    public function platformaccount()
    {
        return $this->fetch();

    }

    public function agentaccountlist()
    {
        return $this->fetch();
    }

    public function commissionrecordslist()
    {
        return $this->fetch();
    }

    public function commissionrecordslist2()
    {
        return $this->fetch();
    }

    public function commissionrecordslist3()
    {
        return $this->fetch();
    }

    public function shopsalesaccount()
    {
        return $this->fetch();
    }

    public function shopgoodssaleslist()
    {
        return $this->fetch();
    }

    public function bestsellergoods()
    {
        return $this->fetch();
    }

    public function shopreport()
    {
        return $this->fetch();
    }

    public function shopgoodssalesrank()
    {
        return $this->fetch();
    }


    public function authgrouplist()
    {
        return $this->fetch();
    }

    public function userdetail()
    {
        return $this->fetch();
    }
}
