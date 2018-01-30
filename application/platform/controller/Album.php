<?php
/**
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-08-22
 * Time: 21:25
 */

namespace app\platform\controller;

use dao\handle\AlbumHandle as AlbumHandle;
use dao\handle\BaseHandle;


class Album extends BaseController
{


    public function __construct()
    {
        parent::__construct();
    }

    public function hello() {
        return "hello";
    }


    /**
     * 图片选择
     * 2016年11月21日 16:23:35
     */
    public function dialogAlbumList()
    {
        $query = $this->getAlbumClassALL();
        $this->assign("number", isset($_GET['number']) ? $_GET['number'] : 1);
      //  $this->assign("spec_id", isset($_GET['spec_id']) ? $_GET['spec_id'] : 0);
      //  $this->assign("spec_value_id", isset($_GET['spec_value_id']) ? $_GET['spec_value_id'] : 0);
        $this->assign("album_list",  $query);
      //  return view($this->style . "System/dialogAlbumList");
      //  return "ok";
        return $this->fetch('album/dialogAlbumList');
    }





    /**
     * 获取图片分组
     */
    public function albumList()
    {
        if (request()->isAjax()) {
            $page_index = request()->post("page_index", 1);
            $page_size = request()->post('page_size', PAGESIZE);
            $search_text = isset($_POST['search_text']) ? $_POST['search_text'] : '';
            $album = new AlbumHandle();
            $condition = array(
              //  'shop_id' => $this->instance_id,
                'album_name' => array(
                    'like',
                    '%' . $search_text . '%'
                )
            );
            $order= " sort";
            $retval = $album->getAlbumClassList($page_index, $page_size, $condition, $order);
            return $retval;
        } else {
            $album_list = $this->getAlbumClassALL();
            $this->assign('album_list', $album_list);
         //   return view($this->style . "System/albumList");
            return $this->fetch('album/albumList');
        }
    }

    /**
     * 创建相册
     */
    public function addAlbumClass()
    {
        $album_name = $_POST['album_name'];
        $sort = isset($_POST['sort']) ? $_POST['sort'] : 0;
        $album = new AlbumHandle();
        $retval = $album->addAlbumClass($album_name, $sort, 0, '', 0);
      //  return AjaxReturn($retval);
        return json(resultArray(0,"操作成功", $retval));

    }

    /**
     * 删除相册
     */
    public function deleteAlbumClass()
    {
        $aclass_id_array = $_POST['aclass_id_array'];
        $album = new AlbumHandle();
        $retval = $album->deleteAlbumClass($aclass_id_array);
     //   return AjaxReturn($retval);
        return json(resultArray(0,"操作成功", $retval));
    }

    /**
     * 相册图片列表
     */
    public function albumPictureList()
    {
        if (request()->isAjax()) {
            $page_index = request()->post("page_index", 1);
            $page_size = request()->post("page_size", PAGESIZE);
            $album_id = request()->post("album_id", 0);
            $is_use = request()->post("is_use", 0);
            $condition = array();
            $condition["album_id"] = $album_id;
            $album = new AlbumHandle();
            if ($is_use > 0) {
                $img_array = $album->getGoodsAlbumUsePictureQuery([
                   // "shop_id" => $this->instance_id
                ]);
                if (! empty($img_array)) {
                    $img_string = implode(",", $img_array);
                    $condition["id"] = [
                        "not in",
                        $img_string
                    ];
                }
            }
            $list = $album->getPictureList($page_index, $page_size, $condition);
            foreach ($list["data"] as $k => $v) {
                $list["data"][$k]["upload_time"] = date("Y-m-d",$v["upload_time"]);
            }
            return $list;
        } else {
            $album_list = $this->getAlbumClassALL();
            $this->assign('album_list', $album_list);
            $album_id = isset($_GET["album_id"]) ? $_GET["album_id"] : 0;
            $url = "album/albumPictureList";
            if ($album_id > 0) {
                $url .= "?album_id=" . $album_id;
            }
            $child_menu_list = array(
                array(
                    'url' => "album/albumList",
                    'menu_name' => "相册",
                    "active" => 0
                ),
                array(
                    'url' => $url,
                    'menu_name' => "图片",
                    "active" => 1
                )
            );
            $album = new AlbumHandle();
            $album_detial = $album->getAlbumClassDetail($album_id);
            $this->assign('child_menu_list', $child_menu_list);
            $this->assign("album_name", $album_detial['album_name']);
            $this->assign("album_id", $album_id);
         //   return view($this->style . "System/albumPictureList");
            return $this->fetch('album/albumPictureList');
        }
    }

