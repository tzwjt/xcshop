<?php

use \dao\extend\QRcode as QRcode;
use think\Config;
use think\Hook;
use think\Request;
use think\response\Redirect;
use dao\extend\email\Email;
use think\Log;
\think\Loader::addNamespace(['Aliyun'=> 'dao/extend/aliyunsms/api_sdk/lib','addons' => 'addons']);
//use alisms\SmsSend;

// 错误级别
// error_reporting(E_ERROR | E_WARNING | E_PARSE);
// 去除警告错误
//error_reporting(E_ALL ^ E_NOTICE);
define("PAGESIZE", Config::get('paginate.list_rows'));
define("PAGESHOW", Config::get('paginate.list_showpages'));
define("PICTURESIZE", Config::get('paginate.picture_page_size'));
define("SMS_APPKEY",  "LTAIwzf6Hl7Naj1R"); //'LTAIwzf6Hl7Naj1R'); //Config::get('LTAIwzf6Hl7Naj1R'));
define("SMS_SECRET", "cAPbkAecCI0Sd6QR7in6tafyxhF5LB"); // Config::get('LTAIwzf6Hl7Naj1R'));
define("SMS_SIGNNAME", "招招电子商务"); // Config::get('LTAIwzf6Hl7Naj1R'));


// 订单退款状态
/* define('ORDER_REFUND_STATUS', 11);
// 订单完成的状态
define('ORDER_COMPLETE_SUCCESS', 4);
define('ORDER_COMPLETE_SHUTDOWN', 5);
define('ORDER_COMPLETE_REFUND', - 2);
// 前台网站风格
define("STYLE_DEFAULT_PC", "shop/default"); // 默认模板样式
define("STYLE_BLUE_PC", "shop/blue"); // 蓝色清爽版样式

// 后台网站风格
define("STYLE_DEFAULT_ADMIN", "admin");
define("STYLE_BLUE_ADMIN", "adminblue");
*/
// 插件目录
define('ADDON_PATH', ROOT_PATH . 'addons' . DS);
/**
 * 行为绑定
 */
//  \think\Hook::add('app_init','app\\common\\behavior\\InitConfigBehavior');

/**
 * $code=0表示“操作成功”
 * $code = 1表示“身体验证失败”
 * $code = 2表示“操作失败"
 * 返回对象
 * @param $array 响应数据
 */
function resultArray($code = 0, $msg="操作成功", $data='')
{   /*
    if(isset($array['data'])) {
        $array['error'] = '';
        $code = 200;
    } elseif (isset($array['error'])) {
        $code = 400;
        $array['data'] = '';
    }
    */
    return [
        'code'  => $code,
        'msg' => $msg,
        'data'  => $data
    ];
}

/**
 * 调试方法
 * @param  array   $data  [description]
 */
function p($data,$die=1)
{
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    if ($die) die;
}

/**
 * 用户密码加密方法
 * @param  string $str      加密的字符串
 * @param  [type] $auth_key 加密符
 * @return string           加密后长度为32的字符串
 */
function user_md5($str, $auth_key = '')
{
    return '' === $str ? '' : md5(sha1($str) . $auth_key);
}

function paramParse($param, $default) {
    return isset($param)? $param : $default;
}

/**
 * cookies加密函数
 * @param string 加密后字符串
 */
function encrypt($data, $key = 'kls8in1e')
{
    $prep_code = serialize($data);
    $block = mcrypt_get_block_size('des', 'ecb');
    if (($pad = $block - (strlen($prep_code) % $block)) < $block) {
        $prep_code .= str_repeat(chr($pad), $pad);
    }
    $encrypt = mcrypt_encrypt(MCRYPT_DES, $key, $prep_code, MCRYPT_MODE_ECB);
    return base64_encode($encrypt);
}

/**
 * cookies 解密密函数
 * @param array 解密后数组
 */
