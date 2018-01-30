<?php
/**
 * BaseController.php
 * @date : 2017.10.10
 * @version : v1.0.
 */
namespace app\platform\controller;

use app\common\controller\CommonController;
use dao\handle\PlatformUserHandle as PlatformUserHandle;

use think\Controller;


class BaseController extends CommonController
{

    protected $user = null;



    protected $userId = 0;
/*
    protected $website = null;
    protected $instance_id;

    protected $instance_name;

    protected $user_name;

    protected $user_headimg;

    protected $module = null;

    protected $controller = null;

    protected $action = null;

    protected $module_info = null;

    protected $rootid = null;

    protected $moduleid = null;

    protected $second_menu_id = null;
*/
    // 二级菜单module_id 手机自定义模板临时添加，用来查询三级菜单
    
    /**
     * 当前版本的路径
     *
     * @var string
     */
 //   protected $style = null;

    public function __construct()
    {
        parent::__construct();
        $this->init();
    }

    /**
     * 初始化
     */
    public function init()
    {
        $platform_user_handle = new  PlatformUserHandle();
        $this->userId = $platform_user_handle->getSessionUserId();

        if (empty($this->userId)) {
            if (request()->isAjax()) {
                return json(resultArray(2, "您未登录系统,请先登录"));
                exit();
            } else {
                //返回到登录界面
                $redirect = url('platform/index/login');  //  __URL(__URL__ . '/' . ADMIN_MODULE . "/login");

                $this->redirect($redirect);
            }
        }
    }

    public function getUserInfo() {
        $platform_user_handle = new  PlatformUserHandle();
        $user_info = $platform_user_handle->getUserInfoById($this->userId);
        if ($user_info['last_login_time'] == "0000-00-00 00:00:00") {
                    $user_info['last_login_time'] = "--";
        }
        if ($user_info['last_login_ip'] == "0.0.0.0") {
            $user_info['last_login_ip'] = "--";
        }

        return json(resultArray(0,"操作成功", $user_info));

    }

    /**
     * 添加操作日志（当前考虑所有操作），
     */
    private function addUserLog()
    {
        $get_data = '';
        if (request()->isGet()) {
            $res = \think\Request::instance()->get();
            $get_data = json_encode($res);
        }
        if (request()->isPost()) {
            $res = \think\Request::instance()->post();
            if (empty($get_data)) {
                $get_data = json_encode($res);
            } else {
                $get_data = $get_data . ',' . json_encode($res);
            }
        }
        // 建议，调试模式，用于
        // $res = $this->user->addUserLog($this->uid, 1, $this->controller, $this->action, \think\Request::instance()->ip(), $get_data);
    }


    /**
     * 获取系统信息
     */
    public function getSystemConfig()
    {
        $system_config['os'] = php_uname(); // 服务器操作系统
        $system_config['server_software'] = $_SERVER['SERVER_SOFTWARE']; // 服务器环境
        $system_config['upload_max_filesize'] = @ini_get('file_uploads') ? ini_get('upload_max_filesize') : 'unknow'; // 文件上传限制
        $system_config['gd_version'] = gd_info()['GD Version']; // GD（图形处理）版本
        $system_config['max_execution_time'] = ini_get("max_execution_time") . "秒"; // 最大执行时间
        $system_config['port'] = $_SERVER['SERVER_PORT']; // 端口
        $system_config['dns'] = $_SERVER['HTTP_HOST']; // 服务器域名
        $system_config['php_version'] = PHP_VERSION; // php版本
        $system_config['ip'] = $_SERVER['SERVER_ADDR']; // 服务器ip
       // $this->assign("system_config", $system_config);
        return json(resultArray(0,"操作成功", $system_config));
    }


}
