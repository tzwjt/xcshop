<?php
/**
 * Wchat.php
 * @date : 2018.1.17
 * @version : v1.0.0.0
 */
namespace app\shop\controller;

use app\shop\controller\BaseController;
use think\Controller;
\think\Loader::addNamespace('dao', 'dao/');
use dao\extend\WchatOauth;
use dao\handle\ConfigHandle;
use dao\handle\SiteHandle;
use dao\handle\WeixinHandle;
use think\Session;

class Wchat extends BaseController
{

    public $wchat;

    public $weixin_handle;

    public $author_appid;

    public $instance_id;

    public $style;

    public function __construct()
    {
        parent::__construct();
        $this->wchat = new WchatOauth(); // 微信公众号相关类
        
        $this->weixin_handle = new WeixinHandle();
        // 使用那个手机模板
        $config = new ConfigHandle();
      //  $use_wap_template = $config->getUseWapTemplate(0);
        
      //  if (empty($use_wap_template)) {
      //      $use_wap_template['value'] = 'default';
       // }
      //  $this->style = "wap/" . $use_wap_template['value'] . "/";
      //  $this->assign("style", "wap/" . $use_wap_template['value']);
        
        $this->getMessage();
    }

    /**
     * ************************************************************************微信公众号消息相关方法 开始******************************************************
     */
    /**
     * ok-2ok
     * 关联公众号微信
     */
    public function relateWeixin()
    {
        $sign = request()->get('signature');
        if (isset($sign)) {
            $signature = $sign;
            $timestamp = request()->get('timestamp');
            $nonce = request()->get('nonce');
            
            $token = "TOKEN";
            $config = new ConfigHandle();
            $this->instance_id = 0;
            $wchat_config = $config->getInstanceWchatConfig($this->instance_id);

            if (! empty($wchat_config["value"]["token"])) {
                $token = $wchat_config["value"]["token"];
            }
            
            $tmpArr = array(
                $token,
                $timestamp,
                $nonce
            );
            
            sort($tmpArr, SORT_STRING);
            $tmpStr = implode($tmpArr);
            $tmpStr = sha1($tmpStr);
            
            if ($tmpStr == $signature) {
                $echostr = request()->get('echostr', '');
                if (! empty($echostr)) {
                    echo $echostr;
                }
                
                return 1;
            } else {
                return 0;
            }
        }
    }

    /**
     * ok-2ok
     * @return \think\response\Json
     */
    public function templateMessage()
    {
        $media_id = request()->get('media_id');
        $weixin = new WeixinHandle();
        $info = $weixin->getWeixinMediaDetailByMediaId($media_id);

        if (! empty($info["media_parent"])) {
            $website = new SiteHandle();
            $website_info = $website->getSiteInfo();
            /**
            $data = array(
                "website_info" => $website_info,
                "info" => $info
            );
*/
            $this->assign("website_info", $website_info);
            $this->assign("info", $info);
            return $this->fetch('wap@index:templateMessage');
         //   return view($this->style . 'Wchat/templateMessage');
          //  return json(resultArray(0,"操作成功", $data));
        } else {
            echo "图文消息没有查询到";
          //  return json(resultArray(2,"图文消息没有查询到"));
        }
    }

