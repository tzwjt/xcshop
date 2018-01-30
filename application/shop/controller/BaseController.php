<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-08-20
 * Time: 18:56
 */

namespace app\shop\controller;

use app\common\controller\CommonController;
use dao\handle\ConfigHandle;
use dao\handle\member\MemberUserHandle;
use think\Session;
use think\Request;
use dao\extend\WchatOauth;

class BaseController extends CommonController
{
    protected $user_id = 0;

    public function checkAuth() {
        $this->user_id = 0;
        Session::set("MEMBER_USER_ID",0);
        $GLOBALS['userInfo'] = 0;

        /*获取头部信息*/
        $header = Request::instance()->header();
        if (empty( $header['authkey']) || empty($header['sessionid'])) {
            header('Content-Type:application/json; charset=utf-8');
            //   return json(resultArray(101,"身份验证未通过"));
            return json(resultArray(1,"身份验证未通过"));

        }

        $authKey = $header['authkey'];
        $sessionId = $header['sessionid'];
        $cache = cache('Auth_'.$authKey);

        // 校验sessionid和authKey
        if (empty($sessionId)||empty($authKey)||empty($cache)) {
            header('Content-Type:application/json; charset=utf-8');
         //   return json(resultArray(101,"身份验证未通过"));
            return json(resultArray(1,"身份验证未通过"));
        //    exit(json_encode(['code'=>101, 'error'=>'登录已失效']));
        }

        if ($sessionId != $cache['sessionId']) {
            header('Content-Type:application/json; charset=utf-8');
            return json(resultArray(1,"身份验证未通过"));
        }



        // 检查账号有效性
        $userInfo = $cache['userInfo'];
        $map['id'] = $userInfo['id'];
        $map['status'] = 1;

       $memberUserHandle =  new MemberUserHandle();
        $res = $memberUserHandle->getUserInfoByCondition($map, 'id');
       // if (!Db::name('admin_user')->where($map)->value('id')) {
        if (empty($res['id'])) {
            header('Content-Type:application/json; charset=utf-8');
         //   return json(resultArray(102,"账号已被删除或禁用"));
            return json(resultArray(1,"账号已被删除或禁用"));
         //   exit(json_encode(['code'=>103, 'error'=>'账号已被删除或禁用']));
        }
        // 更新缓存
        cache('Auth_'.$authKey, $cache, config('LOGIN_SESSION_VALID'));
        /*
        $authAdapter = new AuthAdapter($authKey);
        $request = Request::instance();
        $ruleName = $request->module().'-'.$request->controller() .'-'.$request->action();
        if (!$authAdapter->checkLogin($ruleName, $cache['userInfo']['id'])) {
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode(['code'=>102,'error'=>'没有权限']));
        }
        */
        $GLOBALS['userInfo'] = $userInfo;
        $this->user_id = $userInfo['id'];
        Session::set("MEMBER_USER_ID", $userInfo['id']);
      //  Session::set('RegisterPhone',$phone );
      //  return $userInfo;

    }

    /**
     * 获取分享相关票据
     */
    public function getShareTicket()
    {
        $config = new ConfigHandle();
        $auth_info = $config->getInstanceWchatConfig(0);
        // 获取票据
        if (isWeixin() && ! empty($auth_info['value']['appid'])) {
            // 针对单店版获取微信票据
            $wexin_auth = new WchatOauth();
            $signPackage['appId'] = $auth_info['value']['appid'];
            $signPackage['jsTimesTamp'] = time();
            $signPackage['jsNonceStr'] = $wexin_auth->get_nonce_str();
            $jsapi_ticket = $wexin_auth->jsapi_ticket();
            $signPackage['ticket'] = $jsapi_ticket;
            $url = request()->url(true);
            $Parameters = "jsapi_ticket=" . $signPackage['ticket'] . "&noncestr=" . $signPackage['jsNonceStr'] . "&timestamp=" . $signPackage['jsTimesTamp'] . "&url=" . $url;
            $signPackage['jsSignature'] = sha1($Parameters);
           // return json(resultArray(0,"操作成功",$signPackage));
            return $signPackage;
        } else if ( ! empty($auth_info['value']['appid'])) {
            // 针对单店版获取微信票据
            $wexin_auth = new WchatOauth();
            $signPackage['appId'] = $auth_info['value']['appid'];
            $signPackage['jsTimesTamp'] = time();
            $signPackage['jsNonceStr'] = $wexin_auth->get_nonce_str();
            $jsapi_ticket = $wexin_auth->jsapi_ticket();
            $signPackage['ticket'] = $jsapi_ticket;
            $url = request()->url(true);
            $Parameters = "jsapi_ticket=" . $signPackage['ticket'] . "&noncestr=" . $signPackage['jsNonceStr'] . "&timestamp=" . $signPackage['jsTimesTamp'] . "&url=" . $url;
            $signPackage['jsSignature'] = sha1($Parameters);
          //  return json(resultArray(0,"操作成功",$signPackage));
           return $signPackage;
        } else {
            $signPackage = array(
                'appId' => '',
                'jsTimesTamp' => '',
                'jsNonceStr' => '',
                'ticket' => '',
                'jsSignature' => ''
            );
            return $signPackage;
            //return json(resultArray(0,"操作成功",$signPackage));
        }
    }
}

