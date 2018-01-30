<?php
/**
 * GoodsCategory.php
 * 商品分类模型
 * @author :wjt
 * @date : 2017.08.01
 * @version : v1.0
 */
namespace dao\model;

class GoodsCategory extends BaseModel
{
    protected $name = "goods_category";
    
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $autoWriteTimestamp = true;

    protected $insert = [
        'status' => 1,
    ];  
    
    
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
        $list = $this->pageQuery($page_index, $page_size, $condition, $order, $field);
        return $list;
        
    }
    
    /**
     * 获取商品分类的下级分类
     * @param unknown $pid
     */
    public function getGoodsCategoryListByParentId($pid)
    {
        $list = $this->getGoodsCategoryList(1, 0, 'pid='.$pid, 'pid,sort');
        if(!empty($list)){
            for($i=0; $i<count($list['data']); $i++){
                $parent_id=$list['data'][$i]["id"];
                $child_list = $this->getGoodsCategoryList(1, 1, 'pid='.$parent_id, 'pid,sort');
                if(!empty($child_list) && $child_list['total_count']>0){
                    $list['data'][$i]["is_parent"]=1;
                }else{
                    $list['data'][$i]["is_parent"]=0;
                }
            }
        }
        return $list['data'];
    }
    
    /**
     * 获取格式化后的商品分类
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
     * @param unknown $category_id  添加时$category_id=0
     */
    public function addOrUpdateGoodsCategory($categoryId, $categoryName, $description, $thumb,$pid,
        $sort, $isRecommand, $isHome, $status,$advImg, $advUrl) {
          
         if (empty($categoryName)) {
              $this->error = '商品分类名不能为空';
              return false;
          }
          
          return true;

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
                 $result = $this->save($data);
                 if($result) {
                    $data['category_id'] = $this->id;
             //   hook("goodsCategorySaveSuccess", $data);
                    $res = true;
                }else{
                    //$res = $this->getError();
                    $this->error ="新增时数据库操作失败!";
                    $res = false;
                }
            
            } else {  //更新
                $result = $this->save($data,['id'=>$categoryId]);
                if($result !== false) {
                    $this->save(["level"=>$level+1], ["pid"=>$categoryId]);
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
            $this->error = '操作时出现异常，操作失败!';
            return false;
        }
    }
    
    /**
     *获取指定商品分类的详情
     * @param unknown $category_id
     */
    public function getGoodsCategoryDetail($category_id)
    {
        $res = $this->get($category_id);
        return $res;
    }
}

