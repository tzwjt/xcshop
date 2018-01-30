<?php
/**
 * 文件上传
 * @date : 2017.08.07
 * @version : v1.0
 */
namespace app\platform\controller;

use think\Controller;
use dao\handle\AlbumHandle;

use app\platform\controller\BaseController;


/**
 * 图片上传控制器
 * goods（文件夹存放商品）
 * goods_id（每个商品的）
 * images（商品主图）
 * sku_img（sku图片）
 *
 * common文件夹（存放公共图）
 *
 * advertising文件夹（存放广告位图）
 *
 * avator文件夹（用户头像）
 *
 * pay文件夹（支付生成的图）
 *
 *
 */
use think\Config;
// 存放商品图片、主图、sku
define("UPLOAD_GOODS", Config::get('view_replace_str.UPLOAD_GOODS'));
// 存放商品图片、主图、sku
define("UPLOAD_GOODS_SKU", Config::get('view_replace_str.UPLOAD_GOODS_SKU'));

// 存放商品品牌图
define("UPLOAD_GOODS_BRAND", Config::get('view_replace_str.UPLOAD_GOODS_BRAND'));

// 存放商品分组图片
define("UPLOAD_GOODS_GROUP", Config::get('view_replace_str.UPLOAD_GOODS_GROUP'));

// 存放商品分类图片
define("UPLOAD_GOODS_CATEGORY", Config::get('view_replace_str.UPLOAD_GOODS_CATEGORY'));

// 存放公共图片、网站logo、独立图片、没有任何关联的图片
define("UPLOAD_COMMON", Config::get('view_replace_str.UPLOAD_COMMON'));

// 存放用户头像
define("UPLOAD_AVATOR", Config::get('view_replace_str.UPLOAD_AVATOR'));

// 存放支付生成的二维码图片
define("UPLOAD_PAY", Config::get('view_replace_str.UPLOAD_PAY'));

// 存放广告位图片
define("UPLOAD_ADV", Config::get('view_replace_str.UPLOAD_ADV'));

// 存放物流图片
define("UPLOAD_EXPRESS", Config::get('view_replace_str.UPLOAD_EXPRESS'));

//存放文章图片
define("UPLOAD_CMS", Config::get('view_replace_str.UPLOAD_CMS'));

// 存放代理商图片
define("UPLOAD_AGENT", Config::get('view_replace_str.UPLOAD_AGENT'));
// 存放代理商体验店图片
define("UPLOAD_AGENT_SHOP", Config::get('view_replace_str.UPLOAD_AGENT_SHOP'));

// 存放商城站点图片
define("UPLOAD_SHOP_SITE", Config::get('view_replace_str.UPLOAD_SHOP_SITE'));

// 存放视频文件
define("UPLOAD_VIDEO", Config::get('view_replace_str.UPLOAD_VIDEO'));
class FileUpload extends BaseController
{
    private $return = array();
    
    // 文件路径
    private $file_path = "";
    
    // 重新设置的文件路径
    private $reset_file_path = "";
    
    // 文件名称
    private $file_name = "";
    
    // 文件大小
    private $file_size = 0;
    
