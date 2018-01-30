<?php
// +----------------------------------------------------------------------
// | test [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.zzstudio.net All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Byron Sampson <xiaobo.sun@gzzstudio.net>
// +----------------------------------------------------------------------
namespace addons\wxtemplatemsg;

use addons\Addons;
use dao\extend\WchatOauth;
use dao\model\Goods as GoodsModel;
//use dao\model\MemberBalanceWithdrawModel as NsMemberBalanceWithdrawModel;
use dao\model\OrderGoods as OrderGoodsModel;
use dao\model\Order as OrderModel;
use dao\model\MemberUser  as MemberUserModel;
use dao\handle\OrderHandle;
use dao\handle\order\OrderStatusHandle;

class Wxtemplatemsg extends \addons\Addons
{

    /**
     * ok-2ok
     * @var array
     */
    public $info = array(
        'name' => 'wxtemplatemsg', // 插件名称标识
        'title' => '微信模板消息', // 插件中文名
        'description' => '微信模板消息', // 插件概述
        'status' => 1, // 状态 1启用 0禁用
        'author' => 'xcshop', // 作者
        'version' => '1.0', // 版本号
        'has_addonslist' => 0, // 是否有下级插件 例如：第三方登录插件下有 qq登录，微信登录
        'content' => '', // 插件的详细介绍或使用方法
        'config_hook' => 'wxtemplatemsg'
    );

    /**
     * ok-2ok
     * 设置文件单独的钩子
     * @var string
     */
    public $table = 'xcshop_system_addons_weixin_template_msg';

    /**
     * ok-2ok
     * @var array
     */
    public $menu_info = array(
        [
            'module_name' => '模板消息设置',
            'parent_module_name' => '微信', // 上级模块名称 用来确定上级目录 //
            'last_module_name' => '分享内容设置', // 上一个菜单名称 用来确定菜单排序 //
            'is_menu' => 1, // 是否是菜单
            'is_dev' => 0, // 是否是开发模式可见
            'desc' => '模板消息插件菜单', // 菜单描述
            'module_picture' => '', // 图片（一般为空）
            'icon_class' => '', // 字体图标class（一般为空）
            'is_control_auth' => 1, // 是否有控制权限
            'hook_name' => 'wxtemplatemsg'
        ]
    );

 // 钩子名称（需要该钩子调用的页面）
    
    /**
     * ok-2ok
     * 实现第三方钩子
     *
     * @param array $params            
     */
    public function wxtemplatemsg($params = [])
    {
        $list = \think\Db::table("$this->table")->select();
        $this->assign('list', $list);
        $this->assign('url', __URL(addons_url('wxtemplatemsg://wxtemplatemsg/getTemplateId')));
        $this->assign('change_is_enable_url', __URL(addons_url('wxtemplatemsg://wxtemplatemsg/changeIsEnable')));
        $url = addons_url('wxtemplatemsg://wxtemplatemsg/getTemplateId');
        $this->fetch('wxtemplatemsg');
    }

    /**
     * ok-2ok
     * 订单提交成功通知
     *
     * @param  $params
     */
    public function orderCreateSuccess($params = [])
    {
        $wchat = new WchatOauth();
        // 根据订单ID查询会员openid
        $order_id = $params['order_id'];
        $order = new OrderHandle();
        $order_info = $order->getOrderInfo($order_id);
        $pay_type_name = OrderStatusHandle::getPayType($order_info['payment_type']);
        $uid = $order_info['buyer_id'];
        $url = __URL(__URL__ . "/wap/order/orderdetail?orderId=$order_id");
        $keyword1 = $order_info['order_no']; // 订单编号
        $keyword2 = getTimeStampTurnTime($order_info['create_time']); // 创建时间
        $keyword3 = $order_info['order_money']; // 订单金额
        $keyword4 = $pay_type_name; // 支付类型
        $this->templateMessageSend('OPENTM200444240', '', $uid, $url, $keyword1, $keyword2, $keyword3, $keyword4);
    }