    /**
     * 相册图片列表
     */
    public function dialogAlbumPictureList()
    {
        if (request()->isAjax()) {
            $page_index = $_POST["pageIndex"];
            $album_id = $_POST["album_id"];
            $condition = "album_id = $album_id";
            $album = new AlbumHandle();
            $list = $album->getPictureList($page_index, 10, $condition);
            foreach ($list["data"] as $k => $v) {
                $list["data"][$k]["upload_time"] = date("Y-m-d",$v["upload_time"]);
            }
            return $list;
        } else {
           // return view($this->style . "System/dialogAlbumPictureList");
            return $this->fetch('album/dialogAlbumPictureList');
        }
    }

    /**
     * 删除图片
     *
     * @param  $pic_id_array
     * @return 
     */
    public function deletePicture()
    {
        $pic_id_array = $_POST["pic_id_array"];
        $album = new AlbumHandle();
        $retval = $album->deletePicture($pic_id_array);
       // return AjaxReturn($retval);
        return json(resultArray(0,"操作成功", $retval));
    }

    /**
     * 获取相册详情
     */
    public function getAlbumClassDetail()
    {
        $album_id = $_POST["album_id"];
        $album = new AlbumHandle();
        $retval = $album->getAlbumClassDetail($album_id);
        return $retval;
       // return json(resultArray(0,"操作成功", $retval));
    }

    /**
     * 修改相册
     */
    public function updateAlbumClass()
    {
        $album_id = $_POST["album_id"];
        $aclass_name = $_POST["album_name"];
        $aclass_sort = $_POST["sort"];
        $album_cover = $_POST["album_cover"];
        $album = new AlbumHandle();
        $retval = $album->updateAlbumClass($album_id, $aclass_name, $aclass_sort, 0, $album_cover);
      //  return AjaxReturn($retval);
        return json(resultArray(0,"操作成功", $retval));
    }

    /**
     * 删除制定路径文件
     */
    function delete_file()
    {
        $file_url = isset($_POST['file_url']) ? $_POST['file_url'] : '';
        if (file_exists($file_url)) {
            @unlink($file_url);
            $retval = array(
                'code' => 1,
                'message' => '文件删除成功'
            );
        } else {
            $retval = array(
                'code' => 0,
                'message' => '文件不存在'
            );
        }
        return $retval;
    }

    /**
     * 获取所有相册
     */
    public function getAlbumClassALL()
    {
        $album = new AlbumHandle();
        $retval = $album->getAlbumClassAll([
          //  'shop_id' => $this->instance_id
        ]);
        return $retval;
    }



    /**
     * 图片名称修改
     */
    public function modifyAlbumPictureName()
    {
        $pic_id = $_POST["pic_id"];
        $pic_name = $_POST["pic_name"];
        $album = new AlbumHandle();
        $retval = $album->ModifyAlbumPictureName($pic_id, $pic_name);
       // return AjaxReturn($retval);
        return json(resultArray(0,"操作成功", $retval));
    }

    /**
     * 转移图片所在相册
     */
    public function modifyAlbumPictureClass()
    {
        $pic_id = $_POST["pic_id"];
        $album_id = $_POST["album_id"];
        $album = new AlbumHandle();
        $retval = $album->ModifyAlbumPictureClass($pic_id, $album_id);
      //  return AjaxReturn($retval);
        return json(resultArray(0,"操作成功", $retval));
    }

    /**
     * 设此图片为本相册的封面
     */
    function modifyAlbumClassCover()
    {
        $pic_id = $_POST["pic_id"];
        $album_id = $_POST["album_id"];
        $album = new AlbumHandle();
        $retval = $album->ModifyAlbumClassCover($pic_id, $album_id);
      //  return AjaxReturn($retval);
        return json(resultArray(0,"操作成功", $retval));
    }










}