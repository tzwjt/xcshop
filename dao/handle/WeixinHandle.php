<?php
/**
 * Weixin.php
 * @date : 2017.12.24
 * @version : v1.0.0
 */
namespace dao\handle;

use dao\extend\WchatOauth;
use dao\model\MemberUser as MemberUserModel;
use dao\model\MemberInfo as MemberInfoModel;
use dao\model\WeixinAuth as WeixinAuthModel;
use dao\model\WeixinDefaultReplay as WeixinDefaultReplayModel;
use dao\model\WeixinFans as WeixinFansModel;
use dao\model\WeixinFollowReplay as WeixinFollowReplayModel;
use dao\model\WeixinKeyReplay as WeixinKeyReplayModel;
use dao\model\WeixinMediaItem as WeixinMediaItemModel;
use dao\model\WeixinMedia as WeixinMediaModel;
use dao\model\WeixinMenu as WeixinMenuModel;
use dao\model\WeixinOneKeySubscribe as WeixinOneKeySubscribeModel;
//use dao\model\WeixinQrcodeConfig as WeixinQrcodeConfigModel;
use dao\model\WeixinQrcodeTemplate as WeixinQrcodeTemplateModel;
use dao\model\WeixinUserMsg as WeixinUserMsgModel;
use dao\model\WeixinUserMsgReplay as WeixinUserMsgReplayModel;
use dao\handle\BaseHandle;
use think\Log;

class WeixinHandle extends BaseHandle
{

    /**
     * ok-2ok
     * 获取微信菜单列表
     *
     * @param  $instance_id
     * @param  $pid
     *            当pid=''查询全部
     */
    public function getWeixinMenuList($instance_id=0, $pid = '', $field='*')
    {
        $weixin_menu = new WeixinMenuModel();
        if ($pid == '') {
            $list = $weixin_menu->pageQuery(1, 0, [
                'instance_id' => $instance_id
            ], 'sort', $field);
        } else {
            $list = $weixin_menu->pageQuery(1, 0, [
                'instance_id' => $instance_id,
                'pid' => $pid
            ], 'sort', $field);
        }
        return $list['data'];
    }

