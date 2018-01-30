<?php
namespace app\shop\controller;
use think\Db;
use think\Controller;
header("Content-Type: text/html;charset=utf-8");
class Tyd extends Controller
{
    public function _initialize()
    {
        
    }

    public function index()
    {
        return $this->fetch("tyd");
    }
  
}