    // 文件类型
    private $file_type = "";

    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * 功能说明：文件(图片)上传(存入相册)
     */
    public function uploadFile()
    {
        $this->file_path = request()->post("file_path", "");
        if ($this->file_path == "") {
            $this->return['message'] = "文件路径不能为空";
            return $this->ajaxFileReturn();
        }
        // 重新设置文件路径
        $this->resetFilePath();
        // 检测文件夹是否存在，不存在则创建文件夹
        if (! file_exists($this->reset_file_path)) {
            $mode = intval('0777',8);
            mkdir($this->reset_file_path, $mode, true);
        }
        
        $this->file_name = $_FILES["file_upload"]["name"]; // 文件原名
        $this->file_size = $_FILES["file_upload"]["size"]; // 文件大小
        $this->file_type = $_FILES["file_upload"]["type"]; // 文件类型
        
        if ($this->file_size == 0) {
            $this->return['message'] = "文件大小为0MB,上传失败";
           return $this->ajaxFileReturn();
        }
        /**
        if ($this->file_size > 5000000) {
            $this->return['message'] = "文件大小不能超过5MB";
            return $this->ajaxFileReturn();
        }
         * **/
        
        // 验证文件
        if (! $this->validationFile()) {
            return $this->ajaxFileReturn();
        }
        $guid = time();
        $file_name_explode = explode(".", $this->file_name); // 图片名称
        $suffix = count($file_name_explode) - 1;
        $ext = "." . $file_name_explode[$suffix]; // 获取后缀名
        $newfile = $guid . $ext; // 重新命名文件
        
        $ok = move_uploaded_file($_FILES["file_upload"]["tmp_name"], $this->reset_file_path . $newfile);
        if ($ok) {
            // 文件上传成功执行下边的操作
            if (! strstr(UPLOAD_VIDEO, $this->reset_file_path)) {
                @unlink($_FILES['file_upload']);
                $image_size = getimagesize($this->reset_file_path . $newfile); // 获取图片尺寸
                if ($image_size) {

                    $width = $image_size[0];
                    $height = $image_size[1];
                    $name = $file_name_explode[0];

                    switch ($this->file_path) {
                        case UPLOAD_GOODS:
                            // 商品图
                            $type = request()->post("type", "");
                            $pic_name = request()->post("pic_name", $guid);
                            $album_id = request()->post("album_id", 0);
                            $pic_tag = request()->post("pic_tag", $name);
                            $pic_id = request()->post("pic_id", "");
                            $upload_flag = request()->post("upload_flag", "");
                            // 上传到相册管理，生成四张大小不一的图
                            $retval = $this->photoCreate($this->reset_file_path, $this->reset_file_path . $newfile, "." . $file_name_explode[$suffix], $type, $pic_name, $album_id, $width, $height, $pic_tag, $pic_id);
                            if ($retval > 0) {
                                $this->return['code'] = 0; // $retval;
                                $this->return['message'] = "上传成功";
                                $this->return['data'] = $this->reset_file_path . $newfile;
                            } else {
                                $this->return['message'] = "上传失败";
                            }
                            break;
                        case UPLOAD_GOODS_SKU:
                            // 商品SKU图片
                            $this->return['code'] = 0; // 1;
                            $this->return['data'] = $this->reset_file_path . $newfile;
                            $this->return['message'] = "上传成功";
                            break;
                        case UPLOAD_GOODS_BRAND:
                            $this->return['code'] = 0; // 1;
                            $this->return['data'] = $this->reset_file_path . $newfile;
                            $this->return['message'] = "上传成功";
                            // 商品品牌
                            break;
                        case UPLOAD_GOODS_GROUP:
                            // 商品分组
                            $this->return['code'] = 0; // 1;
                            $this->return['data'] = $this->reset_file_path . $newfile;
                            $this->return['message'] = "上传成功";
                            break;
                        case UPLOAD_GOODS_CATEGORY:
                            // 商品分类
                            $this->return['code'] = 0; // 1;
                            $this->return['data'] = $this->reset_file_path . $newfile;
                            $this->return['message'] = "上传成功";
                            break;
                        case UPLOAD_COMMON:
                            // 公共
                            $this->return['code'] = 0; //1;
                            $this->return['data'] = $this->reset_file_path . $newfile;
                            $this->return['message'] = "上传成功";
                            break;
                        case UPLOAD_AVATOR:
                            // 用户头像
                            $this->return['code'] = 0; //1;
                            $this->return['data'] = $this->reset_file_path . $newfile;
                            $this->return['message'] = "上传成功";
                            break;
                        case UPLOAD_PAY:
                            // 支付
                            $this->return['code'] = 0; //1;
                            $this->return['data'] = $this->reset_file_path . $newfile;
                            $this->return['message'] = "上传成功";
                            break;
                        case UPLOAD_ADV:
                            // 广告位
                            $this->return['code'] = 0; //1;
                            $this->return['data'] = $this->reset_file_path . $newfile;
                            $this->return['message'] = "上传成功";
                            break;
                        case UPLOAD_EXPRESS:
                            // 物流
                            $this->return['code'] = 0; //1;
                            $this->return['data'] = $this->reset_file_path . $newfile;
                            $this->return['message'] = "上传成功";
                            break;
                        case UPLOAD_CMS:
                            // 文章
                            $this->return['code'] = 0; //1;
                            $this->return['data'] = $this->reset_file_path . $newfile;
                            $this->return['message'] = "上传成功";
                            break;
                        case UPLOAD_AGENT:
                            // 代理商图片
                            $this->return['code'] = 0; // 1;
                            $this->return['data'] = $this->reset_file_path . $newfile;
                            $this->return['message'] = "上传成功";
                            break;
                        case UPLOAD_AGENT_SHOP:
                            // 代理商图片
                            $this->return['code'] = 0; // 1;
                            $this->return['data'] = $this->reset_file_path . $newfile;
                            $this->return['message'] = "上传成功";
                            break;
                        case UPLOAD_SHOP_SITE:
                            // 商城站点图片
                            $this->return['code'] = 0; // 1;
                            $this->return['data'] = $this->reset_file_path . $newfile;
                            $this->return['message'] = "上传成功";
                            break;
                    }
                } else {

                    // 强制将文件后缀改掉，文件流不同会导致上传文件失败
                    $this->return['message'] = "请检查您的上传参数配置或上传的文件是否有误";

                }
            } else {
                switch ($this->file_path) {
                    case UPLOAD_VIDEO:
                        // 视频文件
                        $this->return['code'] = 0; // 1;
                        $this->return['data'] = $this->reset_file_path . $newfile; //$ok["path"];
                        $this->return['message'] = "视频上传成功";
                        break;
                }
            }
        } else {
            // 强制将文件后缀改掉，文件流不同会导致上传文件失败
            $this->return['message'] = "请检查您的上传参数配置或上传的文件是否有误";
        }
        return $this->ajaxFileReturn();
    }
    