    /**
     * ok-2ok
     * 添加微信菜单
     * @param  $indtance_id
     * @param  $menu_name
     * @param  $ico
     * @param  $pid
     * @param  $menu_event_type
     * @param  $menu_event_url
     * @param  $sort
     */
    public function addWeixinMenu($instance_id=0, $menu_name, $ico, $pid, $menu_event_type, $menu_event_url, $media_id, $sort)
    {
        $weixin_menu = new WeixinMenuModel();
        $data = array(
            'instance_id' => $instance_id,
            'menu_name' => $menu_name,
            'ico' => $ico,
            'pid' => $pid,
            'menu_event_type' => $menu_event_type,
            'menu_event_url' => $menu_event_url,
            'media_id' => $media_id,
            'sort' => $sort,
            'create_time' => time(),
            'update_time' => time()
        );
       $res =  $weixin_menu->save($data);
        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 修改微信菜单
     * @param  $menu_id
     * @param  $instance_id
     * @param  $menu_name
     * @param  $ico
     * @param  $pid
     * @param  $menu_event_type
     * @param  $menu_event_url
     * @param  $sort
     */
    public function updateWeixinMenu($menu_id, $instance_id, $menu_name, $ico, $pid, $menu_event_type, $menu_event_url, $media_id)
    {
        $weixin_menu = new WeixinMenuModel();
        $data = array(
            'instance_id' => $instance_id,
            'menu_name' => $menu_name,
            'ico' => $ico,
            'pid' => $pid,
            'menu_event_type' => $menu_event_type,
            'menu_event_url' => $menu_event_url,
            'media_id' => $media_id,
            'update_time' => time()
        );
        $retval = $weixin_menu->save($data, [
            "id" => $menu_id
        ]);
        if ($retval > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 修改菜单排序
     *
     * @param  $menu_id_arr
     * @param  $sort
     */
    public function updateWeixinMenuSort($menu_id_arr)
    {
        $weixin_menu = new WeixinMenuModel();
        $retval = 0;
        foreach ($menu_id_arr as $k => $v) {
            $data = array(
                'sort' => $k + 1,
                'update_time' => time()
            );
            $retval += $weixin_menu->save($data, [
                "id" => $v
            ]);
        }
        if ($retval > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 修改菜单名称
     *
     * @param  $menu_id
     * @param  $menu_name
     */
    public function updateWeixinMenuName($menu_id, $menu_name)
    {
        $weixin_menu = new WeixinMenuModel();
        
        $retval = $weixin_menu->save([
            "menu_name" => $menu_name,
            'update_time'=>time()
        ], [
            "id" => $menu_id
        ]);
        if ($retval > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 修改跳转链接
     *
     * @param  $menu_id
     * @param  $menu_eventurl
     */
    public function updateWeixinMenuUrl($menu_id, $menu_event_url)
    {
        $weixin_menu = new WeixinMenuModel();
        
        $retval = $weixin_menu->save([
            "menu_event_url" => $menu_event_url,
            'update_time'=>time()
        ], [
            "id" => $menu_id
        ]);
        if ($retval > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 修改菜单类型，1：文本，2：单图文，3：多图文
     *
     * @param  $menu_id
     * @param  $menu_event_type
     */
    public function updateWeixinMenuEventType($menu_id, $menu_event_type)
    {
        $weixin_menu = new WeixinMenuModel();
        
        $retval = $weixin_menu->save([
            "menu_event_type" => $menu_event_type,
            'update_time'=>time()
        ], [
            "id" => $menu_id
        ]);
        if ($retval > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 修改图文消息
     *
     * @param  $menu_id
     * @param  $media_id
     * @param  $menu_event_type
     */
    public function updateWeiXinMenuMessage($menu_id, $media_id, $menu_event_type)
    {
        $weixin_menu = new WeixinMenuModel();
        $retval = $weixin_menu->save([
            "media_id" => $media_id,
            "menu_event_type" => $menu_event_type,
            'update_time'=>time()
        ], [
            "id" => $menu_id
        ]);
        if ($retval > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 添加微信菜单点击数
     *
     * @param  $menu_id
     */
    public function addMenuHits($menu_id)
    {

    }

    /**
     * ok-2ok
     * 获取微信菜单详情
     * @param  $menu_id
     */
    public function getWeixinMenuDetail($menu_id)
    {
        $weixin_menu = new WeixinMenuModel();
        $data = $weixin_menu->get($menu_id);
        return $data;
    }

    /**
     * ok-2ok
     * 公众号授权
     *
     * @param  $instance_id
     * @param  $authorizer_appid
     * @param  $authorizer_refresh_token
     * @param  $authorizer_access_token
     * @param  $func_info
     * @param  $nick_name
     * @param  $head_img
     * @param  $user_name
     * @param  $alias
     * @param  $qrcode_url
     */
    public function addWeixinAuth($instance_id, $authorizer_appid, $authorizer_refresh_token, $authorizer_access_token, $func_info, $nick_name, $head_img, $user_name, $alias, $qrcode_url)
    {
        $weixin_auth = new WeixinAuthModel();
        $data = array(
            'instance_id' => $instance_id,
            'authorizer_appid' => $authorizer_appid,
            'authorizer_refresh_token' => $authorizer_refresh_token,
            'authorizer_access_token' => $authorizer_access_token,
            'func_info' => $func_info,
            'nick_name' => $nick_name,
            'head_img' => $head_img,
            'user_name' => $user_name,
            'alias' => $alias,
            'qrcode_url' => $qrcode_url,
            'auth_time' => time()
        );
        $count = $weixin_auth->where([
            'instance_id' => $instance_id
        ])->count();
        if ($count == 0) {
            $data['create_time'] = time();
            $data['update_time'] = time();
            $weixin_auth = new WeixinAuthModel();
            $retval = $weixin_auth->save($data);
        } else {
            $data['update_time'] = time();
            $weixin_auth = new WeixinAuthModel();
            $retval = $weixin_auth->save($data, [
                'instance_id' => $instance_id
            ]);
        }

        if ($retval > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 用户关注添加粉丝信息
     *
     * @param  $instance_id
     * @param  $nickname
     * @param  $headimgurl
     * @param  $sex
     * @param  $language
     * @param  $country
     * @param  $province
     * @param  $city
     * @param  $district
     * @param  $openid
     * @param  $groupid
     * @param  $is_subscribe
     * @param  $memo
     */
    public function addWeixinFans($user_id, $source_uid, $instance_id, $nickname, $nickname_decode, $headimgurl, $sex, $language, $country, $province, $city, $district, $openid, $groupid, $is_subscribe, $memo, $unionid)
    {
        $weixin_fans = new WeixinFansModel();
        $count = $weixin_fans->where([
            'openid' => $openid
        ])->count();
        if (! empty($user_id)) {
            $uid = $user_id;
        } else {
            $uid = 0;
        }
        $data = array(
            'uid' => $uid,
            'instance_id' => $instance_id,
            'nickname' => $nickname,
            'nickname_decode' => $nickname_decode,
            'headimgurl' => $headimgurl,
            'sex' => $sex,
            'language' => $language,
            'country' => $country,
            'province' => $province,
            'city' => $city,
            'district' => $district,
            'openid' => $openid,
            'groupid' => $groupid,
            'is_subscribe' => $is_subscribe,
            'update_time' => time(),
            'memo' => $memo,
            'unionid' => $unionid
        );
        if ($count == 0) {
            $weixin_fans = new WeixinFansModel();
            $data['source_uid'] = $source_uid;
            $data['subscribe_date'] = time();
            $data['create_time'] = time();
            $retval = $weixin_fans->save($data);
        } else {
            $weixin_fans = new WeixinFansModel();
            $retval = $weixin_fans->save($data, [
                'openid' => $openid
            ]);
        }
        if ($retval > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 添加关注回复
     *
     * @param  $instance_id
     * @param  $replay_media_id
     * @param  $sort
     */
    public function addFollowReplay($instance_id, $replay_media_id, $sort)
    {
        $weixin_follow_replay = new WeixinFollowReplayModel();
        $data = array(
            'instance_id' => $instance_id,
            'reply_media_id' => $replay_media_id,
            'sort' => $sort,
            'create_time' => time()
        );
        $res = $weixin_follow_replay->save($data);

        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 添加默认回复
     *
     * @param  $instance_id
     * @param  $replay_media_id
     * @param  $sort
     */
    public function addDefaultReplay($instance_id, $replay_media_id, $sort)
    {
        $weixin_default_replay = new WeixinDefaultReplayModel();
        $data = array(
            'instance_id' => $instance_id,
            'reply_media_id' => $replay_media_id,
            'sort' => $sort,
            'create_time' => time()
        );
        $res = $weixin_default_replay->save($data);
        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 修改关注回复
     *
     * @param  $id
     * @param  $instance_id
     * @param  $replay_media_id
     * @param  $sort
     */
    public function updateFollowReplay($id, $instance_id, $replay_media_id, $sort)
    {
        $weixin_follow_replay = new WeixinFollowReplayModel();
        $data = array(
            'instance_id' => $instance_id,
            'reply_media_id' => $replay_media_id,
            'sort' => $sort,
            'update_time' => time()
        );
        $retval = $weixin_follow_replay->save($data, [
            'id' => $id
        ]);
       if ($retval > 0) {
           return true;
       } else {
           return false;
       }
    }

    /**
     * ok-2ok
     * 修改默认回复
     *
     * @param  $id
     * @param  $instance_id
     * @param  $replay_media_id
     * @param  $sort
     */
    public function updateDefaultReplay($id, $instance_id, $replay_media_id, $sort)
    {
        $weixin_default_replay = new WeixinDefaultReplayModel();
        $data = array(
            'instance_id' => $instance_id,
            'reply_media_id' => $replay_media_id,
            'sort' => $sort,
            'update_time' => time()
        );
        $retval = $weixin_default_replay->save($data, [
            'id' => $id
        ]);
        if ($retval > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 添加关键字回复
     *
     * @param  $instance_id
     * @param  $key
     * @param  $match_type
     * @param  $replay_media_id
     * @param  $sort
     */
    public function addKeyReplay($instance_id, $key, $match_type, $replay_media_id, $sort)
    {
        $weixin_key_replay = new WeixinKeyReplayModel();
        $data = array(
            'instance_id' => $instance_id,
            'key' => $key,
            'match_type' => $match_type,
            'reply_media_id' => $replay_media_id,
            'sort' => $sort,
            'create_time' => time()
        );
        $res = $weixin_key_replay->save($data);

        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 修改关键字回复
     *
     * @param  $id
     * @param  $instance_id
     * @param  $key
     * @param  $match_type
     * @param  $replay_media_id
     * @param  $sort
     */
    public function updateKeyReplay($id, $instance_id, $key, $match_type, $replay_media_id, $sort)
    {
        $weixin_key_replay = new WeixinKeyReplayModel();
        $data = array(
            'instance_id' => $instance_id,
            'key' => $key,
            'match_type' => $match_type,
            'reply_media_id' => $replay_media_id,
            'sort' => $sort,
            'update_time' => time()
        );
        $retval = $weixin_key_replay->save($data, [
            'id' => $id
        ]);

        if ($retval > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 获取关键词回复列表
     *
     * @param number $page_index
     * @param number $page_size
     * @param string $condition
     * @param string $order
     */
    public function getKeyReplayList($page_index = 1, $page_size = 0, $condition = '', $order = '', $field='*')
    {
        $weixin_key_replay = new WeixinKeyReplayModel();
        $list = $weixin_key_replay->pageQuery($page_index, $page_size, $condition, $order, $field);
        return $list;
    }

    /**
     * ok-2ok
     * 获取关注时回复列表
     *
     * @param number $page_index
     * @param number $page_size
     * @param string $condition
     * @param string $order
     */
    public function getFollowReplayList($page_index = 1, $page_size = 0, $condition = '', $order = '',  $field = '*')
    {
        $weixin_follow_replay = new WeixinFollowReplayModel();
        $list = $weixin_follow_replay->pageQuery($page_index, $page_size, $condition, $order,  $field);
        return $list;
    }

    /**
     * ok-2ok
     * 获取默认回复列表
     *
     * @param number $page_index
     * @param number $page_size
     * @param string $condition
     * @param string $order
     */
    public function getDefaultReplayList($page_index = 1, $page_size = 0, $condition = '', $order = '', $field='*')
    {
        $weixin_default_replay = new WeixinDefaultReplayModel();
        $list = $weixin_default_replay->pageQuery($page_index, $page_size, $condition, $order, $field);
        return $list;
    }

    /**
     * ok-2ok
     * 获取微信粉丝列表
     *
     * @param number $page_index
     * @param number $page_size
     * @param string $condition
     * @param string $order
     */
    public function getWeixinFansList($page_index = 1, $page_size = 0, $condition = '', $order = '', $field='*')
    {
        $weixin_fans = new WeixinFansModel();
        $list = $weixin_fans->pageQuery($page_index, $page_size, $condition, $order, $field);
        return $list;
    }

    /**
     * ok-2ok
     * 获取微信授权列表
     *
     * @param number $page_index
     * @param number $page_size
     * @param string $condition
     * @param string $order
     */
    public function getWeixinAuthList($page_index = 1, $page_size = 0, $condition = '', $order = '', $field='*')
    {
        $weixin_auth = new WeixinAuthModel();
        $list = $weixin_auth->pageQuery($page_index, $page_size, $condition, $order, $field);
        return $list;
    }


    /**
     * ok-2ok
     * 添加图文消息
     *
     * @param  $title
     * @param  $instance_id
     * @param $type
     * @param  $sort
     * @param $content
     *  * $content格式 = 标题,作者,封面图片,显示在正文,摘要,正文,链接地址;标题,作者,封面图片,显示在正文,摘要,正文,链接地址
     */
    public function addWeixinMedia($title, $instance_id, $type, $sort, $content)
    {
        $weixin_media = new WeixinMediaModel();
        $this->startTrans();
        try {
            $data_media = array(
                'title' => $title,
                'instance_id' => $instance_id,
                'type' => $type,
                'sort' => $sort,
                'create_time' => time()
            );
           $res =  $weixin_media->save($data_media);
            if ($res <= 0 ) {
                $this->rollback();
                Log::write("weixin_media->save 出错");
                return false;
            }
            $media_id = $weixin_media->id;
            if ($type == 1) {
                $res = $this->addWeixinMediaItem($media_id, $title, '', '', '', '', '', '', 0);
                if (empty($res)) {
                    $this->rollback();
                    Log::write("this->addWeixinMediaItem 出错");
                    return false;
                }

            } else 
                if ($type == 2) {
                    $info = explode('`|`', $content);
                    $res = $this->addWeixinMediaItem($media_id, $info[0], $info[1], $info[2], $info[3], $info[4], $info[5], $info[6], 0);
                    if (empty($res)) {
                        $this->rollback();
                        Log::write("this->addWeixinMediaItem 出错");
                        return false;
                    }
                } else 
                    if ($type == 3) {
                        $list = explode('`$`', $content);
                        foreach ($list as $k => $v) {
                            $arr = Array();
                            $arr = explode('`|`', $v);
                           $res =  $this->addWeixinMediaItem($media_id, $arr[0], $arr[1], $arr[2], $arr[3], $arr[4], $arr[5], $arr[6], 0);
                            if (empty($res)) {
                                $this->rollback();
                                Log::write("this->addWeixinMediaItem 出错");
                                return false;
                            }
                        }
                    }
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * ok-2ok
     * 添加图文消息内容项
     *
     * @param $media_id
     * @param $title
     * @param $author
     * @param $cover
     * @param $show_cover_pic
     * @param $summary
     * @param $content
     * @param $content_source_url
     * @param $sort
     */
    public function addWeixinMediaItem($media_id, $title, $author, $cover, $show_cover_pic, $summary, $content, $content_source_url, $sort)
    {
        $weixin_media_item = new WeixinMediaItemModel();
        $data = array(
            'media_id' => $media_id,
            'title' => $title,
            'author' => $author,
            'cover' => $cover,
            'show_cover_pic' => $show_cover_pic,
            'summary' => $summary,
            'content' => $content,
            'content_source_url' => $content_source_url,
            'sort' => $sort,
            'create_time'=>time(),
            'update_time'=>time()
        );
        $retval = $weixin_media_item->save($data);

        if ($retval > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 获取微信图文消息列表
     *
     * @param number $page_index
     * @param number $page_size
     * @param string $condition
     * @param string $order
     */
    public function getWeixinMediaList($page_index = 1, $page_size = 0, $condition = '', $order = '', $field = '*')
    {
        $weixin_media = new WeixinMediaModel();
        $list = $weixin_media->pageQuery($page_index, $page_size, $condition, $order, $field);

        if (! empty($list)) {
            foreach ($list['data'] as $k => $v) {
                $weixin_media_item = new WeixinMediaItemModel();
                $item_list = $weixin_media_item->getConditionQuery([
                    'media_id' => $v['id']
                ], 'title', '');

                $list['data'][$k]['item_list'] = $item_list;
            }
        }
        return $list;
    }

    /**
     * ok-2ok
     * 获取图文消息详情，包括子项
     *
     * @param  $media_id
     */
    public function getWeixinMediaDetail($media_id)
    {
        $weixin_media = new WeixinMediaModel();
        $weixin_media_info = $weixin_media->get($media_id);
        if (! empty($weixin_media_info)) {
            $weixin_media_item = new WeixinMediaItemModel();
            $item_list = $weixin_media_item->getConditionQuery([
                'media_id' => $media_id
            ], '*', '');
            $weixin_media_info['item_list'] = $item_list;
        }
        return $weixin_media_info;
    }

    /**
     * ok-2ok
     * 根据图文消息id查询
     */
    public function getWeixinMediaDetailByMediaId($media_id)
    {
        $weixin_media_item = new WeixinMediaItemModel();
        $item_list = $weixin_media_item->getInfo([
            'id' => $media_id
        ], '*');

        
        if (! empty($item_list)) {
            
            // 主表
            $weixin_media = new WeixinMediaModel();
            $weixin_media_info["media_parent"] = $weixin_media->getInfo([
                "id" => $item_list["media_id"]
            ], "*");

            // 微信配置
            $weixin_auth = new WeixinAuthModel();
            $weixin_media_info["weixin_auth"] = $weixin_auth->getInfo([
                "instance_id" => $weixin_media_info["media_parent"]["instance_id"]
            ], "*");
            
            $weixin_media_info["media_item"] = $item_list;
            
            // 更新阅读次数
            $res = $weixin_media_item->save([
                "hits" => ($item_list["hits"] + 1),
                'update_time'=> time()
            ], [
                "id" => $media_id
            ]);
            
            return $weixin_media_info;
        }
        return null;
    }

    /**
     * ok-2ok
     * 通过author_appid获取shopid
     *
     * @param $author_appid
     */
    public function getShopidByAuthorAppid($author_appid)
    {
        $weixin_auth = new WeixinAuthModel();
        $instance_id = $weixin_auth->getInfo([
            'authorizer_appid' => $author_appid
        ], 'instance_id');

        if (! empty($instance_id['instance_id'])) {
            return $instance_id['instance_id'];
        } else {
            return 0;
        }
    }

    /**
     * ok-2ok
     * 通过微信openID查询uid
     *
     * @param  $openid
     */
    public function getWeixinUidByOpenid($openid)
    {
        $weixin_fans = new WeixinFansModel();
        $uid = $weixin_fans->getInfo([
            'openid' => $openid
        ], 'uid');

        if (! empty($uid['uid'])) {
            return $uid['uid'];
        } else {
            return 0;
        }
    }

    /**
     * ok-2ok
     * 通过appid获取公众账号信息
     *
     * @param  $author_appid
     */
    public function getWeixinInfoByAppid($author_appid)
    {
        $weixin_auth = new WeixinAuthModel();
        $info = $weixin_auth->getInfo([
            'authorizer_appid' => $author_appid
        ], '*');
        return $info;
    }

    /**
     * ok-2ok
     * 取消关注
     *
     * @param  $instance_id
     * @param  $openid
     */
    public function weixinUserUnsubscribe($instance_id=0, $openid)
    {
        $weixin_fans = new WeixinFansModel();
        $data = array(
            'is_subscribe' => 0,
            'unsubscribe_date' => time(),
            'update_time' => time()
        );
        
        $retval = $weixin_fans->save($data, [
            'openid' => $openid,
            'instance_id'=>$instance_id
        ]);

        if ($retval > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 查询用户实例的授权信息
     *
     * @param  $instance_id
     */
    public function getWeixinAuthInfo($instance_id)
    {
        $weixin_auth = new WeixinAuthModel();
        $data = $weixin_auth->getInfo([
            'instance_id' => $instance_id
        ], '*');
        return $data;
    }

    /**
     * ok-2ok
     * 获取实例微信菜单结构
     *
     * @param  $instance_id
     */
    public function getInstanceWchatMenu($instance_id)
    {
        $weixin_menu = new WeixinMenuModel();
        $foot_menu = $weixin_menu->getConditionQuery([
            'instance_id' => $instance_id,
            'pid' => 0
        ], '*', 'sort');

        if (! empty($foot_menu)) {
            foreach ($foot_menu as $k => $v) {
                $foot_menu[$k]['child'] = '';
                $second_menu = $weixin_menu->getConditionQuery([
                    'instance_id' => $instance_id,
                    'pid' => $v['id']
                ], '*', 'sort');
                ;
                if (! empty($second_menu)) {
                    $foot_menu[$k]['child'] = $second_menu;
                    $foot_menu[$k]['child_count'] = count($second_menu);
                } else {
                    $foot_menu[$k]['child_count'] = 0;
                }
            }
        }
        return $foot_menu;
    }

    /**
     * ok-2ok
     * 更新实例自定义菜单到微信
     *
     * @param  $instance_id
     */
    public function updateInstanceMenuToWeixin($instance_id)
    {
        $menu = array();
        $menu_list = $this->getInstanceWchatMenu($instance_id);
        if (! empty($menu_list)) {
            
            foreach ($menu_list as $k => $v) {
                if (! empty($v)) {
                    $menu_item = array(
                        'name' => ''
                    );
                    $menu_item['name'] = $v['menu_name'];
                    
                    // $menu_item['sub_menu'] = array();
                    if (! empty($v['child'])) {
                        
                        foreach ($v['child'] as $k_child => $v_child) {
                            if (! empty($v_child)) {
                                $sub_menu = array();
                                $sub_menu['name'] = $v_child['menu_name'];
                                // $sub_menu['sub_menu'] = array();
                                if ($v_child['menu_event_type'] == 1) {
                                    $sub_menu['type'] = 'view';
                                    $sub_menu['url'] = $v_child['menu_event_url'];
                                } else {
                                    $sub_menu['type'] = 'click';
                                    $sub_menu['key'] = $v_child['id'];
                                }
                                
                                $menu_item['sub_button'][] = $sub_menu;
                            }
                        }
                    } else {
                        if ($v['menu_event_type'] == 1) {
                            $menu_item['type'] = 'view';
                            $menu_item['url'] = $v['menu_event_url'];
                        } else {
                            $menu_item['type'] = 'click';
                            $menu_item['key'] = $v['id'];
                        }
                    }
                    $menu[] = $menu_item;
                }
            }
        }
        $menu_array = array();
        $menu_array['button'] = array();
        foreach ($menu as $k => $v) {
            $menu_array['button'][] = $v;
        }
        // 汉字不编码
        $menu_array = json_encode($menu_array);
        // 链接不转义
        $menu_array = preg_replace_callback("/\\\u([0-9a-f]{4})/i", create_function('$matches', 'return mb_convert_encoding(pack("H*", $matches[1]), "UTF-8", "UCS-2BE");'), $menu_array);
        return $menu_array;
    }

    /**
     * ok-2ok
     * 获取图文消息的微信数据结构
     *
     * @param  $media_info
     *
     *  构造media数据并返回
     *  media_type 消息素材类型1文本 2单图文 3多图文'
     */
    public function getMediaWchatStruct($media_info)
    {
        switch ($media_info['type']) {
            case "1":
                $contentStr = trim($media_info['title']);
                break;
            case "2":
                $pic_url = "";
                if (strstr($media_info['item_list'][0]['cover'], "http")) {
                    $pic_url = $media_info['item_list'][0]['cover'];
                } else {
                    $pic_url = 'http://' . $_SERVER['HTTP_HOST'] . '/' . $media_info['item_list'][0]['cover'];
                }
                $contentStr[] = array(
                    "Title" => $media_info['item_list'][0]['title'],
                    "Description" => $media_info['item_list'][0]['summary'],
                    "PicUrl" => $pic_url,
                    "Url" => __URL(__URL__ . '/shop/wchat/templateMessage?media_id=' . $media_info['item_list'][0]['id'])
                );
                break;
            case "3":
                $contentStr = array();
                foreach ($media_info['item_list'] as $k => $v) {
                    $pic_url = "";
                    if (strstr($v['cover'], "http")) {
                        $pic_url = $v['cover'];
                    } else {
                        $pic_url = 'http://' . $_SERVER['HTTP_HOST'] . '/' . $v['cover'];
                    }
                    $contentStr[$k] = array(
                        "Title" => $v['title'],
                        "Description" => $v['summary'],
                        "PicUrl" => $pic_url,
                        "Url" => __URL(__URL__ . '/shop/wchat/templateMessage?media_id=' . $v['id'])
                    );
                }
                break;
            default:
                $contentStr = "";
                break;
        }
        return $contentStr;
    }

    /**
     * ok-2ok
     * 获取微信回复的消息内容返回media_id
     *
     * @param  $instance_id
     * @param  $key_words
     */
    public function getWhatReplay($instance_id, $key_words)
    {
        $weixin_key_replay = new WeixinKeyReplayModel();
        // 全部匹配
        $condition = array(
            'instance_id' => $instance_id,
            'key' => $key_words,
            'match_type' => 2
        );
        $info = $weixin_key_replay->getInfo($condition, '*');

        if (empty($info)) {
            // 模糊匹配
            $condition = array(
                'instance_id' => $instance_id,
                'key' => array(
                    'LIKE',
                    '%' . $key_words . '%'
                ),
                'match_type' => 1
            );
            $info = $weixin_key_replay->getInfo($condition, '*');
        }
        if (! empty($info)) {
            $media_detail = $this->getWeixinMediaDetail($info['reply_media_id']);
            $content = $this->getMediaWchatStruct($media_detail);
            return $content;
        } else {
            return '';
        }
    }

    /**
     * ok-2ok
     * 获取关注回复
     *
     * @param  $instance_id
     * @return |string
     */
    public function getSubscribeReplay($instance_id)
    {
        $weixin_flow_replay = new WeixinFollowReplayModel();
        $info = $weixin_flow_replay->getInfo([
            'instance_id' => $instance_id
        ], '*');
        if (! empty($info)) {
            $media_detail = $this->getWeixinMediaDetail($info['reply_media_id']);
            $content = $this->getMediaWchatStruct($media_detail);
            return $content;
        } else {
            return '';
        }
    }

    /**
     * ok-2ok
     * 获取微信默认回复
     *
     * @param $instance_id
     * @return array|string
     */
    public function getDefaultReplay($instance_id)
    {
        $weixin_default_replay = new WeixinDefaultReplayModel();
        $info = $weixin_default_replay->getInfo([
            'instance_id' => $instance_id
        ], '*');
        if (! empty($info)) {
            $media_detail = $this->getWeixinMediaDetail($info['reply_media_id']);
            $content = $this->getMediaWchatStruct($media_detail);
            return $content;
        } else {
            return '';
        }
    }

    /**
     * ok-2ok
     * 获取会员 微信公众号二维码
     *
     * @param $uid
     * @param $instance_id
     * @return string
     */
    public function getUserWchatQrcode($uid, $instance_id=0)
    {
        $weixin_auth = new WchatOauth();
        $qrcode_url = $weixin_auth->ever_qrcode($uid);
        return $qrcode_url;
    }

    /**
     * ok-2ok
     * 获取微信推广二维码配置
     * @param $instance_id
     * @param $uid
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function getWeixinQrcodeConfig($instance_id, $uid)
    {
        $member_info = new MemberInfoModel();
        $userinfo = $member_info->getInfo([
            "user_id" => $uid
        ]);

        $qrcode_template_id = $userinfo["qrcode_template_id"];
        $weixin_qrcode = new WeixinQrcodeTemplateModel();
        if ($qrcode_template_id == 0 || $qrcode_template_id == null) {
            $weixin_obj = $weixin_qrcode->getInfo([
                "instance_id" => $instance_id,
                "is_check" => 1
            ], "*");
        } else {
            $weixin_obj = $weixin_qrcode->getInfo([
                "instance_id" => $instance_id,
                "id" => $qrcode_template_id
            ], "*");
        }
        
        if (empty($weixin_obj)) {
            $weixin_obj = $weixin_qrcode->getInfo([
                "instance_id" => $instance_id,
                "is_remove" => 0
            ], "*");
        }
        return $weixin_obj;
    }

    /*
     * (non-PHPdoc)
     * @see \ata\api\IWeixin::updateWeixinQrcodeConfig()
     */
    /**
     * 微信推广二维码设置修改
     * @param $instance_id
     * @param $background
     * @param $nick_font_color
     * @param $nick_font_size
     * @param $is_logo_show
     * @param $header_left
     * @param $header_top
     * @param $name_left
     * @param $name_top
     * @param $logo_left
     * @param $logo_top
     * @param $code_left
     * @param $code_top
     * @return int
     */
    public function updateWeixinQrcodeConfig($instance_id, $background, $nick_font_color, $nick_font_size, $is_logo_show, $header_left, $header_top, $name_left, $name_top, $logo_left, $logo_top, $code_left, $code_top)
    {
        $weixin_qrcode = new WeixinQrcodeConfigModel();
        $num = $weixin_qrcode->where([
            'instance_id' => $instance_id
        ])->count();
        if ($num > 0) {
            $data = array(
                'background' => $background,
                'nick_font_color' => $nick_font_color,
                'nick_font_size' => $nick_font_size,
                'is_logo_show' => $is_logo_show,
                'header_left' => $header_left . 'px',
                'header_top' => $header_top . 'px',
                'name_left' => $name_left . 'px',
                'name_top' => $name_top . 'px',
                'logo_left' => $logo_left . 'px',
                'logo_top' => $logo_top . 'px',
                'code_left' => $code_left . 'px',
                'code_top' => $code_top . 'px'
            );
            $res = $weixin_qrcode->save($data, [
                'instance_id' => $instance_id
            ]);
        } else {
            $data = array(
                'instance_id' => $instance_id,
                'background' => $background,
                'nick_font_color' => $nick_font_color,
                'nick_font_size' => $nick_font_size,
                'is_logo_show' => $is_logo_show,
                'header_left' => $header_left . 'px',
                'header_top' => $header_top . 'px',
                'name_left' => $name_left . 'px',
                'name_top' => $name_top . 'px',
                'logo_left' => $logo_left . 'px',
                'logo_top' => $logo_top . 'px',
                'code_left' => $code_left . 'px',
                'code_top' => $code_top . 'px'
            );
            $weixin_qrcode->save($data);
            $res = 1;
        }
        return $res;
        // TODO Auto-generated method stub
    }

    /**
     * ok-2ok
     * 修改图文消息
     *
     * @param $media_id
     * @param $title
     * @param $instance_id
     * @param $type
     * @param $sort
     * @param $content
     * @return int|string
     */
    public function updateWeixinMedia($media_id, $title, $instance_id, $type, $sort, $content)
    {
        $weixin_media = new WeixinMediaModel();
        $this->startTrans();
        try {
            // 先修改 图文消息表
            $data_media = array(
                'title' => $title,
                'instance_id' => $instance_id,
                'type' => $type,
                'sort' => $sort,
                'update_time' => time()
            );
            $res = $weixin_media->save($data_media, [
                'id' => $media_id
            ]);

            if ($res <= 0) {
                $this->rollback();
                Log::write("weixin_media->save(data_media, ['id' => media_id ]) 出错");
                return false;
            }
            // 修改 图文消息内容的时候 先删除了图文消息内容再添加一次
            $weixin_media_item = new WeixinMediaItemModel();
            $weixin_media_item->destroy([
                'media_id' => $media_id
            ]);
            if ($type == 1) {
                $res = $this->addWeixinMediaItem($media_id, $title, '', '', '', '', '', '', 0);
                if (empty($res)) {
                    $this->rollback();
                    Log::write("this->addWeixinMediaItem 出错");
                    return false;
                }

            } else 
                if ($type == 2) {
                    $info = explode('`|`', $content);
                    $res = $this->addWeixinMediaItem($media_id, $info[0], $info[1], $info[2], $info[3], $info[4], $info[5], $info[6], 0);
                    if (empty($res)) {
                        $this->rollback();
                        Log::write("this->addWeixinMediaItem 出错");
                        return false;
                    }
                } else 
                    if ($type == 3) {
                        $list = explode('`$`', $content);
                        foreach ($list as $k => $v) {
                            $arr = Array();
                            $arr = explode('`|`', $v);
                            $res = $this->addWeixinMediaItem($media_id, $arr[0], $arr[1], $arr[2], $arr[3], $arr[4], $arr[5], $arr[6], 0);
                            if (empty($res)) {
                                $this->rollback();
                                Log::write("this->addWeixinMediaItem 出错");
                                return false;
                            }
                        }
                    }
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * ok-2ok
     * 删除 图文消息
     *
     * @param $media_id
     * @param int $instance_id
     * @return bool
     */
    public function deleteWeixinMedia($media_id, $instance_id=0)
    {
        $res = 0;
        $weixin_media = new WeixinMediaModel();
        $res = $weixin_media->destroy([
            'id' => $media_id,
            'instance_id' => $instance_id
        ]);
        if ($res > 0) {
            $weixin_media_item = new WeixinMediaItemModel();
            $retval = $weixin_media_item->destroy([
                'media_id' => $media_id
            ]);
        }

        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 删除图文消息详情下列表
     * @param $id
     * @return bool
     */
    public function deleteWeixinMediaDetail($id)
    {
        $weixin_media_item = new WeixinMediaItemModel();
        $res = $weixin_media_item->where("id=$id")->delete();

        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * ok-2ok
     * 删除微信自定义菜单
     * @param $menu_id
     * @return int
     */
    public function deleteWeixinMenu($menu_id)
    {
        $weixin_menu = new WeixinMenuModel();
        $res = $weixin_menu->where("id=$menu_id or pid=$menu_id")->delete();

        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 获取 关注时回复信息
     * @param $condition
     * @return null|static
     */
    public function getFollowReplayDetail($condition)
    {
        $weixin_follow_replay = new WeixinFollowReplayModel();
        $info = $weixin_follow_replay->get($condition);
        if ($info['reply_media_id'] > 0) {
            $info['media_info'] = $this->getWeixinMediaDetail($info['reply_media_id']);
        }
        return $info;
    }

    /**
     * ok-2ok
     * 获取 默认回复信息
     * @param $condition
     * @return null|static
     */
    public function getDefaultReplayDetail($condition)
    {
        $weixin_default_replay = new WeixinDefaultReplayModel();
        $info = $weixin_default_replay->get($condition);
        if ($info['reply_media_id'] > 0) {
            $info['media_info'] = $this->getWeixinMediaDetail($info['reply_media_id']);
        }
        return $info;
    }

    /**
     * ok-2ok
     * 删除 关注时 回复
     * @param $instance_id
     * @return bool
     */
    public function deleteFollowReplay($instance_id)
    {
        $weixin_follow_replay = new WeixinFollowReplayModel();
        $res = $weixin_follow_replay->destroy([
            'instance_id' => $instance_id
        ]);

        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 删除默认回复
     * @param $instance_id
     * @return bool
     */
    public function deleteDefaultReplay($instance_id)
    {
        $weixin_default_replay = new WeixinDefaultReplayModel();
        $res = $weixin_default_replay->destroy([
            'instance_id' => $instance_id
        ]);

        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * ok-2ok
     * 获取 关键字回复 详情
     * @param $id
     * @return null|static
     */
    public function getKeyReplyDetail($id)
    {
        $weixin_key_replay = new WeixinKeyReplayModel();
        $info = $weixin_key_replay->get($id);
        if ($info['reply_media_id'] > 0) {
            $info['media_info'] = $this->getWeixinMediaDetail($info['reply_media_id']);
        }
        return $info;
    }

    /**
     * ok-2ok
     * 删除 关键字 回复
     * @param $id
     * @return bool
     */
    public function deleteKeyReplay($id)
    {
        $weixin_key_replay = new WeixinKeyReplayModel();
        $res =  $weixin_key_replay->destroy($id);

        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 得到店铺的推广二维码模板列表
     * @param int $shop_id
     * @return false|static[]
     */
    public function getWeixinQrcodeTemplate($shop_id=0)
    {
        $weixin_qrcode_template = new WeixinQrcodeTemplateModel();
        return $weixin_qrcode_template->all(array(
            "instance_id" => $shop_id,
            "is_remove" => 0
        ));
    }


    /**
     * ok-2ok
     * 将某个模板设置为最新默认模板
     * @param $shop_id
     * @param $id
     * @return bool
     */
    public function modifyWeixinQrcodeTemplateCheck($shop_id, $id)
    {
        $weixin_qrcode_template = new WeixinQrcodeTemplateModel();
        $weixin_qrcode_template->where(array(
            "instance_id" => $shop_id
        ))->update(array(
            "is_check" => 0,
            'update_time'=>time()
        ));
        $retval = $weixin_qrcode_template->where(array(
            "instance_id" => $shop_id,
            "id" => $id
        ))->update(array(
            "is_check" => 1,
            'update_time'=> time()
        ));

        if ($retval > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     *  添加店铺推广二维码模板
     * @param $instance_id
     * @param $background
     * @param $nick_font_color
     * @param $nick_font_size
     * @param $is_logo_show
     * @param $header_left
     * @param $header_top
     * @param $name_left
     * @param $name_top
     * @param $logo_left
     * @param $logo_top
     * @param $code_left
     * @param $code_top
     * @param $template_url
     * @return bool
     */
    public function addWeixinQrcodeTemplate($instance_id, $background, $nick_font_color, $nick_font_size, $is_logo_show, $header_left, $header_top, $name_left, $name_top, $logo_left, $logo_top, $code_left, $code_top, $template_url)
    {
        $weixin_qrcode = new WeixinQrcodeTemplateModel();
        $data = array(
            'instance_id' => $instance_id,
            'background' => $background,
            'nick_font_color' => $nick_font_color,
            'nick_font_size' => $nick_font_size,
            'is_logo_show' => $is_logo_show,
            'header_left' => $header_left . 'px',
            'header_top' => $header_top . 'px',
            'name_left' => $name_left . 'px',
            'name_top' => $name_top . 'px',
            'logo_left' => $logo_left . 'px',
            'logo_top' => $logo_top . 'px',
            'code_left' => $code_left . 'px',
            'code_top' => $code_top . 'px',
            'template_url' => $template_url,
            'create_time'=>time(),
            'update_time' => time()
        );
        $weixin_query = $weixin_qrcode->getConditionQuery([
            "instance_id" => $instance_id,
            "is_check" => 1
        ], "*", '');

        if (empty($weixin_query)) {
            $data["is_check"] = 1;
        }
        $res = $weixin_qrcode->save($data);

        return $weixin_qrcode->id;
    }

    /**
     * ok-2ok
     * 更新模板
     * @param $id
     * @param $instance_id
     * @param $background
     * @param $nick_font_color
     * @param $nick_font_size
     * @param $is_logo_show
     * @param $header_left
     * @param $header_top
     * @param $name_left
     * @param $name_top
     * @param $logo_left
     * @param $logo_top
     * @param $code_left
     * @param $code_top
     * @param $template_url
     * @return bool
     */
    public function updateWeixinQrcodeTemplate($id, $instance_id, $background, $nick_font_color, $nick_font_size, $is_logo_show, $header_left, $header_top, $name_left, $name_top, $logo_left, $logo_top, $code_left, $code_top, $template_url)
    {
        $weixin_qrcode = new WeixinQrcodeTemplateModel();
        $data = array(
            'instance_id' => $instance_id,
            'background' => $background,
            'nick_font_color' => $nick_font_color,
            'nick_font_size' => $nick_font_size,
            'is_logo_show' => $is_logo_show,
            'header_left' => $header_left . 'px',
            'header_top' => $header_top . 'px',
            'name_left' => $name_left . 'px',
            'name_top' => $name_top . 'px',
            'logo_left' => $logo_left . 'px',
            'logo_top' => $logo_top . 'px',
            'code_left' => $code_left . 'px',
            'code_top' => $code_top . 'px',
            'template_url' => $template_url,
            'update_time' => time()
        );
        
        $res = $weixin_qrcode->save($data, [
            'id' => $id
        ]);

        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 删除模板
     * @param $id
     * @param $instance_id
     * @return bool
     */
    public function deleteWeixinQrcodeTemplate($id, $instance_id)
    {
        $weixin_qrcode_template = new WeixinQrcodeTemplateModel();
        $retval = $weixin_qrcode_template->where(array(
            "instance_id" => $instance_id,
            "id" => $id
        ))->update(array(
            "is_remove" => 1,
            'update_time' => time()
        ));

        if ($retval > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 查询单个模板的具体信息
     * @param $id
     * @return array|null|static
     */
    public function getDetailWeixinQrcodeTemplate($id)
    {
        if ($id == 0) {
            $template_obj = array(
                "background" => "",
                "nick_font_color" => "#2B2B2B",
                "nick_font_size" => "23",
                "is_logo_show" => 1,
                "header_left" => "59px",
                "header_top" => "15px",
                "name_left" => "150px",
                "name_top" => "13px",
                "name_top" => "120px",
                "logo_top" => "100px",
                "logo_left" => "120px",
                "code_left" => "70px",
                "code_top" => "300px"
            );
            return $template_obj;
        } else {
            $weixin_qrcode_template = new WeixinQrcodeTemplateModel();
            $template_obj = $weixin_qrcode_template->get($id);
            return $template_obj;
        }
    }

    /**
     * ok-2ok
     * 用户更换 自己的推广二维码
     * @param $shop_id
     * @param $uid
     * @return bool
     */
    public function updateMemberQrcodeTemplate($shop_id, $uid)
    {
        $user = new MemberInfoModel();
        $userinfo = $user->getInfo([
            "user_id" => $uid
        ], "qrcode_template_id");
        $qrcode_template_id = $userinfo["qrcode_template_id"];
        $qrcode_template = new WeixinQrcodeTemplateModel();
        if ($qrcode_template_id == 0 || $qrcode_template_id == null) {
            $template_obj = $qrcode_template->getInfo([
                "instance_id" => $shop_id,
                "is_remove" => 0
            ], "*");

        } else {
            $condition["id"] = array(
                ">",
                $qrcode_template_id
            );
            $condition["instance_id"] = $shop_id;
            $condition["is_remove"] = 0;
            $template_obj = $qrcode_template->getInfo($condition, "*");
            if (empty($template_obj)) {
                $template_obj = $qrcode_template->getInfo([
                    "instance_id" => $shop_id,
                    "is_remove" => 0
                ], "*");
            }
        }
        $res = 0;
        if (! empty($template_obj)) {
           $res = $user->where(array(
                "user_id" => $uid
            ))->update(array(
                "qrcode_template_id" => $template_obj["id"],
                'update' => time()
            ));
        }

        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 获取微信一键关注设置
     * @param $instance_id
     * @return null|static
     */
    public function getInstanceOneKeySubscribe($instance_id)
    {
        $weixin_subscribe = new WeixinOneKeySubscribeModel();
        $info = $weixin_subscribe->get(['instance_id'=>$instance_id]);
        if (empty($info)) {
            $data = array(
                'instance_id' => $instance_id,
                'url' => '',
                'create_time' => time(),
                'update_time' => time()
            );
            $weixin_subscribe->save($data);
            $info = $weixin_subscribe->get(['instance_id'=>$instance_id]);
        }
        return $info;
    }

    /**
     * ok-2ok
     * 设置一键关注
     * @param $instance_id
     * @param $url
     * @return bool
     */
    public function setInsanceOneKeySubscribe($instance_id, $url)
    {
        $weixin_subscribe = new WeixinOneKeySubscribeModel();
        $retval = $weixin_subscribe->save([
            'url' => $url,
            'update_time' => time()
        ], [
            'instance_id' => $instance_id
        ]);

        if ($retval > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 通过微信获取用户对应实例openid
     *
     * @see \ata\api\IWeixin::getUserOpenid()
     */
    public function getUserOpenid($instance_id)
    {}

    /**
     * ok-2ok
     * 获取粉丝个数
     * @param $condition
     * @return int|string
     */
    public function getWeixinFansCount($condition)
    {
        $weixin_fans = new WeixinFansModel();
        $count = $weixin_fans->where($condition)->count();
        return $count;
    }

    /**
     * ok-2ok
     * 获取会员微信关注信息
     * @param $uid
     * @param $instance_id
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function getUserWeixinSubscribeData($uid, $instance_id=0)
    {
        // 查询会员信息
        $user = new MemberUserModel();
        $user_info = $user->getInfo([
            'id' => $uid
        ], 'wx_openid,wx_unionid');
        $fans_info = '';
        // 通过openid查询信息
        if (! empty($user_info['wx_openid'])) {
            $weixin_fans = new WeixinFansModel();
            $fans_info = $weixin_fans->getInfo([
                'openid' => $user_info['wx_openid'],
                'instance_id' => $instance_id
            ]);
        }
        if (empty($fans_info) && ! empty($user_info['wx_unionid'])) {
            $weixin_fans = new WeixinFansModel();
            $fans_info = $weixin_fans->getInfo([
               // 'openid' => $user_info['wx_unionid']
                 'unionid' => $user_info['wx_unionid'],
                'instance_id' => $instance_id
            ]);
        }
        return $fans_info;
    }

    /**
     * ok-2ok
     * 添加 用户消息记录
     * @param $openid
     * @param $content
     * @param $msg_type
     * @return bool
     */
    public function addUserMessage($openid, $content, $msg_type)
    {
        $weixin_user_msg = new WeixinUserMsgModel();
        $uid = $this->getWeixinUidByOpenid($openid);
        $data = array(
            'uid' => $uid,
            'msg_type' => $msg_type,
            'content' => $content,
            'create_time' => time(),
            'update_time' => time()
        );

        $res = 0;
        if ($uid > 0) {
           $res = $weixin_user_msg->save($data);
        }

        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 添加用户消息回复记录
     * @param $msg_id
     * @param $replay_uid
     * @param $replay_type
     * @param $content
     * @return bool
     */
    public function addUserMessageReplay($msg_id, $replay_uid, $replay_type, $content)
    {
        $weixin_user_msg_replay = new WeixinUserMsgReplayModel();
        $data = array(
            'msg_id' => $msg_id,
            'replay_uid' => $replay_uid,
            'replay_type' => $replay_type,
            'content' => $content,
            'replay_time' => time(),
            'create_time' => time(),
            'update_time' => time()
        );
        $res = $weixin_user_msg_replay->save($data);

        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 更新粉丝信息
     * @param $openid_array
     * @param int $instance_id
     * @return array
     */
    public function updateWchatFansList($openid_array, $instance_id = 0){
        $wchatOauth = new WchatOauth();
        $fans_list_info = $wchatOauth ->get_fans_info_list($openid_array);
        //获取微信粉丝列表
        if(isset($fans_list_info["errcode"]) && $fans_list_info["errcode"] < 0){
            return $fans_list_info;
        }else{
            foreach ($fans_list_info['user_info_list'] as $info){
                $province = filterStr($info["province"]);
                $city = filterStr($info["city"]);
                $nickname = filterStr($info['nickname']); 
                $nickname_decode = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $info['nickname']);
                $this->addWeixinFans(0, $instance_id, $nickname, $nickname_decode, $info["headimgurl"], $info["sex"], $info["language"], $info["country"], $province, $city, "", $info["openid"], $info["groupid"], $info["subscribe"], $info["remark"], $info["unionid"]);
            }
        }
        return array(
            'errcode'  => '0',
            'errorMsg' => 'success'
        );
        
    }


    /**
     * ok-2ok
     * 获取微信所有openid
     */
    public function getWeixinOpenidList(){
        $wchatOauth = new WchatOauth();
        $res = $wchatOauth ->get_fans_list("");
        $openid_list = array();
        if(!empty($res['data']))
        {
            $openid_list = $res['data']['openid'];
            $wchatOauth = new WchatOauth();
            while($res['next_openid']){
                $res = $wchatOauth -> get_fans_list($res['next_openid']);
                if(!empty($res['data']))
                {
                    $openid_list = array_merge($openid_list,$res['data']['openid']);
                }
                
            }
            return array(
                'total' => $res['total'],
                'openid_list' => $openid_list,
                'errcode'  => '0',
                'errorMsg' => ''
            );
           
        }else{
            if(!empty($res["errcode"]))
            {
                return array(
                    'errcode'  => $res['errcode'],
                    'errorMsg' => $res['errmsg'],
                    'total'    => 0,
                    'openid_list' => ''
                );
            }else{
                return array(
                    'errcode'  => '-400001',
                    'errorMsg' => '当前无粉丝列表或者获取失败',
                    'total'    => 0,
                    'openid_list' => ''
                );
            }
        }
        
    }
}