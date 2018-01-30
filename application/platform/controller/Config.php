<?php
/**
 * 配置控制器
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-11-25
 * Time: 16:55
 */

namespace app\platform\controller;

use app\platform\controller\BaseController;

use dao\handle\SiteHandle as SiteHandle;
use dao\handle\ConfigHandle as ConfigHandle;
use dao\extend\Send as Send;
use think\Log;


class Config extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * ok-2ok
     * 网站设置
     */
    public function setSiteInfo()
    {
        $platform_site_name =  request()->post('platform_site_name', ''); // 平台站点名称
        $agent_site_name =  request()->post('agent_site_name', ''); // 代理商站点名称
        $web_shop_site_name =  request()->post('web_shop_site_name', ''); // PC商城名称
        $wap_shop_site_name =  request()->post('wap_shop_site_name', ''); // 手机商城名称
        //
        $platform_title = request()->post('platform_title', ''); // 平台网站标题
        $agent_title = request()->post('agent_title', ''); // 代理商网站标题
        $web_shop_title = request()->post('web_shop_title', ''); // web商城标题
        $wap_shop_title = request()->post('wap_shop_title', ''); // wap商城标题
        $logo = request()->post('logo', ''); // 网站logo
        $web_desc = request()->post('web_desc', ''); // 网站描述
        $shop_key_words = request()->post('shop_key_words', ''); // 商城关键字
        $web_icp = request()->post('web_icp', ''); // 网站备案号
        $web_icp_link = request()->post('web_icp_link', '');
        $web_shop_style_id = request()->post('web_shop_style_id', 0); // web商城风格id
        $wap_shop_style_id = request()->post('wap_shop_style_id', 0); // wap商城风格id
        $web_address = request()->post('web_address', ''); // 网站联系地址
        $web_qrcode = request()->post('web_qrcode', ''); // 网站公众号二维码
        $web_url = request()->post('web_url', ''); // web网址
        $web_shop_url = request()->post('web_shop_url', ''); // web店铺url
        $wap_shop_url = request()->post('wap_shop_url', ''); // wap店url
        $web_email = request()->post('web_email', ''); //网站邮箱
        $web_phone = request()->post('web_phone', ''); // 网站联系方式
        $web_qq = request()->post('web_qq', ''); // 网站qq号
        $web_weixin = request()->post('web_weixin', ''); // 网站微信号
        $third_count = request()->post("third_count", ''); // 第三方统计
        $close_reason = request()->post("close_reason", ''); // 站点关闭原因
        $web_status = request()->post("web_status", 1); // 网站运营状态

        $wap_shop_status = request()->post("wap_shop_status", 1); // 手机商城运营状态
        $web_shop_status = request()->post("web_shop_status", 1); // WEB商城运营状态
        $style_id_admin = request()->post("style_id_admin", 0); // 后台网站风格
        $url_type = request()->post("url_type", 0); // 网站访问模式
        $web_popup_title = request()->post("web_popup_title", ''); // 网站弹出框标题

        $site = new SiteHandle();
        $retval = $site->updateSite($platform_site_name, $agent_site_name, $web_shop_site_name, $wap_shop_site_name,
            $platform_title,$agent_title,$web_shop_title,$wap_shop_title,
            $logo, $web_desc,$shop_key_words,$web_icp, $web_icp_link,
            $web_shop_style_id,$wap_shop_style_id,$web_address,$url_type,
            $web_qrcode,$web_url, $web_shop_url,$wap_shop_url, $web_email,
            $web_phone,$web_qq,$web_weixin,$third_count,$close_reason, $web_status,
            $wap_shop_status, $web_shop_status,$style_id_admin,$url_type,$web_popup_title);

        if (empty($retval)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 得到站点配置信息
     * @return \think\response\Json
     */
    public function getSiteInfo()
    {
        $site = new SiteHandle();
        $info = $site->getSiteInfo();
        return json(resultArray(0, "操作成功", $info));

    }

    /**
     * ok-2ok
     * seo设置
     */
    public function setSeoConfig()
    {
        $config = new ConfigHandle();
        $shop_id = 0;
        $seo_title = request()->post("seo_title");
        $seo_meta = request()->post("seo_meta");
        $seo_desc = request()->post("seo_desc");
        $seo_other = request()->post("seo_other");
        $retval = $config->setSeoConfig($shop_id, $seo_title, $seo_meta, $seo_desc, $seo_other);
        if (empty($retval)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 得到seo信息
     * @return \think\response\Json
     */
    public function getSeoConfig() {
        $config = new ConfigHandle();
        $shop_id = 0;
        $shopSet = $config->getSeoConfig($shop_id);
        return json(resultArray(0, "操作成功", $shopSet));
    }

    /**
     * ok-2ok
     * 版权设置
     */
    public function setCopyrightInfo()
    {
        $config = new ConfigHandle();
        $shop_id = 0;
        $copyright_logo = request()->post("copyright_logo");
        $copyright_meta = request()->post("copyright_meta");
        $copyright_link = request()->post("copyright_link");
        $copyright_desc = request()->post("copyright_desc");
        $copyright_companyname = request()->post("copyright_companyname");
        $retval = $config->setCopyrightConfig($shop_id, $copyright_logo, $copyright_meta, $copyright_link, $copyright_desc, $copyright_companyname);

        if (empty($retval)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 得到版权信息
     * @return \think\response\Json
     */
    public function getCopyrightInfo() {
        $config = new ConfigHandle();
        $shop_id = 0;
        $shopSet = $config->getCopyrightConfig($shop_id);
        return json(resultArray(0, "操作成功", $shopSet));
    }

    /**
     * ok-2ok
     * 购物设置
     */
    public function setShopSet()
    {
        $config = new ConfigHandle();

        $order_auto_delinery = request()->post("order_auto_delinery");
        if (empty($order_auto_delinery)) {
            $order_auto_delinery = 0;
        }

        $order_point_pay = request()->post("order_point_pay");
        if (empty($order_point_pay)) {
            $order_point_pay = 0;
        }

        $order_balance_pay = request()->post("order_balance_pay");
        if (empty($order_balance_pay)) {
            $order_balance_pay = 0;
        }
        $order_delivery_complete_time = request()->post("order_delivery_complete_time");
        if (empty($order_delivery_complete_time)) {
            $order_delivery_complete_time = 0;
        }
        $order_show_buy_record = request()->post("order_show_buy_record");
        if (empty($order_show_buy_record)) {
            $order_show_buy_record = 0;
        }
        $order_invoice_tax = request()->post("order_invoice_tax");
        if (empty($order_invoice_tax)) {
            $order_invoice_tax = 0;
        }
        $order_invoice_content = request()->post("order_invoice_content", '');

        $order_delivery_pay = request()->post("order_delivery_pay") ;
        if (empty($order_delivery_pay)) {
            $order_delivery_pay = 0;
        }
        $order_buy_close_time = request()->post("order_buy_close_time");
        if (empty($order_buy_close_time)) {
            $order_buy_close_time = 0;
        }
        $buyer_self_lifting = request()->post("buyer_self_lifting");
        if (empty($buyer_self_lifting)) {
            $buyer_self_lifting = 0;
        }
        $seller_dispatching = request()->post("seller_dispatching", '1');
        $is_logistics = request()->post("is_logistics", '0');
        $shopping_back_points = request()->post("shopping_back_points", '');
        if (empty($shopping_back_points)) {
            $shopping_back_points = 0;
        }
        $retval = $config->setShopConfig( $order_auto_delinery,$order_point_pay, $order_balance_pay, $order_delivery_complete_time, $order_show_buy_record, $order_invoice_tax, $order_invoice_content, $order_delivery_pay, $order_buy_close_time, $buyer_self_lifting, $seller_dispatching, $is_logistics, $shopping_back_points);

        if (empty($retval)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 得到购物配置
     * @return \think\response\Json
     */
    public function  getShopSet() {
        $config = new ConfigHandle();
        $shopSet = $config->getShopConfig();
        return json(resultArray(0, "操作成功", $shopSet));
    }


    /**
     * ok-2ok
     * 设置物流跟踪配置
     * @return \think\response\Json
     */
    public function setExpressMessage()
    {
        $config = new ConfigHandle();

        $shop_id = 0;
        $appid = request()->post("appid");
        $appkey = request()->post("appkey");
        $back_url = request()->post('back_url', '');
        $is_use = request()->post("is_use");

        $res = $config->updateOrderExpressMessageConfig($shop_id, $appid, $appkey, $back_url, $is_use);

        if (empty($res)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 得到物流跟踪配置信息
     * @return \think\response\Json
     */
    public function getExpressMessage() {
        $config = new ConfigHandle();
        $shop_id = 0;
        $expressMessageConfig = $config->getOrderExpressMessageConfig($shop_id);
        return json(resultArray(0, "操作成功", $expressMessageConfig));
    }

    /**
     * ok-2ok
     * 代理商提现设置
     */
    public function setAgentWithdrawSetting()
    {
        $config = new ConfigHandle();
        $key = 'AGENT_WITHDRAW_BALANCE';
        $value = array(
            'agent_withdraw_cash_min' => request()->post('agent_withdraw_cash_min'),
            'agent_withdraw_multiple' => request()->post('agent_withdraw_multiple'),
            'agent_withdraw_poundage' => request()->post('agent_withdraw_poundage'),
            'agent_withdraw_message' => request()->post('agent_withdraw_message')
        );
        $is_use = request()->post('is_use');
        $retval = $config->setAgentBalanceWithdrawConfig($key, $value, $is_use);

        if (empty($retval)) {
            return json(resultArray(2, "操作失败 " . $config->getError()));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }


    /**
     * ok-2ok
     * 得到代理商提现设置
     * @return \think\response\Json
     */
    public function getAgentWithdrawSetting() {
        $config = new ConfigHandle();
        $list = $config->getAgentBalanceWithdrawConfig();

        if (empty($list)) {
            $list['id'] = '';
            $list['value']['agent_withdraw_cash_min'] = '';
            $list['value']['agent_withdraw_multiple'] = '';
            $list['value']['agent_withdraw_poundage'] = '';
            $list['value']['agent_withdraw_message'] = '';
        }
        return json(resultArray(0, "操作成功", $list));
    }

    /**
     * ok-2ok
     * 图片生成配置
     */
    public function setPictureUploadSetting()
    {
        $config = new ConfigHandle();
        $thumb_type = request()->post("thumb_type");
        $upload_size = request()->post("upload_size", "0");
        $upload_ext = request()->post("upload_ext", "gif,jpg,jpeg,bmp,png");
        $data = array(
            "thumb_type" => $thumb_type,
            "upload_size" => $upload_size,
            "upload_ext" => $upload_ext
        );
        $retval = $config->setPictureUploadSetting(0, json_encode($data));
        if (empty($retval)) {
            return json(resultArray(2, "操作失败 " . $config->getError()));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 得到图片上传配置
     * @return \think\response\Json
     */
    public function getPictureUploadSetting() {
        $config = new ConfigHandle();
        $info = $config->getPictureUploadSetting(0);
        return json(resultArray(0, "操作成功", $info));
    }

    /**
     * ok-2ok
     * 邮件短信接口设置
     */
    public function messageConfig()
    {
        $config = new ConfigHandle();
        $email_message = $config->getEmailMessage(0);
        $mobile_message = $config->getMobileMessage(0);
        $data = array (
            'email_message'=>$email_message,
            'mobile_message'=>$mobile_message
        );

        return json(resultArray(0, "操作成功", $data));
    }

    /**
     * ok-2ok
     * 设置邮件接口
     */
    public function setEmailMessage()
    {
        $email_host = request()->post('email_host');
        $email_port = request()->post('email_port');
        $email_addr = request()->post('email_addr');
        $email_id = request()->post('email_id');
        $email_pass = request()->post('email_pass');
        $is_use = request()->post('is_use');
        $email_is_security = request()->post('email_is_security');
        $config = new ConfigHandle();
        $res = $config->setEmailMessage(0, $email_host, $email_port, $email_addr, $email_id, $email_pass, $is_use, $email_is_security);

        if (empty($res)) {
            return json(resultArray(2, "操作失败 " . $config->getError()));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 设置短信接口
     */
    public function setMobileMessage()
    {
        $app_key = request()->post('app_key');
        $secret_key = request()->post('secret_key');
        $free_sign_name = request()->post('free_sign_name');
        $is_use = request()->post('is_use');
        $user_type = request()->post('user_type'); // 用户类型 0:旧用户，1：新用户 默认是旧用户
        $config = new ConfigHandle();
        $res = $config->setMobileMessage(0, $app_key, $secret_key, $free_sign_name, $is_use, $user_type);

        if (empty($res)) {
            return json(resultArray(2, "操作失败 " . $config->getError()));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * 邮件发送测试接口
     */
    public function testSend()
    {
        $is_socket = extension_loaded('sockets');
        $is_connect = function_exists("socket_connect");
        if ($is_socket && $is_connect) {
            $send = new Send();
            // $toemail = "854991437@qq.com";//$_POST['email_test'];
            $title = '美肤日制SKINDAY测试邮件发送';
            $content = '测试邮件发送是否成功？';
            $email_host = request()->post('email_host');
            $email_port = request()->post('email_port');
            $email_addr = request()->post('email_addr');
            $email_id = request()->post('email_id');
            $email_pass = request()->post('email_pass');
            $email_is_security = request()->post('email_is_security');
            $toemail = request()->post('email_test');

            Log::write("email_host:".$email_host);
            Log::write("email_port:".$email_port);
            Log::write("email_addr:".$email_addr);
            Log::write("email_id:".$email_id);
            Log::write("email_pass:".$email_pass);
            Log::write("email_is_security:".$email_is_security);
            Log::write("toemail:".$toemail);



            $res = emailSend($email_host, $email_id, $email_pass, $email_port, $email_is_security, $email_addr, $toemail, $title, $content,'美肤日制SKINDAY商城');
            // $config = new WebConfig();
            // $email_message = $config->getEmailMessage($this->instance_id);
            // $email_value = $email_message["value"];
            // $res = emailSend($email_value['email_host'], $email_value['email_id'], $email_value['email_pass'], $email_value['email_addr'], $toemail, $title, $content);
            // var_dump($res);
            // exit;
            if ($res) {
                return json(resultArray(0, "发送成功"));
            } else {
                return json(resultArray(2, "发送失败"));
            }
        } else {
            return json(resultArray(2, "邮件发送错误"));
        }
    }

    /**
     * ok-2ok
     * 通知系统
     */
    public function notifyIndex()
    {
        $config = new ConfigHandle();
        $shop_id = 0;
        $notify_list = $config->getNoticeConfig($shop_id);

        return json(resultArray(0, "操作成功", $notify_list));
    }

    /**
     * ok-2ok
     * 开启和关闭 邮件 和短信的开启和 关闭
     */
    public function updateNotifyEnable()
    {
        $id = request()->post('id');
        $is_use = request()->post('is_use');
        $config = new ConfigHandle();
        $retval = $config->updateConfigEnable($id, $is_use);

        if (empty($retval)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 更新通知模板
     */
    public function updateNotifyTemplate()
    {
        $template_type = request()->post('type', 'sms');
        $template_data = request()->post('template_data');
        $notify_type = request()->post("notify_type"); //"user"
        $shop_id = 0;
        $config = new ConfigHandle();
        $retval = $config->updateNoticeTemplate($shop_id, $template_type, $template_data, $notify_type);

        if (empty($retval)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 商家通知
     */
    public function businessNotifyTemplate()
    {
        $type = request()->post("type", "sms");
        $config = new ConfigHandle();
        $shop_id = 0;
        $template_detail = $config->getNoticeTemplateDetail($shop_id, $type);

        $template_type_list = $config->getNoticeTemplateType($type, "business");
    //    getNoticeTemplateType($template_type,$notify_type)
        for ($i = 0; $i < count($template_type_list); $i ++) {
            $template_code = $template_type_list[$i]["template_code"];
            $notify_type = $template_type_list[$i]["notify_type"];
            $is_enable = 0;
            $template_title = "";
            $template_content = "";
            $sign_name = "";
            $notification_mode="";
            foreach ($template_detail as $template_obj) {
                if ($template_obj["template_code"] == $template_code && $template_obj["notify_type"] == $notify_type) {
                    $is_enable = $template_obj["is_enable"];
                    $template_title = $template_obj["template_title"];
                    $template_content = str_replace(PHP_EOL, '', $template_obj["template_content"]);
                    $sign_name = $template_obj["sign_name"];
                    $notification_mode = $template_obj["notification_mode"];
                    break;
                }
            }
            $template_type_list[$i]["is_enable"] = $is_enable;
            $template_type_list[$i]["template_title"] = $template_title;
            $template_type_list[$i]["template_content"] = $template_content;
            $template_type_list[$i]["sign_name"] = $sign_name;
            $template_type_list[$i]["notification_mode"] = $notification_mode;
        }
        $template_item_list = $config->getNoticeTemplateItem($template_type_list[0]["template_code"]);

        $data = array (
            "template_type_list"=>$template_type_list,
            "template_json"=> json_encode($template_type_list),
            "template_select"=>$template_type_list[0],
            "template_item_list"=> $template_item_list,
            "template_send_item_json"=>json_encode($template_item_list)
        );

        return json(resultArray(0, "操作成功", $data));
    }

    /**
     * ok-2ok
     * 修改模板
     *
     * @return \think\response\View
     */
    public function notifyTemplate()
    {
        $type = request()->post('type', 'sms');
        $config = new ConfigHandle();
        $shop_id = 0;
        $template_detail = $config->getNoticeTemplateDetail($shop_id, $type);
        $template_type_list = $config->getNoticeTemplateType($type, "user");

        for ($i = 0; $i < count($template_type_list); $i ++) {
            $template_code = $template_type_list[$i]["template_code"];
            $is_enable = 0;
            $template_title = "";
            $template_content = "";
            $sign_name = "";
            foreach ($template_detail as $template_obj) {
                if ($template_obj["template_code"] == $template_code) {
                    $is_enable = $template_obj["is_enable"];
                    $template_title = $template_obj["template_title"];
                    $template_content = str_replace(PHP_EOL, '', $template_obj["template_content"]);
                    $sign_name = $template_obj["sign_name"];
                    break;
                }
            }
            $template_type_list[$i]["is_enable"] = $is_enable;
            $template_type_list[$i]["template_title"] = $template_title;
            $template_type_list[$i]["template_content"] = $template_content;
            $template_type_list[$i]["sign_name"] = $sign_name;
        }
        $template_item_list = $config->getNoticeTemplateItem($template_type_list[0]["template_code"]);
        $data = array(
            "template_type_list"=> $template_type_list,
            "template_json"=>json_encode($template_type_list),
            "template_select"=> $template_type_list[0],
            "template_item_list"=> $template_item_list,
            "template_send_item_json"=>json_encode($template_item_list)
        );

        return json(resultArray(0, "操作成功", $data));

    }

    /**
     * ok-2ok
     * 得到可用的模板项
     */
    public function getTemplateItem()
    {
        $template_code = request()->post('template_code');
        $config = new ConfigHandle();
        $template_item_list = $config->getNoticeTemplateItem($template_code);
        return json(resultArray(0, "操作成功", $template_item_list));
    }

    /**
     * ok-2ok
     * 设置客服
     */
    public function setCustomService()
    {

        $shop_id = 0; // $this->instance_id;
        $key = 'SERVICE_ADDR';
        $value = array(
            'service_addr' => request()->post('service_addr')
        );
        $is_use = request()->post('is_use');
        $config = new ConfigHandle();
        $retval = $config->setCustomServiceConfig($shop_id, $key, $value, $is_use);

        if (empty($retval)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }


    /**
     * ok-2ok
     * 得到客服设置
     * @return \think\response\Json
     */
    public function  customerService()
    {
        $shop_id = 0;
        $config = new ConfigHandle();
        $list = $config->getCustomServiceConfig($shop_id);

        if (empty($list)) {
            $list['id'] = '';
            $list['value']['service_addr'] = '';
        }

        return json(resultArray(0, "操作成功",$list));
    }



    /**
     * ok-2ok
     * 支付配置--微信
     */
    public function setPayWchatConfig()
    {
        $config = new ConfigHandle();
        // 微信支付
        $appid = str_replace(' ', '', request()->post('appid'));
        $appsecret = str_replace(' ', '', request()->post('appsecret'));
        $paySignKey = str_replace(' ', '', request()->post('paySignKey'));
        $MCHID = str_replace(' ', '', request()->post('MCHID'));
        $is_use = request()->post('is_use');
        // 保存数据
        $retval = $config->setWpayConfig(0, $appid, $appsecret, $MCHID, $paySignKey, $is_use);

        if (empty($retval)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 得到微信支付配置信息
     * @return \think\response\Json
     */
    public function payWchatConfig() {
        $config = new ConfigHandle();
        $data = $config->getWpayConfig(0);
        return json(resultArray(0, "操作成功", $data));
    }

    /**
     * ok-2ok
     * 设置支付宝配置
     */
    public function setPayAliConfig()
    {
        $config = new ConfigHandle();
        // 支付宝
        $partnerid = str_replace(' ', '', request()->post('partnerid'));
        $seller = str_replace(' ', '', request()->post('seller'));
        $ali_key = str_replace(' ', '', request()->post('ali_key'));
        $is_use = request()->post('is_use');
            // 获取数据
        $retval = $config->setAlipayConfig(0, $partnerid, $seller, $ali_key, $is_use);

        if (empty($retval)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 得到阿里支付配置
     * @return \think\response\Json
     */
    public function payAliConfig() {
        $config = new ConfigHandle();
        $data = $config->getAlipayConfig(0);
        return json(resultArray(0, "操作成功", $data));
    }

    /**
     * ok-2ok
     * 设置微信和支付宝开关状态是否启用
     */
    public function setPayStatus()
    {
        $config = new ConfigHandle();
        $is_use = request()->post("is_use");
        $type = request()->post("type");
        $retval = $config->setPayStatusConfig(0, $is_use, $type);

        if (empty($retval)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }



    /**
     * ok-2ok
     * 支付
     */
    public function paymentConfig()
    {
        $config = new ConfigHandle();
        $shop_id = 0;
        $pay_list = $config->getPayConfig($shop_id);
        return json(resultArray(0, "操作成功", $pay_list));
    }


    /**
     * ok-2ok
     * 原路退款设置
     */
    public function originalRoadRefundSetting()
    {
        $config = new ConfigHandle();
        $type = request()->post("type", "wechat"); // 默认微信
        if (empty($type)) {
            $type = "wechat"; // （type=），这种情况下获取到的值为空
        }

        // 设置默认值
        if ($type == 'wechat') {
            $original_road_refund_setting_info = [
                "is_use" => 0,
                "apiclient_cert" => "",
                "apiclient_key" => ""
            ];
        } elseif ($type == "alipay") {
            $original_road_refund_setting_info = [
                "is_use" => 0
            ];
        }

        $pay_list = $config->getPayConfig(0);
        $wechat_is_use = 0; // 微信支付开启标识
        foreach ($pay_list as $v) {
            if ($v['key'] == "ALIPAY") {
                $alipay_is_use = $v['is_use'];
            } elseif ($v['key'] == 'WPAY') {
                $wechat_is_use = $v['is_use'];
            }
        }

        $data = $config->getOriginalRoadRefundSetting(0, $type);

        if (! empty($data)) {
            $original_road_refund_setting_info = json_decode($data['value'], true);
        }

        $info = array(
            "alipay_is_use"=> $alipay_is_use,
            "wechat_is_use"=> $wechat_is_use,
            "original_road_refund_setting_info"=> $original_road_refund_setting_info
        );

        return json(resultArray(0, "操作成功", $info));
    }

    /**
     * ok-2ok
     * 设置原路退款信息
     */
    public function setOriginalRoadRefundSetting()
    {
        $type = request()->post("type");
        $value = request()->post("value");
        $res = 0;

        if (! empty($type) && ! empty($value)) {
            $config = new ConfigHandle();
            $res = $config->setOriginalRoadRefundSetting(0, $type, $value);
        }

        if (empty($res)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 设置支付订单描述信息配置
     */
    public function setOrderPayInfoConfig()
    {
        $config = new ConfigHandle();

        $pay_body =  request()->post('pay_body');
        $pay_details =  request()->post('pay_details');

        // 保存数据
        $retval = $config->setPayInfoConfig(0, $pay_body, $pay_details);


        if (empty($retval)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 得到支付订单描述信息配置
     */
    public function getOrderPayInfoConfig() {
        $config = new ConfigHandle();
        $data = $config->getPayInfoConfig(0);
        return json(resultArray(0, "操作成功", $data));
    }

    /**
     * 配置伪静态路由规则
     */
    public function customPseudoStaticRule()
    {
        if (request()->isAjax()) {
            $webSite = new WebSite();
            $page_index = request()->post("page_index", 1);
            $page_size = request()->post("page_size", PAGESIZE);
            $rule_list = $webSite->getUrlRouteList($page_index, $page_size);
            return $rule_list;
        }
        return view($this->style . "Config/customPseudoStaticRule");
    }

    /**
     * 添加路由规则
     */
    public function addRoutingRules()
    {
        if (request()->isAjax()) {
            $rule = request()->post("rule", "");
            $route = request()->post("route", "");
            $is_open = request()->post("is_open", 1);
            $route_model = request()->post("route_model", 1);
            $remark = request()->post("remark", "");
            $webSite = new WebSite();
            $res = $webSite->addUrlRoute($rule, $route, $is_open, $route_model, $remark);
            return AjaxReturn($res);
        }
        return view($this->style . "Config/addRoutingRules");
    }

    /**
     * 编辑路由规则
     */
    public function updateRoutingRule()
    {
        $webSite = new WebSite();
        if (request()->isAjax()) {
            $routeid = request()->post("routeid", "");
            $rule = request()->post("rule", "");
            $route = request()->post("route", "");
            $is_open = request()->post("is_open", 1);
            $route_model = request()->post("route_model", 1);
            $remark = request()->post("remark", "");
            $res = $webSite->updateUrlRoute($routeid, $rule, $route, $is_open, $route_model, $remark);
            return AjaxReturn($res);
        }
        $routeid = request()->get("routeid", "");
        $routeDetail = $webSite->getUrlRouteDetail($routeid);
        if (empty($routeDetail)) {
            $this->error("未获取路由规则信息");
        } else {
            $this->assign("routeDetail", $routeDetail);
        }
        return view($this->style . "Config/updateRoutingRules");
    }

    /**
     * 判断路由规则或者路由地址是否存在
     */
    public function url_route_if_exists()
    {
        if (request()->isAjax()) {
            $type = request()->post("type", "");
            $value = request()->post("value", "");
            $webSite = new WebSite();
            $res = $webSite->url_route_if_exists($type, $value);
            return $res;
        }
    }

    /**
     * 删除伪静态路由规则
     */
    public function delete_url_route()
    {
        if (request()->isAjax()) {
            $routeid = request()->post("routeid", "");
            $webSite = new WebSite();
            $res = $webSite->delete_url_route($routeid);
            return AjaxReturn($res);
        }
    }




}