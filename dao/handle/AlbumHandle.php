<?php
/**
 * AlbumHandle.php
 * 相册以及图片的处理
 * @date : 2017.08.5
 * @version : v1.0
 */
namespace dao\handle;

use dao\handle\BaseHandle; 
use dao\model\AlbumClass as AlbumClassModel;
use dao\model\AlbumPicture as AlbumPictureModel;
use dao\model\Goods as GoodsModel;
use dao\handle\GoodsHandle;

class AlbumHandle extends BaseHandle
{
    public $album_class;
    public $album_picture;
    function __construct()
    {
        parent::__construct();
        $this->album_class = new AlbumClassModel();
        $this->album_picture = new AlbumPictureModel();
    }
    
     /**
     * 获取相册列表
     * @param number $page_index
     * @param number $page_size
     * @param string $condition
     * @param string $order
     * @param string $field
     */
    public function getAlbumClassList($page_index = 1, $page_size = 0, $condition = '', $order = '', $field = '*')
    {
        $album_class = new AlbumClassModel();
        $list = $album_class->pageQuery($page_index, $page_size, $condition, $order, $field);
        if (! empty($list['data'])) {
            foreach ($list['data'] as $k => $v) {
                // 查询相册图片数量
                $count = $this->getAlbumPictureCount($v['id']);
                $list['data'][$k]['pic_count'] = $count;
                // 查询相册背景图片
                $album_picture = new AlbumPictureModel();
                $pic_cover = "";
                if ($v["album_cover"]) {
                    $pic_info = $album_picture->getInfo([
                        'album_id' => $v['id'],
                        "id" => $v["album_cover"]
                    ], 'pic_cover');
                    if (! empty($pic_info)) {
                        $pic_cover = $pic_info["pic_cover"];
                    }
                    $list['data'][$k]['pic_info'] = $pic_info;
                    $list['data'][$k]["pic_album_cover"] = $pic_cover;
                }
            }
        }
        return $list;
    }
    
    /**
     * 查询相册图片数
     *
     * @param  $album_id
     */
    private function getAlbumPictureCount($album_id)
    {
        $album_picture = new AlbumPictureModel();
        $count = $album_picture->where('album_id=' . $album_id)->count();
        return $count;
    }
    
    /**
     * 创建相册
     * @param  $aclass_name
     * @param  $aclass_sort
     * @param number $pid
     * @param string $aclass_cover
     * @param number $is_default
     */
    public function addAlbumClass($aclass_name, $aclass_sort, $pid = 0, $aclass_cover = '', $is_default = 0)
    {
        $album_class = new AlbumClassModel();
        $data = array(
            'album_name' => $aclass_name,
            'sort' => $aclass_sort,
            'album_cover' => $aclass_cover,
            'is_default' => $is_default,
           // 'shop_id' => $instance_id,
            'pid' => $pid
        );
        $retval = $album_class->save($data);
        if ($retval == 1) {
            return $album_class->id;
        } else {
            return $retval;
        }
    }
    
    /**
     * 编辑相册
     * @param  $aclass_name
     * @param  $aclass_sort
     * @param number $pid
     * @param string $aclass_cover
     * @param number $is_default
     */
    public function updateAlbumClass($aclass_id, $aclass_name, $aclass_sort, $pid = 0, $aclass_cover = '', $is_default = 0)
    {
        $album_class = new AlbumClassModel();
        $data = array(
            'album_name' => $aclass_name,
            'sort' => $aclass_sort,
            "album_cover" => $aclass_cover
        );
        $retval = $album_class->save($data, [
            'id' => $aclass_id
        ]);
        return $retval;
    }
    
    
    /**
     * 改变相册排序
     * @param  $aclass_id
     * @param  $aclass_sort
     */
    public function modifyAlbumSort($aclass_id, $aclass_sort)
    {
        $album_class = new AlbumClassModel();
        $data = array(
            'sort' => $aclass_sort
        );
        
        $retval = $album_class->save($data, [
            'id' => $aclass_id
        ]);
        return $retval;
    }
    
     /**
     * 改变相册上级
     * @param  $aclass_id
     * @param  $pid
     */
    public function modifyAlbumPid($aclass_id, $pid)
    {
        $album_class = new AlbumClassModel();
        $data = array(
            'pid' => $pid
        );
        
        $res = $this->album_class->save($data, [
            'id' => $aclass_id
        ]);
        return $res;
    }
    
