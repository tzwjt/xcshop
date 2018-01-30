<?php
/**
 * WeixinMessage.php
 * @date : 2017.12.24
 * @version : v1.0.0.0
 */
namespace dao\handle;

use dao\handle\BaseHandle;
use dao\handle\WeixinHandle;
use dao\model\WeixinInstanceMsg as WeixinInstanceMsgModel;
use dao\model\Order as OrderModel;
use dao\extend\WchatOauth;
use dao\model\WeixinAuth as WeixinAuthModel;
use dao\model\Goods as GoodsModel;
use dao\model\OrderGoodsExpress as OrderGoodsExpressModel;

class WeixinMessageHandle extends BaseHandle
{

    /**
     * ok-2ok
     * 获取模板相关内容
     * @param $template_no
     * @param $instance_id
     * @return array|false|\PDOStatement|string|\think\Model
     */
    private function getMessageInfo($template_no, $instance_id)
    {
        // 模板消息内容
        $instance_message = new WeixinInstanceMsgModel();
        $message = $instance_message->getInfo([
            'template_no' => $template_no,
            'instance_id' => $instance_id
        ], '*');
        return $message;
    }

    /**
     * ok-2ok
     * 获取商品相关内容
     * @param $order_goods_id
     * @param int $instance_id
     * @return mixed|string
     */
    private function getGoodsInfo($order_goods_id, $instance_id=0)
    {
        // 模板消息内容
        $goods = new GoodsModel();
        $goods_info = $goods->getInfo([
            'id' => $order_goods_id,
          //  'shop_id' => $instance_id
        ], '*');
        if (!empty($goods_info)) {
            if (!empty($goods_info['title'])) {
                return $goods_info['title'];
            } else {
                return '';
            }
        } else {
            return "";
        }
    }

    /**
     * ok-2ok
     * 查询发货物流信息
     * @param $order_id
     * @return array|false|\PDOStatement|string|\think\Model
     */
    private function getExpressInfo($order_id)
    {
        $ordergoodsexpress = new OrderGoodsExpressModel();
        $express_info = $ordergoodsexpress->getInfo([
            'order_id' => $order_id
        ], '*');
        return $express_info;
    }

    /**
     * ok-2ok
     * 获取用户openid相关内容
     * @param $uid
     * @param int $instance_id
     * @return mixed|string
     */
    private function getUserOpenid($uid, $instance_id = 0)
    {
        // 消息要发送的人
        $weixin = new WeixinHandle();
        $fans_info = $weixin->getUserWeixinSubscribeData($uid, $instance_id);

        if (! empty($fans_info['openid'])) {
            return $fans_info['openid'];
        } else {    
            return '';
        }
    }

    /**
     * ok-2ok
     * 发送消息
     * @param $instance_id
     * @param $openid
     * @param $templateId
     * @param $url
     * @param $first
     * @param $keyword1
     * @param $keyword2
     * @param $keyword3
     * @param $keyword4
     * @param $remark
     */
    private function sendMessage($instance_id, $openid, $templateId, $url, $first, $keyword1, $keyword2, $keyword3, $keyword4, $remark)
    {
        if(isWeixin())
        {
            $weixin_auth = new WchatOauth();
            $weixin_auth->templateMessageSend($openid, $templateId, $url, $first, $keyword1, $keyword2, $keyword3, $keyword4, $remark);
       
        }
        
    }

    /**
     * ok-2ok
     * 获取微信模板消息
     * @param $instance_id
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getWeixinInstanceMsg($instance_id)
    {
        if ($instance_id == '0') {
            $WeixinInstanceMsgModel = new WeixinInstanceMsgModel();
            $message = $WeixinInstanceMsgModel->getConditionQuery('', '*', '');
            return $message;
        } else {
            $WeixinInstanceMsgModel = new WeixinInstanceMsgModel();
            $message = $WeixinInstanceMsgModel->getConditionQuery([
            'instance_id' => $instance_id,
        ], '*', '');
            return $message;
        }
    }

    /**
     * ok-2ok
     * 更新微信模板消息内容
     * @param $instance_id
     * @return bool
     */
    public function updateWeixinInstanceMessage($instance_id)
    {
        $WeixinInstanceMsgModel = new WeixinInstanceMsgModel();
        $message = $WeixinInstanceMsgModel->getConditionQuery('', '*', '');
        $res = 0;
        if (! empty($message)) {
            $weixin_auth = new WchatOauth();
            foreach ($message as $k => $v) {
                if (! empty($weixin_auth)) {
                    $template_no = $weixin_auth->templateID($v['template_no']);
                    if (! empty($template_no->templateID)) {
                       $res =  $WeixinInstanceMsgModel->save([
                            'template_id' => $template_no->templateID,
                            'update_time' => time()
                        ], [
                            'template_no' => $v['template_no'],
                            'instance_id' => $instance_id
                        ]);
                    }
                }
            }
        }

        if ($res > 0) {
            return true;
        } else {
            return false;
        }

    }
    
    /*
     * 获取微信消息模板
     * (non-PHPdoc)
     * @see \ata\api\IWeixinMessage::getWeixinMsgTemplate()
     */
    public function getWeixinMsgTemplate()
    {
        // TODO Auto-generated method stub
    }
    
