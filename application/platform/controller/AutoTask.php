<?php
/**
 * 自动任务
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-11-08
 * Time: 15:57
 */

namespace app\platform\controller;
use app\common\controller\CommonController;

use dao\handle\EventsHandle;
use think\Log;
use dao\handle\NoticeHandle;




class AutoTask extends CommonController
{

    public function __construct()
    {
        parent::__construct();

    }

    /**
     * 加载执行任务
     */
    public function loadAutoTask()
    {
        $event = new EventsHandle();
        $retval_order_close = $event->ordersClose(); //订单长时间未付款自动交易关闭
       //满减送超过期限自动关闭, 进入时间自动开始
        $retval_mansong_operation = $event->mansongOperation();
        //限时折扣自动开始以及自动关闭
        $retval_discount_operation = $event->discountOperation();
        // 订单收货后7天自动交易完成
        $retval_order_complete = $event->ordersComplete();
        //自动收货
        $retval_order_autodeilvery = $event->autoDeilvery();
        //优惠券自动过期
        $retval_auto_coupon_close = $event->autoCouponClose();
        Log::write('检测自动收货' . $retval_order_autodeilvery);
        Log::write($retval_auto_coupon_close.'个优惠券已过期');

        $notice=new NoticeHandle();
        $notice->sendNoticeRecords();
    }

}