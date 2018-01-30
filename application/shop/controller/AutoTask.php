<?php
/**
 * 自动任务
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-11-08
 * Time: 16:13
 */

namespace app\shop\controller;

use app\common\controller\CommonController;
use dao\handle\EventsHandle;
use dao\handle\NoticeHandle;
use think\Controller;
use think\Log;
use think\Cache;


class AutoTask extends CommonController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * ok-2ok-3ok
     * 加载执行任务
     */
    public function loadAutoTask()
    {
        $this->autoTask();
        $this->minutesTask();
        $this->hoursTask();
    }

    /**
     * ok-2ok-3ok
     * 立即执行事件
     */
    public function autoTask(){
        $event = new EventsHandle();
        $retval_mansong_operation = $event->mansongOperation();
        $retval_discount_operation = $event->discountOperation();
        $retval_auto_coupon_close = $event->autoCouponClose();

        $notice=new NoticeHandle();
        $notice->sendNoticeRecords();
    }

    /**
     * ok-2ok-3ok
     * 每分钟执行事件
     */
    public function minutesTask(){
        $time = time() - 60;
        $cache = Cache::get("auto_minutes_task");
        if(!empty($cache) && $time < $cache)
        {
            return 1;
        }else{
            $event = new EventsHandle();
            $retval_order_close = $event->ordersClose();
            $retval_order_complete = $event->ordersComplete();
            Cache::set("auto_minutes_task", time());
            return 1;
        }
    }

    /**
     * ok-2ok-3ok
     * 每小时执行事件
     */
    public function hoursTask(){
        $time = time()- 60 * 60;
        $cache = Cache::get("auto_hours_task");
        if(!empty($cache) && $time < $cache)
        {
            return 1;
        }else{
            $event = new EventsHandle();
            $retval_order_autodeilvery = $event->autoDeilvery();
            Cache::set("auto_hours_task", time());
            return 1;
        }
    }

}