    /**
     * 删除相册
     * @param  $aclass_id
     */
    public function deleteAlbumClass($aclass_id_array)
    {
        $album_class = new AlbumClassModel();
        $album_picture = new AlbumPictureModel();
        $this-> startTrans();
        try {
            $condition = array(
               // 'shop_id' => $shop_id,
                'id' => array(
                    'in',
                    $aclass_id_array
                )
            );
            $album_info = $album_class->getInfo([
                "is_default" => 1
            ], "id");
            $album_id = $album_info["id"];
            // 删除所选相册
            $res = $album_class->destroy($condition); //删除方法
            // 将被删除相册下的图片转移到默认
            $condition2 = array(
                'album_id' => array(
                    'in',
                    $aclass_id_array
                )
            );
            $album_picture->save([
                "album_id" => $album_id
            ], $condition2);
            $this->album_class->commit();
            if ($res == 1) {
              //  hook("albumDeleteSuccess", $aclass_id_array);
                return true;  //SUCCESS;
            } else {
                return  false; // DELETE_FAIL;
            }
        } catch (\Exception $e) {
            $this->album_class->rollback();
            return $e->getMessage();
        }
        
        return 0;
        // TODO Auto-generated method stub
    }
    
     /**
     * 获取相册图片列表
     * @param number $page_index
     * @param number $page_size
     * @param string $condition
     * @param string $order
     * @param string $field
     */
    public function getPictureList($page_index = 1, $page_size = 0, $condition = '', $order = " create_time desc", $field = '*')
    {
        $album_picture = new AlbumPictureModel();
        $list = $album_picture->pageQuery($page_index, $page_size, $condition, $order, $field);
        return $list;
    }
    
    /**
     * 图片增加
     * @param params ['album_id','pic_name','pic_tag','pic_cover','pic_size','pic_spec',
     *     'pic_cover_big','pic_size_big','pic_spec_big','pic_cover_mid','pic_size_mid','pic_spec_mid',
     *      'pic_cover_small','pic_size_small','pic_spec_small','pic_cover_micro','pic_size_micro','pic_spec_micro'];
     */
    /*
    public function addPicture($pic_name, $pic_tag, $aclass_id, $pic_cover, $pic_size, $pic_spec, $pic_cover_big, $pic_size_big, $pic_spec_big, $pic_cover_mid, $pic_size_mid, $pic_spec_mid, $pic_cover_small, $pic_size_small, $pic_spec_small, $pic_cover_micro, $pic_size_micro, $pic_spec_micro, $instance_id = 0)
    {
        $album_picture = new AlbumPictureModel();
        
        $allowFields = ['album_id','is_wide','pic_name','pic_tag','pic_cover','pic_size','pic_spec',
            'pic_cover_big','pic_size_big','pic_spec_big','pic_cover_mid','pic_size_mid','pic_spec_mid',
            'pic_cover_small','pic_size_small','pic_spec_small','pic_cover_micro','pic_size_micro','pic_spec_micro'];
        //另外加一个参数'is_wide'
        $params['is_wide'] = 0;
            
        $res = $album_picture->allowField($allowFields)->save($params);
       
        if ($res == 1) {
            return $album_picture->id;
        } else {
            return $res;
        }
    }
    */
    /*
     * (non-PHPdoc)
     * @see \data\api\IAlbum::addPicture()
     */
    public function addPicture($pic_name, $pic_tag, $aclass_id, $pic_cover, $pic_size, $pic_spec, $pic_cover_big,
                                 $pic_size_big, $pic_spec_big, $pic_cover_mid, $pic_size_mid, $pic_spec_mid, 
                                  $pic_cover_small, $pic_size_small, $pic_spec_small,
                                  $pic_cover_micro, $pic_size_micro, $pic_spec_micro)  // $instance_id = 0)
    {
        $album_picture = new AlbumPictureModel();
        $data = array(
            //'shop_id' => $instance_id,
            'album_id' => $aclass_id,
            'is_wide' => "0",
            'pic_name' => $pic_name,
            'pic_tag' => $pic_tag,
            'pic_cover' => $pic_cover,
            'pic_size' => $pic_size,
            'pic_spec' => $pic_spec,
            'pic_cover_big' => $pic_cover_big,
            'pic_size_big' => $pic_size_big,
            'pic_spec_big' => $pic_spec_big,
            'pic_cover_mid' => $pic_cover_mid,
            'pic_size_mid' => $pic_size_mid,
            'pic_spec_mid' => $pic_spec_mid,
            'pic_cover_small' => $pic_cover_small,
            'pic_size_small' => $pic_size_small,
            'pic_spec_small' => $pic_spec_small,
            'pic_cover_micro' => $pic_cover_micro,
            'pic_size_micro' => $pic_size_micro,
            'pic_spec_micro' => $pic_spec_micro,
            'upload_time' => time()
        );
        $res = $album_picture->save($data);
        if ($res == 1) {
            return $album_picture->id;
        } else {
            return $res;
        }
    }