function decrypt($str, $key = 'kls8in1e')
{
    $str = base64_decode($str);
    $str = mcrypt_decrypt(MCRYPT_DES, $key, $str, MCRYPT_MODE_ECB);
    $block = mcrypt_get_block_size('des', 'ecb');
    $pad = ord($str[($len = strlen($str)) - 1]);
    if ($pad && $pad < $block && preg_match('/' . chr($pad) . '{' . $pad . '}$/', $str)) {
        $str = substr($str, 0, strlen($str) - $pad);
    }
    return unserialize($str);
}

/**
 * 阿里大于新用户发送短信
 */
function aliDayunSmsSend( $send_mobile, $template_code,  $smsParam,
                          $appkey=SMS_APPKEY, $secret=SMS_SECRET, $signName=SMS_SIGNNAME)
{
    require_once 'dao/extend/aliyunsms/Api/SmsSend.php';
  //  require_once 'dao/extend/aliyunsms/SendSmsRequest.php';

    header('Content-Type: text/plain; charset=utf-8');
    $sms = new SmsSend(
        $appkey,
        $secret
    );
    $response = $sms->sendSms(
        $signName, // 短信签名
        $template_code, // 短信模板编号
        $send_mobile, // 短信接收者
        $smsParam  // 短信模板中字段的值
    );
    return $response;
    /*
    $result = json_decode(json_encode($response),TRUE);
    return $result;
    // 短信API产品名
    $product = "Dysmsapi";
    // 短信API产品域名
    $domain = "dysmsapi.aliyuncs.com";
    // 暂时不支持多Region
    $region = "cn-hangzhou";
    // 服务结点
    $endPointName = "cn-hangzhou"; //

    $profile = DefaultProfile::getProfile($region, $appkey, $secret);
    DefaultProfile::addEndpoint("cn-hangzhou", "cn-hangzhou", $product, $domain);

    // 增加服务结点
    DefaultProfile::addEndpoint($endPointName, $region, $product, $domain);

    $acsClient = new DefaultAcsClient($profile);

    $request = new SendSmsRequest();
    // 必填-短信接收号码
    $request->setPhoneNumbers($send_mobile);
    // 必填-短信签名
    $request->setSignName($signName);
    // 必填-短信模板Code
    $request->setTemplateCode($template_code);
    // 选填-假如模板中存在变量需要替换则为必填(JSON格式)
    $request->setTemplateParam($smsParam);

    // 可选，设置模板参数
    */
    /*
    if($templateParam) {
        $request->setTemplateParam(json_encode($templateParam));
    }
    */
    // 选填-发送短信流水号
    /*
    $request->setOutId("0");
    // 发起访问请求
    $acsResponse = $acsClient->getAcsResponse($request);
    return $acsResponse;
    */
}

/**
 * 阿里大于短信发送
 *
 * @param  $appkey
 * @param  $secret
 * @param  $signName
 * @param  $smsParam
 * @param  $send_mobile
 * @param  $template_code
 */
/*
function aliSmsSend($appkey, $secret, $signName, $smsParam, $send_mobile, $template_code, $sms_type = 0)
{
    if ($sms_type == 0) {
        // 旧用户发送短信
        return aliSmsSendOld($appkey, $secret, $signName, $smsParam, $send_mobile, $template_code);
    } else {
        // 新用户发送短信
        return aliSmsSendNew($appkey, $secret, $signName, $smsParam, $send_mobile, $template_code);
    }
}
*/
/**
 * 阿里大于旧用户发送短信
 *
 * @param  $appkey
 * @param  $secret
 * @param  $signName
 * @param  $smsParam
 * @param  $send_mobile
 * @param  $template_code
 * @return Ambigous <, \ResultSet, mixed>
 */