    public function resetFilePath()
    {
        $file_path = "";
        switch ($this->file_path) {
            case UPLOAD_GOODS:
                $file_path = $this->file_path;
                break;
            case UPLOAD_GOODS_SKU:
                $file_path = $this->file_path . request()->post("goods_path", "") . "/";
                break;
            case UPLOAD_GOODS_BRAND:
                $file_path = $this->file_path;
                // 商品品牌
                break;
            case UPLOAD_GOODS_GROUP:
                // 商品分组
                $file_path = $this->file_path;
                break;
            case UPLOAD_GOODS_CATEGORY:
                // 商品分类
                $file_path = $this->file_path;
                break;
            case UPLOAD_COMMON:
                $file_path = $this->file_path;
                // 公共
                break;
            case UPLOAD_AVATOR:
                $file_path = $this->file_path;
                // 用户头像
                break;
            case UPLOAD_PAY:
                $file_path = $this->file_path;
                // 支付
                break;
            case UPLOAD_ADV:
                $file_path = $this->file_path;
                // 广告位
                break;
            case UPLOAD_EXPRESS:
                // 物流
                $file_path = $this->file_path;
                break;
            case UPLOAD_CMS:
                // 文章
                $file_path = $this->file_path;
                break;
            case UPLOAD_AGENT:
                // 代理商
                $file_path = $this->file_path;
                break;
            case UPLOAD_AGENT_SHOP:
                // 代理商
                $file_path = $this->file_path;
                break;

            case UPLOAD_SHOP_SITE:
                // 商城站点
                $file_path = $this->file_path;
                break;
            case UPLOAD_VIDEO:
                // 视频
                $file_path = $this->file_path;
                break;

        }
        $this->reset_file_path = $file_path;
    }
    
