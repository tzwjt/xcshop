<?php
/**
 * OrderExpressHandle.php
 * 订单项物流操作 -ok
 * @date : 2017.8.17
 * @version : v1.0
 */
namespace dao\handle\order;

use dao\model\OrderGoodsExpress as OrderGoodsExpressModel;
use dao\model\MemberUser as MemberUserModel;
use dao\model\ExpressCompany as OrderExpressCompanyModel;
use dao\handle\BaseHandle;
use dao\handle\PlatformUserHandle;
use think\Log;


class OrderExpressHandle extends BaseHandle
{

    function __construct()
    {
        parent::__construct();
    }

    /**
     * 物流公司发货--ok-2ok
     *
     * @param  $order_id            
     * @param  $order_goods_id_array
     *            订单项数组
     * @param  $express_name
     *            包裹名称
     * @param  $shipping_type
     *            发货方式1 需要物流 0无需物流
     * @param  $express_company_id
     *            物流公司ID
     * @param  $express_no
     *            物流单号
     */
    public function delivey($user_id, $user_type,$order_id, $order_goods_id_array, $express_name, $shipping_type, $express_company_id, $express_no)
    {
        $action_name = "";
        if ($user_type == 1) {
            $user = new MemberUserModel();
            $user_name = $user->getInfo([
                'id' => $user_id
            ], 'login_phone');
            $action_name = $user_name['login_phone'];
        } else if ($user_type == 2) {
            $action_name =  PlatformUserHandle::loginUserName(); //    "platform";
        } else if ($user_type == 3) {
            $action_name = "agent";
        }
        $order_express = new OrderGoodsExpressModel();
        $this->startTrans();
        try {
            $count = $order_express->getCount([
                'order_goods_id_array' => $order_goods_id_array
            ]);
            if ($count == 0) {
                
                $express_company = new OrderExpressCompanyModel();
                $express_company_info = $express_company->getInfo([
                    'id' => $express_company_id
                ], 'company_name');
                $data_goods_delivery = array(
                    'order_id' => $order_id,
                    'order_goods_id_array' => $order_goods_id_array,
                    'express_name' => $express_name,
                    'shipping_type' => $shipping_type,
                    'express_company' => $express_company_info['company_name'],
                    'express_company_id' => $express_company_id,
                    'express_no' => $express_no,
                    'shipping_time' => time(),
                    'user_id' => $user_id,
                    'user_name' => $action_name, //$user_name['user_name']
                    'user_type' => $user_type
                );
                $res = $order_express->save($data_goods_delivery);
                if (empty($res)) {
                    $this->rollback();
                   Log::write('order_express->save');
                    return false;
                }
                // 循环添加到订单商品项
                $order_goods = new OrderGoodsHandle();
              //  orderGoodsDelivery($user_id, $user_type, $order_id, $order_goods_id_array)
              //  orderGoodsDelivery($user_id, $user_type, $order_id, $order_goods_id_array)
               $res =  $order_goods->orderGoodsDelivery($user_id, $user_type,$order_id, $order_goods_id_array);
                if (empty($res)) {
                    $this->rollback();
                    $this->error = $order_goods->getError();
                    return false;
                }
                Log::write("hook orderDelivery");

                runhook("Notify", "orderDelivery", array(
                    "order_goods_ids" => $order_goods_id_array
                ));
                $this->commit();
            }
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error =  $e->getMessage();
            return false;
        }
    }
}