    /**
     * ok-2ok
     * 订单发货成功通知
     *
     * @param  $params
     */
    public function orderDeliverySuccess($params = [])
    {
        $wchat = new WchatOauth();
        $order_id = $params['order_id'];
        $order = new OrderHandle();
        $order_info = $order->getOrderInfo($order_id);
        $uid = $order_info['buyer_id'];
        $url = __URL(__URL__ . "/wap/order/orderdetail?orderId=$order_id");
        $keyword1 = $order_info['order_no']; // 订单编号
        $keyword2 = $params['express_name'] != '' ? $params['express_name'] : '无需物流'; // 快递公司
        $keyword3 = $params['express_no'] != '' ? $params['express_no'] : '无需物流'; // 快递单号
        $keyword4 = '';
        $this->templateMessageSend('OPENTM201541214', '', $uid, $url, $keyword1, $keyword2, $keyword3, $keyword4);
    }

    /**
     * ok-2ok
     * 订单线上付款成功 （订单付款成功通知，本店分销提成通知，下级分店分销提成通知， 下下级分店分销提成通知）（暂时没有测试）
     */
    public function orderOnLinePaySuccess($params = [])
    {
        $wchat = new WchatOauth();
        $order_pay_no = $params['order_pay_no'];
        $order_model = new OrderModel();
        $order = new OrderHandle();
        // 可能是多个订单
        $order_id_array = $order_model->where([
            'out_trade_no' => $order_pay_no
        ])->column('id');
        foreach ($order_id_array as $k => $order_id) {
            $order_info = $order->getOrderInfo($order_id);
            $uid = $order_info['buyer_id'];
            $url = __URL(__URL__ . "/wap/order/orderdetail?orderId=$order_id");
            $keyword1 = $order_info['order_no']; // 订单编号
            $keyword2 = getTimeStampTurnTime($order_info['pay_time']); // 支付时间
            $keyword3 = $order_info['pay_money']; // 支付金额
            $keyword4 = OrderStatusHandle::getPayType($order_info['payment_type']); // 支付方式
            $this->templateMessageSend('OPENTM200444326', '', $uid, $url, $keyword1, $keyword2, $keyword3, $keyword4);
        }
    }

    /**
     * ok-2ok
     * 订单线下付款成功 （订单付款成功通知，本店分销提成通知，下级分店分销提成通知， 下下级分店分销提成通知）
     */
    public function orderOffLinePaySuccess($params = [])
    {
        $wchat = new WchatOauth();
        $order_id = $params['order_id'];
        $order = new OrderHandle();
        $order_info = $order->getOrderInfo($order_id);
        $uid = $order_info['buyer_id'];
        $url = __URL(__URL__ . "/wap/order/orderdetail?orderId=$order_id");
        $keyword1 = $order_info['order_no']; // 订单编号
        $keyword2 = getTimeStampTurnTime($order_info['pay_time']); // 支付时间
        $keyword3 = $order_info['pay_money']; // 支付金额
        $keyword4 = OrderStatusHandle::getPayType($order_info['payment_type']); // 支付方式
        $this->templateMessageSend('OPENTM200444326', '', $uid, $url, $keyword1, $keyword2, $keyword3, $keyword4);
    }

    /**
     * ok-2ok
     * 订单申请退款通知
     */
    public function orderGoodsRefundAskforSuccess($params = [])
    {
        $wchat = new WchatOauth();
        $order_id = $params['order_id'];
        $order = new OrderHandle();
        $order_info = $order->getOrderInfo($order_id);
        $order_goods_id_str = $params['order_goods_id'];
        $goods = new GoodsModel();
        $order_goods = new OrderGoodsModel();
        $order_goods_id_arr = explode(',', $order_goods_id_str);
        $goods_name = '';
        foreach ($order_goods_id_arr as $k => $v) {
            $goods_name = $goods_name . ',' . $order_goods->getInfo([
                'id' => $v
            ], 'goods_name')['goods_name'];
        }
        $goods_name = substr($goods_name, 1);
        $uid = $order_info['buyer_id'];
        $url = __URL(__URL__ . "/wap/order/orderdetail?orderId=$order_id");
        $keyword1 = $params['refund_require_money']; // 退款金额
        $keyword2 = $goods_name; // 商品详情
        $keyword3 = $order_info['order_no']; // 订单编号
        $keyword4 = ''; // 无
        $this->templateMessageSend('OPENTM207103254', '', $uid, $url, $keyword1, $keyword2, $keyword3, $keyword4);
    }