    /**
     * 上传文件后，ajax返回信息
     *
     *
     * @param array $return
     */
    private function ajaxFileReturn()
    {
        if (!isset($this->return['code'])) {
            $this->return['code'] = 2; // 错误码
        }
        if ( null === $this->return['code'] || "" === $this->return['code']) {
            $this->return['code'] = 2; // 错误码
        }
        
        if (empty($this->return['message']) || null == $this->return['message'] || "" == $this->return['message']) {
            $this->return['message'] = ""; // 消息
        }
        
        if (empty($this->return['data']) || null == $this->return['data'] || "" == $this->return['data']) {
            $this->return['data'] = ""; // 数据
        }
        
        return json(resultArray($this->return['code'] , $this->return['message'],  $this->return['data']));
       // return json_encode($this->return);
    }
    
    
    /**
     *
     * @param  $this->file_path
     *            文件路径
     * @param  $this->file_size
     *            文件大小
     * @param  $this->file_type
     *            文件类型
     * @return string||number|\think\false
     */
    private function validationFile()
    {
        $flag = true;
        switch ($this->file_path) {
            case UPLOAD_GOODS:
                // 商品图片
                if ($this->file_type != "image/gif" && $this->file_type != "image/png" && $this->file_type != "image/jpeg" && $this->file_size > 3000000) {
                    $this->return['message'] = '文件上传失败,请检查您上传的文件类型,文件大小不能超过3MB';
                    $flag = false;
                }
                break;
            case UPLOAD_GOODS_SKU:
                // 商品SKU图片
                if ($this->file_type != "image/gif" && $this->file_type != "image/png" && $this->file_type != "image/jpeg" && $this->file_size > 1000000) {
                    $this->return['message'] = '文件上传失败,请检查您上传的文件类型,文件大小不能超过1MB';
                    $flag = false;
                }
                break;
            case UPLOAD_GOODS_BRAND:
                // 商品品牌
                break;
            case UPLOAD_GOODS_GROUP:
                // 商品分组
                break;
            case UPLOAD_GOODS_CATEGORY:
                // 商品分类
                break;
            case UPLOAD_COMMON:
                // 公共
                break;
            case UPLOAD_AVATOR:
                // 用户头像
                if ($this->file_type != "image/gif" && $this->file_type != "image/png" && $this->file_type != "image/jpeg" && $this->file_size > 1000000) {
                    $this->return['message'] = '文件上传失败,请检查您上传的文件类型,文件大小不能超过1MB';
                    $flag = false;
                }
                break;
            case UPLOAD_PAY:
                // 支付
                if ($this->file_type != "image/gif" && $this->file_type != "image/png" && $this->file_type != "image/jpeg" && $this->file_size > 1000000) {
                    $this->return['message'] = '文件上传失败,请检查您上传的文件类型,文件大小不能超过1MB';
                    $flag = false;
                }
                break;
            case UPLOAD_ADV:
                // 广告位
                break;
            case UPLOAD_EXPRESS:
                // 物流
                if ($this->file_type != "image/gif" && $this->file_type != "image/png" && $this->file_type != "image/jpeg" && $this->file_size > 1000000) {
                    $this->return['message'] = '文件上传失败,请检查您上传的文件类型,文件大小不能超过1MB';
                    $flag = false;
                }
                break;
            case UPLOAD_CMS:
                // 文章
                if ($this->file_type != "image/gif" && $this->file_type != "image/png" && $this->file_type != "image/jpeg" && $this->file_size > 1000000) {
                    $this->return['message'] = '文件上传失败,请检查您上传的文件类型,文件大小不能超过1MB';
                    $flag = false;
                }
                break;
            case UPLOAD_AGENT:
                // 代理商
                if ($this->file_type != "image/gif" && $this->file_type != "image/png" && $this->file_type != "image/jpeg" && $this->file_size > 1000000) {
                    $this->return['message'] = '文件上传失败,请检查您上传的文件类型,文件大小不能超过1MB';
                    $flag = false;
                }
                break;
            case UPLOAD_AGENT_SHOP:
                // 代理商
                if ($this->file_type != "image/gif" && $this->file_type != "image/png" && $this->file_type != "image/jpeg" && $this->file_size > 1000000) {
                    $this->return['message'] = '文件上传失败,请检查您上传的文件类型,文件大小不能超过1MB';
                    $flag = false;
                }
                break;

            case UPLOAD_SHOP_SITE:
                // 商城站点
                if ($this->file_type != "image/gif" && $this->file_type != "image/png" && $this->file_type != "image/jpeg" && $this->file_size > 1000000) {
                    $this->return['message'] = '文件上传失败,请检查您上传的文件类型,文件大小不能超过1MB';
                    $flag = false;
                }
                break;

            case UPLOAD_VIDEO:
                if ($this->file_type != "video/mp4" && $this->file_size > 500000000) {
                    $this->return['message'] = '文件上传失败,请检查您上传的文件类型,文件大小不能超过500MB';
                    $flag = false;
                }
                break;

        }
        return $flag;
    }
    
