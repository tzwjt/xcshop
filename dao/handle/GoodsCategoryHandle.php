<?php
/**
 * GoodsCategoryHandle.php
 * @date : 2017.08.06
 * @version : v1.0
 */
namespace dao\handle;

/**
 * 处理商品分类
 */

use dao\model\GoodsCategory as GoodsCategoryModel;
use dao\model\GoodsBrand as GoodsBrandModel;

class GoodsCategoryHandle extends BaseHandle
{
    private $goods_category;
    
    function __construct(){
        parent:: __construct();
        $this->goods_category = new GoodsCategoryModel();
    }
    
    /**
     * 商品分类列表
     * @param number $page_index
     * @param number $page_size
     * @param string $condition
     * @param string $order
     * @param string $field
     */
    public function getGoodsCategoryList($page_index=1, $page_size=0, $condition = '', $order = '', $field = '*')
    {
        $list = $this->goods_category->pageQuery($page_index, $page_size, $condition, $order, $field);
        return $list;  
    }
    
    
    /**
     * 获取商品分类的子分类
     * @param  $pid
     */
    public function getGoodsCategoryListByParentId($pid,  $status='')
    {
        $list = $this->getGoodsCategoryList(1, 0, 'pid='.$pid, 'pid,sort');
        if(!empty($list)){
            for($i=0; $i<count($list['data']); $i++){
                $parent_id=$list['data'][$i]['id'];
                if (empty($status)) {
                    $child_list = $this->getGoodsCategoryList(1, 1, 'pid=' . $parent_id, 'pid,sort');
                } else {
                    $condition = array(
                        'pid'=>  $parent_id,
                        'status' => $status
                    );
                    $child_list = $this->getGoodsCategoryList(1, 1, $condition, 'pid,sort');
                }
                if(!empty($child_list) && $child_list['total_count']>0){
                    $list['data'][$i]["is_parent"]=1;
                }else{
                    $list['data'][$i]["is_parent"]=0;
                }
            }
        }
        return $list['data'];
        // TODO Auto-generated method stub
        
    }
    
    
    /**
     * 获取格式化后的商品分类，得到一个三级分类
     */
    public function getFormatGoodsCategoryList(){
        
        $one_list = $this->getGoodsCategoryListByParentId(0);
        if (! empty($one_list)) {
            foreach ($one_list as $k => $v) {
                $two_list = array();
                $two_list = $this->getGoodsCategoryListByParentId($v['id']);
                $v['child_list'] = $two_list;
                if (! empty($two_list)) {
                    foreach ($two_list as $k1 => $v1) {
                        $three_list = array();
                        $three_list = $this->getGoodsCategoryListByParentId($v1['id']);
                        $v1['child_list'] = $three_list;
                    }
                }
            }
        }
        return $one_list;
    }
    
    /**
     * 添加或者更新商品分类信息
     * @param  $category_id  添加时$category_id=0
     */
    public function addOrUpdateGoodsCategory($categoryId, $categoryName, $description, $thumb,$pid,
        $sort, $isRecommand, $isHome, $status,$advImg, $advUrl) {
            
            if (empty($categoryName)) {
                $this->error = '商品分类名不能为空';
                return false;
            }
            
           
            
            // 验证֤
            /*
             $validate = validate($this->name);
             if (!$validate->check($param)) {
             $this->error = $validate->getError();
             return false;
             }
             */
            
            if($pid == 0){
                $level = 1;
            }else{
                $level = $this->getGoodsCategoryDetail($pid)['level'] + 1;
            }
            
            $data = array(
                'category_name' => $categoryName,
                'thumb' => $thumb,
                'pid' => $pid,
                'level' => $level,
                'status' => $status,
                'description' => $description,
                'sort' => $sort,
                'is_recommand' => $isRecommand,
                'is_home' => $isHome,
                'adv_img' => $advImg,
                'adv_url' => $advUrl,
            );
            
            try {
                if($categoryId == 0)  //新增
                {
                    $result = $this->goods_category->save($data);
                    if($result) {
                     //  $data['category_id'] = $this->id;
                        //   hook("goodsCategorySaveSuccess", $data);
                        $res = true;
                    }else{
                        //$res = $this->getError();
                        $this->error ="新增时数据库操作失败!";
                        $res = false;
                    }
                    
                } else {  //更新
                    $result = $this->goods_category->save($data,['id'=>$categoryId]);
                    if($result !== false) {
                        $this->goods_category->save(["level"=>$level+1], ["pid"=>$categoryId]);
                        $data['category_id'] = $categoryId;
                        //   hook("goodsCategorySaveSuccess", $data);
                        //  return $res;
                        $res = true;
                    }   else    {
                        $this->error ="更新时数据库操作失败!";
                        $res = false;
                    }
                }
                return $res;
                
            } catch(\Exception $e) {
                // $this->rollback();
                $this->error = '操作时出现异常,'.$e->getMessage().', 操作失败!';
                return false;
            }
    }
    
    /**
     * 删除商品分类信息
     * @param  $goods_classid_array
     */
    public function deleteGoodsCategory($category_id)
    {
        $sub_list = $this->getGoodsCategoryListByParentId($category_id);
        if (! empty($sub_list)) {
            $this->error = "因其存在下级分类，不可删除!";
            $res = false; //SYSTEM_DELETE_FAIL;
        }else {
            $res = $this->goods_category->destroy($category_id);
         //   hook("goodsCategoryDeleteSuccess", $category_id);
        }
        return $res;
       
        
    }
    
    /**
     * 获取分类级次
     * @param  $category_id
     */
    public function getLevel($category_id)
    {
        $res = $this->goods_category->getInfo(['id'=>$category_id],'level');
        return $res;
    }
    