/*
function aliSmsSendOld($appkey, $secret, $signName, $smsParam, $send_mobile, $template_code)
{
    require_once 'data/extend/alisms/TopSdk.php';
    $c = new TopClient();
    $c->appkey = $appkey;
    $c->secretKey = $secret;
    $req = new AlibabaAliqinFcSmsNumSendRequest();
    $req->setExtend("");
    $req->setSmsType("normal");
    $req->setSmsFreeSignName($signName);
    $req->setSmsParam($smsParam);
    $req->setRecNum($send_mobile);
    $req->setSmsTemplateCode($template_code);
    $result = $resp = $c->execute($req);
    return $result;
}
*/

/**
 * 阿里大于新用户发送短信
 *
 * @param  $appkey
 * @param  $secret
 * @param  $signName
 * @param  $smsParam
 * @param  $send_mobile
 * @param  $template_code
 */
/*
function aliSmsSendNew($appkey, $secret, $signName, $smsParam, $send_mobile, $template_code)
{
    require_once 'data/extend/alisms_new/aliyun-php-sdk-core/Config.php';
    require_once 'data/extend/alisms_new/SendSmsRequest.php';
    // 短信API产品名
    $product = "Dysmsapi";
    // 短信API产品域名
    $domain = "dysmsapi.aliyuncs.com";
    // 暂时不支持多Region
    $region = "cn-hangzhou";
    $profile = DefaultProfile::getProfile($region, $appkey, $secret);
    DefaultProfile::addEndpoint("cn-hangzhou", "cn-hangzhou", $product, $domain);
    $acsClient = new DefaultAcsClient($profile);

    $request = new SendSmsRequest();
    // 必填-短信接收号码
    $request->setPhoneNumbers($send_mobile);
    // 必填-短信签名
    $request->setSignName($signName);
    // 必填-短信模板Code
    $request->setTemplateCode($template_code);
    // 选填-假如模板中存在变量需要替换则为必填(JSON格式)
    $request->setTemplateParam($smsParam);
    // 选填-发送短信流水号
    $request->setOutId("0");
    // 发起访问请求
    $acsResponse = $acsClient->getAcsResponse($request);
    return $acsResponse;
}

*/

/**
 * 判断当前是否是微信浏览器
 */
function isWeixin()
{
    if (strpos($_SERVER['HTTP_USER_AGENT'],

            'MicroMessenger') !== false) {

        return 1;
    }

    return 0;
}

/**
 * 检测ID是否在ID组
 *
 * @param  $id
 *            数字
 * @param  $id_arr
 *            数字,数字
 */
function checkIdIsinIdArr($id, $id_arr)
{
    $id_arr = $id_arr . ',';
    $result = strpos($id_arr, $id . ',');
    if ($result !== false) {
        return 1;
    } else {
        return 0;
    }
}

/**
 * 时间转时间戳
 *
 * @param  $time
 */
function getTimeTurnTimeStamp($time)
{
    $time_stamp = strtotime($time);
    return $time_stamp;
}

/**
 * 生成流水号
 *
 * @return string
 */
function getSerialNo()
{
    $no_base = date("ymdhis", time());
    $serial_no = $no_base . rand(111, 999);
    return $serial_no;
}

/**
 * 导出数据为excal文件
 *
 */