    /**
     * 删除文件
     */
    public function removeFile()
    {
        $filename = request()->post("filename", "");
        $res = array();
        $success_count = 0;
        $error_count = 0;
        if ($filename != "") {
            $filename_arr = explode(",", $filename);
            foreach ($filename_arr as $v) {
                if ($v != "") {
                    if (@unlink($v)) {
                        $success_count ++;
                    } else {
                        $error_count ++;
                    }
                }
            }
        }
        $res['success_count'] = $success_count;
        $res['error_count'] = $error_count;
        return $res;
    }
    
    /**
     * 各类型图片生成
     *
     * @param  $photoPath
     * @param  $ext
     * @param number $type
     */
    public function photoCreate($upFilePath, $photoPath, $ext, $type = 0, $pic_name, $album_id, $width, $height, $pic_tag, $pic_id)
    {
        $image = \think\Image::open($photoPath);
        $photoArray = array(
            "bigPath" => array(
                "path" => $upFilePath . md5(time() . $pic_tag) . "1" . $ext,
                "width" => 700,
                "height" => 700,
                'type' => '1'
            ),
            "middlePath" => array(
                "path" => $upFilePath . md5(time() . $pic_tag) . "2" . $ext,
                "width" => 360,
                "height" => 360,
                'type' => '2'
            ),
            "smallPath" => array(
                "path" => $upFilePath . md5(time() . $pic_tag) . "3" . $ext,
                "width" => 240,
                "height" => 240,
                'type' => '3'
            ),
            "littlePath" => array(
                "path" => $upFilePath . md5(time() . $pic_tag) . "4" . $ext,
                "width" => 60,
                "height" => 60,
                'type' => '4'
            )
        );
        foreach ($photoArray as $v) {
            if (stristr($type, $v['type'])) {
                $image->thumb($v["width"], $v["height"], \think\Image::THUMB_CENTER)->save($v["path"]);
            }
        }
        $albumHandle = new AlbumHandle();
        if ($pic_id == "") {
            $retval = $albumHandle->addPicture($pic_name, $pic_tag, $album_id, $photoPath, $width . "," . $height, $width . "," . $height, $photoArray["bigPath"]["path"], $photoArray["bigPath"]["width"] . "," . $photoArray["bigPath"]["height"], $photoArray["bigPath"]["width"] . "," . $photoArray["bigPath"]["height"], $photoArray["middlePath"]["path"], $photoArray["middlePath"]["width"] . "," . $photoArray["middlePath"]["height"], $photoArray["middlePath"]["width"] . "," . $photoArray["middlePath"]["height"], $photoArray["smallPath"]["path"], $photoArray["smallPath"]["width"] . "," . $photoArray["smallPath"]["height"], $photoArray["smallPath"]["width"] . "," . $photoArray["smallPath"]["height"], $photoArray["littlePath"]["path"], $photoArray["littlePath"]["width"] . "," . $photoArray["littlePath"]["height"], $photoArray["littlePath"]["width"] . "," . $photoArray["littlePath"]["height"]);
        } else {
            $retval = $albumHandle->modifyAlbumPicture($pic_id, $photoPath, $width . "," . $height, $width . "," . $height, $photoArray["bigPath"]["path"], $photoArray["bigPath"]["width"] . "," . $photoArray["bigPath"]["height"], $photoArray["bigPath"]["width"] . "," . $photoArray["bigPath"]["height"], $photoArray["middlePath"]["path"], $photoArray["middlePath"]["width"] . "," . $photoArray["middlePath"]["height"], $photoArray["middlePath"]["width"] . "," . $photoArray["middlePath"]["height"], $photoArray["smallPath"]["path"], $photoArray["smallPath"]["width"] . "," . $photoArray["smallPath"]["height"], $photoArray["smallPath"]["width"] . "," . $photoArray["smallPath"]["height"], $photoArray["littlePath"]["path"], $photoArray["littlePath"]["width"] . "," . $photoArray["littlePath"]["height"], $photoArray["littlePath"]["width"] . "," . $photoArray["littlePath"]["height"]);
            $retval = $pic_id;
        }
        return $retval;
    }

