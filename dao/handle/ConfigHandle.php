<?php
/**
 * Config.php
 * @date : 2017.8.24
 * @version : v1.0
 */
namespace dao\handle;

/**
 * 系统配置业务层
 */

use dao\model\Config as ConfigModel;

//use dao\model\CustomTemplateModel;
//use dao\model\NoticeModel;
use dao\model\NoticeTemplateItem as NoticeTemplateItemModel;
use dao\model\NoticeTemplate as NoticeTemplateModel;
use dao\model\NoticeTemplateType as NoticeTemplateTypeModel;
use dao\handle\BaseHandle as BaseHandle;
use think\Db;
use think\Log;
//use Qiniu\json_decode;


/**
 * 系统配置业务层
 */


//use Qiniu\json_decode;
use think\Cache;
//use think\Db;

class ConfigHandle extends BaseHandle
{

    private $config_module;

    function __construct()
    {
        parent::__construct();
        $this->config_module = new ConfigModel();
    }


    /**
     * 设置 默认佣金率
     */

    public function setDefaultCommissionRate($keywords, $rate)
    {

        $info = $this->config_module->getInfo([
            'key' => $keywords,
            'instance_id' => 0
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' =>0,
                'key' =>$keywords,
                'value' => $rate,
                'is_use' => 1,
               // 'create_time' => time()
            );
            $res = $config_module->save($data);
            if (empty($res)) {
                return false;
            } else {
                return true;
            }
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'value' =>$rate,
                'is_use' => 1,
            );
            $res = $config_module->save($data, [
                'instance_id' => 0,
                'key' => $keywords
            ]);

            if ($res === false) {
                return false;
            } else {
                return true;
            }
        }
    }


    /**
     * ok-2ok
     * 获取微信基本配置(WCHAT)
     */
    public function getWchatConfig($instance_id=0)
    {
        $wchat_config = Cache::get("wchat_config" . $instance_id);
        if (empty($wchat_config)) {
            $info = $this->config_module->getInfo([
                'key' => 'WCHAT',
                'instance_id' => $instance_id
            ], 'value,is_use');
            if (empty($info['value'])) {
                $wchat_config = array(
                    'value' => array(
                        'APP_KEY' => '',
                        'APP_SECRET' => '',
                        'AUTHORIZE' => '',
                        'CALLBACK' => ''
                    ),
                    'is_use' => 0
                );
            } else {
                $info['value'] = json_decode($info['value'], true);
                $wchat_config = $info;
            }
            Cache::set("wchat_config" . $instance_id, $wchat_config);
        }
        return $wchat_config;

    }



    /**
     * ok-2ok
     * 开放平台网站应用授权登录
     */
    public function setWchatConfig($instance_id, $appid, $appsecret, $url, $call_back_url, $is_use)
    {
        Cache::set("wchat_config" . $instance_id, '');
        $info = array(
            'APP_KEY' => $appid,
            'APP_SECRET' => $appsecret,
            'AUTHORIZE' => $url,
            'CALLBACK' => $call_back_url
        );
        $value = json_encode($info);
        $count = $this->config_module->where([
            'key' => 'WCHAT',
            'instance_id' => $instance_id
        ])->count();
        if ($count > 0) {
            $data = array(
                'value' => $value,
                'is_use' => $is_use,
                'update_time' => time()
            );
            $res = $this->config_module->where([
                'key' => 'WCHAT',
                'instance_id' => $instance_id
            ])->update($data);
            if ($res == 1) {
                return true; //SUCCESS;
            } else {
                return false; // UPDATA_FAIL;
            }
        } else {
            $data = array(
                'instance_id' => $instance_id,
                'key' => 'WCHAT',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time(),
                'update_time' => time()
            );
            $res = $this->config_module->save($data);

            if ($res == 1) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 获取QQ互联配置(QQ)
     */
    /*
    public function getQQConfig($instance_id)
    {
        $qq_config = Cache::get("qq_config" . $instance_id);
        if (empty($qq_config)) {
            $info = $this->config_module->getInfo([
                'key' => 'QQLOGIN',
                'instance_id' => $instance_id
            ], 'value,is_use');
            if (empty($info['value'])) {
                $qq_config = array(
                    'value' => array(
                        'APP_KEY' => '',
                        'APP_SECRET' => '',
                        'AUTHORIZE' => '',
                        'CALLBACK' => ''
                    ),
                    'is_use' => 0
                );
            } else {
                $info['value'] = json_decode($info['value'], true);
                $qq_config = $info;
            }
            Cache::set("qq_config" . $instance_id, $qq_config);
        }
        return $qq_config;
    }
    */

    /**
     * qq互联
     */
    /*
    public function setQQConfig($instance_id, $appkey, $appsecret, $url, $call_back_url, $is_use)
    {
        Cache::set("qq_config" . $instance_id, '');
        $info = array(
            'APP_KEY' => $appkey,
            'APP_SECRET' => $appsecret,
            'AUTHORIZE' => $url,
            'CALLBACK' => $call_back_url
        );
        $value = json_encode($info);
        $count = $this->config_module->where([
            'key' => 'QQLOGIN',
            'instance_id' => $instance_id
        ])->count();
        if ($count > 0) {
            $data = array(
                'value' => $value,
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $this->config_module->where([
                'key' => 'QQLOGIN',
                'instance_id' => $instance_id
            ])->update($data);
            if ($res == 1) {
                return SUCCESS;
            } else {
                return UPDATA_FAIL;
            }
        } else {
            $data = array(
                'instance_id' => $instance_id,
                'key' => 'QQLOGIN',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $this->config_module->save($data);
            return $res;
        }
    }
   */

    /**
     * 获取系统登录配置信息
     */
    /*
    public function getLoginConfig()
    {
        $instance_id = 0;
        $wchat_config = $this->getWchatConfig($instance_id);
        $qq_config = $this->getQQConfig($instance_id);

        $mobile_config = $this->getMobileMessage($instance_id);
        $email_config = $this->getEmailMessage($instance_id);
        $data = array(
            'wchat_login_config' => $wchat_config,
            'qq_login_config' => $qq_config,
            'mobile_config' => $mobile_config,
            'email_config' => $email_config
        );
        return $data;
    }
   */


    /**
     * ok-2ok
     * 获取微信支付参数(WPAY)
     */
    public function getWpayConfig($instance_id=0)
    {
        $info = $this->config_module->getInfo([
            'instance_id' => $instance_id,
            'key' => 'WPAY'
        ], 'value,is_use');
       // if (empty($info['value'])) {
        if (empty($info)) {
            return array(
                'value' => array(
                    'appid' => '',
                    'appsecret' => '',
                    'mch_id' => '',
                    'mch_key' => ''
                ),
                'is_use' => 0
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
    }

    /**
     * ok-2ok
     * 设置微信支付参数(WPAY)
     *
     * @param  $appid
     *            微信登录appid
     * @param  $appkey
     *            微信登录appkey
     * @param  $mch_id
     *            商户账号
     * @param  $mch_key
     *            商户支付秘钥
     */
    public function setWpayConfig($instanceid=0, $appid, $appsecret, $mch_id, $mch_key, $is_use)
    {
        $data = array(
            'appid' => $appid,
            'appsecret' => $appsecret,
            'mch_id' => $mch_id,
            'mch_key' => $mch_key
        );
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'WPAY',
            'instance_id' => $instanceid
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'key' => 'WPAY',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'WPAY',
                'value' => $value,
                'is_use' => $is_use,
                'update_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'key' => 'WPAY'
            ]);
        }

        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 获取支付宝支付参数(ALIPAY)
     */
    public function getAlipayConfig($instance_id=0)
    {
        $info = $this->config_module->getInfo([
            'instance_id' => $instance_id,
            'key' => 'ALIPAY'
        ], 'value,is_use');
      //  if (empty($info['value'])) {
        if (empty($info)) {
            return array(
                'value' => array(
                    'ali_partnerid' => '',
                    'ali_seller' => '',
                    'ali_key' => ''
                ),
                'is_use' => 0
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
    }


    /**
     * ok-2ok
     * 设置支付宝支付配置(ALIPAY)
     *
     * @param  $partnerid
     *            商户ID
     * @param  $seller
     *            商户账号
     * @param  $ali_key
     *            商户秘钥
     */
    public function setAlipayConfig($instanceid=0, $partnerid, $seller, $ali_key, $is_use)
    {
        $data = array(
            'ali_partnerid' => $partnerid,
            'ali_seller' => $seller,
            'ali_key' => $ali_key
        );
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'ALIPAY',
            'instance_id' => $instanceid
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'key' => 'ALIPAY',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'ALIPAY',
                'value' => $value,
                'is_use' => $is_use,
                'update_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'key' => 'ALIPAY'
            ]);
        }

        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 设置微信和支付宝开关状态
     */
    public function setPayStatusConfig($instanceid=0, $is_use, $type)
    {
        $config_module = new ConfigModel();
        $result = $config_module->getInfo([
            'instance_id' => $instanceid,
            'key' => $type
        ], 'value');
        if (empty($result['value'])) {
            $info = array();
            $value = json_encode($info);
            $data = array(
                'is_use' => $is_use,
                'update_time' => time(),
                'value' => $value
            );
        } else {
            $data = array(
                'is_use' => $is_use,
                'update_time' => time()
            );
        }
        $res = $config_module->save($data, [
            'instance_id' => $instanceid,
            'key' => $type
        ]);

        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * PC商城热搜关键词获取
     */
    /*
    public function getHotsearchConfig($instanceid)
    {
        $info = $this->config_module->getInfo([
            'key' => 'HOTKEY',
            'instance_id' => $instanceid
        ], 'value');
        if (empty($info['value'])) {
            return null;
        } else {
            return json_decode($info['value'], true);
        }
    }
   */
    /**
     * PC商城热搜关键词设置
     *
     * @param  $partnerid
     * @param  $seller
     * @param  $ali_key
     */
    /*
    public function setHotsearchConfig($instanceid, $keywords, $is_use)
    {
        $data = $keywords;
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'HOTKEY',
            'instance_id' => $instanceid
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'key' => 'HOTKEY',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'value' => $value,
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'key' => 'HOTKEY'
            ]);
        }
        return $res;

    }
*/
    /**
     * pc 商城获取 默认搜索
     *
     * @param  $instanceid
     */
    /*
    public function getDefaultSearchConfig($instanceid)
    {
        $info = $this->config_module->getInfo([
            'key' => 'DEFAULTKEY',
            'instance_id' => $instanceid
        ], 'value');
        if (empty($info['value'])) {
            return null;
        } else {
            return json_decode($info['value'], true);
        }
    }
    */

    /**
     * PC商城热搜关键词设置
     *
     * @param  $instanceid
     * @param  $keywords
     * @param  $is_use
     */
    /*
    public function setDefaultSearchConfig($instanceid, $keywords, $is_use)
    {
        $data = $keywords;
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'DEFAULTKEY',
            'instance_id' => $instanceid
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'key' => 'DEFAULTKEY',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'value' => $value,
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'key' => 'DEFAULTKEY'
            ]);
        }
        return $res;
    }
    */

    /**
     * 获取 用户通知
     */
    /*
    public function getUserNotice($instanceid)
    {
        $info = $this->config_module->getInfo([
            'key' => 'USERNOTICE',
            'instance_id' => $instanceid
        ], 'value');
        if (empty($info['value'])) {
            return null;
        } else {
            return json_decode($info['value'], true);
        }
    }
    */

    /**
     * 设置 用户通知
     */
    /*
    public function setUserNotice($instanceid, $keywords, $is_use)
    {
        $data = $keywords;
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'USERNOTICE',
            'instance_id' => $instanceid
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'key' => 'USERNOTICE',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'value' => $value,
                'is_use' => $is_use,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'key' => 'USERNOTICE'
            ]);
        }
        return $res;
    }
    */

    /**
     * ok-2ok
     * 获取 发送邮件接口设置
     */
    public function getEmailMessage($instanceid=0)
    {
        $info = $this->config_module->getInfo([
            'key' => 'EMAILMESSAGE',
            'instance_id' => $instanceid
        ], 'value, is_use');
        //if (empty($info['value'])) {
        if (empty($info)) {
            return array(
                'value' => array(
                    'email_host' => '',
                    'email_port' => '',
                    'email_addr' => '',
                    'email_pass' => '',
                    'email_id' => '',
                    'email_is_security' => 0
                ),
                'is_use' => 0
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
    }


    /**
     * ok-2ok
     * 设置 发送邮件接口设置
     */
    public function setEmailMessage($instanceid=0, $email_host, $email_port, $email_addr, $email_id, $email_pass, $is_use, $email_is_security)
    {
        $data = array(
            'email_host' => $email_host,
            'email_port' => $email_port,
            'email_addr' => $email_addr,
            'email_id' => $email_id,
            'email_pass' => $email_pass,
            'email_is_security' => $email_is_security
        );
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'EMAILMESSAGE',
            'instance_id' => $instanceid
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'key' => 'EMAILMESSAGE',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'EMAILMESSAGE',
                'value' => $value,
                'is_use' => $is_use,
                'update_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'key' => 'EMAILMESSAGE'
            ]);
        }

        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * ok-2ok
     * 获取 发送短信接口设置
     *
     * @param  $instanceid
     */
    public function getMobileMessage($instanceid=0)
    {
        $info = $this->config_module->getInfo([
            'key' => 'MOBILEMESSAGE',
            'instance_id' => $instanceid
        ], 'value, is_use');
       // if (empty($info['value'])) {
        if (empty($info)) {
            return array(
                'value' => array(
                    'appKey' => '',
                    'secretKey' => '',
                    'freeSignName' => '',
                    'user_type'=>0
                ),
                'is_use' => 0
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
    }


    /**
     * ok-2ok
     * 设置 发送短信接口设置
     *
     * @param  $instanceid
     * @param  $app_key
     * @param  $secret_key
     * @param  $is_use
     * @param  $user_type
     *            用户类型
     */
    public function setMobileMessage($instanceid, $app_key, $secret_key, $free_sign_name, $is_use, $user_type)
    {
        $data = array(
            'appKey' => $app_key,
            'secretKey' => $secret_key,
            'freeSignName' => $free_sign_name,
            'user_type' => $user_type
        );
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'MOBILEMESSAGE',
            'instance_id' => $instanceid
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'key' => 'MOBILEMESSAGE',
                'value' => $value,
                'is_use' => $is_use,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'MOBILEMESSAGE',
                'value' => $value,
                'is_use' => $is_use,
                'update_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'key' => 'MOBILEMESSAGE'
            ]);
        }

        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }



    /**
     * 获取 微信开放平台接口设置
     *
     * @param  $instanceid
     */
    /*
    public function getWinxinOpenPlatformConfig($instanceid)
    {
        $info = $this->config_module->getInfo([
            'key' => 'WXOPENPLATFORM',
            'instance_id' => $instanceid
        ], 'value, is_use');
        if (empty($info['value'])) {
            return array(
                'value' => array(
                    'appId' => '',
                    'appsecret' => '',
                    'encodingAesKey' => '',
                    'tk' => ''
                ),
                'is_use' => 1
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
    }
*/
    /**
     * 设置 微信开放平台接口设置
     */
    /*
    public function setWinxinOpenPlatformConfig($instanceid, $appid, $appsecret, $encodingAesKey, $tk)
    {
        $data = array(
            'appId' => $appid,
            'appsecret' => $appsecret,
            'encodingAesKey' => $encodingAesKey,
            'tk' => $tk
        );
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'WXOPENPLATFORM',
            'instance_id' => $instanceid
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'key' => 'WXOPENPLATFORM',
                'value' => $value,
                'is_use' => 1,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'WXOPENPLATFORM',
                'value' => $value,
                'is_use' => 1,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'key' => 'WXOPENPLATFORM'
            ]);
        }
        return $res;
    }
    */

    /**
     * 获取 登录验证码
     */
    /*
    public function getLoginVerifyCodeConfig($instanceid)
    {
        $verify_config = Cache::get("LoginVerifyCodeConfig" . $instanceid);
        if (empty($verify_config)) {
            $info = $this->config_module->getInfo([
                'key' => 'LOGINVERIFYCODE',
                'instance_id' => $instanceid
            ], 'value, is_use');
            if (empty($info['value'])) {
                $verify_config = array(
                    'value' => array(
                        'platform' => 0,
                        'admin' => 0,
                        'pc' => 0
                    ),
                    'is_use' => 1
                );
                Cache::set("LoginVerifyCodeConfig" . $instanceid, $verify_config);
            } else {
                $info['value'] = json_decode($info['value'], true);
                $verify_config = $info;
                Cache::set("LoginVerifyCodeConfig" . $instanceid, $verify_config);
            }
        }
        return $verify_config;
    }
*/
    /**
     * 设置 登录验证码是否开启
     *
     * @param  $platform
     * @param  $admin
     * @param  $pc
     */
    /*
    public function setLoginVerifyCodeConfig($instanceid, $platform, $admin, $pc)
    {
        $data = array(
            'platform' => $platform,
            'admin' => $admin,
            'pc' => $pc
        );
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'LOGINVERIFYCODE',
            'instance_id' => $instanceid
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instanceid,
                'key' => 'LOGINVERIFYCODE',
                'value' => $value,
                'is_use' => 1,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'LOGINVERIFYCODE',
                'value' => $value,
                'is_use' => 1,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instanceid,
                'key' => 'LOGINVERIFYCODE'
            ]);
        }
        Cache::set("LoginVerifyCodeConfig" . $instanceid, '');
        return $res;
    }
*/
    /**
     * ok-2ok
     * 得到店铺的系统通知项
     */
    public function getNoticeConfig($shop_id=0)
    {
        $config_model = new ConfigModel();
        $condition = array(
            'instance_id' => $shop_id,
            'key' => array(
                'in',
                'EMAILMESSAGE,MOBILEMESSAGE'
            )
        );
        $notify_list = $config_model->getConditionQuery($condition, "*", "");

        if (! empty($notify_list)) {
            for ($i = 0; $i < count($notify_list); $i ++) {
                if ($notify_list[$i]["key"] == "EMAILMESSAGE") {
                    $notify_list[$i]["notify_name"] = "邮件通知";
                } else
                    if ($notify_list[$i]["key"] == "MOBILEMESSAGE") {
                        $notify_list[$i]["notify_name"] = "短信通知";
                    }
            }
            return $notify_list;
        } else {
            return null;
        }
    }

    /**
     * ok-2ok
     * 对于单店铺系统设置微信配置
     */
    public function setInstanceWchatConfig($instance_id=0, $appid, $appsecret, $token)
    {
        $data = array(
            'appid' => trim($appid),
            'appsecret' => trim($appsecret),
            'token' => trim($token)
        );
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'SHOPWCHAT',
            'instance_id' => $instance_id
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instance_id,
                'key' => 'SHOPWCHAT',
                'value' => $value,
                'is_use' => 1,
                'create_time' => time(),
                'update_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'SHOPWCHAT',
                'value' => $value,
                'is_use' => 1,
                'update_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instance_id,
                'key' => 'SHOPWCHAT'
            ]);
        }

        if ($res == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 对于单店铺系统获取微信配置
     */
    public function getInstanceWchatConfig($instance_id)
    {
        $info = $this->config_module->getInfo([
            'key' => 'SHOPWCHAT',
            'instance_id' => $instance_id
        ], 'value');
        if (empty($info['value'])) {
            return array(
                'value' => array(
                    'appid' => '',
                    'appsecret' => '',
                    'token' => 'TOKEN'
                ),
                'is_use' => 1
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
    }

    /**
     * ok-2ok
     * 设置支付信息配置
     */
    public function setPayInfoConfig($instance_id=0, $pay_body, $pay_details)
    {
        $data = array(
            'pay_body' => $pay_body,
            'pay_details' => $pay_details
        );
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            //  'key' => 'SHOPWCHAT',
            'key' => 'ORDOR_PAY_INFO',
            'instance_id' => $instance_id
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $instance_id,
                //   'key' => 'SHOPWCHAT',
                'key' => 'ORDOR_PAY_INFO',
                'value' => $value,
                'is_use' => 1,
                'create_time' => time()
            );
            $res = $config_module->save($data);

            if (empty($res)) {
                return false;
            }
        } else {
            $config_module = new ConfigModel();
            $data = array(
                //  'key' => 'SHOPWCHAT',
                'key' => 'ORDOR_PAY_INFO',
                'value' => $value,
                'is_use' => 1,
                'update_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $instance_id,
                // 'key' => 'SHOPWCHAT'
                'key' => 'ORDOR_PAY_INFO'
            ]);
            if (empty($res)) {
                return false;
            }
        }
        // return $res;
        return true;
    }

    /**
     * ok-2ok
     * 得到订单的支付信息配置
     */
    public function getPayInfoConfig($instance_id=0)
    {
        $info = $this->config_module->getInfo([
            // 'key' => 'SHOPWCHAT',
            'key' => 'ORDOR_PAY_INFO',
            'instance_id' => $instance_id
        ], 'value');
        if (empty($info['value'])) {
            return array(
                'value' => array(
                    'pay_body' => '',
                    'pay_details' => ''
                ),
                'is_use' => 1
            );
        } else {
         //  Log::write("config value:".$info['value']);
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
    }

    /**
     * 获取其他支付方式配置
     */
    /*
    public function getOtherPayTypeConfig()
    {
        $info = $this->config_module->getInfo([
            'key' => 'OTHER_PAY',
            'instance_id' => 0
        ], 'value');
        if (empty($info['value'])) {
            return array(
                'value' => array(
                    'is_coin_pay' => 0,
                    'is_balance_pay' => 0
                ),
                'is_use' => 1
            );
        } else {
            $info['value'] = json_decode($info['value'], true);
            return $info;
        }
    }
*/
    /**
     * 设置其他支付方式配置
     */
    /*
    public function setOtherPayTypeConfig($is_coin_pay, $is_balance_pay)
    {
        $data = array(
            'is_coin_pay' => $is_coin_pay,
            'is_balance_pay' => $is_balance_pay
        );
        $value = json_encode($data);
        $info = $this->config_module->getInfo([
            'key' => 'OTHER_PAY',
            'instance_id' => 0
        ], 'value');
        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => 0,
                'key' => 'OTHER_PAY',
                'value' => $value,
                'is_use' => 1,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => 'OTHER_PAY',
                'value' => $value,
                'is_use' => 1,
                'modify_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => 0,
                'key' => 'OTHER_PAY'
            ]);
        }
        return $res;
    }
*/
    /**
     * 设置公告
     *
     * @param  $id
     * @param  $is_enable
     */
    /*
    public function setNotice($shopid, $notice_message, $is_enable)
    {
        $notice = new NoticeModel();
        $data = array(
            'notice_message' => $notice_message,
            'is_enable' => $is_enable
        );
        $res = $notice->save($data, [
            'shopid' => $shopid
        ]);
        return $res;
    }
*/
    /**
     * 获取公告单条详情
     *
     * @param  $id
     */
    /*
    public function getNotice($shopid)
    {
        $notice = new NoticeModel();
        $notice_info = $notice->getInfo([
            'shopid' => $shopid
        ]);
        if (empty($notice_info)) {
            $data = array(
                'shopid' => $shopid,
                'notice_message' => '',
                'is_enable' => 0
            );
            $notice->save($data);
            $notice_info = $notice->getInfo([
                'shopid' => $shopid
            ]);
        }
        return $notice_info;
    }
*/
    /**
     * 获取系统设置value值
     * 传入字符串 $key = 'key1,key2,key3,.....'
     * 返回数组 array('key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', .....)
     *
     * @param  $instance_id
     * @param  $key
     */
    public function getConfig($instance_id, $key)
    {
        $config = new ConfigModel();
        $array = array();
        $info = $config->getInfo([
            'instance_id' => $instance_id,
            'key' => $key
        ]);
        return $info;
    }

    public function getValidConfig($instance_id, $key)
    {
        $config = new ConfigModel();
        $array = array();
        $info = $config->getInfo([
            'instance_id' => $instance_id,
            'is_use' => 1,
            'key' => $key
        ]);
        return $info;
    }

    /**
     * 设置系统设置
     * 传入数组 格式必须为
     * 例：$array[0] = array(
     * 'instance_id' => $this->instance_id,
     * 'key' => 'ORDER_BUY_CLOSE_TIME',
     * 'value' => '30',
     * 'desc' => '订单下单之后多少分钟未支付则关闭订单',
     * 'is_use' => 1
     * );
     * $array[1] = array(
     * 'instance_id' => $this->instance_id,
     * 'key' => 'ORDER_DELIVERY_COMPLETE_TIME',
     * 'value' => '7',
     * 'desc' => '订单收货之后多长时间自动完成',
     * 'is_use' => 1
     * );
     * ...
     */
    public function setConfig($params)
    {
        $config = new ConfigModel();
        foreach ($params as $key => $value) {
            if ($this->checkConfigKeyIsset($value['instance_id'], $value['key'])) {
                $res = $this->updateConfig($value['instance_id'], $value['key'], $value['value'], $value['desc'], $value['is_use']);
            } else {
                $res = $this->addConfig($value['instance_id'], $value['key'], $value['value'], $value['desc'], $value['is_use']);
            }
        }
        return $res;
    }

    /**
     * ok-2ok
     * 添加设置
     *
     * @param  $instance_id
     * @param  $key
     * @param  $value
     * @param  $desc
     * @param  $is_use
     */
    protected function addConfig($instance_id, $key, $value, $desc, $is_use)
    {
        $config = new ConfigModel();
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $data = array(
            'instance_id' => $instance_id,
            'key' => $key,
            'value' => $value,
            'desc' => $desc,
            'is_use' => $is_use,
            'create_time' => time()
        );
        $res = $config->save($data);
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 修改配置
     *
     * @param  $instance_id
     * @param  $key
     * @param  $value
     * @param  $desc
     * @param  $is_use
     */
    protected function updateConfig($instance_id, $key, $value, $desc, $is_use)
    {
        $config = new ConfigModel();
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $data = array(
            'value' => $value,
            'desc' => $desc,
            'is_use' => $is_use,
            'update_time'=>time()
          //  'modify_time' => time()
        );
        $res = $config->save($data, [
            'instance_id' => $instance_id,
            'key' => $key
        ]);
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 判断当前设置是否存在
     * 存在返回 true 不存在返回 false
     *
     * @param  $instance_id
     * @param  $key
     */
    public function checkConfigKeyIsset($instance_id, $key)
    {
        $config = new ConfigModel();
        $num = $config->where([
            'instance_id' => $instance_id,
            'key' => $key
        ])->count();
        return $num > 0 ? true : false;
    }

    /**
     * ok-2ok
     * 得到系统通知的详情
     *
     * @param  $shop_id
     * @param  $template_code
     */

    public function getNoticeTemplateDetail($shop_id=0, $template_type)
    {
        $notice_template_model = new NoticeTemplateModel();
        $condition = array(
            "template_type" => $template_type,
            "instance_id" => $shop_id
        );
        $template_list = $notice_template_model->getConditionQuery($condition, "*", "");

        return $template_list;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IConfig::getNoticeTemplateOneDetail()
     */
    /*
    public function getNoticeTemplateOneDetail($shop_id, $template_type, $template_code)
    {
        $notice_template_model = new NoticeTemplateModel();
        $info = $notice_template_model->getInfo([
            'instance_id' => $shop_id,
            'template_type' => $template_type,
            'template_code' => $template_code
        ]);
        return $info;
    }
*/
    /**
     * ok-2ok
     * 更新通知模板
     * @param  $template_id
     * @param  $shop_id
     * @param  $template_code
     * @param  $template
     */
    public function updateNoticeTemplate($shop_id=0, $template_type, $template_array,$notify_type)
    {
        $template_data = json_decode($template_array, true);
        foreach ($template_data as $template_obj) {
            $template_code = $template_obj["template_code"];
            $template_title = $template_obj["template_title"];
            $template_content = $template_obj["template_content"];
            $sign_name = $template_obj["sign_name"];
            $is_enable = $template_obj["is_enable"];
            $notification_mode = $template_obj["notification_mode"];
            $notice_template_model = new NoticeTemplateModel();
            $t_count = $notice_template_model->getCount([
                "instance_id" => $shop_id,
                "template_type" => $template_type,
                "template_code" => $template_code,
                "notify_type" => $notify_type
            ]);

            if ($t_count > 0) {
                // 更新
                $data = array(
                    "template_title" => $template_title,
                    "template_content" => $template_content,
                    "sign_name" => $sign_name,
                    "is_enable" => $is_enable,
                    "update_time" => time(),
                    "notification_mode" => $notification_mode
                );
                $res = $notice_template_model->save($data, [
                    "instance_id" => $shop_id,
                    "template_type" => $template_type,
                    "template_code" => $template_code,
                    "notify_type" => $notify_type
                ]);
            } else {
                // 添加
                $data = array(
                    "instance_id" => $shop_id,
                    "template_type" => $template_type,
                    "template_code" => $template_code,
                    "template_title" => $template_title,
                    "template_content" => $template_content,
                    "sign_name" => $sign_name,
                    "is_enable" => $is_enable,
                    "create_time" => time(),
                    "update_time" => time(),
                    "notify_type" => $notify_type,
                    "notification_mode" => $notification_mode
                );
                $res = $notice_template_model->save($data);
            }
        }

        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 得到店铺的系统通知项
     * (non-PHPdoc)
     *
     * @see \ata\api\IConfig::getNoticeConfig()
     */
    /*
    public function getNoticeConfig($shop_id)
    {
        $config_model = new ConfigModel();
        $condition = array(
            'instance_id' => $shop_id,
            'key' => array(
                'in',
                'EMAILMESSAGE,MOBILEMESSAGE'
            )
        );
        $notify_list = $config_model->getQuery($condition, "*", "");
        if (! empty($notify_list)) {
            for ($i = 0; $i < count($notify_list); $i ++) {
                if ($notify_list[$i]["key"] == "EMAILMESSAGE") {
                    $notify_list[$i]["notify_name"] = "邮件通知";
                } else
                    if ($notify_list[$i]["key"] == "MOBILEMESSAGE") {
                        $notify_list[$i]["notify_name"] = "短信通知";
                    }
            }
            return $notify_list;
        } else {
            return null;
        }
    }
*/
    /**
     * 得到短信模板通知项
     *
     * @param  $shop_id
     */
    /*
    public function getMobileConfig($shop_id)
    {
        $config_model = new ConfigModel();
        $condition = array(
            'instance_id' => $shop_id,
            'key' => 'MOBILEMESSAGE'
        );
        $notify_list = $config_model->getQuery($condition, "*", "");
        if (! empty($notify_list)) {
            if ($notify_list["key"] == "MOBILEMESSAGE") {
                $notify_list["notify_name"] = "短信通知";
            }

            return $notify_list;
        } else {
            return null;
        }
    }
*/
    /**
     * 得到店铺的email的配置信息
     *
     * @param  $shop_id
     */
    /*
    public function getNoticeEmailConfig($shop_id)
    {
        $config_model = new ConfigModel();
        $condition = array(
            'instance_id' => $shop_id,
            'key' => 'EMAILMESSAGE'
        );
        $email_detail = $config_model->getQuery($condition, "*", "");
        return $email_detail;
    }
*/
    /**
     * 得到店铺的短信配置信息
     *
     * @param  $shop_id
     */
    /*
    public function getNoticeMobileConfig($shop_id)
    {
        $config_model = new ConfigModel();
        $condition = array(
            'instance_id' => $shop_id,
            'key' => 'MOBILEMESSAGE'
        );
        $mobile_detail = $config_model->getQuery($condition, "*", "");
        return $mobile_detail;
    }
*/
    /**
     * ok-2ok
     * 得到通知的邮件发送项
     */
    public function getNoticeTemplateItem($template_code)
    {
        $notice_model = new NoticeTemplateItemModel();
        $item_list = $notice_model->where("FIND_IN_SET('" . $template_code . "', type_ids)")->select();
        return $item_list;
    }



    /**
     * ok-2ok
     * 得到通知模板的集合
     */
    public function getNoticeTemplateType($template_type,$notify_type)
    {
        $notice_type_model = new NoticeTemplateTypeModel();
        $type_list = $notice_type_model->where("(template_type='" . $template_type . "' or template_type='all') and notify_type = '".$notify_type."'")->select();
        return $type_list;
    }

    /**
     * ok-2ok
     * 支付的通知项
     *
     * @param  $shop_id
     * @return string|NULL
     */
    public function getPayConfig($shop_id=0)
    {
        $config_model = new ConfigModel();
        $condition = array(
            'instance_id' => $shop_id,
            'key' => array(
                'in',
                'WPAY,ALIPAY'
            )
        );
        $notify_list = $config_model->getConditionQuery($condition, "*", "");

        if (! empty($notify_list)) {
            for ($i = 0; $i < count($notify_list); $i ++) {
                if ($notify_list[$i]["key"] == "WPAY") {
                    $notify_list[$i]["logo"] = "public/static/admin/images/wchat.png";
                    $notify_list[$i]["pay_name"] = "微信支付";
                    $notify_list[$i]["desc"] = "该系统支持微信网页支付和扫码支付";
                } else
                    if ($notify_list[$i]["key"] == "ALIPAY") {
                        $notify_list[$i]["pay_name"] = "支付宝支付";
                        $notify_list[$i]["logo"] = "public/static/admin/images/pay.png";
                        $notify_list[$i]["desc"] = "该系统支持即时到账接口";
                    }
            }
            return $notify_list;
        } else {
            return null;
        }
    }

    /**
     * ok-2ok
     * 获取原路退款信息
     */
    public function getOriginalRoadRefundSetting($shop_id=0, $type)
    {
        if ($type == "wechat") {
            $key = 'ORIGINAL_ROAD_REFUND_SETTING_WECHAT';
        } elseif ($type == "alipay") {
            $key = 'ORIGINAL_ROAD_REFUND_SETTING_ALIPAY';
        }

        $info = $this->config_module->getInfo([
            'key' => $key,
            'instance_id' => $shop_id
        ], 'value');
        return $info;
    }

    /**
     * ok-2ok
     * 设置原路退款信息
     */
    public function setOriginalRoadRefundSetting($shop_id=0, $type, $value)
    {
        if ($type == "wechat") {
            $key = 'ORIGINAL_ROAD_REFUND_SETTING_WECHAT';
        } elseif ($type == "alipay") {
            $key = 'ORIGINAL_ROAD_REFUND_SETTING_ALIPAY';
        }

        $info = $this->config_module->getInfo([
            'key' => $key,
            'instance_id' => $shop_id
        ], 'value');

        if (empty($info)) {
            $config_module = new ConfigModel();
            $data = array(
                'instance_id' => $shop_id,
                'key' => $key,
                'value' => $value,
                'is_use' => 1,
                'create_time' => time()
            );
            $res = $config_module->save($data);
        } else {
            $config_module = new ConfigModel();
            $data = array(
                'key' => $key,
                'value' => $value,
                'is_use' => 1,
                'update_time' => time()
            );
            $res = $config_module->save($data, [
                'instance_id' => $shop_id,
                'key' => $key
            ]);
        }

        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * 获取会员余额提现设置
     *
     * @param  $shop_id
     */
    /*
    public function getBalanceWithdrawConfig($shop_id)
    {
        $key = 'WITHDRAW_BALANCE';
        $info = $this->getConfig($shop_id, $key);
        if (empty($info)) {
            $params[0] = array(
                'instance_id' => $shop_id,
                'key' => $key,
                'value' => array(
                    'withdraw_cash_min' => 0.00,
                    'withdraw_multiple' => 0,
                    'withdraw_poundage' => 0,
                    'withdraw_message' => ''
                ),
                'desc' => '会员余额提现设置',
                'is_use' => 0
            );
            $this->setConfig($params);
            $info = $this->getConfig($shop_id, $key);
        }
        $info['value'] = json_decode($info['value'], true);
        return $info;
    }
*/

    /**
     * ok-2ok
     * 获取代理商余额提现设置
     *
     * @param  $shop_id
     */
    public function getAgentBalanceWithdrawConfig()
    {
        $key = 'AGENT_WITHDRAW_BALANCE';
        $info = $this->getConfig(0, $key);
        if (empty($info)) {
            $params[0] = array(
                'instance_id' => 0,
                'key' => $key,
                'value' => array(
                    'agent_withdraw_cash_min' => 0.00,
                    'agent_withdraw_multiple' => 0,
                    'agent_withdraw_poundage' => 0,
                    'agent_withdraw_message' => ''
                ),
                'desc' => '代理商余额提现设置',
                'is_use' => 1
            );
            $this->setConfig($params);
            $info = $this->getConfig(0, $key);
        }
        $info['value'] = json_decode($info['value'], true);
        return $info;
    }

    /**
     * ok-2ok
     * 设置代理商余额提现设置
     *
     * @param  $shop_id
     * @param  $key
     * @param  $value
     * @param  $desc
     * @param  $is_use
     */
    public function setAgentBalanceWithdrawConfig($key, $value, $is_use)
    {
        $params[0] = array(
            'instance_id' => 0,
            'key' => $key,
            'value' => array(
                'agent_withdraw_cash_min' => $value['agent_withdraw_cash_min'],
                'agent_withdraw_multiple' => $value['agent_withdraw_multiple'],
                'agent_withdraw_poundage' => $value['agent_withdraw_poundage'],
                'agent_withdraw_message' => $value['agent_withdraw_message']
            ),
            'desc' => '代理商余额提现设置',
            'is_use' => $is_use
        );
        $res = $this->setConfig($params);
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 设置会员余额设置
     *
     * @param  $shop_id
     * @param  $key
     * @param  $value
     * @param  $desc
     * @param  $is_use
     */
    /*
    public function setBalanceWithdrawConfig($shop_id, $key, $value, $is_use)
    {
        $params[0] = array(
            'instance_id' => $shop_id,
            'key' => $key,
            'value' => array(
                'withdraw_cash_min' => $value['withdraw_cash_min'],
                'withdraw_multiple' => $value['withdraw_multiple'],
                'withdraw_poundage' => $value['withdraw_poundage'],
                'withdraw_message' => $value['withdraw_message']
            ),
            'desc' => '会员余额提现设置',
            'is_use' => $is_use
        );
        $res = $this->setConfig($params);
        return $res;
    }
*/
    /**
     * ok-2ok
     * 获取美洽客服链接地址
     *
     * @param  $shop_id
     */
    public function getCustomServiceConfig($shop_id=0)
    {
        $key = 'SERVICE_ADDR';
        $info = $this->getConfig($shop_id, $key);
        if (empty($info)) {
            $params[0] = array(
                'instance_id' => $shop_id,
                'key' => $key,
                'value' => array(
                    'service_addr' => ''
                ),
                'desc' => '美洽客服链接地址设置',
                'is_use'=>0
            );
            $this->setConfig($params);
            $info = $this->getConfig($shop_id, $key);
        }
        $info['value'] = json_decode($info['value'], true);
        return $info;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IConfig::getrecommendConfig()
     */
    /*
    public function getrecommendConfig($shop_id)
    {
        $key = 'IS_RECOMMEND';
        $info = $this->getConfig($shop_id, $key);
        if (empty($info)) {
            $params[0] = array(
                'instance_id' => $shop_id,
                'key' => $key,
                'value' => array(
                    'is_recommend' => ''
                ),
                'desc' => '首页商城促销版块显示设置'
            );
            $this->setConfig($params);
            $info = $this->getConfig($shop_id, $key);
        }
        $info['value'] = json_decode($info['value'], true);
        return $info;
    }
    */

    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IConfig::setisrecommendConfig()
     */
    /*
    public function setisrecommendConfig($shop_id, $key, $value)
    {
        $params[0] = array(
            'instance_id' => $shop_id,
            'key' => $key,
            'value' => array(
                'is_recommend' => $value['is_recommend']
            ),
            'desc' => '首页商品促销版块是否显示设置',
            'is_use' => 1
        );
        $res = $this->setConfig($params);
        return $res;
    }
*/
    /**
     * (non-PHPdoc)
     *
     * @see \data\api\IConfig::setiscategoryConfig()
     */
    /*
    public function setiscategoryConfig($shop_id, $key, $value)
    {
        $params[0] = array(
            'instance_id' => $shop_id,
            'key' => $key,
            'value' => array(
                'is_category' => $value['is_category']
            ),
            'desc' => '首页商品分类是否显示设置',
            'is_use' => 1
        );
        $res = $this->setConfig($params);
        return $res;
    }
*/
    /*
    public function getcategoryConfig($shop_id)
    {
        $key = 'IS_CATEGORY';
        $info = $this->getConfig($shop_id, $key);
        if (empty($info)) {
            $params[0] = array(
                'instance_id' => $shop_id,
                'key' => $key,
                'value' => array(
                    'is_category' => ''
                ),
                'desc' => '首页商品分类是否显示设置'
            );
            $this->setConfig($params);
            $info = $this->getConfig($shop_id, $key);
        }
        $info['value'] = json_decode($info['value'], true);
        return $info;
    }
*/
    /**
     * ok-2ok
     * 美洽客服链接地址设置
     */
    public function setCustomServiceConfig($shop_id=0, $key, $value, $is_use)
    {
        $params[0] = array(
            'instance_id' => $shop_id,
            'key' => $key,
            'value' => array(
                'service_addr' => $value['service_addr']
            ),
            'desc' => '美洽客服链接地址',
            'is_use'=>$is_use
        );
        $res = $this->setConfig($params);
        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 获取seo设置
     */
    public function getSeoConfig($shop_id=0)
    {
        $seo_config = Cache::get("seo_config" . $shop_id);
        if (empty($seo_config)) {
            $seo_title = $this->getConfig($shop_id, 'SEO_TITLE');
            $seo_meta = $this->getConfig($shop_id, 'SEO_META');
            $seo_desc = $this->getConfig($shop_id, 'SEO_DESC');
            $seo_other = $this->getConfig($shop_id, 'SEO_OTHER');
            if (empty($seo_title) && empty($seo_meta) && empty($seo_desc) && empty($seo_other)) {
                $this->setSeoConfig($shop_id, '', '', '', '');
                $array = array(
                    'seo_title' => '',
                    'seo_meta' => '',
                    'seo_desc' => '',
                    'seo_other' => ''
                );
            } else {
                $array = array(
                    'seo_title' => $seo_title['value'],
                    'seo_meta' => $seo_meta['value'],
                    'seo_desc' => $seo_desc['value'],
                    'seo_other' => $seo_other['value']
                );
            }
            Cache::set("seo_config" . $shop_id, $array);
            $seo_config = $array;
        }

        return $seo_config;
    }


    /**
     * ok-2ok
     * 得到版权信息
     * @param $shop_id
     * @return array
     */
    public function getCopyrightConfig($shop_id=0)
    {
        $copyright_logo = $this->getConfig($shop_id, 'COPYRIGHT_LOGO');
        $copyright_meta = $this->getConfig($shop_id, 'COPYRIGHT_META');
        $copyright_link = $this->getConfig($shop_id, 'COPYRIGHT_LINK');
        $copyright_desc = $this->getConfig($shop_id, 'COPYRIGHT_DESC');
        $copyright_companyname = $this->getConfig($shop_id, 'COPYRIGHT_COMPANYNAME');
        if (empty($copyright_logo) && empty($copyright_meta) && empty($copyright_link) && empty($copyright_desc) && empty($copyright_companyname)) {
            $this->setCopyrightConfig($shop_id, '', '', '', '', '');
            $array = array(
                'copyright_logo' => '',
                'copyright_meta' => '',
                'copyright_link' => '',
                'copyright_desc' => '',
                'copyright_companyname' => ''
            );
        } else {
            $array = array(
                'copyright_logo' => $copyright_logo['value'],
                'copyright_meta' => $copyright_meta['value'],
                'copyright_link' => $copyright_link['value'],
                'copyright_desc' => $copyright_desc['value'],
                'copyright_companyname' => $copyright_companyname['value']
            );
        }
        return $array;
    }

    /**
     * ok-2ok
     * @return array
     */
    public function getShopConfig()
    {
        $order_auto_delinery = $this->getConfig(0, 'ORDER_AUTO_DELIVERY');//订单多长时间自动完成
        $order_point_pay = $this->getConfig(0, 'ORDER_POINT_PAY'); //是否开启积分支付
        $order_balance_pay = $this->getConfig(0, 'ORDER_BALANCE_PAY'); //是否开启余额支付
        $order_delivery_complete_time = $this->getConfig(0, 'ORDER_DELIVERY_COMPLETE_TIME'); //收货后多长时间自动完成
        $order_show_buy_record = $this->getConfig(0, 'ORDER_SHOW_BUY_RECORD'); //是否显示购买记录
        $order_invoice_tax = $this->getConfig(0, 'ORDER_INVOICE_TAX');   //发票税率
        $order_invoice_content = $this->getConfig(0, 'ORDER_INVOICE_CONTENT');  //发票内容
        $order_delivery_pay = $this->getConfig(0, 'ORDER_DELIVERY_PAY');  //是否开启货到付款
        $order_buy_close_time = $this->getConfig(0, 'ORDER_BUY_CLOSE_TIME'); //订单自动关闭时间
        $buyer_self_lifting = $this->getConfig(0, 'BUYER_SELF_LIFTING');  //是否开启买家自提
        $seller_dispatching = $this->getConfig(0, 'ORDER_SELLER_DISPATCHING'); //是否开启商家配送
        $is_logistics = $this->getConfig(0, 'ORDER_IS_LOGISTICS'); //是否允许选择物流
        $shopping_back_points = $this->getConfig(0, 'SHOPPING_BACK_POINTS'); //购物返积分设置
        if (empty($order_auto_delinery) && empty($order_point_pay) && empty($order_balance_pay) && empty($order_delivery_complete_time) && empty($order_show_buy_record) && empty($order_invoice_tax) && empty($order_invoice_content) && empty($order_delivery_pay) && empty($order_buy_close_time)) {
            $this->setShopConfig(0, '', '', '', '', '', '', '', '');
            $array = array(
                'order_auto_delinery' => '',
                'order_point_pay' => '',
                'order_balance_pay' => '',
                'order_delivery_complete_time' => '',
                'order_show_buy_record' => '',
                'order_invoice_tax' => '',
                'order_invoice_content' => '',
                'order_delivery_pay' => '',
                'order_buy_close_time' => '',
                'buyer_self_lifting' => '',
                'seller_dispatching' => '',
                'is_logistics' => '1',
                'shopping_back_points' => ''
            );
        } else {
            $array = array(
                'order_auto_delinery' => $order_auto_delinery['value'],
                'order_point_pay' => $order_point_pay['value'],
                'order_balance_pay' => $order_balance_pay['value'],
                'order_delivery_complete_time' => $order_delivery_complete_time['value'],
                'order_show_buy_record' => $order_show_buy_record['value'],
                'order_invoice_tax' => $order_invoice_tax['value'],
                'order_invoice_content' => $order_invoice_content['value'],
                'order_delivery_pay' => $order_delivery_pay['value'],
                'order_buy_close_time' => $order_buy_close_time['value'],
                'buyer_self_lifting' => $buyer_self_lifting['value'],
                'seller_dispatching' => $seller_dispatching['value'],
                'is_logistics' => $is_logistics['value'],
                'shopping_back_points' => $shopping_back_points['value']
            );
        }
        if ($array['order_buy_close_time'] == 0) {
            $array['order_buy_close_time'] = 60;
        }

        return $array;
    }
    /*
    public function getShopConfig($shop_id)
    {
        $order_auto_delinery = $this->getConfig($shop_id, 'ORDER_AUTO_DELIVERY');
        $order_balance_pay = $this->getConfig($shop_id, 'ORDER_BALANCE_PAY');
        $order_delivery_complete_time = $this->getConfig($shop_id, 'ORDER_DELIVERY_COMPLETE_TIME');
        $order_show_buy_record = $this->getConfig($shop_id, 'ORDER_SHOW_BUY_RECORD');
        $order_invoice_tax = $this->getConfig($shop_id, 'ORDER_INVOICE_TAX');
        $order_invoice_content = $this->getConfig($shop_id, 'ORDER_INVOICE_CONTENT');
        $order_delivery_pay = $this->getConfig($shop_id, 'ORDER_DELIVERY_PAY');
        $order_buy_close_time = $this->getConfig($shop_id, 'ORDER_BUY_CLOSE_TIME');
        $buyer_self_lifting = $this->getConfig($shop_id, 'BUYER_SELF_LIFTING');
        $seller_dispatching = $this->getConfig($shop_id, 'ORDER_SELLER_DISPATCHING');
        $is_logistics = $this->getConfig($shop_id, 'ORDER_IS_LOGISTICS');
        $shopping_back_points = $this->getConfig($shop_id, 'SHOPPING_BACK_POINTS');
        if (empty($order_auto_delinery) || empty($order_balance_pay) || empty($order_delivery_complete_time) || empty($order_show_buy_record) || empty($order_invoice_tax) || empty($order_invoice_content) || empty($order_delivery_pay) || empty($order_buy_close_time)) {
            $this->SetShopConfig($shop_id, '', '', '', '', '', '', '', '');
            $array = array(
                'order_auto_delinery' => '',
                'order_balance_pay' => '',
                'order_delivery_complete_time' => '',
                'order_show_buy_record' => '',
                'order_invoice_tax' => '',
                'order_invoice_content' => '',
                'order_delivery_pay' => '',
                'order_buy_close_time' => '',
                'buyer_self_lifting' => '',
                'seller_dispatching' => '',
                'is_logistics' => '1',
                'shopping_back_points' => ''
            );
        } else {
            $array = array(
                'order_auto_delinery' => $order_auto_delinery['value'],
                'order_balance_pay' => $order_balance_pay['value'],
                'order_delivery_complete_time' => $order_delivery_complete_time['value'],
                'order_show_buy_record' => $order_show_buy_record['value'],
                'order_invoice_tax' => $order_invoice_tax['value'],
                'order_invoice_content' => $order_invoice_content['value'],
                'order_delivery_pay' => $order_delivery_pay['value'],
                'order_buy_close_time' => $order_buy_close_time['value'],
                'buyer_self_lifting' => $buyer_self_lifting['value'],
                'seller_dispatching' => $seller_dispatching['value'],
                'is_logistics' => $is_logistics['value'],
                'shopping_back_points' => $shopping_back_points['value']
            );
        }
        if ($array['order_buy_close_time'] == 0) {
            $array['order_buy_close_time'] = 60;
        }

        return $array;
    }
*/

    /**
     * ok-2ok
     * 设置商城SEO
     * @param int $shop_id
     * @param $seo_title
     * @param $seo_meta
     * @param $seo_desc
     * @param $seo_other
     * @return bool
     */
    public function setSeoConfig($shop_id=0, $seo_title, $seo_meta, $seo_desc, $seo_other)
    {
        $array[0] = array(
            'instance_id' => $shop_id,
            'key' => 'SEO_TITLE',
            'value' => $seo_title,
            'desc' => '标题附加字',
            'is_use' => 1
        );
        $array[1] = array(
            'instance_id' => $shop_id,
            'key' => 'SEO_META',
            'value' => $seo_meta,
            'desc' => '商城关键词',
            'is_use' => 1
        );
        $array[2] = array(
            'instance_id' => $shop_id,
            'key' => 'SEO_DESC',
            'value' => $seo_desc,
            'desc' => '关键词描述',
            'is_use' => 1
        );
        $array[3] = array(
            'instance_id' => $shop_id,
            'key' => 'SEO_OTHER',
            'value' => $seo_other,
            'desc' => '其他页头信息',
            'is_use' => 1
        );
        $res = $this->setConfig($array);
        Cache::set("seo_config" . $shop_id, '');

        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * ok-2ok
     * 设置版权信息
     * @param int $shop_id
     * @param $copyright_logo
     * @param $copyright_meta
     * @param $copyright_link
     * @param $copyright_desc
     * @param $copyright_companyname
     * @return bool
     */
    public function setCopyrightConfig($shop_id=0, $copyright_logo, $copyright_meta, $copyright_link, $copyright_desc, $copyright_companyname)
    {
        $array[0] = array(
            'instance_id' => $shop_id,
            'key' => 'COPYRIGHT_LOGO',
            'value' => $copyright_logo,
            'desc' => '版权logo',
            'is_use' => 1
        );
        $array[1] = array(
            'instance_id' => $shop_id,
            'key' => 'COPYRIGHT_META',
            'value' => $copyright_meta,
            'desc' => '备案号',
            'is_use' => 1
        );
        $array[2] = array(
            'instance_id' => $shop_id,
            'key' => 'COPYRIGHT_LINK',
            'value' => $copyright_link,
            'desc' => '版权链接',
            'is_use' => 1
        );
        $array[3] = array(
            'instance_id' => $shop_id,
            'key' => 'COPYRIGHT_DESC',
            'value' => $copyright_desc,
            'desc' => '版权信息',
            'is_use' => 1
        );
        $array[4] = array(
            'instance_id' => $shop_id,
            'key' => 'COPYRIGHT_COMPANYNAME',
            'value' => $copyright_companyname,
            'desc' => '公司名称',
            'is_use' => 1
        );
        $res = $this->setConfig($array);

        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * @param $order_auto_delinery
     * @param $order_balance_pay
     * @param $order_delivery_complete_time
     * @param $order_show_buy_record
     * @param $order_invoice_tax
     * @param $order_invoice_content
     * @param $order_delivery_pay
     * @param $order_buy_close_time
     * @param $buyer_self_lifting
     * @param $seller_dispatching
     * @param $is_logistics
     * @param $shopping_back_points
     * @return false|int
     */
    public function setShopConfig( $order_auto_delinery,$order_point_pay, $order_balance_pay, $order_delivery_complete_time, $order_show_buy_record, $order_invoice_tax, $order_invoice_content, $order_delivery_pay, $order_buy_close_time, $buyer_self_lifting, $seller_dispatching, $is_logistics, $shopping_back_points)
    {
        $array[0] = array(
            'instance_id' => 0,
            'key' => 'ORDER_AUTO_DELIVERY',
            'value' => $order_auto_delinery,//14
            'desc' => '订单多长时间自动完成',
            'is_use' => 1
        );
        $array[1] = array(
            'instance_id' =>0,
            'key' => 'ORDER_POINT_PAY',
            'value' => $order_point_pay, //1
            'desc' => '是否开启积分支付',
            'is_use' => 1
        );
        $array[2] = array(
            'instance_id' =>0,
            'key' => 'ORDER_BALANCE_PAY',
            'value' => $order_balance_pay, //1
            'desc' => '是否开启余额支付',
            'is_use' => 1
        );
        $array[3] = array(
            'instance_id' => 0,
            'key' => 'ORDER_DELIVERY_COMPLETE_TIME',
            'value' => $order_delivery_complete_time, //7
            'desc' => '收货后多长时间自动完成',
            'is_use' => 1
        );
        $array[4] = array(
            'instance_id' => 0,
            'key' => 'ORDER_SHOW_BUY_RECORD',
            'value' => $order_show_buy_record, //1
            'desc' => '是否显示购买记录',
            'is_use' => 1
        );
        $array[5] = array(
            'instance_id' => 0,
            'key' => 'ORDER_INVOICE_TAX',
            'value' => $order_invoice_tax,//0
            'desc' => '发票税率',
            'is_use' => 1
        );
        $array[6] = array(
            'instance_id' => 0,
            'key' => 'ORDER_INVOICE_CONTENT',
            'value' => $order_invoice_content, //''
            'desc' => '发票内容',
            'is_use' => 1
        );
        $array[7] = array(
            'instance_id' => 0,
            'key' => 'ORDER_DELIVERY_PAY',
            'value' => $order_delivery_pay, //0
            'desc' => '是否开启货到付款',
            'is_use' => 1
        );
        $array[8] = array(
            'instance_id' => 0,
            'key' => 'ORDER_BUY_CLOSE_TIME',
            'value' => $order_buy_close_time, //60
            'desc' => '订单自动关闭时间',
            'is_use' => 1
        );
        $array[9] = array(
            'instance_id' => 0,
            'key' => 'BUYER_SELF_LIFTING',
            'value' => $buyer_self_lifting, //1
            'desc' => '是否开启买家自提',
            'is_use' => 1
        );
        $array[10] = array(
            'instance_id' => 0,
            'key' => 'ORDER_SELLER_DISPATCHING',//1
            'value' => $seller_dispatching,
            'desc' => '是否开启商家配送',
            'is_use' => 1
        );
        $array[11] = array(
            'instance_id' => 0,
            'key' => 'ORDER_IS_LOGISTICS',
            'value' => $is_logistics, //0
            'desc' => '是否允许选择物流',
            'is_use' => 1
        );
        $array[12] = array(
            'instance_id' => 0,
            'key' => 'SHOPPING_BACK_POINTS',
            'value' => $shopping_back_points, //0
            'desc' => '购物返积分设置',
            'is_use' => 1
        );

        $res = $this->setConfig($array);

        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }
    /*
    public function SetShopConfig($shop_id, $order_auto_delinery, $order_balance_pay, $order_delivery_complete_time, $order_show_buy_record, $order_invoice_tax, $order_invoice_content, $order_delivery_pay, $order_buy_close_time, $buyer_self_lifting, $seller_dispatching, $is_logistics, $shopping_back_points)
    {
        $array[0] = array(
            'instance_id' => $this->instance_id,
            'key' => 'ORDER_AUTO_DELIVERY',
            'value' => $order_auto_delinery,
            'desc' => '订单多长时间自动完成',
            'is_use' => 1
        );
        $array[1] = array(
            'instance_id' => $this->instance_id,
            'key' => 'ORDER_BALANCE_PAY',
            'value' => $order_balance_pay,
            'desc' => '是否开启余额支付',
            'is_use' => 1
        );
        $array[2] = array(
            'instance_id' => $this->instance_id,
            'key' => 'ORDER_DELIVERY_COMPLETE_TIME',
            'value' => $order_delivery_complete_time,
            'desc' => '收货后多长时间自动完成',
            'is_use' => 1
        );
        $array[3] = array(
            'instance_id' => $this->instance_id,
            'key' => 'ORDER_SHOW_BUY_RECORD',
            'value' => $order_show_buy_record,
            'desc' => '是否显示购买记录',
            'is_use' => 1
        );
        $array[4] = array(
            'instance_id' => $this->instance_id,
            'key' => 'ORDER_INVOICE_TAX',
            'value' => $order_invoice_tax,
            'desc' => '发票税率',
            'is_use' => 1
        );
        $array[5] = array(
            'instance_id' => $this->instance_id,
            'key' => 'ORDER_INVOICE_CONTENT',
            'value' => $order_invoice_content,
            'desc' => '发票内容',
            'is_use' => 1
        );
        $array[6] = array(
            'instance_id' => $this->instance_id,
            'key' => 'ORDER_DELIVERY_PAY',
            'value' => $order_delivery_pay,
            'desc' => '是否开启货到付款',
            'is_use' => 1
        );
        $array[7] = array(
            'instance_id' => $this->instance_id,
            'key' => 'ORDER_BUY_CLOSE_TIME',
            'value' => $order_buy_close_time,
            'desc' => '订单自动关闭时间',
            'is_use' => 1
        );
        $array[8] = array(
            'instance_id' => $this->instance_id,
            'key' => 'BUYER_SELF_LIFTING',
            'value' => $buyer_self_lifting,
            'desc' => '是否开启买家自提',
            'is_use' => 1
        );
        $array[9] = array(
            'instance_id' => $this->instance_id,
            'key' => 'ORDER_SELLER_DISPATCHING',
            'value' => $seller_dispatching,
            'desc' => '是否开启商家配送',
            'is_use' => 1
        );
        $array[10] = array(
            'instance_id' => $this->instance_id,
            'key' => 'ORDER_IS_LOGISTICS',
            'value' => $is_logistics,
            'desc' => '是否允许选择物流',
            'is_use' => 1
        );
        $array[11] = array(
            'instance_id' => $this->instance_id,
            'key' => 'SHOPPING_BACK_POINTS',
            'value' => $shopping_back_points,
            'desc' => '购物返积分设置',
            'is_use' => 1
        );

        $res = $this->setConfig($array);
        return $res;
    }
*/
    /*
   * 设置积分奖励配置
     * SetIntegralConfig(0, $register, $sign, $share)
   */
    public function setIntegralConfig($shop_id, $register, $sign, $share)
    {
        $array[0] = array(
            'instance_id' => $shop_id,
            'key' => 'REGISTER_INTEGRAL',
            'value' => $register,
            'desc' => '注册送积分',
            'is_use' => 1
        );
        $array[1] = array(
            'instance_id' => $shop_id,
            'key' => 'SIGN_INTEGRAL',
            'value' => $sign,
            'desc' => '签到送积分',
            'is_use' => 1
        );
        $array[2] = array(
            'instance_id' => $shop_id,
            'key' => 'SHARE_INTEGRAL',
            'value' => $share,
            'desc' => '分享送积分',
            'is_use' => 1
        );
        $res = $this->setConfig($array);
        return $res;
    }


    /*
    * 得到积分奖励配置
    */
   public function getIntegralConfig($shop_id)
   {
       $register_integral = $this->getConfig($shop_id, 'REGISTER_INTEGRAL');
       $sign_integral = $this->getConfig($shop_id, 'SIGN_INTEGRAL');
       $share_integral = $this->getConfig($shop_id, 'SHARE_INTEGRAL');
       if (empty($register_integral) || empty($sign_integral) || empty($share_integral)) {
           $this->SetIntegralConfig($shop_id, '', '', '');
           $array = array(
               'register_integral' => '',
               'sign_integral' => '',
               'share_integral' => ''
           );
       } else {
           $array = array(
               'register_integral' => $register_integral['value'],
               'sign_integral' => $sign_integral['value'],
               'share_integral' => $share_integral['value']
           );
       }
       return $array;
   }

   /**
    * ok-2ok
    * 修改状态
    * (non-PHPdoc)
    */
    public function updateConfigEnable($id, $is_use)
    {
        $config_model = new ConfigModel();
        $data = array(
            "is_use" => $is_use,
            "update_time" => time()
        );
        $retval = $config_model->save($data, [
            "id" => $id
        ]);
        if (empty($retval)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 获取注册与访问的设置
     */
    /*
    public function getRegisterAndVisit($shop_id)
    {
        $register_and_visit = $this->getConfig($shop_id, 'REGISTERANDVISIT');
        if (empty($register_and_visit) || $register_and_visit == null) {
            // 按照默认值显示生成
            $value_array = array(
                'is_register' => "1",
                'register_info' => "plain",
                'name_keyword' => "",
                'pwd_len' => "5",
                'pwd_complexity' => "",
                'terms_of_service' => "",
                'is_requiretel' => 0
            );

            $data = array(
                'instance_id' => $shop_id,
                'key' => 'REGISTERANDVISIT',
                'value' => json_encode($value_array),
                'create_time' => time(),
                'is_use' => "1"
            );

            $config_model = new ConfigModel();
            $res = $config_model->save($data);
            if ($res > 0) {
                $register_and_visit = $this->getConfig($shop_id, 'REGISTERANDVISIT');
            }
        }
        return $register_and_visit;
    }
*/
    /**
     * 设置注册与访问的设置
     */
    /*
    public function setRegisterAndVisit($shop_id, $is_register, $register_info, $name_keyword, $pwd_len, $pwd_complexity, $terms_of_service, $is_requiretel, $is_use)
    {
        $value_array = array(
            'is_register' => $is_register,
            'register_info' => $register_info,
            'name_keyword' => $name_keyword,
            'pwd_len' => $pwd_len,
            'pwd_complexity' => $pwd_complexity,
            'is_requiretel' => $is_requiretel,
            'terms_of_service' => $terms_of_service
        );

        $data = array(
            'value' => json_encode($value_array),
            'modify_time' => time(),
            'is_use' => $is_use
        );

        $config_model = new ConfigModel();
        $res = $config_model->save($data, [
            'key' => 'REGISTERANDVISIT',
            'instance_id' => $shop_id
        ]);
        return $res;
    }
*/
    /**
     * 数据库表信息列表
     */
    public function getDatabaseList()
    {
        $databaseList = Db::query("SHOW TABLE STATUS");
        return $databaseList;
    }

    /**
     * ok-2ok
     * 查询物流跟踪的配置信息
     *
     * @param  $shop_id
     */

    public function getOrderExpressMessageConfig($shop_id=0)
    {
        $express_detail = $this->config_module->getInfo([
            'instance_id' => $shop_id,
            'key' => 'ORDER_EXPRESS_MESSAGE'
        ], 'value,is_use');
        if (empty($express_detail['value'])) {
            return array(
                'value' => array(
                    'appid' => '',
                    'appkey' => '',
                    'back_url' => ''
                ),
                'is_use' => 0
            );
        } else {
            $express_detail['value'] = json_decode($express_detail['value'], true);
            return $express_detail;
        }
    }


    /**
     * ok-2ok
     * 更新物流跟踪的配置信息
     *
     * @param  $shop_id
     * @param  $appid
     * @param  $appkey
     * @param  $is_use
     */
    public function updateOrderExpressMessageConfig($shop_id=0, $appid, $appkey, $back_url, $is_use=1)
    {
        $express_detail = $this->config_module->getInfo([
            'instance_id' => $shop_id,
            'key' => 'ORDER_EXPRESS_MESSAGE'
        ], 'value,is_use');
        $value = array(
            "appid" => $appid,
            "appkey" => $appkey,
            "back_url" => $back_url
        );
        $value = json_encode($value);
        $config_model = new ConfigModel();
        if (empty($express_detail)) {
            $data = array(
                "instance_id" => $shop_id,
                "key" => 'ORDER_EXPRESS_MESSAGE',
                "value" => $value,
                "create_time" => time(),
                "update_time" => time(),
                "desc" => "物流跟踪配置信息",
                "is_use" => $is_use
            );
            $res = $config_model->save($data);
            if (empty($res)) {
                return false;
            } else {
                return true;
            }
        } else {
            $data = array(
                "key" => 'ORDER_EXPRESS_MESSAGE',
                "value" => $value,
                "update_time" => time(),
                "is_use" => $is_use
            );
            $result = $config_model->save($data, [
                "instance_id" => $shop_id,
                "key" => "ORDER_EXPRESS_MESSAGE"
            ]);

            if (empty($result)) {
                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * 获取当前使用的手机模板
     */
    /*
    public function getUseWapTemplate($instanceid)
    {
        $config_model = new ConfigModel();
        $res = $config_model->getInfo([
            'key' => 'USE_WAP_TEMPLATE',
            'instance_id' => $instanceid
        ], 'value', '');
        return $res;
    }
*/
    /**
     * 设置要使用手机模板
     */
    /*
    public function setUseWapTemplate($instanceid, $folder)
    {
        $res = 0;
        $config_model = new ConfigModel();
        $info = $this->config_module->getInfo([
            'key' => 'USE_WAP_TEMPLATE',
            'instance_id' => $instanceid
        ], 'value');
        if (empty($info)) {
            $data['instance_id'] = $instanceid;
            $data['key'] = 'USE_WAP_TEMPLATE';
            $data['value'] = $folder;
            $data['create_time'] = time();
            $data['modify_time'] = time();
            $data['desc'] = '当前使用的手机端模板文件夹';
            $data['is_use'] = 1;
            $res = $config_model->save($data);
        } else {
            $data['instance_id'] = $instanceid;
            $data['value'] = $folder;
            $data['modify_time'] = time();
            $res = $config_model->save($data, [
                'key' => 'USE_WAP_TEMPLATE'
            ]);
        }
        return $res;
    }
*/
    /**
     * 获取当前使用的PC端模板
     */
    /*
    public function getUsePCTemplate($instanceid)
    {
        $user_pc_template = Cache::get("user_pc_template" . $instanceid);
        if (empty($user_pc_template)) {
            $config_model = new ConfigModel();
            $user_pc_template = $config_model->getInfo([
                'key' => 'USE_PC_TEMPLATE',
                'instance_id' => $instanceid
            ], 'value', '');
            Cache::set("user_pc_template" . $instanceid, $user_pc_template);
        }
        return $user_pc_template;
    }
*/
    /**
     * 设置要使用的PC端模板
     */
    /*
    public function setUsePCTemplate($instanceid, $folder)
    {
        Cache::set("user_pc_template" . $instanceid, '');
        $res = 0;
        $config_model = new ConfigModel();
        $info = $this->config_module->getInfo([
            'key' => 'USE_PC_TEMPLATE',
            'instance_id' => $instanceid
        ], 'value');

        $data['instance_id'] = $instanceid;
        $data['key'] = 'USE_PC_TEMPLATE';
        $data['value'] = $folder;
        $data['create_time'] = time();
        $data['modify_time'] = time();
        if (empty($info)) {
            $data['desc'] = '当前使用的PC端模板文件夹';
            $data['is_use'] = 1;
            $res = $config_model->save($data);
        } else {
            $res = $config_model->save($data, [
                'key' => 'USE_PC_TEMPLATE'
            ]);
        }
        return $res;
    }
*/
    /**
     * 自提点运费菜单配置
     *
     * @param  $is_enable
     * @param  $pickup_freight
     * @param  $manjian_freight
     */

    public function setPickupPointFreight($is_enable, $pickup_freight, $manjian_freight)
    {
        $config_value = array(
            'is_enable' => $is_enable,
            'pickup_freight' => $pickup_freight,
            'manjian_freight' => $manjian_freight
        );
        $config_key = 'PICKUPPOINT_FREIGHT';
        $config_info = $this->getConfig(0, $config_key);
        if (empty($config_info)) {
            $res = $this->addConfig(0, $config_key, json_encode($config_value), '自提点运费菜单配置', 1);
            if (empty($res)) {
                return false;
            } else {
                return true;
            }
        } else {
            $res = $this->updateConfig(0, $config_key, json_encode($config_value), '自提点运费菜单配置', 1);
            if ($res === false) {
                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * 开启关闭自定义模板
     *  店铺id $shop_id
     * 1：开启，0：禁用 $is_enable
     */
    /*
    public function setIsEnableCustomTemplate($shop_id, $is_enable)
    {
        $res = 0;
        $config_model = new ConfigModel();
        $info = $this->config_module->getInfo([
            'key' => 'WAP_CUSTOM_TEMPLATE_IS_ENABLE',
            'instance_id' => $shop_id
        ], 'value');
        if (empty($info)) {
            $data['instance_id'] = $shop_id;
            $data['key'] = 'WAP_CUSTOM_TEMPLATE_IS_ENABLE';
            $data['value'] = $is_enable;
            $data['is_use'] = 1;
            $data['create_time'] = time();
            $res = $config_model->save($data);
        } else {
            $data['instance_id'] = $shop_id;
            $data['value'] = $is_enable;
            $data['modify_time'] = time();
            $res = $config_model->save($data, [
                'key' => 'WAP_CUSTOM_TEMPLATE_IS_ENABLE'
            ]);
        }
        return $res;
    }
*/
    /**
     * 获取自定义模板是否启用，0 不启用 1 启用
     */
    /*
    public function getIsEnableCustomTemplate($shop_id)
    {
        $is_enable = 0;
        $config_model = new ConfigModel();
        $value = $config_model->getInfo([
            'key' => 'WAP_CUSTOM_TEMPLATE_IS_ENABLE',
            'instance_id' => $shop_id
        ], 'value');
        if (! empty($value)) {
            $is_enable = $value["value"];
        }
        return $is_enable;
    }
*/
    /**
     * 获取上传方式
     */
    /*
    public function getUploadType($shop_id)
    {

        $upload_type = $this->config_module->getInfo([
            "key" => "UPLOAD_TYPE",
            "instance_id" => $shop_id
        ], "*");
        if (empty($upload_type)) {
            $res = $this->addConfig($shop_id, "UPLOAD_TYPE", 1, "上传方式 1 本地  2 七牛", 1);
            return 1;
        } else {
            return $upload_type['value'];
        }
    }
*/
    /**
     * 获取七牛参数配置
     */
    /*
    public function getQiniuConfig($shop_id)
    {
        // TODO Auto-generated method stub
        $qiniu_info = $this->config_module->getInfo([
            "key" => "QINIU_CONFIG",
            "instance_id" => $shop_id
        ], "*");
        if (empty($qiniu_info)) {
            $data = array(
                "Accesskey" => "",
                "Secretkey" => "",
                "Bucket" => "",
                "QiniuUrl" => ""
            );
            $res = $this->addConfig($shop_id, "QINIU_CONFIG", json_encode($data), "七牛云存储参数配置", 1);
            if (! $res > 0) {
                return null;
            } else {
                $qiniu_info = $this->config_module->getInfo([
                    "key" => "QINIU_CONFIG",
                    "instance_id" => $shop_id
                ], "*");
            }
        }
        $value_info = $qiniu_info["value"];
        $value = json_decode($qiniu_info["value"], true);
        return $value;
    }
*/
    /**
     * 修改上传类型
     */
    /*
    public function setUploadType($shop_id, $value)
    {
        $upload_info = $this->config_module->getInfo([
            "key" => "UPLOAD_TYPE",
            "instance_id" => $shop_id
        ], "*");
        if (! empty($upload_info)) {
            $data = array(
                "value" => $value
            );
            $res = $this->config_module->save($data, [
                "instance_id" => $shop_id,
                "key" => "UPLOAD_TYPE"
            ]);
        } else {
            $res = $this->addConfig($shop_id, "UPLOAD_TYPE", $value, "上传方式 1 本地  2 七牛", 1);
        }

        return $res;
    }
*/
    /*
    public function setQiniuConfig($shop_id, $value)
    {
        $qiniu_info = $this->config_module->getInfo([
            "key" => "QINIU_CONFIG",
            "instance_id" => $shop_id
        ], "*");
        if (empty($qiniu_info)) {
            $data = array(
                "Accesskey" => "",
                "Secretkey" => "",
                "Bucket" => "",
                "QiniuUrl" => ""
            );
            $res = $this->addConfig($shop_id, "QINIU_CONFIG", json_encode($data), "七牛云存储参数配置", 1);
        } else {
            $data = array(
                "value" => $value
            );
            $res = $this->config_module->save($data, [
                "key" => "QINIU_CONFIG",
                "instance_id" => $shop_id
            ]);
        }
        return $res;
    }
    */

    /*
     * ok-2ok
     * 图片上传设置
    */
    public function setPictureUploadSetting($shop_id=0, $value)
    {
        $info = $this->config_module->getInfo([
            "key" => "IMG_THUMB",
            "instance_id" => $shop_id
        ], "*");
        if (! empty($info)) {
            $data = array(
                "value" => $value,
                'update_time'=>time()
            );
            $res = $this->config_module->save($data, [
                "instance_id" => $shop_id,
                "key" => "IMG_THUMB"
            ]);
        } else {
            $res = $this->addConfig($shop_id, "IMG_THUMB", $value, "图片生成参数配置  thumb_type(缩略)  3 居中裁剪 2 缩放后填充 4 左上角裁剪 5 右下角裁剪 6 固定尺寸缩放 ", 1);
        }

        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * ok-2ok
     * 得到图片上传配置
     * @param $shop_id
     * @return mixed|null
     */
    public function getPictureUploadSetting($shop_id=0)
    {
        $info = $this->config_module->getInfo([
            "key" => "IMG_THUMB",
            "instance_id" => $shop_id
        ], "*");
        if (empty($info)) {
            $data = array(
                "thumb_type" => "2",
                "upload_size" => "0",
                "upload_ext" => "gif,jpg,jpeg,bmp,png"
            );
            $res = $this->addConfig($shop_id, "IMG_THUMB", json_encode($data), "thumb_type(缩略)  3 居中裁剪 2 缩放后填充 4 左上角裁剪 5 右下角裁剪 6 固定尺寸缩放" , 1);

           if (! $res > 0) {
                return null;
            } else {
                $info = $this->config_module->getInfo([
                    "key" => "IMG_THUMB",
                    "instance_id" => $shop_id
                ], "*");
            }
        }
     //   $value_info = $info["value"];
        $value = json_decode($info["value"], true);
        return $value;
    }

    /**
     * ok-2ok
     * 检测支付配置是否开启，支付配置和原路退款配置都要开启才行（配置信息也要填写）
     */
    public function checkPayConfigEnabled($shop_id=0, $type)
    {
        $msg = ""; // 支付配置是否开启,1 开启，0 未开启（条件是各个配置项都不能为空，并且是启用状态）
      //  $admin_main = ThinkPHPConfig::get('view_replace_str.ADMIN_MAIN');
        $original_road_refund_info = $this->getOriginalRoadRefundSetting($shop_id, $type);

        if (! empty($original_road_refund_info['value'])) {

            $refund_setting = json_decode($original_road_refund_info['value'], true);
            if ($type == "alipay") {

                $pay_info = $this->getAlipayConfig($shop_id);

                if (empty($pay_info) || empty($pay_info['value']['ali_partnerid']) || empty($pay_info['value']['ali_seller']) || empty($pay_info['value']['ali_key'])) {
                   // $msg = "<p>请检查支付宝支付配置信息填写是否正确(<a href='" . __URL($admin_main . "/config/payaliconfig") . "' target='_blank'>点击此处进行配置</a>)</p>";
                    $msg = "<p>请检查支付宝支付配置信息填写是否正确</p>";
                    $this->error = $msg;
                    return false;
                   // return $msg;
                }

                if ($pay_info['is_use'] == 0) {
                   // $msg = "<p>当前未开启支付宝支付配置(<a href='" . __URL($admin_main . "/config/payaliconfig") . "' target='_blank'>点击此处去开启</a>)</p>";
                    $msg = "<p>当前未开启支付宝支付配置</p>";
                    $this->error = $msg;
                    return false;
                    //return $msg;
                } else {

                    // 支付配置开启后，再判断原路退款配置是否开启、填写了各项值
                    if ($refund_setting['is_use'] == 0) {
                        //$msg = "<p>当前未开启支付宝原路退款配置(<a href='" . __URL($admin_main . "/config/originalroadrefundsetting?type=alipay") . "' target='_blank'>点击此处去开启</a>)</p>";
                        $msg = "<p>当前未开启支付宝原路退款配置</p>";
                        $this->error = $msg;
                        return false;
                       // return $msg;
                    }
                }
            } elseif ($type == "wechat") {

                $pay_info = $this->getWpayConfig($shop_id);
                if (empty($pay_info) || empty($pay_info['value']['appid']) || empty($pay_info['value']['appsecret']) || empty($pay_info['value']['mch_id']) || empty($pay_info['value']['mch_key'])) {
                  //  $msg = "<p>请检查微信支付配置信息填写是否正确(<a href='" . __URL($admin_main . "/config/payconfig?type=wchat") . "' target='_blank'>点击此处进行配置</a>)</p>";
                    $msg = "<p>请检查微信支付配置信息填写是否正确</p>";
                    $this->error = $msg;
                    return false;
                   // return $msg;
                }

                if ($pay_info['is_use'] == 0) {
                   // $msg = "<p>当前未开启微信支付配置(<a href='" . __URL($admin_main . "/config/payconfig?type=wchat") . "' target='_blank'>点击此处去开启</a>)</p>";
                    $msg = "<p>当前未开启微信支付配置</p>";
                    $this->error = $msg;
                    return false;
                   // return $msg;
                } else {

                    if (empty($refund_setting['apiclient_cert']) || empty($refund_setting['apiclient_key'])) {
                       // $msg = "<p>请检查微信原路退款配置信息填写是否正确(<a href='" . __URL($admin_main . "/config/originalroadrefundsetting") . "' target='_blank'>点击此处进行配置</a>)</p>";
                        $msg = "<p>请检查微信原路退款配置信息填写是否正确</p>";
                        $this->error = $msg;
                        return false;
                       // return $msg;
                    }
                    if ($refund_setting['is_use'] == 0) {
                       // $msg = "<p>当前未开启微信原路退款配置(<a href='" . __URL($admin_main . "/config/originalroadrefundsetting") . "' target='_blank'>点击此处去开启</a>)</p>";
                        $msg = "<p>当前未开启微信原路退款配置</p>";
                        $this->error = $msg;
                        return false;
                       // return $msg;
                    }
                }
            }
        } else {
            if ($type == "alipay") {
                //$msg = "<p>当前未开启支付宝原路退款配置(<a href='" . __URL($admin_main . "/config/originalroadrefundsetting?type=alipay") . "' target='_blank'>点击此处进行配置</a>)</p>";
                $msg = "<p>当前未开启支付宝原路退款配置</p>";
                $this->error = $msg;
                return false;
            } elseif ($type == "wechat") {
               // $msg = "<p>请检查微信原路退款配置信息填写是否正确(<a href='" . __URL($admin_main . "/config/originalroadrefundsetting") . "' target='_blank'>点击此处进行配置</a>)</p>";
                $msg = "<p>请检查微信原路退款配置信息填写是否正确</p>";
                $this->error = $msg;
                return false;
            }
        }
       // return $msg;
        return true;
    }
}