    /*
     * (non-PHPdoc)
     * @see \ata\api\IWeixinMessage::sendWeixinOrderCreateMessage()
     */
    public function sendWeixinOrderCreateMessage($order_id)
    {
        $res = $this->sendMessage(0, 'oXTarwCCbPb9eouZmwCr6CHtNI0I', 'K6kXn9_h1Z5tFHyT1IB8sQMkGHhuvuKEgbFdkzLcOnk', '', '测试发送first', '测试发送k1', '测试发送k2', '测试发送k3', '测试发送k4', '测试发送re');
        return $res;
        // 消息要发送的内容
//         $order = new NsOrderModel();
//         $order_data = $order->getInfo([
//             'order_id' => $order_id
//         ], '*');
//         // 查询发送人信息
//         $openid = $this->getUserOpenid($order_data['buyer_id'], $order_data['shop_id']);
//         // 查询模板信息
//         $msg_info = $this->getMessageInfo('OPENTM204763758', $order_data['shop_id']);
//         if (! empty($msg_info) && ! empty($openid)) {
//             $this->sendMessage($order_data['shop_id'], $openid, $msg_info['template_id'], '', $msg_info['headtext'], $order_data['out_trade_no'], $order_data['create_time'], $order_data['pay_money'], '微信支付', $msg_info['bottomtext']);
//         }
    }

    /**
     * ok-2ok
     * 发送订单支付消息
     * @param $order_id
     */
    public function sendWeixinOrderPayMessage($order_id)
    {
        // 消息要发送的内容
        $order = new OrderModel();
        $order_data = $order->getInfo([
            'id' => $order_id
        ], '*');
        // 查询发送人信息
        $openid = $this->getUserOpenid($order_data['buyer_id'], $order_data['shop_id']);
        // 查询模板信息
        $msg_info = $this->getMessageInfo('OPENTM200444326', $order_data['shop_id']);
        if (! empty($msg_info) && ! empty($openid)) {
            $this->sendMessage($order_data['shop_id'], $openid, $msg_info['template_id'], '', $msg_info['headtext'], $order_data['out_trade_no'], $order_data['create_time'], $order_data['pay_money'], '微信支付', $order_data['bottomtext']);
        }
    }

    /**
     * ok-2ok
     * 发送订单发货通知
     * @param $order_id
     */
    public function sendWeixinOrderDeliverMessage($order_id)
    {
        // 消息要发送的内容
        $order = new OrderModel();
        $order_data = $order->getInfo([
            'id' => $order_id
        ], '*');
        // 查询发货物流信息
        $express_info = $this->getExpressInfo($order_id);
        // 查询发送人信息
        $openid = $this->getUserOpenid($order_data['buyer_id'], $order_data['shop_id']);
        // 查询模板信息
        $msg_info = $this->getMessageInfo('OPENTM201541214', $order_data['shop_id']);
        if (! empty($msg_info) && ! empty($openid)) {
            $this->sendMessage($order_data['shop_id'], $openid, $msg_info['template_id'], '', $msg_info['headtext'], $order_data['out_trade_no'], $express_info['express_company'], $express_info['express_no'], '', $order_data['bottomtext']);
        }
    }

    /**
     * ok-2ok
     * 发送订单退款结果通知
     * @param $order_id
     * @param $order_goods_id
     */
    public function sendWeixinOrderRefundMessage($order_id, $order_goods_id)
    {
        // 消息要发送的内容
        $order = new OrderModel();
        $order_data = $order->getInfo([
            'id' => $order_id
        ], '*');
        // 查询发送人信息
        $openid = $this->getUserOpenid($order_data['buyer_id'], $order_data['shop_id']);
        // 查询模板信息
        $msg_info = $this->getMessageInfo('OPENTM205986235', $order_data['shop_id']);
        if (! empty($msg_info) && ! empty($openid)) {
            $this->sendMessage($order_data['shop_id'], $openid, $msg_info['template_id'], '', $msg_info['headtext'], $order_data['out_trade_no'], $order_data['pay_money'], '', '', $order_data['bottomtext']);
        }
    }

    /**
     * ok-2ok
     * 发送订单退款申请
     * @param $order_id
     * @param $order_goods_id
     */
    public function sendWeixinOrderRefundApply($order_id, $order_goods_id)
    {
        // 消息要发送的内容
        $order = new OrderModel();
        $order_data = $order->getInfo([
            'id' => $order_id
        ], '*');
        // 查询发送人信息
        $openid = $this->getUserOpenid($order_data['buyer_id'], $order_data['shop_id']);
        // 查询货物信息
        $goods = $this->getGoodsInfo($order_goods_id, $order_data['shop_id']);
        // 查询模板信息
        $msg_info = $this->getMessageInfo('OPENTM207103254', $order_data['shop_id']);
        if (! empty($msg_info) && ! empty($openid)) {
            $this->sendMessage($order_data['shop_id'], $openid, $msg_info['template_id'], '', $msg_info['headtext'], $order_data['pay_money'], $goods, $order_data['out_trade_no'], '', $order_data['bottomtext']);
        }
    }

    /**
     * ok-2ok
     * 给用户发送消息
     * @param $openid
     * @param $msg_type
     * @param $content
     * @return string
     */
    public function sendMessageToUser($openid, $msg_type, $content){
        $weixin_auth = new WchatOauth();
        $res = $weixin_auth->MessageSendToUser($openid, $msg_type, $content);
        return $res;
    }
}