    /**
     * 各类型图片生成
     *
     * @param  $photoPath
     * @param  $ext
     * @param number $type
     */
    public function photoCreateComm($upFilePath, $photoPath, $ext, $type = 0, $pic_name, $album_id, $width, $height, $pic_tag, $pic_id)
    {
        $image = \think\Image::open($photoPath);
        $photoArray = array(
            "bigPath" => array(
                "path" => $upFilePath . md5(time() . $pic_tag) . "1" . $ext,
                "width" => 1000,
                "height" => 600,
                'type' => '1'
            ),

            "smallPath" => array(
                "path" => $upFilePath . md5(time() . $pic_tag) . "3" . $ext,
                "width" => 500,
                "height" =>300,
                'type' => '3'
            ),

        );
        foreach ($photoArray as $v) {
           // if (stristr($type, $v['type'])) {
                $image->thumb($v["width"], $v["height"], \think\Image::THUMB_FIXED)->save($v["path"]);
          //  }
        }

        return $photoArray;

    }
    
    /**
     * 用于相册多图上传
     *
     */
    public function photoAlbumUpload()
    {
        $data = array();
        $this->file_path = request()->post("file_path", "");
        if ($this->file_path == "") {
            $data['state'] = '0';
            $data['message'] = "文件路径不能为空";
            $data['origin_file_name'] = $this->file_name;
            return json($data);
        }
        // var_dump($_FILES["file_upload"]["name"]);
        // 重新设置文件路径
        $this->resetFilePath();
        // 检测文件夹是否存在，不存在则创建文件夹
        if (! file_exists($this->reset_file_path)) {
            $mode = intval('0777',8);
            mkdir($this->reset_file_path, $mode, true);
        }
        
        $this->file_name = $_FILES["file_upload"]["name"]; // 文件原名
        $this->file_size = $_FILES["file_upload"]["size"]; // 文件大小
        $this->file_type = $_FILES["file_upload"]["type"]; // 文件类型
        
        if ($this->file_size == 0) {
            $data['state'] = '0';
            $data['message'] = "文件大小为0MB";
            $data['origin_file_name'] = $this->file_name;
            return json($data);
        }
        if ($this->file_size > 5000000) {
            $data['state'] = '0';
            $data['message'] = "文件大小不能超过5MB";
            $data['origin_file_name'] = $this->file_name;
            return json($data);
        }
        
        // 验证文件
        if (! $this->validationFile()) {
            return $this->ajaxFileReturn();
        }
        $guid = time();
        $file_name_explode = explode(".", $this->file_name); // 图片名称
        $suffix = count($file_name_explode) - 1;
        $ext = "." . $file_name_explode[$suffix]; // 获取后缀名
        // 获取原文件名
        $tmp_array = $file_name_explode;
        unset($tmp_array[$suffix]);
        $file_new_name = implode(".", $tmp_array);
        $newfile = md5($file_new_name . $guid) . $ext; // 重新命名文件
        $ok = @move_uploaded_file($_FILES["file_upload"]["tmp_name"], $this->reset_file_path . $newfile);
        if ($ok) {
            @unlink($_FILES['file_upload']);
            $image_size = @getimagesize($this->reset_file_path . $newfile); // 获取图片尺寸
            if ($image_size) {
                
                $width = $image_size[0];
                $height = $image_size[1];
                $name = $file_name_explode[0];
                $type = request()->post("type", "");
                $pic_name = request()->post("pic_name", $file_new_name . $guid);
                $album_id =  request()->post("album_id", 0);
                if (empty($album_id)) {
                    $album_id = 1;
                }
                $pic_tag = request()->post("pic_tag", $file_new_name);
                $pic_id = request()->post("pic_id", "");
                $upload_flag = request()->post("upload_flag", "");
                // 上传到相册管理，生成四张大小不一的图
                $retval = $this->photoCreate($this->reset_file_path, $this->reset_file_path . $newfile, "." . $file_name_explode[$suffix], $type, $pic_name, $album_id, $width, $height, $pic_tag, $pic_id);
                if ($retval > 0) {
                    $albumHandle = new AlbumHandle();
                    $picture_info = $albumHandle->getAlubmPictureDetail([
                        "id" => $retval
                    ]);
                    $data['file_id'] = $retval;
                    $data['file_name'] = $picture_info["pic_cover_mid"];
                    $data['origin_file_name'] = $this->file_name;
                    $data['file_path'] = $this->reset_file_path . $newfile;
                    $data['state'] = '1';
                    return json($data);
                } else {
                    $data['state'] = '0';
                    $data['message'] = "图片上传失败";
                    $data['origin_file_name'] = $this->file_name;
                    return json($data);
                }
            } else {
                $data['state'] = '0';
                $data['message'] = "图片上传失败";
                $data['origin_file_name'] = $this->file_name;
                return json($data);
            }
        } else {
            $data['state'] = '0';
            $data['message'] = "图片上传失败";
            $data['origin_file_name'] = $this->file_name;
            return json($data);
        }
    }

