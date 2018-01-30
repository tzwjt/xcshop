<?php
/**
 * 商城网站的相关设置
 * Created by PhpStorm.
 * User: jhon
 * Date: 2017-12-16
 * Time: 11:52
 */

namespace dao\handle;

use dao\handle\BaseHandle as BaseHandle;
use dao\model\ShopIndexBlock as ShopIndexBlockModel;
use dao\model\ShopIntroduce as ShopIntroduceModel;
use dao\model\ShopIntroduce;
use dao\model\ShopMenu as ShopMenuModel;


class ShopSiteSettingHandle extends BaseHandle
{

    function __construct()
    {
        parent::__construct();
    }

    /**
     * ok-2ok
     * 新增商城首页层
     */
    public function addShopIndexBlock($sort, $position, $title, $type, $is_use, $content)
    {
        $data['sort'] = $sort;
        $data['position'] = $position;
        $data['title'] = $title;
        $data['type'] = $type;
        $data['is_use'] = $is_use;
        $data['content'] = json_encode($content);
        $data['create_time'] = time();

        $shop_index_block = new ShopIndexBlockModel();
        $res = $shop_index_block->save($data);
        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 修改商城首页层
    */
    public function updateShopIndexBlock($block_id, $sort, $position, $title, $type, $is_use, $content)
    {
        $data['sort'] = $sort;
        $data['position'] = $position;
        $data['title'] = $title;
        $data['type'] = $type;
        $data['is_use'] = $is_use;
        $data['content'] = json_encode($content);
        $data['update_time'] = time();

        $shop_index_block = new ShopIndexBlockModel();
        $res = $shop_index_block->save($data,['id'=>$block_id]);
        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 得到商城首层的种类名
     * @param $sort
     * @return string
     */
    private function getShopIndexBlockSortName($sort) {
        if ($sort == 1) {
            return '手机端';
        } else if ($sort == 2) {
            return 'PC端';
        } else {
            return  '';
        }
    }

    /**
     * ok-2ok
     * 得到商城首页层的类型名
     * @param $sort
     * @return string
     */
    private function getShopIndexBlockTypeName($type) {
        if ($type == 1) {
            return '图片';
        } else if ($type == 2) {
            return '视频';
        } else if ($type == 3) {
            return '图片+视频';
        } else {
            return  '';
        }
    }

    /**
     * ok-2ok
     * 得到商城首页层列表
     */
    public function getShopIndexBlockList($page_index = 1, $page_size = 0, $condition = '', $fields="*",  $order = 'sort asc, position asc')
    {
        $shop_index_block = new ShopIndexBlockModel();
        $list = $shop_index_block->pageQuery($page_index, $page_size, $condition, $order, $fields);
       foreach ($list['data'] as $k=>$v) {
           if (!empty($v['sort'])) {
               $list['data'][$k]['sort_name'] = $this->getShopIndexBlockSortName($v['sort']);
           }

           if (!empty($v['type'])) {
               $list['data'][$k]['type_name']=$this->getShopIndexBlockTypeName($v['type']);
           }

           if (!empty($v['content'])) {
               $list['data'][$k]['content'] = json_decode($v['content'],true);
           }
       }
        return $list;
    }

    /**
     * ok-2ok
     * 删除商城首页层
     */
    public function delShopIndexBlock($block_id){

        $shop_index_block = new ShopIndexBlockModel();
        $res =  $shop_index_block->destroy(['id'=>$block_id]);
        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 得到商城首页层详情
     */
    public function getShopIndexBlock($block_id)
    {
        $shop_index_block = new ShopIndexBlockModel();
        $info = $shop_index_block->get($block_id);
        $info['sort_name'] = $this->getShopIndexBlockSortName($info['sort']);
        $info['type_name']=$this->getShopIndexBlockTypeName($info['type']);
        if (!empty($info['content'])) {
            $info['content'] = json_decode($info['content'], true);
        }

        return $info;
    }

    /**
     * ok-2ok
     * 设置商城首页层是否可用
     */
    public function setShopIndexBlockUse($block_id, $is_use){
        $shop_index_block = new ShopIndexBlockModel();
        $data = array(
            'is_use'=>$is_use,
            'update_time'=>time()
        );
        $res = $shop_index_block->save($data,['id'=>$block_id]);
        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 更改商城首页层的位置
     */
    public function updateShopIndexBlockPosition($block_id, $position){
        $shop_index_block = new ShopIndexBlockModel();
        $data = array(
            'position'=>$position,
            'update_time'=>time()
        );
        $retval = $shop_index_block->save(
            $data, [
            'id' => $block_id
        ]);

        if ($retval > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 新增商城介绍
     */
    public function addShopIntroduce($sort, $title, $pic, $is_use, $content)
    {
        $data['sort'] = $sort;
        $data['title'] = $title;
        $data['pic'] = $pic;
        $data['content'] = $content;
        $data['is_use'] = $is_use;
        $data['create_time'] = time();

        $shop_introduce = new ShopIntroduceModel();
        $res = $shop_introduce->save($data);
        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 修改商城介绍
     */
    public function updateShopIntroduce($introduce_id, $sort, $title, $pic, $is_use, $content)
    {
        $data['sort'] = $sort;
        $data['title'] = $title;
        $data['pic'] = $pic;
        $data['content'] = $content;
        $data['is_use'] = $is_use;
        $data['update_time'] = time();

        $shop_introduce = new ShopIntroduceModel();
        $res = $shop_introduce->save($data,['id'=>$introduce_id]);
        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 设置商城介绍是否可用
     */
    public function setShopIntroduceUse($introduce_id, $is_use){
        $shop_introduce = new ShopIntroduceModel();
        $data = array(
            'is_use'=>$is_use,
            'update_time'=>time()
        );
        $res = $shop_introduce->save($data,['id'=>$introduce_id]);
        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 得到商城介绍详情
     */
    public function getShopIntroduce($condition, $field='*')
    {
        $shop_introduce = new ShopIntroduceModel();
        $info = $shop_introduce->getInfo($condition, $field);
        if (!empty($info) && !empty($info['sort'])) {
            $info['sort_name'] = $this->getShopIntroduceSortName($info['sort']);
        }

        return $info;
    }

    /**
     * ok-2ok
     * 得到商城介绍的种类名
     * @param $sort
     * @return string
     */
    private function getShopIntroduceSortName($sort) {
        if ($sort == 1) {
            return '手机端';
        } else if ($sort == 2) {
            return 'PC端';
        } else if ($sort == 3) {
            return '手机端+PC端';
        } else {
            return  '';
        }
    }

    /**
     * ok-2ok
     * 新增商城菜单
     */
    public function addShopMenu($sort, $position, $menu_name,$menu_logo, $menu_url, $is_use)
    {
        $data['sort'] = $sort;
        $data['position'] = $position;
        $data['menu_name'] = $menu_name;
        $data['menu_logo'] = $menu_logo;
        $data['menu_url'] = $menu_url;
        $data['is_use'] = $is_use;
        $data['create_time'] = time();

        $shop_menu = new ShopMenuModel();
        $res = $shop_menu->save($data);
        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 更新商城菜单
     */
    public function updateShopMenu($menu_id, $sort, $position, $menu_name,$menu_logo, $menu_url, $is_use)
    {
        $data['sort'] = $sort;
        $data['position'] = $position;
        $data['menu_name'] = $menu_name;
        $data['menu_logo'] = $menu_logo;
        $data['menu_url'] = $menu_url;
        $data['is_use'] = $is_use;
        $data['update_time'] = time();

        $shop_menu = new ShopMenuModel();
        $res = $shop_menu->save($data, ['id'=>$menu_id]);
        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 得到商城菜单列表
     */
    public function getShopMenuList($page_index = 1, $page_size = 0, $condition = '', $fields="*",  $order = 'sort asc, position asc')
    {
        $shop_menu= new ShopMenuModel();
        $list = $shop_menu->pageQuery($page_index, $page_size, $condition, $order, $fields);
        foreach ($list['data'] as $k=>$v) {
            if (!empty($v['sort'])) {
                $list['data'][$k]['sort_name'] = $this->getShopMenuSortName($v['sort']);
            }
        }
        return $list;
    }

    /**
     * ok-2ok
     * 得到商城菜单的种类名
     * @param $sort
     * @return string
     */
    private function getShopMenuSortName($sort) {
        if ($sort == 1) {
            return '手机端';
        } else if ($sort == 2) {
            return 'PC端';
        } else {
            return  '';
        }
    }

    /**
     * ok-2ok
     * 删除商城菜单
     */
    public function delShopMenu($menu_id){
        $shop_menu = new ShopMenuModel();
        $res =  $shop_menu->destroy(['id'=>$menu_id]);
        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 得到商城菜单详情
     */
    public function getShopMenu($menu_id)
    {
        $shop_menu = new ShopMenuModel();
        $info = $shop_menu->get($menu_id);
        $info['sort_name'] = $this->getShopMenuSortName($info['sort']);

        return $info;
    }

    /**
     * ok-2ok
     * 设置商城菜单是否可用
     */
    public function setShopMenuUse($menu_id, $is_use){
        $shop_menu = new ShopMenuModel();
        $data = array(
            'is_use'=>$is_use,
            'update_time'=>time()
        );
        $res = $shop_menu->save($data,['id'=>$menu_id]);
        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 更改商城菜单的位置
     */
    public function updateShopMenuPosition($menu_id, $position)
    {
        $shop_menu = new ShopMenuModel();
        $data = array(
            'position' => $position,
            'update_time' => time()
        );
        $retval = $shop_menu->save(
            $data, [
            'id' => $menu_id
        ]);

        if ($retval > 0) {
            return true;
        } else {
            return false;
        }
    }
}