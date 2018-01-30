<?php
/**
 * 数据统计相关
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-11-24
 * Time: 19:36
 */

namespace app\platform\controller;

use dao\handle\GoodsHandle;
use dao\handle\GoodsCategoryHandle;
use dao\handle\OrderHandle;
//use dao\handle\Shop;
use think\helper\Time;


class DataAnalysis extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * ok-2ok-3ok
     * 商品销售排行
     */
    public function goodsSalesRank()
    {
        $goods = new GoodsHandle();
        $goods_list = $goods->getGoodsRank("");

        //   getGoodsRank($condition)
        return json(resultArray(0, "操作成功", $goods_list));
    }

    /**
     * ok-2ok-3ok
     * 商品销售统计
     */
    public function goodsAccountList()
    {
        $page_index = request()->post('page_index', 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $goods_id = request()->post('goods_id', 0);
        $start_date = request()->post('start_date', 0);
        $end_date = request()->post('end_date', 0);
        $condition = array();
        $condition = array(
            "ho.order_status" => [
                'NEQ',
                0
            ],
            "ho.order_status" => [
                'NEQ',
                5
            ]
        );
        if ($start_date != 0 && $end_date != 0) {
            $condition["ho.pay_time"] = [
                [
                    ">",
                    getTimeTurnTimeStamp($start_date)
                ],
                [
                    "<",
                    getTimeTurnTimeStamp($end_date)
                ]
            ];
        } elseif ($start_date != 0 && $end_date == 0) {
            $condition["ho.pay_time"] = [
                [
                    ">",
                    getTimeTurnTimeStamp($start_date)
                ]
            ];
        } elseif ($start_date == 0 && $end_date != 0) {
            $condition["ho.pay_time"] = [
                [
                    "<",
                    getTimeTurnTimeStamp($end_date)
                ]
            ];
        }
        if ($goods_id > 0) {
            $condition["hog.goods_id"] = $goods_id;
        }
        $order = new OrderHandle();
        $list = $order->getOrderAccountRecordsList($page_index, $page_size, $condition, 'hog.id desc');

        return json(resultArray(0, "操作成功", $list));
    }

    /**
     * 店铺销售明细
     */
    /**
    public function orderRecordsList()
    {

        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", PAGESIZE);
        $condition = array();
        $start_date = request()->post('start_date', '');
        $end_date = request()->post('end_date', '');
        if ($start_date != "" && $end_date != "") {
            $condition["create_time"] = [
                [
                    ">",
                    getTimeTurnTimeStamp($start_date)
                ],
                [
                    "<",
                    getTimeTurnTimeStamp($end_date)
                ]
            ];
        } else
            if ($start_date != "" && $end_date == "") {
                $condition["create_time"] = [
                    [
                        ">",
                        getTimeTurnTimeStamp($start_date)
                    ]
                ];
            } else
                if ($start_date == "" && $end_date != "") {
                    $condition["create_time"] = [
                        [
                            "<",
                            getTimeTurnTimeStamp($end_date)
                        ]
                    ];
                }
        $order = new OrderHandle();
        $list = $order->getOrderList($page_index, $page_size, $condition, " create_time desc ");
        return $list;
    }





            $child_menu_list = array(
                array(
                    'url' => "account/orderaccountcount",
                    'menu_name' => "订单统计",
                    "active" => 0
                ),
                array(
                    'url' => "account/orderrecordslist",
                    'menu_name' => "销售明细",
                    "active" => 1
                )
            );
            $this->assign('child_menu_list', $child_menu_list);

            $time = request()->get('time','');
            $type = request()->get('type',0);
            $start_time = "";
            $end_time = "";
            if ($time == "day") {
                $start_time = date("Y-m-d", time());
                $end_time = date("Y-m-d H:i:s", time());
            } elseif ($time == "week") {
                $start_time = date('Y-m-d', strtotime('-7 days'));
                $end_time = date("Y-m-d H:i:s", time());
            } elseif ($time == "month") {
                $start_time = date('Y-m-d', strtotime('-30 days'));
                $end_time = date("Y-m-d H:i:s", time());
            }
            $this->assign("start_time", $start_time);
            $this->assign("end_time", $end_time);
            return view($this->style . "Account/orderRecordsList");
        }
    }
**/
    /**
     * 订单销售统计
     */
    /*
    public function orderAccountCount()
    {
        $child_menu_list = array(
            array(
                'url' => "account/orderaccountcount",
                'menu_name' => "订单统计",
                "active" => 1
            ),
            array(
                'url' => "account/orderrecordsList",
                'menu_name' => "销售明细",
                "active" => 0
            )
        );
        $this->assign('child_menu_list', $child_menu_list);
        $order_service = new Order();
        // 获取日销售统计
        $account = $order_service->getShopOrderAccountDetail($this->instance_id);
        $this->assign("account", $account);
        return view($this->style . "Account/orderAccountCount");
    }
*/
    /**
     * ok-2ok-3ok
     * 销售概况
     */
    public function salesAccount()
    {
        $order_handle = new OrderHandle();
        // 获取所需销售统计
        $account = $order_handle->getShopAccountCountInfo();
        return json(resultArray(0, "操作成功", $account));
    }

    /**
     * ok-2ok-3ok
     * 前30日销售统计
     */
    public function getSaleNumCount()
    {
        $order = new OrderHandle();
        $data = array();
        list ($start, $end) = Time::month();
        for ($j = 0; $j < ($end + 1 - $start) / 86400; $j++) {
            $date_start = date("Y-m-d H:i:s", $start + 86400 * $j);
            $date_end = date("Y-m-d H:i:s", $start + 86400 * ($j + 1));
            $count = $order->getOrderCount([
                'create_time' => [
                    'between',
                    [
                        getTimeTurnTimeStamp($date_start),
                        getTimeTurnTimeStamp($date_end)
                    ]
                ],
                "order_status" => [
                    'NEQ',
                    0
                ],
                "order_status" => [
                    'NEQ',
                    5
                ]
            ]);
            $data[0][$j] = (1 + $j) . '日';
            $data[1][$j] = $count;
        }
        return json(resultArray(0, "操作成功", $data));
    }

    /**
     * ok-2ok-3ok
     * 商品销售详情--商品分析
     */
    public function goodsSalesList()
    {
        $order = new OrderHandle();
        $page_index = request()->post('page_index', 1);
        $page_size = request()->post('page_size', PAGESIZE);
        $goods_name = request()->post("goods_name", '');
        $condition = array();
        if ($goods_name != '') {
                $condition = array(
                    "order_status" => [
                        'NEQ',
                        0
                    ],
                    "order_status" => [
                        'NEQ',
                        5
                    ]
                );
                $condition["title"] = array(
                    'like',
                    '%' . $goods_name . '%'
                );
        }
          //  $condition["shop_id"] = $this->instance_id;
        $list = $order->getShopGoodsSalesList($page_index, $page_size, $condition, 'create_time desc');
        return json(resultArray(0, "操作成功", $list));
    }




    /**
     * 商品销售详情--热卖商品
     *
     */
    public function bestSellerGoods()
    {
        $child_menu_list = array(
            array(
                'url' => "account/shopGoodsSalesList",
                'menu_name' => "商品分析",
                "active" => 0
            ),
            array(
                'url' => "account/bestSellerGoods",
                'menu_name' => "热卖商品",
                "active" => 1
            )
        );
        $this->assign('child_menu_list', $child_menu_list);
        return view($this->style . "Account/bestSellerGoods");
    }

    /**
     * ok-2ok-3ok
     * 商品销售chart数据
     */
    public function getGoodsSalesChartCount()
    {
        $date = request()->post('date',1);
        $type = request()->post('type',1);
        $category_id_1 = request()->post('category_id_1','');
        $category_id_2 = request()->post('category_id_2','');
        $category_id_3 = request()->post('category_id_3','');
        if ($date == 1) {
            list ($start, $end) = Time::today();
            $start_date = getTimeTurnTimeStamp(date("Y-m-d H:i:s", $start));
            $end_date = getTimeTurnTimeStamp(date("Y-m-d H:i:s", $end));
        } else if ($date == 3) {
            $start_date = getTimeTurnTimeStamp(date('Y-m-d 00:00:00', strtotime('last day this week + 1 day')));
            $end_date = getTimeTurnTimeStamp(date('Y-m-d 00:00:00', strtotime('last day this week +8 day')));
        } else if ($date == 4) {
            list ($start, $end) = Time::month();
            $start_date = getTimeTurnTimeStamp(date("Y-m-d H:i:s", $start));
            $end_date = getTimeTurnTimeStamp(date("Y-m-d H:i:s", $end));
        }
        $condition = array();
       // $condition["shop_id"] = $this->instance_id;
        if ($category_id_1 != '') {
           // $condition["category_id_1"] = $category_id_1;
            $condition["pcate"] = $category_id_1;
            if ($category_id_2 != '') {
              //  $condition["category_id_2"] = $category_id_2;
                $condition["ccate"] = $category_id_2;
                if ($category_id_3 != '') {
                   // $condition["category_id_3"] = $category_id_3;
                    $condition["tcate"] = $category_id_3;
                }
            }
        }
        $order = new OrderHandle();
        $goods_list = $order->getShopGoodsSalesQuery(0, $start_date, $end_date, $condition);

        if ($type == 1) {
            $sort_array = array();
            foreach ($goods_list as $k => $v) {
                $sort_array[$v["title"]] = $v["sales_money"];
            }
            arsort($sort_array);
            $sort = array();
            $num = array();
            $i = 0;
            foreach ($sort_array as $t => $b) {
                if ($i < 30) {
                    $sort[] = $t;
                    $num[] = $b;
                    $i ++;
                } else {
                    break;
                }
            }
            $data = array(
                $sort,
                $num
            );
        } else if ($type == 2) {
            $sort_array = array();
            foreach ($goods_list as $k => $v) {
                $sort_array[$v["title"]] = $v["sales_num"];
            }
            arsort($sort_array);
            $sort = array();
            $money = array();
            $i = 0;
            foreach ($sort_array as $t => $b) {
                if ($i < 30) {
                        $sort[] = $t;
                        $money[] = $b;
                        $i ++;
                } else {
                        break;
                }
            }
            $data=  array(
                    $sort,
                    $money
                );
        }
        return json(resultArray(0, "操作成功", $data));
    }

    /**
     * 运营报告
     */
    public function shopReport()
    {
        return view($this->style . "Account/shopReport");
    }

    /**
     * ok-2ok-3ok
     * 运营报告
     * 店铺下单量/下单金额图标数据
     *
     * @return Ambigous <multitype:, unknown>
     */
    public function getOrderChartCount()
    {
        $date = request()->post('date',1);
        $type = request()->post('type',1);
        $data = array();
        if ($date == 1) {
            list ($start, $end) = Time::today();
            for ($i = 0; $i < 24; $i ++) {
                $date_start = date("Y-m-d H:i:s", $start + 3600 * $i);
                $date_end = date("Y-m-d H:i:s", $start + 3600 * ($i + 1));
                $condition = [
                   // 'shop_id' => $this->instance_id,
                    'create_time' => [
                        'between',
                        [
                            getTimeTurnTimeStamp($date_start),
                            getTimeTurnTimeStamp($date_end)
                        ]
                    ],
                    "order_status" => [
                        'NEQ',
                        0
                    ],
                    "order_status" => [
                        'NEQ',
                        5
                    ]
                ];
                $count = $this->getSaleData($condition, $type);

                $data[0][$i] = $i . ':00';
                $data[1][$i] = $count;
            }
        } else if ($date == 2) {
            list ($start, $end) = Time::yesterday();
            for ($j = 0; $j < 24; $j ++) {
                    $date_start = date("Y-m-d H:i:s", $start + 3600 * $j);
                    $date_end = date("Y-m-d H:i:s", $start + 3600 * ($j + 1));
                    $condition = [
                      //  'shop_id' => $this->instance_id,
                        'create_time' => [
                            'between',
                            [
                                getTimeTurnTimeStamp($date_start),
                                getTimeTurnTimeStamp($date_end)
                            ]
                        ],
                        "order_status" => [
                            'NEQ',
                            0
                        ],
                        "order_status" => [
                            'NEQ',
                            5
                        ]
                    ];
                    $count = $this->getSaleData($condition, $type);
                    $data[0][$j] = $j . ':00';
                    $data[1][$j] = $count;
            }
        } else if ($date == 3) {
            $start = strtotime(date('Y-m-d 00:00:00', strtotime('last day this week + 1 day')));
            for ($j = 0; $j < 7; $j ++) {
                        $date_start = date("Y-m-d H:i:s", $start + 86400 * $j);
                        $date_end = date("Y-m-d H:i:s", $start + 86400 * ($j + 1));
                        $condition = [
                          //  'shop_id' => $this->instance_id,
                            'create_time' => [
                                'between',
                                [
                                    getTimeTurnTimeStamp($date_start),
                                    getTimeTurnTimeStamp($date_end)
                                ]
                            ],
                            "order_status" => [
                                'NEQ',
                                0
                            ],
                            "order_status" => [
                                'NEQ',
                                5
                            ]
                        ];
                        $count = $this->getSaleData($condition, $type);
                        $data[0][$j] = '星期' . ($j + 1);
                        $data[1][$j] = $count;
            }
        } else if ($date == 4) {
            list ($start, $end) = Time::month();
            for ($j = 0; $j < ($end + 1 - $start) / 86400; $j ++) {
                            $date_start = date("Y-m-d H:i:s", $start + 86400 * $j);
                            $date_end = date("Y-m-d H:i:s", $start + 86400 * ($j + 1));
                            $condition = [
                              //  'shop_id' => $this->instance_id,
                                'create_time' => [
                                    'between',
                                    [
                                        getTimeTurnTimeStamp($date_start),
                                        getTimeTurnTimeStamp($date_end)
                                    ]
                                ],
                                "order_status" => [
                                    'NEQ',
                                    0
                                ],
                                "order_status" => [
                                    'NEQ',
                                    5
                                ]
                            ];
                            $count = $this->getSaleData($condition, $type);
                            $data[0][$j] = (1 + $j) . '日';
                            $data[1][$j] = $count;
            }
        }
        return json(resultArray(0, "操作成功", $data));
    }

    /**
     * ok-2ok-3ok
     * 查询一段时间内的总下单量及下单金额
     */
    public function getOrderSaleCount()
    {
        $date = request()->post('date',1);
        // 查询一段时间内的下单量及下单金额
        if ($date == 1) {
            list ($start, $end) = Time::today();
            $start_date = date("Y-m-d H:i:s", $start);
            $end_date = date("Y-m-d H:i:s", $end);
        } else if ($date == 3) {
                $start_date = date('Y-m-d 00:00:00', strtotime('last day this week + 1 day'));
                $end_date = date('Y-m-d 00:00:00', strtotime('last day this week +8 day'));
        } else if ($date == 4) {
                    list ($start, $end) = Time::month();
                    $start_date = date("Y-m-d H:i:s", $start);
                    $end_date = date("Y-m-d H:i:s", $end);
        }
        $condition = array();
      //  $condition["shop_id"] = $this->instance_id;
     //   $condition["shop_id"];
        $condition["create_time"] = [
            'between',
            [
                getTimeTurnTimeStamp($start_date),
                getTimeTurnTimeStamp($end_date)
            ]
        ];
        $count_money = $this->getSaleData($condition, 1);
        $count_num = $this->getSaleData($condition, 2);
        $data =  array(
            "count_money" => $count_money,
            "count_num" => $count_num
        );

        return json(resultArray(0, "操作成功", $data));
    }

    /**
     * ok-2ok-3ok
     * 下单量/下单金额 数据
     */
    public function getSaleData($condition, $type)
    {
        $order = new OrderHandle();
        if ($type == 1) {
            $count = $order->getShopSaleSum($condition);
            $count = (float) sprintf('%.2f', $count);
        } else {
            $count = $order->getShopSaleNumSum($condition);
        }
        return $count;
    }

    /**
     * 同行商品买卖
     */
    public function shopGoodsGroupSaleCount()
    {
        $goods_category = new GoodsCategory();
        $list = $goods_category->getGoodsCategoryListByParentId(0);
        $this->assign("cateGoryList", $list);
        return view($this->style . "Account/shopGoodsGroupSaleCount");
    }
}