    /**
     * 用于相册多图上传
     *
     */
    public function photoAlbumUploadComm()
    {
        $data = array();
        $this->file_path = request()->post("file_path", "");
        if ($this->file_path == "") {
            $data['state'] = '0';
            $data['message'] = "文件路径不能为空";
            $data['origin_file_name'] = $this->file_name;
            return json($data);
        }
        // var_dump($_FILES["file_upload"]["name"]);
        // 重新设置文件路径
        $this->resetFilePath();
        // 检测文件夹是否存在，不存在则创建文件夹
        if (! file_exists($this->reset_file_path)) {
            $mode = intval('0777',8);
            mkdir($this->reset_file_path, $mode, true);
        }

        $this->file_name = $_FILES["file_upload"]["name"]; // 文件原名
        $this->file_size = $_FILES["file_upload"]["size"]; // 文件大小
        $this->file_type = $_FILES["file_upload"]["type"]; // 文件类型

        if ($this->file_size == 0) {
            $data['state'] = '0';
            $data['message'] = "文件大小为0MB";
            $data['origin_file_name'] = $this->file_name;
            return json($data);
        }
        if ($this->file_size > 2000000) {
            $data['state'] = '0';
            $data['message'] = "文件大小不能超过2MB";
            $data['origin_file_name'] = $this->file_name;
            return json($data);
        }

        // 验证文件
        if (! $this->validationFile()) {
            return $this->ajaxFileReturn();
        }
        $guid = time();
        $file_name_explode = explode(".", $this->file_name); // 图片名称
        $suffix = count($file_name_explode) - 1;
        $ext = "." . $file_name_explode[$suffix]; // 获取后缀名
        // 获取原文件名
        $tmp_array = $file_name_explode;
        unset($tmp_array[$suffix]);
        $file_new_name = implode(".", $tmp_array);
        $newfile = md5($file_new_name . $guid) . $ext; // 重新命名文件
        $ok = @move_uploaded_file($_FILES["file_upload"]["tmp_name"], $this->reset_file_path . $newfile);
        if ($ok) {
            @unlink($_FILES['file_upload']);
            $image_size = @getimagesize($this->reset_file_path . $newfile); // 获取图片尺寸
            if ($image_size) {

                $width = $image_size[0];
                $height = $image_size[1];
                $name = $file_name_explode[0];
                $type = request()->post("type", "");
                $pic_name = request()->post("pic_name", $file_new_name . $guid);
                $album_id =  request()->post("album_id", 0);
                if (empty($album_id)) {
                    $album_id = 1;
                }
                $pic_tag = request()->post("pic_tag", $file_new_name);
                $pic_id = request()->post("pic_id", "");
                $upload_flag = request()->post("upload_flag", "");
                // 上传到相册管理，生成四张大小不一的图
                $retval = $this->photoCreateComm($this->reset_file_path, $this->reset_file_path . $newfile, "." . $file_name_explode[$suffix], $type, $pic_name, $album_id, $width, $height, $pic_tag, $pic_id);
                if (!empty($retval)) {

                    $data['file'] = $retval;
                 //   $data['file_name'] =
                    $data['origin_file_name'] = $this->file_name;
                    $data['file_path'] = $this->reset_file_path . $newfile;
                    $data['state'] = '1';

                    return json($data);
                } else {
                    $data['state'] = '0';
                    $data['message'] = "图片上传失败";
                    $data['origin_file_name'] = $this->file_name;
                    return json($data);
                }
            } else {
                $data['state'] = '0';
                $data['message'] = "图片上传失败";
                $data['origin_file_name'] = $this->file_name;
                return json($data);
            }
        } else {
            $data['state'] = '0';
            $data['message'] = "图片上传失败";
            $data['origin_file_name'] = $this->file_name;
            return json($data);
        }
    }
    