function dataExcel($expTitle, $expCellName, $expTableData)
{
    include 'dao/extend/phpexcel_classes/PHPExcel.php';
    $xlsTitle = iconv('utf-8', 'gb2312', $expTitle); // 文件名称
    $fileName = $expTitle . date('_YmdHis'); // or $xlsTitle 文件名称可根据自己情况设定
    $cellNum = count($expCellName);
    $dataNum = count($expTableData);
    $objPHPExcel = new \PHPExcel();
    $cellName = array(
        'A',
        'B',
        'C',
        'D',
        'E',
        'F',
        'G',
        'H',
        'I',
        'J',
        'K',
        'L',
        'M',
        'N',
        'O',
        'P',
        'Q',
        'R',
        'S',
        'T',
        'U',
        'V',
        'W',
        'X',
        'Y',
        'Z',
        'AA',
        'AB',
        'AC',
        'AD',
        'AE',
        'AF',
        'AG',
        'AH',
        'AI',
        'AJ',
        'AK',
        'AL',
        'AM',
        'AN',
        'AO',
        'AP',
        'AQ',
        'AR',
        'AS',
        'AT',
        'AU',
        'AV',
        'AW',
        'AX',
        'AY',
        'AZ'
    );
    for ($i = 0; $i < $cellNum; $i ++) {
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '2', $expCellName[$i][1]);
    }
    for ($i = 0; $i < $dataNum; $i ++) {
        for ($j = 0; $j < $cellNum; $j ++) {
            $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 3), $expTableData[$i][$expCellName[$j][0]]);
        }
    }
    $objPHPExcel->setActiveSheetIndex(0);
    header('pragma:public');
    header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
    header("Content-Disposition:attachment;filename=$fileName.xls"); // attachment新窗口打印inline本窗口打印
    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');
}

/**
 * 时间戳转时间
 */
function getTimeStampTurnTime($time_stamp)
{
    if ($time_stamp > 0) {
        $time = date('Y-m-d H:i:s', $time_stamp);
    } else {
        $time = "";
    }
    return $time;
}

/**
 * 根据 ip 获取 当前城市
 */
function get_city_by_ip($xCip = '')
{
    if (!empty($xCip)) {
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $cip = $_SERVER["HTTP_CLIENT_IP"];
        } elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } elseif (!empty($_SERVER["REMOTE_ADDR"])) {
            $cip = $_SERVER["REMOTE_ADDR"];
        } else {
            $cip = "";
        }
    } else {
        $cip = $xCip;
    }

    $url = 'http://restapi.amap.com/v3/ip';
    $data = array(
        'output' => 'json',
        'key' => '661c62da467ccab52c56daaa93147a09',  //16199cf2aca1fb54d0db495a3140b8cb',
        'ip' => $cip
    );

    $postdata = http_build_query($data);
    $opts = array(
        'http' => array(
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => $postdata
        )
    );

    $context = stream_context_create($opts);

    $result = file_get_contents($url, false, $context);
    $res = json_decode($result, true);
    if (count($res['province']) == 0) {
        $res['province'] = '北京市';
    }
    if (! empty($res['province']) && $res['province'] == "局域网") {
        $res['province'] = '北京市';
    }
    if (count($res['city']) == 0) {
        $res['city'] = '北京市';
    }
    return $res;
}


/**
 * 获取标准二维码格式
 *
 * @param unknown $url
 * @param unknown $path
 * @param unknown $ext
 */
function getQRcode($url, $path, $qrcode_name)
{
    if (! is_dir($path)) {
        $mode = intval('0777', 8);
        mkdir($path, $mode, true);
        chmod($path, $mode);
    }
    $path = $path . '/' . $qrcode_name . '.png';
    if(file_exists($path)){
        unlink($path);
    }
    QRcode::png($url, $path, '', 6, 3);
    return $path;
}

/**
 * 发送邮件
 */
function emailSend($email_host, $email_id, $email_pass, $email_port, $email_is_security, $email_addr, $toemail, $title, $content, $shopName = "")
{
    $result = false;
    try {
        $mail = new Email();
        if (! empty($shopName)) {
            $mail->_shopName = $shopName;
        } else {
            $mail->_shopName = "美肤日制SKINDAY商城";
        }
        $mail->setServer($email_host, $email_id, $email_pass, $email_port, $email_is_security);
        $mail->setFrom($email_addr);
        $mail->setReceiver($toemail);
        $mail->setMail($title, $content);
        $result = $mail->sendMail();
    } catch (\Exception $e) {
        $result = false;
    }
    return $result;
}

/***********************  2018-01-01 **************************************************/

/**
 * 阿里大于短信发送
 */