    /**
     * ok-2ok
     * 订单退款结果通知（卖家确认退款）
     */
    public function orderGoodsConfirmRefundSuccess($params = [])
    {
        $wchat = new WchatOauth();
        $order_id = $params['order_id'];
        $order = new OrderHandle();
        $order_info = $order->getOrderInfo($order_id);
        $uid = $order_info['buyer_id'];
        $url = __URL(__URL__ . "/wap/order/orderdetail?orderId=$order_id");
        $keyword1 = $order_info['order_no']; // 订单编号
        $keyword2 = $order_info['pay_money']; // 订单金额
        $keyword3 = $params['refund_real_money']; // 实退金额
        $keyword4 = ''; // 支付方式
        $this->templateMessageSend('OPENTM205986235', '', $uid, $url, $keyword1, $keyword2, $keyword3, $keyword4);
    }

    /**
     * 会员提现申请通知
     */
    /*
    public function memberWithdrawApplyCreateSuccess($params = [])
    {
        $id = $params['id'];
        $withdraw = new MemberBalanceWithdrawModel();
        $info = $withdraw->getInfo([
            'id' => $id
        ], '*');
        $uid = $info['uid'];
        $url = __URL(__URL__ . "/wap/member/balancewithdraw");
        $keyword1 = $info['cash']; // 本次提现金额
        $keyword2 = $info['account_number']; // 提现账户
        $keyword3 = getTimeStampTurnTime($info['ask_for_date']); // 申请时间
        $keyword4 = ''; // 预计到账时间
        $this->templateMessageSend('OPENTM207292959', '提现申请提醒', $uid, $url, $keyword1, $keyword2, $keyword3, $keyword4);
    }
*/
    /**
     * 会员提现申请审核通过（提现审核结果通知）
     */
    /**
    public function memberWithdrawAuditAgree($params = [])
    {
        $id = $params['id'];
        $withdraw = new NsMemberBalanceWithdrawModel();
        $info = $withdraw->getInfo([
            'id' => $id
        ], '*');
        $uid = $info['uid'];
        $url = __URL(__URL__ . "/wap/member/balancewithdraw");
        $keyword1 = $info['cash']; // 本次提现金额
        $keyword2 = $info['account_number']; // 提现账户
        $keyword3 = getTimeStampTurnTime($info['ask_for_date']); // 申请时间
        $keyword4 = ''; // 预计到账时间
        \think\Log::write('测试模板消息会员提现申请审核通过：' . $keyword1 . $keyword2 . $keyword3 . $keyword4);
        $this->templateMessageSend('OPENTM207292959', '提现审核结果通知', $uid, $url, $keyword1, $keyword2, $keyword3, $keyword4);
    }
*/
    /**
     * ok-2ok
     * 会员注册成功通知
     */
    public function memberRegisterSuccess($params = [])
    {
        $uid = $params['uid'];
        $url = '';
        $keyword1 = $params['member_name']; // 会员昵称
        $keyword2 = getTimeStampTurnTime($params['reg_time']); // 注册时间
        $keyword3 = '';
        $keyword4 = '';
        $this->templateMessageSend('OPENTM203347141', '', $uid, $url, $keyword1, $keyword2, $keyword3, $keyword4);
    }

    /**
     * **************************************************分销版本模板消息**********************************************************************
     */
    /**
     * 推广员申请创建成功
     */
    /**
    public function promoterApplyCreateSuccess($params = [])
    {
        $uid = $params['uid'];
        $url = '';
        $keyword1 = $params['promoter_shop_name']; // 店铺名称
        $keyword2 = getTimeStampTurnTime($params['regidter_time']); // 通过时间
        $keyword3 = '';
        $keyword4 = '';
        $this->templateMessageSend('OPENTM409846856', '推广员申请提醒', $uid, $url, $keyword1, $keyword2, $keyword3, $keyword4);
    }
    **/