    /**
     * 图片删除
     * @param  $pic_id
     */
    public function deletePicture($pic_id_array)
    {
        $album_picture = new AlbumPictureModel();
        $shop_id = 0; //$this->instance_id;
        $pic_array = explode(',', $pic_id_array);
        $res = 1;
        if (! empty($pic_array)) {
            $user_img_array = $this->getGoodsAlbumUsePictureQuery(""); //[
             //   "shop_id" => $shop_id
         //   ]);
            
            // 判断当前图片是否在商品中使用过
            foreach ($pic_array as $pic_id) {
                $retval = in_array($pic_id, $user_img_array);
                if (! $retval) {
                    $condition = array(
                     //   'shop_id' => $shop_id,
                        'id' => $pic_id
                    );
                    // 得到当前图片的信息
                    $picture_obj = $album_picture->get($pic_id);
                    if (! empty($picture_obj)) {
                        $pic_cover = $picture_obj["pic_cover"];
                        removeImageFile($pic_cover);
                        $pic_cover_big = $picture_obj["pic_cover_big"];
                        removeImageFile($pic_cover_big);
                        $pic_cover_mid = $picture_obj["pic_cover_mid"];
                        removeImageFile($pic_cover_mid);
                        $pic_cover_small = $picture_obj["pic_cover_small"];
                        removeImageFile($pic_cover_small);
                        $pic_cover_micro = $picture_obj["pic_cover_micro"];
                        removeImageFile($pic_cover_micro);
                    }
                    $result = $album_picture->destroy($condition);
                    if (! $result > 0) {
                        $res = - 1;
                    }
                } else {
                    $res = - 1;
                }
            }
        } else {
            $res = - 1;
        }
        if ($res == 1) {
            return true; //SUCCESS;
        } else {
            return false; //DELETE_FAIL;
        }
    }
    

    
    /**
     * 获取相册详情
     * @param  $album_id
     */
    public function getAlbumClassDetail($album_id)
    {
        $album_class = new AlbumClassModel();
        $res = $album_class->get($album_id);
        return $res;
    }
    
    /**
     * 获取图片详情
     * @param  $pic_id
     */
    public function getAlbumDetail($pic_id)
    {
        $album_picture = new AlbumPictureModel($pic_id);
        $res = $album_picture->get($pic_id);
        return $res;
        // TODO Auto-generated method stub
    }
    
    /**
     *按条件获取所有相册
     */
    public function getAlbumClassAll($data = '')
    {
        $album_class = new AlbumClassModel();
        $res = $album_class->getConditionQuery($data, "*", "sort");
        if (! empty($res)) {
            foreach ($res as $k => $v) {
                // 查询相册图片数量
                $count = $this->getAlbumPictureCount($v['id']);
                $res[$k]['pic_count'] = $count;
                // 查询相册背景图片
                $album_pic = new AlbumPictureModel();
                $pic_info = $album_pic->getInfo([
                    'id' => $v['album_cover']
                ], 'pic_cover');
                $res[$k]['pic_info'] = $pic_info;
            }
        }
        return $res;
    }
    
    /*
     * 替换图片
     */
  //  public function modifyAlbumPicture($pic_id, $params)
        /*$pic_cover, $pic_size, $pic_spec, $pic_cover_big, $pic_size_big, $pic_spec_big, $pic_cover_mid, $pic_size_mid, $pic_spec_mid, $pic_cover_small, $pic_size_small, $pic_spec_small, $pic_cover_micro, $pic_size_micro, $pic_spec_micro)
        */
  //  {
        // TODO Auto-generated method stub
        /*
        $data = array(
            'pic_cover' => $pic_cover,
            'pic_size' => $pic_size,
            'pic_spec' => $pic_spec,
            'pic_cover_big' => $pic_cover_big,
            'pic_size_big' => $pic_size_big,
            'pic_spec_big' => $pic_spec_big,
            'pic_cover_mid' => $pic_cover_mid,
            'pic_size_mid' => $pic_size_mid,
            'pic_spec_mid' => $pic_spec_mid,
            'pic_cover_small' => $pic_cover_small,
            'pic_size_small' => $pic_size_small,
            'pic_spec_small' => $pic_spec_small,
            'pic_cover_micro' => $pic_cover_micro,
            'pic_size_micro' => $pic_size_micro,
            'pic_spec_micro' => $pic_spec_micro,
            'upload_time' => time()
        );
        */
        /*
        $album_picture = new AlbumPictureModel();
        
        $allowFields = [
            'pic_cover_big','pic_size_big','pic_spec_big','pic_cover_mid','pic_size_mid','pic_spec_mid',
            'pic_cover_small','pic_size_small','pic_spec_small','pic_cover_micro','pic_size_micro','pic_spec_micro'];
        
        $res = $album_picture->allowField($allowFields)->save($params, [
            "id" => $pic_id
        ]);
        return $res;
    }
    */