function aliSmsSend($appkey, $secret, $signName, $smsParam, $send_mobile, $template_code, $sms_type = 0)
{
    if ($sms_type == 0) {
        // 旧用户发送短信
        return aliSmsSendOld($appkey, $secret, $signName, $smsParam, $send_mobile, $template_code);
    } else {
        // 新用户发送短信
        return aliSmsSendNew($appkey, $secret, $signName, $smsParam, $send_mobile, $template_code);
    }
}

/**
 * 阿里大于旧用户发送短信
 */
function aliSmsSendOld($appkey, $secret, $signName, $smsParam, $send_mobile, $template_code)
{
    require_once 'dao/extend/alisms/TopSdk.php';
    $c = new TopClient();
    $c->appkey = $appkey;
    $c->secretKey = $secret;
    $req = new AlibabaAliqinFcSmsNumSendRequest();
    $req->setExtend("");
    $req->setSmsType("normal");
    $req->setSmsFreeSignName($signName);
    $req->setSmsParam($smsParam);
    $req->setRecNum($send_mobile);
    $req->setSmsTemplateCode($template_code);
    $result = $resp = $c->execute($req);
    return $result;
}

/**
 * 阿里大于新用户发送短信
 */
function aliSmsSendNew($appkey, $secret, $signName, $smsParam, $send_mobile, $template_code)
{
    require_once 'dao/extend/alisms_new/aliyun-php-sdk-core/Config.php';
    require_once 'dao/extend/alisms_new/SendSmsRequest.php';
    // 短信API产品名
    $product = "Dysmsapi";
    // 短信API产品域名
    $domain = "dysmsapi.aliyuncs.com";
    // 暂时不支持多Region
    $region = "cn-hangzhou";
    $profile = DefaultProfile::getProfile($region, $appkey, $secret);
    DefaultProfile::addEndpoint("cn-hangzhou", "cn-hangzhou", $product, $domain);
    $acsClient = new DefaultAcsClient($profile);

    $request = new SendSmsRequest();
    // 必填-短信接收号码
    $request->setPhoneNumbers($send_mobile);
    // 必填-短信签名
    $request->setSignName($signName);
    // 必填-短信模板Code
    $request->setTemplateCode($template_code);
    // 选填-假如模板中存在变量需要替换则为必填(JSON格式)
    $request->setTemplateParam($smsParam);
    // 选填-发送短信流水号
    $request->setOutId("0");
    // 发起访问请求
    $acsResponse = $acsClient->getAcsResponse($request);
    return $acsResponse;
}


/**
 * 执行钩子
 */
function runhook($class, $tag, $params = null)
{
    $result = array();
    try {
        $result = Hook::exec("\\dao\\extend\\hook\\" . $class, $tag, $params);
    } catch (\Exception $e) {
        $result["code"] = - 1;
        $result["message"] = "请求失败!";
    }
    return $result;
}


/**
 * 处理插件钩子
 *
 * @param string $hook
 *            钩子名称
 * @param mixed $params
 *            传入参数
 * @return void
 */
function hook($hook, $params = [])
{
    // 钩子调用
    \think\Hook::listen($hook, $params);
}

/**
 * 判断钩子是否存在
 */
function hook_is_exist($hook)
{
    $res = \think\Hook::get($hook);
    if (empty($res)) {
        return false;
    }
    return true;
}

/**
 * ok-2ok
 * 返回系统是否配置了伪静态
 *
 * @return string
 */
function rewrite_model()
{
    $rewrite_model = REWRITE_MODEL;
    if ($rewrite_model) {
        return 1;
    } else {
        return 0;
    }
}

function url_model()
{
    $url_model = 0;
    try {
        \think\Loader::addNamespace('dao', 'dao/');
        $site_handle = new \dao\handle\SiteHandle();
        $site_info = $site_handle->getSiteInfo();
        if (! empty($site_info)) {
            $url_model = isset($site_info["url_type"]) ? $site_info["url_type"] : 0;
        }
    } catch (Exception $e) {
        $url_model = 0;
    }
    return $url_model;
}