    /**
     * 推广员审核结果 （推广员的审核通知， 下级推广员审核通知， 下下级推广员审核通知）
     */
    /**
    public function promoterAuditAgreeSuccess($params = [])
    {
        $uid = $params['uid'];
        $url = '';
        $keyword1 = $params['promoter_shop_name']; // 店铺名称
        $keyword2 = getTimeStampTurnTime($params['regidter_time']); // 通过时间
        $keyword3 = '';
        $keyword4 = '';
        $this->templateMessageSend('OPENTM409846856', '申请通过通知', $uid, $url, $keyword1, $keyword2, $keyword3, $keyword4);
        // 判断当前推广员是否有上级
    }
**/
    /**
     * ok-2ok
     * 发送模板消息
     * $template_no,$title 作为查询模板消息的条件 $title主要用来查询 当模板编号相同时，用来区分是哪个模板消息 ，可以为空
     */
    protected function templateMessageSend($template_no, $title, $uid, $url, $keyword1, $keyword2, $keyword3, $keyword4)
    {
        $wchat = new WchatOauth();
        $openid = $this->getOpenidByUid($uid);
        if ($openid) {
            // 根据模板编号查出 模板信息
            $where['template_no'] = $template_no;
            if ($title != '') {
                $where['title'] = $title;
            }
            $t_info = \think\Db::table("$this->table")->where($where)->find();
            if ($t_info['is_enable'] == 1) {
                $wchat->templateMessageSend($openid, $t_info['template_id'], $url, $t_info['headtext'], $keyword1, $keyword2, $keyword3, $keyword4, $t_info['bottomtext']);
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 根据uid获取openid
     */
    protected function getOpenidByUid($uid)
    {
        $uesr = new MemberUserModel();
        // 获取会员的openid
        $openid = $uesr->getInfo([
            'id' => $uid
        ], 'wx_openid');
        if ($openid) {
            return $openid['wx_openid'];
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 安装方法
     */
    public function install()
    {
        $this->uninstall();
        $table_name = $this->table;
        $sql = <<<SQL
        CREATE TABLE `{$table_name}` (
          id int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
          instance_id int(11) NOT NULL COMMENT '店铺id（单店版为0）',
          template_no varchar(55) NOT NULL COMMENT '模版编号',
          template_id varbinary(55) DEFAULT NULL COMMENT '微信模板消息的ID',
          title varchar(100) NOT NULL COMMENT '模版标题',
          is_enable tinyint(4) NOT NULL DEFAULT 1 COMMENT '是否启用',
          headtext varchar(255) NOT NULL COMMENT '头部文字',
          bottomtext varchar(255) NOT NULL COMMENT '底部文字',
          create_time int(11) NOT NULL DEFAULT '0' COMMENT '建立时间',
          update_time int(11) NOT NULL DEFAULT '0' COMMENT '修改时间',
          PRIMARY KEY (id),
          KEY IDX_instance_id (instance_id) USING BTREE,
          KEY IDX_template_no (template_no) USING BTREE,
          KEY IDX_template_id (template_id) USING BTREE
        )
        ENGINE = INNODB
        AUTO_INCREMENT = 1
        AVG_ROW_LENGTH = 4096
        CHARACTER SET utf8
        COLLATE utf8_general_ci
        COMMENT = '微信实例消息';
SQL;
        \think\Db::execute($sql);
        if (count(db()->query("SHOW TABLES LIKE '{$table_name}'")) != 1) {
            session('addons_install_error', ',微信模板消息表未创建成功，请手动检查插件中的sql，修复后重新安装');
            return false;
        } else {
            $data = array(
                [
                    'instance_id' => 0,
                    'template_no' => 'OPENTM203347141',
                    'template_id' => '',
                    'title' => '会员注册成功通知',
                    'is_enable' => 1,
                    'headtext' => '恭喜，您已成功注册为会员！',
                    'bottomtext' => '恭喜，您已成为会员，您将享受会员所有权利！',
                    'create_time' => time(),
                    'update_time' => time()
                ],
                [
                    'instance_id' => 0,
                    'template_no' => 'OPENTM200444240',
                    'template_id' => '',
                    'title' => '订单提交成功通知',
                    'is_enable' => 1,
                    'headtext' => '亲！您已成功创建订单，点击进入完成付款。',
                    'bottomtext' => '感谢您的支持，我们将为您提供更好的服务。',
                    'create_time' => time(),
                    'update_time' => time()
                ],
                [
                    'instance_id' => 0,
                    'template_no' => 'OPENTM201541214',
                    'template_id' => '',
                    'title' => '订单发货通知',
                    'is_enable' => 1,
                    'headtext' => '亲，你的订单已发货',
                    'bottomtext' => '请关注订单,注意查收',
                    'create_time' => time(),
                    'update_time' => time()
                ],
                [
                    'instance_id' => 0,
                    'template_no' => 'OPENTM200444326',
                    'template_id' => '',
                    'title' => '订单付款成功通知',
                    'is_enable' => 1,
                    'headtext' => '亲！您的订单已成功付款。',
                    'bottomtext' => '',
                    'create_time' => time(),
                    'update_time' => time()
                ],
                [
                    'instance_id' => 0,
                    'template_no' => 'OPENTM207103254',
                    'template_id' => '',
                    'title' => '退款申请',
                    'is_enable' => 1,
                    'headtext' => '您已申请退款，我们将尽快处理您提交的退款申请。您可以在“退换货”中查看退款进度',
                    'bottomtext' => '如您的退款有疑问，请联系我们的客服人员，帮助您解决处理！',
                    'create_time' => time(),
                    'update_time' => time()
                ],
                [
                    'instance_id' => 0,
                    'template_no' => 'OPENTM205986235',
                    'template_id' => '',
                    'title' => '退款结果通知',
                    'is_enable' => 1,
                    'headtext' => '亲，您的退款已处理。',
                    'bottomtext' => '如您还有疑问，请与客服人员联系。',
                    'create_time' => time(),
                    'update_time' => time()
                ],
                [
                    'instance_id' => 0,
                    'template_no' => 'OPENTM207292959',
                    'template_id' => '',
                    'title' => '提现申请提醒',
                    'is_enable' => 1,
                    'headtext' => '亲，您的提现申请已提交',
                    'bottomtext' => '我们将在1到3个工作日内处理完毕，请耐心等待。',
                    'create_time' => time(),
                    'update_time' => time()
                ],
                [
                    'instance_id' => 0,
                    'template_no' => 'OPENTM400094285',
                    'template_id' => '',
                    'title' => '提现审核结果通知',
                    'is_enable' => 1,
                    'headtext' => '亲，您提现申请已通过',
                    'bottomtext' => '我们将在1到3个工作日内处理完毕，请耐心等待。',
                    'create_time' => time(),
                    'update_time' => time()
                ],
                [
                    'instance_id' => 0,
                    'template_no' => 'OPENTM409846856',
                    'template_id' => '',
                    'title' => '申请通过通知',
                    'is_enable' => 1,
                    'headtext' => '您的推广员申请已经通过',
                    'bottomtext' => '如您还有疑问，请与客服人员联系。',
                    'create_time' => time(),
                    'update_time' => time()
                ],
                [
                    'instance_id' => 0,
                    'template_no' => 'OPENTM409846856',
                    'template_id' => '',
                    'title' => '下级申请通过通知',
                    'is_enable' => 1,
                    'headtext' => '您的下级推广员申请已经通过',
                    'bottomtext' => '如您还有疑问，请与客服人员联系。',
                    'create_time' => time(),
                    'update_time' => time()
                ],
                [
                    'instance_id' => 0,
                    'template_no' => 'OPENTM409846856',
                    'template_id' => '',
                    'title' => '下下级申请通过通知',
                    'is_enable' => 1,
                    'headtext' => '您的下下级推广员申请已经通过',
                    'bottomtext' => '如您还有疑问，请与客服人员联系。',
                    'create_time' => time(),
                    'update_time' => time()
                ],
                [
                    'instance_id' => 0,
                    'template_no' => 'OPENTM201010537',
                    'template_id' => '',
                    'title' => '本店分销订单提成通知',
                    'is_enable' => 1,
                    'headtext' => '亲，您已成功分销出一笔订单，继续努力哦',
                    'bottomtext' => '',
                    'create_time' => time(),
                    'update_time' => time()
                ],
                [
                    'instance_id' => 0,
                    'template_no' => 'OPENTM201010537',
                    'template_id' => '',
                    'title' => '下级分店分销订单提成通知',
                    'is_enable' => 1,
                    'headtext' => '亲，您的下级推广员成功分销出一笔订单，继续努力哦',
                    'bottomtext' => '',
                    'create_time' => time(),
                    'update_time' => time()
                ],
                [
                    'instance_id' => 0,
                    'template_no' => 'OPENTM201010537',
                    'template_id' => '',
                    'title' => '下下级分店分销订单提成通知',
                    'is_enable' => 1,
                    'headtext' => '亲，您的下下级推广员成功分销出一笔订单，继续努力哦',
                    'bottomtext' => '',
                    'create_time' => time(),
                    'update_time' => time()
                ]
            );
            \think\Db::table("$table_name")->insertAll($data);
        }
        return true;
    }

    /**
     * ok-2ok
     * 卸载方法
     */
    public function uninstall()
    {
        $table_name = $this->table;
        $sql = "DROP TABLE IF EXISTS `{$table_name}`;";
        \think\Db::execute($sql);
        return true;
    }
}