    /**
     * ok-2ok
     * 微信开放平台模式(需要对消息进行加密和解密)
     * 微信获取消息以及返回接口
     */
    public function getMessage()
    {
        $from_xml = file_get_contents('php://input');
        if (empty($from_xml)) {
            return;
        }
        $signature = request()->get('msg_signature', '');
        $timestamp = request()->get('timestamp', '');
        $nonce = request()->get('nonce', '');
        $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
        $ticket_xml = $from_xml;
        $postObj = simplexml_load_string($ticket_xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        
        $this->instance_id = 0;
        if (! empty($postObj->MsgType)) {
            switch ($postObj->MsgType) {
                case "text":
                    $weixin = new WeixinHandle();
                    // 用户发的消息 存入表中
                    $weixin->addUserMessage((string) $postObj->FromUserName, (string) $postObj->Content, (string) $postObj->MsgType);

                    $resultStr = $this->msgTypeText($postObj);
                    break;
                case "event":
                    $resultStr = $this->msgTypeEvent($postObj);
                    break;
                default:
                    $resultStr = "";
                    break;
            }
        }
        if (! empty($resultStr)) {
            echo $resultStr;
        } else {
            echo '';
        }
        exit();
    }

    /**
     * ok-2ok
     * 文本消息回复格式
     * @param $postObj
     * @return string|void
     */
    private function msgTypeText($postObj)
    {
        $funcFlag = 0; // 星标
        $wchat_replay = $this->weixin_handle->getWhatReplay($this->instance_id, (string) $postObj->Content);

        // 判断用户输入text
        if (! empty($wchat_replay)) { // 关键词匹配回复
            $contentStr = $wchat_replay; // 构造media数据并返回
        } elseif ($postObj->Content == "uu") {
            $contentStr = "shopId：" . $this->instance_id;
        } elseif ($postObj->Content == "TESTCOMPONENT_MSG_TYPE_TEXT") {
            $contentStr = "TESTCOMPONENT_MSG_TYPE_TEXT_callback"; // 微店插件功能 关键词，预留口
        } elseif (strpos($postObj->Content, "QUERY_AUTH_CODE") !== false) {
            $get_str = str_replace("QUERY_AUTH_CODE:", "", $postObj->Content);
            $contentStr = $get_str . "_from_api"; // 微店插件功能 关键词，预留口
        } else {
            $content = $this->weixin_handle->getDefaultReplay($this->instance_id);

            if (! empty($content)) {
                $contentStr = $content;
            } else {
                $contentStr = '';
            }
        }
        if (is_array($contentStr)) {
            $resultStr = $this->wchat->event_key_news($postObj, $contentStr);
        } elseif (! empty($contentStr)) {
            $resultStr = $this->wchat->event_key_text($postObj, $contentStr);
        } else {
            $resultStr = '';
        }
        return $resultStr;
    }

    /**
     * ok-2ok
     * 事件消息回复机制
     */
    // 事件自动回复 MsgType = Event
    private function msgTypeEvent($postObj)
    {
        $contentStr = "";
        switch ($postObj->Event) {
            case "subscribe": // 关注公众号
                $str = $this->wchat->get_fans_info($postObj->FromUserName);
                if (preg_match("/^qrscene_/", $postObj->EventKey)) {
                    $source_uid = substr($postObj->EventKey, 8);
                    $_SESSION['source_shop_id'] = $this->instance_id;
                    $_SESSION['source_uid'] = $source_uid;
                } elseif (! empty($_SESSION['source_uid'])) {
                    $source_uid = $_SESSION['source_uid'];
                    $_SESSION['source_shop_id'] = $this->instance_id;
                } else {
                    $source_uid = 0;
                }
                $Userstr = json_decode($str);
                $nickname = base64_encode($Userstr->nickname);
                $nickname_decode = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $Userstr->nickname);
                $headimgurl = $Userstr->headimgurl;
                $sex = $Userstr->sex;
                $language = $Userstr->language;
                $country = $Userstr->country;
                $province = $Userstr->province;
                $city = $Userstr->city;
                $district = "无";
                $openid = $Userstr->openid;
                if (! empty($Userstr->unionid)) {
                    $unionid = $Userstr->unionid;
                } else {
                    $unionid = '';
                }
                $subscribe_date = date('Y/n/j G:i:s', (int) $postObj->CreateTime);
                $memo = $Userstr->remark;
                $user_id = Session::get("MEMBER_USER_ID");

                if (!isset($user_id) || empty($user_id)) {
                    $user_id = 0;
                 }
                $weichat_subscribe = $this->weixin_handle->addWeixinFans($user_id, (int) $source_uid, $this->instance_id, $nickname, $nickname_decode, $headimgurl, $sex, $language, $country, $province, $city, $district, $openid, '', 1, $memo, $unionid); // 关注

                // 添加关注回复
                $content = $this->weixin_handle->getSubscribeReplay($this->instance_id);
                if (! empty($content)) {
                    $contentStr = $content;
                }
                // 构造media数据并返回 */
                break;
            case "unsubscribe": // 取消关注公众号
                $openid = $postObj->FromUserName;
                $weichat_unsubscribe = $this->weixin_handle->weixinUserUnsubscribe($this->instance_id, (string) $openid);
                break;
            case "VIEW": // VIEW事件 - 点击菜单跳转链接时的事件推送
                /* $this->wchat->weichat_menu_hits_view($postObj->EventKey); //菜单计数 */
                $contentStr = "";
                break;
            case "SCAN": // SCAN事件 - 用户已关注时的事件推送 - 扫描带参数二维码事件
                $contentStr = "";
                // $contentStr = "shop_url：".$this->shop_url." uid：".$postObj->EventKey; //二维码推广
                
                break;
            case "CLICK": // CLICK事件 - 自定义菜单事件
                $menu_detail = $this->weixin_handle->getWeixinMenuDetail($postObj->EventKey);
                $media_info = $this->weixin_handle->getWeixinMediaDetail($menu_detail['media_id']);
                $contentStr = $this->weixin_handle->getMediaWchatStruct($media_info); // 构造media数据并返回 */
                break;
            default:
                break;
        }
        // $contentStr = $postObj->Event."from_callback";//测试接口正式部署之后注释不要删除
        if (is_array($contentStr)) {
            $resultStr = $this->wchat->event_key_news($postObj, $contentStr);
        } else {
            $resultStr = $this->wchat->event_key_text($postObj, $contentStr);
        }
        return $resultStr;
    }

/**
 * ************************************************************************微信公众号消息相关方法 结束******************************************************
 */
}