/**
 * ok-2ok
 * 获取url参数
 *
 * @param  $action
 * @param string $param
 */
function __URL($url, $param = '')
{
    //$url = \str_replace('SHOP_MAIN', '', $url);
    //$url = \str_replace('APP_MAIN', 'wap', $url);
   // $url = \str_replace('ADMIN_MAIN', ADMIN_MODULE, $url);
    // 处理后台页面
    $url = \str_replace(__URL__ . '/platform', 'platform', $url);
    $url = \str_replace(__URL__ . '/agent', 'agent', $url);
    $url = \str_replace(__URL__ . '/shop', 'shop', $url);
    $url = \str_replace(__URL__ . '/web', 'web', $url);
    $url = \str_replace(__URL__ . '/wap', 'wap', $url);
    $url = \str_replace(__URL__, '', $url);
    if (empty($url)) {
        return __URL__;
    } else {
        $str = substr($url, 0, 1);
        if ($str === '/' || $str === "\\") {
            $url = substr($url, 1, strlen($url));
        }
      //  if (REWRITE_MODEL) {

      //      $url = urlRouteConfig($url, $param);
      //      return $url;
     //   }
        $action_array = explode('?', $url);
        // 检测是否是pathinfo模式
        $url_model = url_model();
        if ($url_model) {
            $base_url = __URL__ . '/' . $action_array[0];
            $tag = '?';
        } else {
            $base_url = __URL__ . '?s=/' . $action_array[0];
            $tag = '&';
        }
        if (! empty($action_array[1])) {
            // 有参数
            return $base_url . $tag . $action_array[1];
        } else {
            if (! empty($param)) {
                return $base_url . $tag . $param;
            } else {
                return $base_url;
            }
        }
    }
}

/**
 * 过滤特殊字符(微信qq)
 *
 * @param  $str
 */
function filterStr($str)
{
    if ($str) {
        $name = $str;
        $name = preg_replace_callback('/\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]/', function ($matches) {
            return '';
        }, $name);
        $name = preg_replace_callback('/xE0[x80-x9F][x80-xBF]‘.‘|xED[xA0-xBF][x80-xBF]/S', function ($matches) {
            return '';
        }, $name);
        // 汉字不编码
        $name = json_encode($name);
        $name = preg_replace_callback("/\\\ud[0-9a-f]{3}/i", function ($matches) {
            return '';
        }, $name);
        if (! empty($name)) {
            $name = json_decode($name);
            return $name;
        } else {
            return '';
        }
    } else {
        return '';
    }
}

/**
 * ok-2ok
 * 制作推广二维码
 * @param $upload_path
 * @param $path
 * @param $thumb_qrcode
 * @param $user_headimg
 * @param $shop_logo
 * @param $user_name
 * @param $data
 * @param $create_path
 */