    public function modifyAlbumPicture($pic_id, $pic_cover, $pic_size, $pic_spec, $pic_cover_big, $pic_size_big, $pic_spec_big, $pic_cover_mid, $pic_size_mid, $pic_spec_mid, $pic_cover_small, $pic_size_small, $pic_spec_small, $pic_cover_micro, $pic_size_micro, $pic_spec_micro)
    {
        $album_picture = new AlbumPictureModel();
        $data = array(
            'pic_cover' => $pic_cover,
            'pic_size' => $pic_size,
            'pic_spec' => $pic_spec,
            'pic_cover_big' => $pic_cover_big,
            'pic_size_big' => $pic_size_big,
            'pic_spec_big' => $pic_spec_big,
            'pic_cover_mid' => $pic_cover_mid,
            'pic_size_mid' => $pic_size_mid,
            'pic_spec_mid' => $pic_spec_mid,
            'pic_cover_small' => $pic_cover_small,
            'pic_size_small' => $pic_size_small,
            'pic_spec_small' => $pic_spec_small,
            'pic_cover_micro' => $pic_cover_micro,
            'pic_size_micro' => $pic_size_micro,
            'pic_spec_micro' => $pic_spec_micro,
            'upload_time' => time()
        );
        $res = $this->album_picture->save($data, [
            "id" => $pic_id
        ]);
        return $res;
    }
    
    /**
     * 图片名称修改
     * @param  $pic_id
     * @param  $pic_name
     */
    public function modifyAlbumPictureName($pic_id, $pic_name)
    {
        $data = array(
            'pic_name' => $pic_name
        );
        
        $album_picture = new AlbumPictureModel();
        $res = $album_picture->save($data, [
            "id" => $pic_id
        ]);
        
        if ($res == 1) {
            return true; //SUCCESS;
        } else {
            return false; //UPDATA_FAIL;
        }
    }
    
    /**
     * 更改图片所在相册
     * @param  $pic_id
     * @param  $album_id
     */
    public function modifyAlbumPictureClass($pic_id, $album_id)
    {
        $album_picture = new AlbumPictureModel();
        $data = array(
            'album_id' => $album_id
        );
        $condition["id"] = ["in", $pic_id];
        $res = $album_picture->save($data, $condition);
        if ($res > 0) {
            return true; //SUCCESS;
        } else {
            return false; //UPDATA_FAIL;
        }
    }
    
     /**
     * 设此图片为本相册的封面
     * @param  $pic_id
     * @param  $album_id
     */
    public function modifyAlbumClassCover($pic_id, $album_id)
    {
        $data = array(
            'album_cover' => $pic_id
        );
        $album_class = new AlbumClassModel();
        $res = $album_class->save($data, [
            "id" => $album_id
        ]);
        if ($res == 1) {
            return true; //SUCCESS;
        } else {
            return false; //UPDATA_FAIL;
        }
    }
    
     /**
     * 获取相册图片详情
     * @param  $condition
     */
    public function getAlubmPictureDetail($condition)
    {
        $album_picture = new AlbumPictureModel();
        $res = $album_picture->getInfo($condition, "*");
        return $res;
    }
    
    
    /*
     * (non-PHPdoc)
     * @see \data\api\IAlbum::getGoodsAlbumUsePictureQuery()
     */

    public function getGoodsAlbumUsePictureQuery($condition)
    {
        // TODO Auto-generated method stub
        
        $goods = new GoodsModel();
        $goods_query = $goods->getConditionQuery($condition, "thumb_url", "");
    //    $goods_deleted = new NsGoodsDeletedModel();
    //    $goods_deleted_query = $goods_deleted->getConditionQuery($condition, "img_id_array", "");
        $img_array = array();
        foreach ($goods_query as $k => $v) {
            if (trim($v["thumb_url"]) != "") {
                $tmp_array = explode(",", trim($v["thumb_url"]));
                $img_array = array_merge($img_array, $tmp_array);
            }
        }
        /*
        foreach ($goods_deleted_query as $k => $v) {
            if (trim($v["img_id_array"]) != "") {
                $tmp_array = explode(",", trim($v["img_id_array"]));
                $img_array = array_merge($img_array, $tmp_array);
            }
        }
        */
        $img_array = array_unique($img_array);
        return $img_array;
    }

    
    
    
}