    /**
     * 商品规格图片上传
     */
    public function specImgUpload(){
        $data = array();
        $this->file_path = request()->post("file_path", "");
        if ($this->file_path == "") {
            $data['code'] = '0';
            $data['message'] = "文件路径不能为空";
            return json_encode($data);
        }
        // var_dump($_FILES["file_upload"]["name"]);
        // 重新设置文件路径
        $this->resetFilePath();
        // 检测文件夹是否存在，不存在则创建文件夹
        if (! file_exists($this->reset_file_path)) {
            $mode = intval('0777',8);
            mkdir($this->reset_file_path, $mode, true);
        }
        
        $this->file_name = $_FILES["file_upload"]["name"]; // 文件原名
        $this->file_size = $_FILES["file_upload"]["size"]; // 文件大小
        $this->file_type = $_FILES["file_upload"]["type"]; // 文件类型
        
        if ($this->file_size == 0) {
            $data['code'] = '0';
            $data['message'] = "文件大小为0MB";
            return json_encode($data);
        }
        if ($this->file_size > 5000000) {
            $data['code'] = '0';
            $data['message'] = "文件大小不能超过5MB";
            return json_encode($data);
        }
        
        // 验证文件
        if (! $this->validationFile()) {
            $data['code'] = '0';
            $data['message'] = "文件大小不能超过5MB";
            return json_encode($data);
        }
        $guid = time();
        $file_name_explode = explode(".", $this->file_name); // 图片名称
        $suffix = count($file_name_explode) - 1;
        $ext = "." . $file_name_explode[$suffix]; // 获取后缀名
        // 获取原文件名
        $tmp_array = $file_name_explode;
        unset($tmp_array[$suffix]);
        $file_new_name = implode(".", $tmp_array);
        $newfile = md5($file_new_name . $guid) . $ext; // 重新命名文件
        $ok = @move_uploaded_file($_FILES["file_upload"]["tmp_name"], $this->reset_file_path . $newfile);
        
        if ($ok) {
            @unlink($_FILES['file_upload']);
            $image_size = @getimagesize($this->reset_file_path . $newfile); // 获取图片尺寸
            if ($image_size) {
                // 上传到相册管理，生成四张大小不一的图
                $image = \think\Image::open($this->reset_file_path . $newfile);
                $image->thumb(60, 60, \think\Image::THUMB_CENTER)->save($this->reset_file_path . md5(time() . $file_new_name) . "4" . $ext);
                $data['code'] = 1;
                $data['file_path'] = $this->reset_file_path . md5(time() . $file_new_name) . "4" . $ext;
                $data['message'] = "图片上传成功";
                return json_encode($data);
            }else{
                $data['code'] = 0;
                $data['message'] = "图片上传失败";
                return json_encode($data);
            }
        }else{
            $data['code'] = '0';
            $data['message'] = "图片上传失败";
            return json_encode($data);
        }
    }
    
    
    
    
}