function showUserQecode($upload_path, $path, $thumb_qrcode, $user_headimg, $shop_logo, $user_name, $data, $create_path)
{
    // 暂无法生成
    if (! strstr($path, "http://") && ! strstr($path, "https://")) {
        if (! file_exists($path)) {
            $path = "public/static/images/template_qrcode.png";
        }
    }

    if (! file_exists($upload_path)) {
        $mode = intval('0777', 8);
        mkdir($upload_path, $mode, true);
    }

    // 定义中继二维码地址

    $image = \think\Image::open($path);
    // 生成一个固定大小为360*360的缩略图并保存为thumb_....jpg
    $image->thumb(288, 288, \think\Image::THUMB_CENTER)->save($thumb_qrcode);
    // 背景图片
    $dst = $data["background"];

    if (! strstr($dst, "http://") && ! strstr($dst, "https://")) {
        if (! file_exists($dst)) {
            $dst = "public/static/images/qrcode_bg/shop_qrcode_bg.png";
        }
    }

    // $dst = "http://pic107.nipic.com/file/20160819/22733065_150621981000_2.jpg";
    // 生成画布
    list ($max_width, $max_height) = getimagesize($dst);
    // $dests = imagecreatetruecolor($max_width, $max_height);
    $dests = imagecreatetruecolor(640, 1134);
    $dst_im = getImgCreateFrom($dst);
    imagecopy($dests, $dst_im, 0, 0, 0, 0, $max_width, $max_height);
    // ($dests, $dst_im, 0, 0, 0, 0, 640, 1134, $max_width, $max_height);
    imagedestroy($dst_im);
    // 并入二维码
    // $src_im = imagecreatefrompng($thumb_qrcode);
    $src_im = getImgCreateFrom($thumb_qrcode);
    $src_info = getimagesize($thumb_qrcode);
    // imagecopy($dests, $src_im, $data["code_left"] * 2, $data["code_top"] * 2, 0, 0, $src_info[0], $src_info[1]);
    imagecopy($dests, $src_im, $data["code_left"] * 2, $data["code_top"] * 2, 0, 0, $src_info[0], $src_info[1]);
    imagedestroy($src_im);
    // 并入用户头像

    if (! strstr($user_headimg, "http://") && ! strstr($user_headimg, "https://")) {
        if (! file_exists($user_headimg)) {
            $user_headimg = "public/static/images/qrcode_bg/head_img.png";
        }
    }
    $src_im_1 = getImgCreateFrom($user_headimg);
    $src_info_1 = getimagesize($user_headimg);
    // imagecopy($dests, $src_im_1, $data['header_left'] * 2, $data['header_top'] * 2, 0, 0, $src_info_1[0], $src_info_1[1]);
    // imagecopy($dests, $src_im_1, $data['header_left'] * 2, $data['header_top'] * 2, 0, 0, $src_info_1[0], $src_info_1[1]);
    imagecopyresampled($dests, $src_im_1, $data['header_left'] * 2, $data['header_top'] * 2, 0, 0, 80, 80, $src_info_1[0], $src_info_1[1]);
    imagedestroy($src_im_1);

    // 并入网站logo
    if ($data['is_logo_show'] == '1') {
        if (! strstr($shop_logo, "http://") && ! strstr($shop_logo, "https://")) {
            if (! file_exists($shop_logo)) {
                $shop_logo = "public/static/images/logo.png";
            }
        }
        $src_im_2 = getImgCreateFrom($shop_logo);
        $src_info_2 = getimagesize($shop_logo);
        // imagecopy($dests, $src_im_2, $data['logo_left'] * 2, $data['logo_top'] * 2, 0, 0, $src_info_2[0], $src_info_2[1]);
        imagecopyresampled($dests, $src_im_2, $data['logo_left'] * 2, $data['logo_top'] * 2, 0, 0, 200, 80, $src_info_2[0], $src_info_2[1]);
        imagedestroy($src_im_2);
    }
    // 并入用户姓名
    if ($user_name == "") {
        $user_name = "用户";
    }
    $rgb = hColor2RGB($data['nick_font_color']);
    $bg = imagecolorallocate($dests, $rgb['r'], $rgb['g'], $rgb['b']);
    $name_top_size = $data['name_top'] * 2 + $data['nick_font_size'];
    @imagefttext($dests, $data['nick_font_size'], 0, $data['name_left'] * 2, $name_top_size, $bg, "public/static/font/Microsoft.ttf", $user_name);
    header("Content-type: image/jpeg");
    if ($create_path == "") {
        imagejpeg($dests);
    } else {
        imagejpeg($dests, $create_path);
    }
}

/**
 * ok-2ok
 * 分类获取图片对象
 * @param $img_path
 * @return resource|string
 */
function getImgCreateFrom($img_path)
{
    $ename = getimagesize($img_path);
    $ename = explode('/', $ename['mime']);
    $ext = $ename[1];
    $image = '';
    switch ($ext) {
        case "png":

            $image = imagecreatefrompng($img_path);
            break;
        case "jpeg":

            $image = imagecreatefromjpeg($img_path);
            break;
        case "jpg":

            $image = imagecreatefromjpeg($img_path);
            break;
        case "gif":

            $image = imagecreatefromgif($img_path);
            break;
    }
    return $image;
}

