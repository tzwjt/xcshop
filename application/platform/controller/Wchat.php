<?php
/**
 * Wchat.php
 * @date : 2018.1.17
 * @version : v1.0.0.0
 */

namespace app\platform\controller;

use dao\extend\WchatOauth;
use dao\handle\ConfigHandle;
//use dao\handle\Shop;
use dao\handle\ShopHandle;
use dao\handle\SiteHandle;
use dao\handle\WeixinHandle;
use dao\handle\WeixinMessageHandle;
//use Qiniu\json_decode;
use app\platform\controller\BaseController;

/**
 * 微信管理
 *
 * @author Administrator
 *        
 */
class Wchat extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * ok-2ok
     * 微信账户设置
     */
    public function config()
    {
        $config = new ConfigHandle();
        $wchat_config = $config->getInstanceWchatConfig(0);
        // 获取当前域名
        $domain_name = \think\Request::instance()->domain();
        $url = $domain_name . \think\Request::instance()->root();
        // 去除链接的http://头部
        $url_top = substr($url, 7);
        // 去除链接的尾部index.php
        $url_top = str_replace('/index.php', '', $url_top);
        $call_back_url = __URL(__URL__ . '/shop/wchat/relateWeixin');
        // $call_back_url = str_replace('/index.php', '', $call_back_url);
        /**
         * $this->assign("url", $url_top);
         * $this->assign("call_back_url", $call_back_url);
         * $this->assign('wchat_config', $wchat_config["value"]);
         * **/
        $data = array(
            "url" => $url_top,
            "call_back_url" => $call_back_url,
            'wchat_config' => $wchat_config["value"]
        );

        return json(resultArray(0, "操作成功", $data));
    }

    /**
     * ok-2ok
     * 修改微信配置
     */
    public function setInstanceWchatConfig()
    {
        $config = new ConfigHandle();
        $appid = str_replace(' ', '', request()->post('appid', ''));
        $appsecret = str_replace(' ', '', request()->post('appsecret', ''));
        $token = request()->post('token', '');
        $res = $config->setInstanceWchatConfig(0, $appid, $appsecret, $token);

        if (empty($res)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 微信菜单
     */
    public function menu()
    {
        $weixin = new WeixinHandle();
        $menu_list = $weixin->getInstanceWchatMenu(0);
        $default_menu_info = array(); // 默认显示菜单
        $menu_list_count = count($menu_list);
        $class_index = count($menu_list);
        if ($class_index > 0) {
            if ($class_index == MAX_MENU_LENGTH) {
                $class_index = MAX_MENU_LENGTH - 1;
            }
        }
        if ($menu_list_count > 0) {
            $default_menu_info = $menu_list[$menu_list_count - 1];
        } else {
            $default_menu_info["menu_name"] = "";
            $default_menu_info["id"] = 0;
            $default_menu_info["child_count"] = 0;
            $default_menu_info["media_id"] = 0;
            $default_menu_info["menu_event_url"] = "";
            $default_menu_info["menu_event_type"] = 1;
        }
        $media_detail = array();
        if ($default_menu_info["media_id"]) {
            // 查询图文消息
            $media_detail = $weixin->getWeixinMediaDetail($default_menu_info["media_id"]);
            $media_detail["item_list_count"] = count($media_detail["item_list"]);
        } else {
            $media_detail["create_time"] = "";
            $media_detail["title"] = "";
            $media_detail["item_list_count"] = 0;
        }
        $default_menu_info["media_list"] = $media_detail;

        $site = new SiteHandle();
        $site_info = $site->getSiteInfo();

        $data = array(
            "wx_name" => $site_info['wap_shop_site_name'],
            "menu_list" => $menu_list,
            "MAX_MENU_LENGTH" => MAX_MENU_LENGTH,   // 一级菜单数量
            "MAX_SUB_MENU_LENGTH" => MAX_SUB_MENU_LENGTH, // 二级菜单数量
            "menu_list_count" => $menu_list_count,
            "default_menu_info" => $default_menu_info,
            "class_index" => $class_index
        );
        return json(resultArray(0, "操作成功", $data));
    }

    /**
     * ok-2ok
     * 更新菜单到微信,保存并发布
     */
    public function updateMenuToWeixin()
    {
        $weixin = new WeixinHandle();
        $result = $weixin->updateInstanceMenuToWeixin(0);
        $config = new ConfigHandle();
        $auth_info = $config->getInstanceWchatConfig(0);

        if (!empty($auth_info['value']['appid']) && !empty($auth_info['value']['appsecret'])) {
            $wchat_auth = new WchatOauth();

            $res = $wchat_auth->menu_create($result);
            if (!empty($res)) {
                $res = json_decode($res, true);
                if ($res['errcode'] == 0) {
                    $retval = 1;
                } else {
                    $retval = $res['errmsg'];
                }
            } else {
                $retval = 0;
            }
        } else {
            $retval = "当前未配置微信授权";
        }

        if ($retval === 1) {
            return json(resultArray(0, "操作成功"));
        } else {
            return json(resultArray(2, "操作失败" . $retval));
        }
    }

    /**
     * ok-2ok
     * 添加微信自定义菜单
     */
    public function addWeixinMenu()
    {
        $menu = request()->post('menu', '');
        $res = false;
        if (!empty($menu)) {
            $menu = json_decode($menu, true);
            $weixin = new WeixinHandle();
            $instance_id = 0;
            $menu_name = $menu["menu_name"]; // 菜单名称
            $ico = ""; // 菜图标单
            $pid = $menu["pid"]; // 父级菜单（一级菜单）
            $menu_event_type = $menu["menu_event_type"]; // '1普通url 2 图文素材 3 功能',
            $menu_event_url = $menu["menu_event_url"]; // '菜单url',
            $media_id = $menu["media_id"]; // '图文消息ID',
            $sort = $menu["sort"]; // 排序
            $res = $weixin->addWeixinMenu($instance_id, $menu_name, $ico, $pid, $menu_event_type, $menu_event_url, $media_id, $sort);
        }

        if (empty($res)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 修改微信自定义菜单
     */
    public function updateWeixinMenu()
    {
        $menu = request()->post('menu', '');
        $res = false;
        if (!empty($menu)) {
            $weixin = new WeixinHandle();
            $instance_id = 0;
            $menu_name = $menu["menu_name"]; // 菜单名称
            $menu_id = $menu["menu_id"];
            $ico = ""; // 菜图标单
            $pid = $menu["pid"]; // 父级菜单（一级菜单）
            $menu_event_type = $menu["menu_event_type"]; // '1普通url 2 图文素材 3 功能',
            $menu_event_url = $menu["menu_event_url"]; // '菜单url',
            $media_id = $menu["media_id"]; // '图文消息ID',
            $res = $weixin->updateWeixinMenu($menu_id, $instance_id, $menu_name, $ico, $pid, $menu_event_type, $menu_event_url, $media_id);
        }
        if (empty($res)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 修改排序
     */
    public function updateWeixinMenuSort()
    {
        $menu_id_arr = request()->post('menu_id_arr', '');
        $res = false;
        if (!empty($menu_id_arr)) {
            $menu_id_arr = explode(",", $menu_id_arr);
            $weixin = new WeixinHandle();
            $res = $weixin->updateWeixinMenuSort($menu_id_arr);
        }
        if (empty($res)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 修改微信菜单名称
     */
    public function updateWeixinMenuName()
    {
        $menu_name = request()->post('menu_name');
        $menu_id = request()->post('menu_id');
        $res = false;
        if (!empty($menu_name)) {
            $weixin = new WeixinHandle();
            $res = $weixin->updateWeixinMenuName($menu_id, $menu_name);
        }
        if (empty($res)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 修改跳转链接地址
     */
    public function updateWeixinMenuUrl()
    {
        $menu_event_url = request()->post('menu_event_url');
        $menu_id = request()->post('menu_id');
        $res = false;
        if (!empty($menu_event_url)) {
            $weixin = new WeixinHandle();
            $res = $weixin->updateWeixinMenuUrl($menu_id, $menu_event_url);
        }
        if (empty($res)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 修改菜单类型，1：文本，2：单图文，3：多图文
     */
    public function updateWeixinMenuEventType()
    {
        $menu_event_type = request()->post('menu_event_type');
        $menu_id = request()->post('menu_id');
        $res = false;
        if (!empty($menu_event_type)) {
            $weixin = new WeixinHandle();
            $res = $weixin->updateWeixinMenuEventType($menu_id, $menu_event_type);
        }
        if (empty($res)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 修改图文消息
     */
    public function updateWeiXinMenuMessage()
    {
        $menu_event_type = request()->post('menu_event_type');
        $menu_id = request()->post('menu_id');
        $media_id = request()->post('media_id');

        $res = false;
        if (!empty($menu_event_type)) {
            $weixin = new WeixinHandle();
            $res = $weixin->updateWeiXinMenuMessage($menu_id, $media_id, $menu_event_type);
        }
        if (empty($res)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 删除微信自定义菜单
     */
    public function deleteWeixinMenu()
    {
        $menu_id = request()->post('menu_id');
        $res = false;
        if (!empty($menu_id)) {
            $weixin = new WeixinHandle();
            $res = $weixin->deleteWeixinMenu($menu_id);
        }
        if (empty($res)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 获取图文素材
     */
    public function getWeixinMediaDetail()
    {
        $media_id = request()->post('media_id');
        $weixin = new WeixinHandle();
        $data = $weixin->getWeixinMediaDetail($media_id);

        return json(resultArray(0, "操作成功", $data));
    }

    /**
     * ok-2ok
     * 设置微信推广二维码
     */
    public function setQrcode()
    {
        //$shop = new Shop()
        $weixin = new WeixinHandle();
        $site_hande = new SiteHandle();
        $site_info = $site_hande->getSiteInfo();
        $instance_id = 0;

        $id = request()->post('id', 0);
        $background = request()->post('background', '');
        $nick_font_color = request()->post('nick_font_color', '#000');
        $nick_font_size = request()->post('nick_font_size', '12');
        $is_logo_show = request()->post('is_logo_show', '1');
        $header_left = request()->post('header_left', '59');
        $header_top = request()->post('header_top', '15');
        $name_left = request()->post('name_left', '128');
        $name_top = request()->post('name_top', '23');
        $logo_left = request()->post('logo_left', '60');
        $logo_top = request()->post('logo_top', '200');
        $code_left = request()->post('code_left', '70');
        $code_top = request()->post('code_top', '300');
        $upload_path = "upload/qrcode/promote_qrcode_template"; // 后台推广二维码模版
        $template_url = $upload_path . '/qrcode_template_' . $id . '_' . $instance_id . '.png';
        if ($id == 0) {
            $res = $weixin->addWeixinQrcodeTemplate($instance_id, $background, $nick_font_color, $nick_font_size, $is_logo_show, $header_left, $header_top, $name_left, $name_top, $logo_left, $logo_top, $code_left, $code_top, $template_url);


            showUserQecode($upload_path, '', $upload_path . '/thumb_template' . 'qrcode_' . $res . '_' . $instance_id . '.png', '', $site_info['logo'], '', request()->post(), $upload_path . '/qrcode_template_' . $res . '_' . $instance_id . '.png');
            $res = $weixin->updateWeixinQrcodeTemplate($res, $instance_id, $background, $nick_font_color, $nick_font_size, $is_logo_show, $header_left, $header_top, $name_left, $name_top, $logo_left, $logo_top, $code_left, $code_top, $upload_path . '/qrcode_template_' . $res . '_' . $instance_id . '.png');
        } else {
            $res = $weixin->updateWeixinQrcodeTemplate($id, $instance_id, $background, $nick_font_color, $nick_font_size, $is_logo_show, $header_left, $header_top, $name_left, $name_top, $logo_left, $logo_top, $code_left, $code_top, $template_url);
            showUserQecode($upload_path, '', $upload_path . '/thumb_template' . 'qrcode_' . $id . '_' . $instance_id . '.png', '', $site_info['logo'], '', request()->post(), $upload_path . '/qrcode_template_' . $id . '_' . $instance_id . '.png');
        }

        if (empty($res)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 得到微信推广二维码
     * @return \think\response\Json
     */
    public function getQrcode()
    {
        $id = request()->post('id', 0);
        $weixin = new WeixinHandle();
        if (empty($id)) {
            $info = $weixin->getDetailWeixinQrcodeTemplate(0);
        } else {
            $info = $weixin->getDetailWeixinQrcodeTemplate($id);
        }

        $site_hande = new SiteHandle();
        $site_info = $site_hande->getSiteInfo();
        //    $this->assign('id', $id);
        //    $this->assign("info", $info);
        //  $this->assign('web_info', $web_info);

        $data = array(
            "info" => $info
        );
        return json(resultArray(0, "操作成功", $data));
    }

    /**
     * ok-2ok
     * 回复设置
     */
    public function replayConfig()
    {
        $type = request()->post('type', 1);
        $instance_id = 0;
        $child_menu_list = array(
            array(
                'url' => "wchat/replayConfig?type=1",
                'menu_name' => "关注时回复",
                "active" => $type == 1 ? 1 : 0
            ),
            array(
                'url' => "wchat/replayConfig?type=2",
                'menu_name' => "关键字回复",
                "active" => $type == 2 ? 1 : 0
            ),
            array(
                'url' => "wchat/replayConfig?type=3",
                'menu_name' => "默认回复",
                "active" => $type == 3 ? 1 : 0
            )
        );
        // $this->assign('child_menu_list', $child_menu_list);
        // $this->assign('type', $type);
        $info = "";
        if ($type == 1) {
            $weixin = new WeixinHandle();
            $info = $weixin->getFollowReplayDetail([
                'instance_id' => $instance_id
            ]);

            // $this->assign('info', $info);
        } else
            if ($type == 2) {
            } else
                if ($type == 3) {
                    $weixin = new WeixinHandle();
                    $info = $weixin->getDefaultReplayDetail([
                        'instance_id' => $instance_id
                    ]);
                    // $this->assign('info', $info);
                }

        $data = array(
            'child_menu_list' => $child_menu_list,
            'type' => $type,
            'info' => $info
        );

        return json(resultArray(0, "操作成功", $data));
    }

    /**
     * ok-2ok
     * 添加 或 修改 关注时回复
     */
    public function addOrUpdateFollowReply()
    {
        $weixin = new WeixinHandle();
        $id = request()->post('id', -1);
        $replay_media_id = request()->post('media_id', 0);
        $instance_id = 0;
        $res = false;
        if ($id < 0) {
            $res = false;
        } else
            if ($id == 0) {
                if ($replay_media_id > 0) {
                    $res = $weixin->addFollowReplay($instance_id, $replay_media_id, 0);
                } else {
                    $res = false;
                }
            } else
                if ($id > 0) {
                    if ($replay_media_id > 0) {
                        $res = $weixin->updateFollowReplay($id, $instance_id, $replay_media_id, 0);
                    } else {
                        $res = false;
                    }
                }
        if (empty($res)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 添加 或 修改 默认回复
     */
    public function addOrUpdateDefaultReply()
    {
        $weixin = new WeixinHandle();
        $id = request()->post('id', -1);
        $replay_media_id = request()->post('media_id', 0);
        $res = false;
        $instance_id = 0;
        if ($id < 0) {
            $res = false;
        } else
            if ($id == 0) {
                if ($replay_media_id > 0) {
                    $res = $weixin->addDefaultReplay($instance_id, $replay_media_id, 0);
                } else {
                    $res = false;
                }
            } else
                if ($id > 0) {
                    if ($replay_media_id > 0) {
                        $res = $weixin->updateDefaultReplay($id, $instance_id, $replay_media_id, 0);
                    } else {
                        $res = false;
                    }
                }
        if (empty($res)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 删除图文消息
     *
     * @return number
     */
    public function deleteWeixinMedia()
    {
        $media_id = request()->post('media_id', '');
        $res = false;
        $instance_id = 0;
        if (!empty($media_id)) {
            $weixin = new WeixinHandle();
            $res = $weixin->deleteWeixinMedia($media_id, $instance_id);
        }
        if (empty($res)) {
            return json(resultArray(2, "删除失败"));
        } else {
            return json(resultArray(0, "删除成功"));
        }
    }

    /**
     * ok-2ok
     * 删除图文详情页列表
     */
    public function deleteWeixinMediaDetail()
    {
        $id = request()->post('id', '');
        $res = false;
        if (!empty($id)) {
            $weixin = new WeixinHandle();
            $res = $weixin->deleteWeixinMediaDetail($id);
        }
        if (empty($res)) {
            return json(resultArray(2, "删除失败"));
        } else {
            return json(resultArray(0, "删除成功"));
        }
    }

    /**
     * ok-2ok
     *  进入消息素材
     */
    public function materialMessage()
    {
        $type = request()->post('type', 0);
        $child_menu_list = array(
            array(
                'url' => "wchat/materialMessage",
                'menu_name' => "全部",
                "active" => $type == 0 ? 1 : 0
            ),
            array(
                'url' => "wchat/materialMessage?type=1",
                'menu_name' => "文本",
                "active" => $type == 1 ? 1 : 0
            ),
            array(
                'url' => "wchat/materialMessage?type=2",
                'menu_name' => "单图文",
                "active" => $type == 2 ? 1 : 0
            ),
            array(
                'url' => "wchat/materialMessage?type=3",
                'menu_name' => "多图文",
                "active" => $type == 3 ? 1 : 0
            )
        );

        $data = array(
            'type' => $type,
            'child_menu_list' => $child_menu_list

        );

        return json(resultArray(0, "操作成功", $data));
        //  return view($this->style . 'Wchat/materialMessage');
    }

    /**
     * ok-2ok
     *  得到消息素材
     */
    public function getMaterialMessage()
    {

        $type = request()->post('type', 0);
        $search_text = request()->post('search_text', '');
        $page_index = request()->post('page_index', 1);
        $page_size = request()->post("page_size", PAGESIZE);
        $weixin = new WeixinHandle();
        $condition = array();
        if ($type != 0) {
            $condition['type'] = $type;
        }
        if (!empty($search_text)) {
            $condition['title'] = array(
                'like',
                '%' . $search_text . '%'
            );
        }
        $condition = array_filter($condition);
        $field = '*';
        $list = $weixin->getWeixinMediaList($page_index, $page_size, $condition, 'create_time desc', $field);
        return json(resultArray(0, "操作成功", $list));
    }

    /**
     * ok-2ok
     * 分享内容设置
     */
    public function setShareConfig()
    {
        $shop = new ShopHandle();
        $goods_param_1 = request()->post('goods_param_1', '');
        $goods_param_2 = request()->post('goods_param_2', '');
        $shop_param_1 = request()->post('shop_param_1', '');
        $shop_param_2 = request()->post('shop_param_2', '');
        $shop_param_3 = request()->post('shop_param_3', '');
        $qrcode_param_1 = request()->post('qrcode_param_1', '');
        $qrcode_param_2 = request()->post('qrcode_param_2', '');
        $instance_id = 0;
        $res = $shop->updateShopShareCinfig($instance_id, $goods_param_1, $goods_param_2, $shop_param_1, $shop_param_2, $shop_param_3, $qrcode_param_1, $qrcode_param_2);

        if (empty($res)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 得到分享内容设置
     */
    public function getShareConfig()
    {
        $shop = new ShopHandle();
        $instance_id = 0;
        $config = $shop->getShopShareConfig($instance_id);
        $data = array (
            "config" => $config
        );
         return json(resultArray(0, "操作成功", $data));
    }

    /**
     * 模板消息设置
     */
    public function templateMessage()
    {
      //  return view($this->style . 'Wchat/templateMessage');
    }

    /**
     * ok-2ok
     * 设置一键关注设置
     */
    public function setOneKeySubscribe()
    {
        $weixin = new WeixinHandle();
        $instance_id = 0;
        $url = request()->post('url');
        $res = $weixin->setInsanceOneKeySubscribe($instance_id, $url);

        if (empty($res)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     *  进入一键关注设置
     */
    public function oneKeySubscribe()
    {
        $weixin = new WeixinHandle();
        $instance_id = 0;
        $data = $weixin->getInstanceOneKeySubscribe($instance_id);

        $res = array(
            'one_key_url'=> $data
        );
        return json(resultArray(0, "操作成功", $res));
    }

    /**
     * ok-2ok
     * 添加 消息
     */
    public function addMedia()
    {
        $type = request()->post('type');
        $title = request()->post('title');
        $content = request()->post('content');
        $sort = 0;
        $instance_id = 0;
        $weixin = new WeixinHandle();
        $res = $weixin->addWeixinMedia($title, $instance_id, $type, $sort, $content);

        if (empty($res)) {
            return json(resultArray(2, "操作失败 ".$weixin->getError()));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 修改消息素材
     */
    public function updateMedia()
    {
        $weixin = new WeixinHandle();
        $media_id = request()->post('media_id');
        $type = request()->post('type');
        $title = request()->post('title');
        $content = request()->post('content');
        $sort = 0;
        $instance_id = 0;
        $res = $weixin->updateWeixinMedia($media_id, $title, $instance_id, $type, $sort, $content);

        if (empty($res)) {
            return json(resultArray(2, "操作失败 ".$weixin->getError()));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 得到消息素材
     */
    public function getMediaDetail()
    {
        $media_id = request()->post('media_id');
        $weixin = new WeixinHandle();
        $info = $weixin->getWeixinMediaDetail($media_id);
        $data = array(
            'info'=> $info
        );
        return json(resultArray(0, "操作成功", $data));
    }

    /**
     * ok-2ok
     * ajax 加载 选择素材 弹框数据
     */
    public function onloadMaterial()
    {
        $type = request()->post('type', 0);
        $search_text = request()->post('search_text', '');
        $page_index = request()->post("page_index", 1);
        $page_size = request()->post("page_size", PAGESIZE);
        $weixin = new WeixinHandle();
        $condition = array();
        if ($type != 0) {
            $condition['type'] = $type;
        }
        if (!empty($search_text)) {
            $condition['title'] = array(
                'like',
                '%' . $search_text . '%'
            );
        }
        $condition = array_filter($condition);
        $field = '*';
        $list = $weixin->getWeixinMediaList($page_index, $page_size, $condition, 'create_time desc', $field);
         return json(resultArray(0, "操作成功", $list));
    }

    /**
     * ok-2ok
     * 删除 回复
     */
    public function delReply()
    {
        $type = request()->post('type', '');
        $instance_id = 0;
        $res = false;
        if ($type == '') {
            return json(resultArray(2, "未指定类型"));
        } else {
            if ($type == 1) {
                // 删除 关注时回复
                $weixin = new WeixinHandle();
                $res = $weixin->deleteFollowReplay($instance_id);
              //  return AjaxReturn($res);
            } else 
                if ($type == 3) {
                    // 删除 关注时回复
                    $weixin = new WeixinHandle();
                    $res = $weixin->deleteDefaultReplay($instance_id);
                }
        }

        if (empty($res)) {
            return json(resultArray(2, "删除失败"));
        } else {
            return json(resultArray(0, "删除成功"));
        }
    }

    /**
     * ok-2ok
     * 关键字 回复
     */
    public function keyReplayList()
    {
        $weixin = new WeixinHandle();
        $instance_id = 0;
        $order = '';
        $field='*';
        $list = $weixin->getKeyReplayList(1, 0, [
            'instance_id' => $instance_id
        ], $order, $field);

        return json(resultArray(0, "操作成功", $list));
    }

    /**
     * ok-2ok
     * 添加 或 修改 关键字 回复
     */
    public function addOrUpdateKeyReplay()
    {
        $weixin = new WeixinHandle();
        $id = request()->post('id', -1);
        $key = request()->post('key', '');
        $match_type = request()->post('match_type', 1);
        $replay_media_id = request()->post('media_id', 0);
        $sort = 0;
        $instance_id = 0;
        $res = false;
        if ($id > 0) {
            $res = $weixin->updateKeyReplay($id, $instance_id, $key, $match_type, $replay_media_id, $sort);
        } else if ($id == 0) {
            $res = $weixin->addKeyReplay($instance_id, $key, $match_type, $replay_media_id, $sort);
        } else if ($id < 0) {
            $res = false;
        }
        if (empty($res)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 得到关键字 回复用于修改
     * @return \think\response\Json
     */
    public function getKeyReplay() {
        $id = request()->post('id', 0);
      //  $this->assign('id', $id);
        $info = array(
            'key' => '',
            'match_type' => 1,
            'reply_media_id' => 0,
            'madie_info' => array()
        );
        $weixin = new WeixinHandle();
        if ($id > 0) {
            $info = $weixin->getKeyReplyDetail($id);
        }
        $secend_menu['module_name'] = "编辑回复";
        $child_menu_list = array(
            array(
                'url' => "Wchat/addOrUpdateKeyReplay.html?id=" . $id,
                'menu_name' => "编辑回复",
                "active" => 1
            )
        );
        $data = array(
            'id' => $id
        );
        
        if (! empty($id)) {
            $data['secend_menu'] = $secend_menu;
            $data['child_menu_list'] = $child_menu_list;
           // $this->assign("secend_menu", $secend_menu);
            //$this->assign('child_menu_list', $child_menu_list);
        }
        $data['info'] = $info;
       // $this->assign('info', $info);
        return json(resultArray(0, "操作成功", $data));
    }

    /**
     * ok-2ok
     * 删除 回复
     */
    public function delKeyReply()
    {
        $id = request()->post('id');
        if (empty($id)) {
            return json(resultArray(2, "未指定具体回复"));
        } else {
            // 删除 关注时回复
            $weixin = new WeixinHandle();
            $res = $weixin->deleteKeyReplay($id);
            if (empty($res)) {
                return json(resultArray(2, "删除失败"));
            } else {
                return json(resultArray(0, "删除成功"));
            }
        }
    }

    public function saveQrcodeConfig()
    {}

    /**
     * ok-2ok
     * 二维码模板列表
     */
    public function weixinQrcodeTemplate()
    {
        $weixin = new WeixinHandle();
        $instance_id = 0;
        $template_list = $weixin->getWeixinQrcodeTemplate($instance_id);
        return json(resultArray(0, "操作成功",$template_list ));
    }

    /**
     * ok-2ok
     * 修改模板是否被使用
     */
    public function modifyWeixinQrcodeTemplateValid()
    {
        $id = request()->post('id');
        $weixin = new WeixinHandle();
        $instance_id = 0;
        $retval = $weixin->modifyWeixinQrcodeTemplateCheck($instance_id, $id);

        if (empty($retval)) {
            return json(resultArray(2, "操作失败"));
        } else {
            return json(resultArray(0, "操作成功"));
        }
    }

    /**
     * ok-2ok
     * 删除模板
     */
    public function deleteWeixinQrcodeTemplateValid()
    {
        $id = request()->post('id');
        $weixin = new WeixinHandle();
        $instance_id = 0;
        if (empty($id)) {
            return json(resultArray(2, "未指定参数"));
        }
        $retval = $weixin->deleteWeixinQrcodeTemplate($id, $instance_id);

        if (empty($retval)) {
            return json(resultArray(2, "删除失败"));
        } else {
            return json(resultArray(0, "删除成功"));
        }

    }

    /**
     * ok-2ok
     * 模板消息列表
     */
    public function messageTemplate()
    {
        $WeixinMessage = new WeixinMessageHandle();
        $instance_id = 0;
        $message = $WeixinMessage->getWeixinInstanceMsg($instance_id);

        return json(resultArray(0, "操作成功",$message));
    }

    public function testSend()
    {
        $weixin_message = new WeixinMessageHandle();
        $weixin = new WeixinHandle();
        // $res = $weixin_message->sendWeixinOrderCreateMessage(1);
        $weixin->addUserMessageReplay(1, 1, 'text', 'this is kefu replay message!');
        $res = $weixin_message->sendMessageToUser('oXTarwCCbPb9eouZmwCr6CHtNI0I', 'text', 'this is kefu replay message!');
        var_dump($res);
    }
}   
