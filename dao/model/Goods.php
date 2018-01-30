<?php
namespace dao\model;

/**
 * GoodsCategory.php
 * 商品分类模型
 * @author :wjt
 * @date : 2017.08.01
 * @version : v1.0
 */
class Goods extends BaseModel
{
    protected $name = "goods";
    
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    protected $autoWriteTimestamp = true;
    
    protected $insert = [
       // 'status' => 1,
    ];
    
    /**
     * 添加或者更新商品的基本信息
     * @param unknown $param
     */
    public function addOrUpdateGoodsBasic($params ) {
        
        if (empty($params)) {
            $this->error = '参数为空';
            return false;
        }
            
       // $title = $param['title'];
        if (empty($params['title'])) {
            $this->error = '商品名称不能为空';
            return false;
        }
        
        // 验证֤
        /*
         $validate = validate($this->name);
         if (!$validate->check($params)) {
         $this->error = $validate->getError();
         return false;
         }
         */
            
       // $goodsId = $param['goods_id'];
        $goodsId = 0;
        if (isset( $params['goods_id'])) {
            $goodsId = $params['goods_id'];
        }
        
        if (empty($goodsId)) {
            $goodsId = 0;
        }
            
        try {
            $allowFields = ['sort','title','sub_title','short_title','keywords','unit','pcate',
                'ccate','tcate','status','type','thumb','thumb_url','thumb_first','product_price',
                'market_price','cost_price','sales','is_new','is_hot','is_recommand','is_send_free',
                'is_no_discount','cash','dispatch_type','dispatch_id','dispatch_price','invoice',
                'quality','repair','seven', 'province','city','groups_type','auto_receive','can_no_trefund',
                'create_time','update_time'];
                
        
           
            //保存商品基本信息
         //   $this->startTrans();
            if ($goodsId == 0) { //新增
              //  $data_goods['create_time'] = time();
               // $data_goods['update_time'] = time();
                $result = $this->allowField($allowFields)->save($params);
                
                if ($result > 0) {
                    $res = true;
                } else {
                    $this->error ="新增时数据库操作失败!";
                    $res = false;
                }
                
            } else {
               // $data_goods['update_time'] = time();
                $result = $this->allowField($allowFields)->save($params, [
                    'id' => $goodsId
                ]);
                
                if ($result !== false) {
                    $res = true;
                } else {  //更新
                    $this->error ="更新时数据库操作失败!";
                    $res = false;
                }
            }
         //   $this->goods->commit();
            return $res;
        } catch (\Exception $e) {
         //   $this->goods->rollback();
            $this->error = '操作时出现异常，操作失败!'.$e->getMessage();
            return false;
        }
       
    }
    
    
}