/**
 * ok-2ok
 * 颜色十六进制转化为rgb
 */
function hColor2RGB($hexColor)
{
    $color = str_replace('#', '', $hexColor);
    if (strlen($color) > 3) {
        $rgb = array(
            'r' => hexdec(substr($color, 0, 2)),
            'g' => hexdec(substr($color, 2, 2)),
            'b' => hexdec(substr($color, 4, 2))
        );
    } else {
        $color = str_replace('#', '', $hexColor);
        $r = substr($color, 0, 1) . substr($color, 0, 1);
        $g = substr($color, 1, 1) . substr($color, 1, 1);
        $b = substr($color, 2, 1) . substr($color, 2, 1);
        $rgb = array(
            'r' => hexdec($r),
            'g' => hexdec($g),
            'b' => hexdec($b)
        );
    }
    return $rgb;
}

/**
 * 获取插件类的类名
 *
 * @param $name 插件名
 * @param string $type
 *            返回命名空间类型
 * @param string $class
 *            当前类名
 * @return string
 */
function get_addon_class($name, $type = '', $class = null)
{
    $name = \think\Loader::parseName($name);
    if ($type == '' && $class == null) {
        $dir = ADDON_PATH . $name . '/core';
        if (is_dir($dir)) {
            // 目录存在
            $type = 'addons_index';
        } else {
            $type = 'addon_index';
        }
    }
    $class = \think\Loader::parseName(is_null($class) ? $name : $class, 1);
    switch ($type) {
        // 单独的插件addon 入口文件
        case 'addon_index':
            $namespace = "\\addons\\" . $name . "\\" . $class;
            break;
        // 单独的插件addon 控制器
        case 'addon_controller':
            $namespace = "\\addons\\" . $name . "\\controller\\" . $class;
            break;
        // 有下级插件的插件addons 入口文件
        case 'addons_index':
            $namespace = "\\addons\\" . $name . "\\core\\" . $class;
            break;
        // 有下级插件的插件addons 控制器
        case 'addons_controller':
            $namespace = "\\addons\\" . $name . "\\core\\controller\\" . $class;
            break;
        // 插件类型下的下级插件plugin
        default:
            $namespace = "\\addons\\" . $name . "\\" . $type . "\\controller\\" . $class;
    }
//   Log::write('namespace:'.$namespace);
    return $namespace;
}

/**
 * ok-2ok
 * 插件显示内容里生成访问插件的url
 * @param $url
 * @param array $param
 * @return string
 */
function addons_url($url, $param = [])
{
    $url = parse_url($url);
    $case = config('url_convert');
    $addons = $case ? \think\Loader::parseName($url['scheme']) : $url['scheme'];
    $controller = $case ? \think\Loader::parseName($url['host']) : $url['host'];
    $action = trim($case ? strtolower($url['path']) : $url['path'], '/');
    /* 解析URL带的参数 */
    if (isset($url['query'])) {
        parse_str($url['query'], $query);
        $param = array_merge($query, $param);
    }
    if (strpos($action, '/') !== false) {
        // 有插件类型 插件类型://插件名/控制器名/方法名
        $controller_action = explode('/', $action);
        $params = array(
            'addons_type' => $addons,
            'addons' => $controller,
            'controller' => $controller_action[0],
            'action' => $controller_action[1]
        );
    } else {
        // 没有插件类型 插件名://控制器名/方法名
        $params = array(
            'addons' => $addons,
            'controller' => $controller,
            'action' => $action
        );
    }
    /* 基础参数 */
    $params = array_merge($params, $param); // 添加额外参数
    $return_url = url("shop/addons/execute", $params, '', true);
    return $return_url;
}





