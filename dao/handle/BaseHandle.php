<?php
/**
 * BaseHandle.php
 * @author : wjt
 * @date : 2017.08.02
 * @version : v1.0
 */
namespace dao\handle;

use think\Db;
use think\Log;
use think\Request;

class BaseHandle
{
    public $error = "";
    
    public function __construct(){
        $this->init();
    }
    
    /**
     * 初始化数据
     */
    private function init(){
        
    }
    
    public function getError() {
        return $this->error;
    }
    
    /**
     * 数据库开启事务
     */
    public function startTrans()
    {
        Db::startTrans();
    }
    
    /**
     * 数据库事务提交
     */
    public function commit()
    {
        Db::commit();
    }
    
    /**
     * 数据库事务回滚
     */
    public function rollback()
    {
        Db::rollback();
    }

    /**
     * 检测ID是否在ID组
     *
     * @param  $id
     *            数字
     * @param  $id_arr
     *            数字,数字
     */
    public function checkIdIsinIdArr($id, $id_arr)
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
    public function getTimeTurnTimeStamp($time)
    {
        $time_stamp = strtotime($time);
        return $time_stamp;
    }
    
}