    /**
     * 获取分类名称
     * @param  $category_id
     */
    public function getCategoryName($category_id)
    {
        $res = $this->goods_category->getInfo(['id'=>$category_id],'category_name');
        return $res;
        
    }
    
    /**
     * 获取指定商品分类的详情
     * @param  $goods_classid
     */
    public function getGoodsCategoryDetail($category_id)
    {
        $res = $this->goods_category->get($category_id);
        return $res;
        // TODO Auto-generated method stub
        
    }
    
    public function getGoodsCategoryTree($pid){
        //暂时  获取 两级
        $list = array();
        $one_list = $this->getGoodsCategoryListByParentId($pid);
        foreach($one_list as $k1=>$v1){
            $two_list = array();
            $two_list = $this->getGoodsCategoryListByParentId($v1['id']);
            $one_list[$k1]['child_list'] = $two_list;
        }
        $list = $one_list;
        return $list;
    }
    
    
    /**
     * 修改商品分类  单个字段
     * @param  $category_id
     * @param  $field_name
     * @param  $field_value
     */
    public function modifyGoodsCategoryField($category_id, $field_name, $field_value){
        $res = $this->goods_category->modifyTableField('id',$category_id, $field_name, $field_value);
        return $res;
    }
    
    /**
     * 获取商品分类的子项列
     * @param  $category_id
     * @return string|
     */
    public function getCategoryTreeList($category_id)
    {
        $goods_goods_category = new GoodsCategoryModel();
        $level = $goods_goods_category->getInfo(['id' => $category_id], 'level');
        if(!empty($level))
        {
            $category_list = array();
            if($level['level'] == 1)
            {
                $child_list = $goods_goods_category->getConditionQuery(['pid' => $category_id], 'id,pid', '');
                $category_list = $child_list;
                if(!empty($child_list))
                {
                    foreach ($child_list as $k => $v)
                    {
                        $grandchild_list = $goods_goods_category->getConditionQuery(['pid' => $v['id']], 'id', '');
                        if(!empty($grandchild_list))
                        {
                            $category_list = array_merge($category_list, $grandchild_list);
                        }
                    }
                }
            }elseif($level['level'] == 2)
            {
                $child_list = $goods_goods_category->getConditionQuery(['pid' => $category_id], 'id,pid', '');
                $category_list = $child_list;
            }
            $array = array();
            if(!empty($category_list))
            {
                
                foreach ($category_list as $k => $v)
                {
                    $array[] = $v['id'];
                }
                
            }
            if(!empty($array))
            {
                $id_list = implode(',', $array);
                return $id_list.','.$category_id;
            }else{
                return $category_id;
            }
            
            
        }else{
            return $category_id;
        }
    }
    
    /**
     * 获取分类的父级分类
     * @param  $category_id
     */
    public function getCategoryParentQuery($category_id)
    {
        $parent_category_info = array();
        $grandparent_category_info = array();
        $category_name= "";
        $parent_category_name= "";
        $grandparent_category_name= "";
        $goods_goods_category = new GoodsCategoryModel();
        $category_info = $goods_goods_category->getInfo(["id"=>$category_id],"*");
        $level = $category_info["level"];
        $nav_name = array();
        if(!empty($category_info))
        {
            $category_name = $category_info["category_name"];
            if($level == 3){
                $parent_category_info = $goods_goods_category->getInfo(["id"=>$category_info["pid"]],"*");
                
                if(!empty($parent_category_info)){
                    $grandparent_category_info =  $goods_goods_category->getInfo(["id"=>$parent_category_info["pid"]],"*");
                    
                }
                $nav_name = array($grandparent_category_info, $parent_category_info, $category_info);
            }else if($level == 2){
                $parent_category_info = $goods_goods_category->getInfo(["id"=>$category_info["pid"]],"*");
                $nav_name = array($parent_category_info, $category_info);
            }else{
                $nav_name = array($category_info);
            }
            
        }
        return $nav_name;
    }
    
    /**
     * 得到上级的分类组合
     * @param  $category_id
     */
    public function getParentCategory($category_id){
        $category_ids=$category_id;
        $category_names="";
        $pid=0;
        $goods_category = new GoodsCategoryModel();
        $category_obj=$goods_category->get($category_id);
        if(!empty($category_obj)){
            $category_names=$category_obj["category_name"];
            $pid=$category_obj["pid"];
            while ($pid!=0) {
                $goods_category = new GoodsCategoryModel();
                $category_obj=$goods_category->get($pid);
                if(!empty($category_obj)){
                    $category_ids=$category_ids.",".$pid;
                    $category_name=$category_obj["category_name"];
                    $category_names=$category_names.",".$category_name;
                    $pid=$category_obj["pid"];
                }else{
                    $pid=0;
                }
            }
        }
        $category_id_str=explode(",", $category_ids);
        $category_names_str=explode(",", $category_names);
        $category_result_ids="";
        $category_result_names="";
        for($i=count($category_id_str);$i>=0; $i--){
            if($category_result_ids==""){
                $category_result_ids=$category_id_str[$i];
            }else{
                $category_result_ids=$category_result_ids.",".$category_id_str[$i];
            }
        }
        for($i=count($category_names_str);$i>=0; $i--){
            if($category_result_names==""){
                $category_result_names=$category_names_str[$i];
            }else{
                $category_result_names=$category_result_names.":".$category_names_str[$i];
            }
        }
        $parent_Category=array(
            "category_ids"=>$category_result_ids,
            "category_names"=>$category_result_names
        );
        
        return $parent_Category;
    }
}
    
    
    
    

