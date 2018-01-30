<?php
/**
 * 平台主界面
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-11-13
 * Time: 11:44
 */

namespace app\platform\controller;

use app\platform\controller\BaseController;
use dao\handle\AgentHandle;
use dao\handle\GoodsHandle;
use dao\handle\member\MemberUserHandle as MemberUserHandle;
use dao\handle\MemberHandle as MemberHandle;

use dao\handle\OrderHandle;
use dao\handle\PlatformUserHandle;
use think\helper\Time;
use think\Log;

class IndexInfo extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getUrlTest() {
       Log::write(" __URL__:".__URL__);
        Log::write("url1：".url(__URL__ . '/shop/Pay/aliPayReturn'));
        Log::write("url2：".url( '/shop/Pay/aliPayReturn'));
        Log::write("url2：".request()->domain(). url(__URL__ . '/shop/Pay/aliPayReturn'));
    }


    /**
     * ok-2ok
     * 访问者域名
     * @return \think\response\Json
     */
    public function getDomain()
    {
        $main = \think\Request::instance()->domain();
        return json(resultArray(0,"操作成功",$main));
    }

    /**
     * ok-2ok
     * 商品销售排行
     */
    public function getGoodsRealSalesRank()
    {
        $goods = new GoodsHandle();
        $goods_list = $goods->getGoodsRank('');
        return json(resultArray(0,"操作成功",$goods_list));
    }



    /**
     * ok-2ok
     * 得到登录用户的信息
     */
    public function getLoginUserInfo()
    {
        return $this->getUserInfo();
        /**
       $user_handle = new PlatformUserHandle();
       $user =  $user_handle->getLoginUserInfo();
        return json(resultArray(0,"操作成功",$user));
         * **/
    }




    /**
     * ok-2ok
     * 获取 商品 数量 全部 出售中 已审核 已下架 库存预警数
     */
    public function getGoodsCount()
    {
        $goods_count = new GoodsHandle();
        $goods_count_array = array();
        // 全部
        $goods_count_array['all'] = $goods_count->getGoodsCount([
        ]);
        // 出售中
        $goods_count_array['sale'] = $goods_count->getGoodsCount([
            'status' => 1
        ]);
        // 仓库中已审核
        $goods_count_array['audit'] = $goods_count->getGoodsCount([
            'status' => 0
        ]);
        // 下架
        $goods_count_array['shelf'] = $goods_count->getGoodsCount([
            'status' => 10
        ]);
        //库存低于预警值的商品数
        $goods_count_array['warning'] = $goods_count->getGoodsCount([
            'min_stock_alarm' => array("neq", 0),
            'total' => array("exp", "<= min_stock_alarm")
        ]);
        return json(resultArray(0,"操作成功",$goods_count_array));
    }

    /**
     * ok-2ok
     * 获取 订单数量 代付款 待发货 已发货 已收货 已完成 已关闭 退款中 已退款
     */
    public function getOrderCount()
    {
        $order = new OrderHandle();
        $order_count_array = array();
        $order_count_array['daifukuan'] = $order->getOrderCount([
            'order_status' => 0
        ]); // 代付款
        $order_count_array['daifahuo'] = $order->getOrderCount([
            'order_status' => 1
        ]); // 代发货
        $order_count_array['yifahuo'] = $order->getOrderCount([
            'order_status' => 2
        ]); // 已发货
        $order_count_array['yishouhuo'] = $order->getOrderCount([
            'order_status' => 3
        ]); // 已收货
        $order_count_array['yiwancheng'] = $order->getOrderCount([
            'order_status' => 4
        ]); // 已完成
        $order_count_array['yiguanbi'] = $order->getOrderCount([
            'order_status' => 5
        ]); // 已关闭
        $order_count_array['tuikuanzhong'] = $order->getOrderCount([
            'order_status' => - 1
        ]); // 退款中
        $order_count_array['yituikuan'] = $order->getOrderCount([
            'order_status' => - 2
        ]); // 已退款
        $order_count_array['all'] = $order->getOrderCount([
            'is_deleted' => 0
        ]); // 全部订单数量，排除已删除的

        return json(resultArray(0,"操作成功",$order_count_array));
    }

    /**
     * ok-2ok
     * 获取销售统计
     */
    public function getSalesStatistics()
    {
        $order = new OrderHandle();
     //   $condition['shop_id'] = $this->instance_id;
        //[待发货、已发货、已收货、已完成]
        $condition['order_status'] = [
            'in',
            "1,2,3,4"
        ];
        $tmp_condition = $condition;

        // 查询今天
        $start = strtotime(date('Y-m-d 00:00:00'));
        $end = strtotime(date('Y-m-d H:i:s'));
        $tmp_condition["create_time"] = [
            'between',
            [
                $start,
                $end
            ]
        ];
        $data['curr_day_money'] = $order->getPayMoneySum($tmp_condition); // 查询今天的订单总金额
        $tmp_condition = $condition;

        //getTimeTurnTimeStamp($time)
        $start = mktime(0, 0, 0, date("m", strtotime("-1 day")), date("d", strtotime("-1 day")), date("Y", strtotime("-1 day")));

            //getTimeTurnTimeStamp(0, 0, 0, date("m", strtotime("-1 day")), date("d", strtotime("-1 day")), date("Y", strtotime("-1 day")));
        $end = mktime(23, 59, 59, date("m", strtotime("-1 day")), date("d", strtotime("-1 day")), date("Y", strtotime("-1 day")));
       // getTimeTurnTimeStamp(23, 59, 59, date("m", strtotime("-1 day")), date("d", strtotime("-1 day")), date("Y", strtotime("-1 day")));
        $tmp_condition["create_time"] = [
            'between',
            [
                $start,
                $end
            ]
        ];
        $data['yesterday_money'] = $order->getPayMoneySum($tmp_condition);
        $data['yesterday_goods'] = $order->getGoodsNumSum($tmp_condition);
        // 查看本月
        $tmp_condition = $condition;
        $start = mktime(0, 0, 0, date("m", time()), 1, date("Y", time()));
        $end = mktime(23, 59, 59, date("m", time()), date("t"), date("Y", time()));
        $tmp_condition["create_time"] = [
            'between',
            [
                $start,
                $end
            ]
        ];
        $data['month_money'] = $order->getPayMoneySum($tmp_condition);
        $data['month_goods'] = $order->getGoodsNumSum($tmp_condition);
        return json(resultArray(0,"操作成功",$data));
    }

    /**
     * ok-2ok
     * 订单 图表 数据
     */
    public function getOrderChartCount()
    {
        $type = request()->post('type',4);
        $order = new OrderHandle();
        $data = array();
        if ($type == 1) {
            list ($start, $end) = Time::today();
            for ($i = 0; $i < 24; $i ++) {
                $date_start = date("Y-m-d H:i:s", $start + 3600 * $i);
                $date_end = date("Y-m-d H:i:s", $start + 3600 * ($i + 1));
                $count = $order->getOrderCount([
                    'create_time' => [
                        'between',
                        [
                            getTimeTurnTimeStamp($date_start),
                            getTimeTurnTimeStamp($date_end)
                        ]
                    ]
                ]);
                $data[$i] = array(
                    $i . ':00',
                    $count
                );
            }
        } elseif ($type == 2) {
            list ($start, $end) = Time::yesterday();
            for ($j = 0; $j < 24; $j ++) {
                $date_start = date("Y-m-d H:i:s", $start + 3600 * $j);
                $date_end = date("Y-m-d H:i:s", $start + 3600 * ($j + 1));
                $count = $order->getOrderCount([
                    'create_time' => [
                        'between',
                        [
                            getTimeTurnTimeStamp($date_start),
                            getTimeTurnTimeStamp($date_end)
                        ]
                    ]
                ]);
                $data[$j] = array(
                    $j . ':00',
                    $count
                );
            }
        } elseif ($type == 3) {
            list ($start, $end) = Time::week();
            $start = $start - 604800;
            for ($j = 0; $j < 7; $j ++) {
                $date_start = date("Y-m-d H:i:s", $start + 86400 * $j);
                $date_end = date("Y-m-d H:i:s", $start + 86400 * ($j + 1));
                $count = $order->getOrderCount([
                    'create_time' => [
                        'between',
                        [
                            getTimeTurnTimeStamp($date_start),
                            getTimeTurnTimeStamp($date_end)
                        ]
                    ]
                ]);
                $data[$j] = array(
                    '星期' . ($j + 1),
                    $count
                );
            }
        } elseif ($type == 4) {
            list ($start, $end) = Time::month();
            for ($j = 0; $j < ($end + 1 - $start) / 86400; $j ++) {
                $date_start = date("Y-m-d H:i:s", $start + 86400 * $j);
                $date_end = date("Y-m-d H:i:s", $start + 86400 * ($j + 1));
                $count = $order->getOrderCount([
                    'create_time' => [
                        'between',
                        [
                            getTimeTurnTimeStamp($date_start),
                            getTimeTurnTimeStamp($date_end)
                        ]
                    ]
                ]);
                $data[$j] = array(
                    (1 + $j) . '日',
                    $count
                );
            }
        }
        return json(resultArray(0,"操作成功",$data));
    }



    /**
     * 咨询个数
     *
     * @param unknown $condition
     * @return unknown
     */
    /**
    public function getConsultCount()
    {
        $goods = new GoodsService();
        $good_count = $goods->getConsultCount(array(
            "shop_id" => $this->instance_id,
            "consult_reply" => ""
        ));
        return $good_count;
    }
**/
    /**
     * 获取全部关注人数
     * 2017年7月20日 15:25:31 王永杰
     */
    /**
    public function getWeiXinFansCount()
    {
        $weixin = new Weixin();
        $count = $weixin->getWeixinFansCount([
            'instance_id' => $this->instance_id
        ]);
        return $count;
    }
**/

    /**
     * ok-2ok
     * 获取有效会员数
     */
    public function getValidMemberCount()
    {
        $member = new MemberHandle();
         $count = $member->getMemberCount([
                'status'=>1
         ]);
        return json(resultArray(0,"操作成功",$count));
    }

    /**
     * ok-2ok
     * 得到有效代理商数
     * @return \think\response\Json
     */
    public function getValidAgentCount() {
        $agent_handle = new AgentHandle();
        $agent_count = $agent_handle->getAgentCount([
            'status'=>2
        ]);
        $agent_first_count = $agent_handle->getAgentCount([
            'status'=>2,
            'agent_type'=>1
        ]);
        $agent_second_count = $agent_handle->getAgentCount([
            'status'=>2,
            'agent_type'=>2
        ]);

        $data = array (
            'agent_count'=>$agent_count,
            'agent_first_count'=>$agent_first_count,
            'agent_second_count'=>$agent_second_count
        );

        return json(resultArray(0,"操作成功",$data));
    }

    /**
     * ok-2ok-3ok
     * 得到代理商数
     * @return \think\response\Json
     */
    public function getAgentCount() {
        $agent_handle = new AgentHandle();
        $agent_count_array = array();
        $agent_count_array['all'] = $agent_handle->getAgentCount('');
        $agent_count_array['weiwanshan'] = $agent_handle->getAgentCount([
            'status'=>0
        ]);
        //未完善
        $agent_count_array['daishenghe'] = $agent_handle->getAgentCount([
            'status'=>1,
        ]);
        //待审核
        $agent_count_array['zhengchang'] = $agent_handle->getAgentCount([
            'status'=>2,
           // 'agent_type'=>2
        ]);
       //正常

        $agent_count_array['weitongguo'] = $agent_handle->getAgentCount([
            'status'=>3,
            // 'agent_type'=>2
        ]);
        //未通过
        $agent_count_array['zanting'] = $agent_handle->getAgentCount([
            'status'=>4,
            // 'agent_type'=>2
        ]);
        //暂停
        $agent_count_array['wuxiao'] = $agent_handle->getAgentCount([
            'status'=>5,
            // 'agent_type'=>2
        ]);
        //无效


        return json(resultArray(0,"操作成功",$agent_count_array));
    }
    /**
     * 订单 图表 数据
     */
    /**
    public function getWeiXinFansChartCount()
    {
        $type = request()->post('date',4);
        $weixin = new Weixin();
        $data = array();
        if ($type == 1) {
            list ($start, $end) = Time::today();
            for ($i = 0; $i < 24; $i ++) {
                $date_start = date("Y-m-d H:i:s", $start + 3600 * $i);
                $date_end = date("Y-m-d H:i:s", $start + 3600 * ($i + 1));
                $count = $weixin->getWeixinFansCount([
                    'instance_id' => $this->instance_id,
                    'subscribe_date' => [
                        'between',
                        [
                            getTimeTurnTimeStamp($date_start),
                            getTimeTurnTimeStamp($date_end)
                        ]
                    ]
                ]);
                $data[0][$i] = $i . ':00';
                $data[1][$i] = $count;
            }
        } elseif ($type == 2) {
            list ($start, $end) = Time::yesterday();
            for ($j = 0; $j < 24; $j ++) {
                $date_start = date("Y-m-d H:i:s", $start + 3600 * $j);
                $date_end = date("Y-m-d H:i:s", $start + 3600 * ($j + 1));
                $count = $weixin->getWeixinFansCount([
                    'instance_id' => $this->instance_id,
                    'subscribe_date' => [
                        'between',
                        [
                            getTimeTurnTimeStamp($date_start),
                            getTimeTurnTimeStamp($date_end)
                        ]
                    ]
                ]);
                $data[0][$j] = $j . ':00';
                $data[1][$j] = $count;
            }
        } elseif ($type == 3) {
            list ($start, $end) = Time::week();
            $start = $start - 604800;
            for ($j = 0; $j < 7; $j ++) {
                $date_start = date("Y-m-d H:i:s", $start + 86400 * $j);
                $date_end = date("Y-m-d H:i:s", $start + 86400 * ($j + 1));
                $count = $weixin->getWeixinFansCount([
                    'instance_id' => $this->instance_id,
                    'subscribe_date' => [
                        'between',
                        [
                            getTimeTurnTimeStamp($date_start),
                            getTimeTurnTimeStamp($date_end)
                        ]
                    ]
                ]);
                $data[0][$j] = '星期' . ($j + 1);
                $data[1][$j] = $count;
            }
        } elseif ($type == 4) {
            list ($start, $end) = Time::month();
            for ($j = 0; $j < ($end + 1 - $start) / 86400; $j ++) {
                $date_start = date("Y-m-d H:i:s", $start + 86400 * $j);
                $date_end = date("Y-m-d H:i:s", $start + 86400 * ($j + 1));
                $count = $weixin->getWeixinFansCount([
                    'instance_id' => $this->instance_id,
                    'subscribe_date' => [
                        'between',
                        [
                            getTimeTurnTimeStamp($date_start),
                            getTimeTurnTimeStamp($date_end)
                        ]
                    ]
                ]);
                $data[0][$j] = (1 + $j) . '日';
                $data[1][$j] = $count;
            }
        }
        return $data;
    }
**/
    /**
     * ok-2ok
     * 设置操作提示是否显示
     * 保存7天
     */
    public function setWarmPromptIsShow()
    {
        $value = request()->post("value", "show");
        $res = cookie("warm_promt_is_show", $value, 60 * 60 * 24 * 7);
        return $this->getWarmPromptIsShow();
    }

}