<?php

/**
 * BaseModel.php
 * 基础模型,所有模型都可继承此模型，基于RESTFUL CRUD操作
 * @author :wjt
 * @date : 2017.08.01
 * @version : v1.0
 */

namespace dao\model;

use think\Model;
use think\Db;
use think\Validate;

class BaseModel extends Model 
{
    protected $name;

	protected $rule = [];

	protected $msg = [];

	protected $Validate;

	public function __construct($data = []){
		parent::__construct($data);
		$this->Validate = new Validate($this->rule, $this->msg);
		$this->Validate->extend('no_html_parse', function ($value, $rule) {
			return true;
		});
	}

	/**
	 * 获取空模型
	 */
	public function getEModel($tables)
	{
		$rs = Db::query('show columns FROM `' . config('database.prefix') . $tables . "`");
		$obj = [];
		if ($rs) {
			foreach ($rs as $key => $v) {
				$obj[$v['Field']] = $v['Default'];
				if ($v['Key'] == 'PRI')
					$obj[$v['Field']] = 0;
			}
		}
		return $obj;
	}

	public function save($data = [], $where = [], $sequence = null){
		$data = $this->htmlClear($data);
		$retval = parent::save($data, $where, $sequence);
		if(!empty($where))
		{
			//表示更新数据
			if($retval == 0)
			{
				if($retval !== false)
				{
					$retval = 1;
				}
			}
		}
//         $retval = ['code' => $code, 'message' => $this->getError()];
		return $retval;
	}

	public function ihtmlspecialchars($string) {
		if(is_array($string)) {
			foreach($string as $key => $val) {
				$string[$key] = $this->ihtmlspecialchars($val);
			}
		} else {
			$string = preg_replace('/&amp;((#(d{3,5}|x[a-fa-f0-9]{4})|[a-za-z][a-z0-9]{2,5});)/', '&\1',
				str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $string));
		}
		return $string;
	}

	protected function htmlClear($data){
		$rule =  $this->rule;
		$info = empty($rule) ? $this->Validate : $rule;
		foreach ($data as $k=>$v){
			if (!empty($info)) {
				if (is_array($info)) {
					$is_Specialchars=$this->is_Specialchars($info, $k);
					// 数据对象赋值
					if($is_Specialchars){
						$data[$k] = $this->ihtmlspecialchars($v);
					}else{
						$data[$k] = $v;
					}
//                     foreach ($rule as $key => $value) {
//                         if(strcasecmp($value,"no_html_parse")!= 0){
//                             $data[$k] = $this->ihtmlspecialchars($v);
//                         }else{
//                             $data[$k] = $v;
//                         }
//                     }
				} else {
					;
				}
			}
		}
		return $data;
	}

	/**
	 * 判断当前k 是否在数组的k值中
	 * @param  $rule
	 * @param  $k
	 */
	protected function is_Specialchars($rule, $k){
		$is_have=true;
		foreach ($rule as $key => $value) {
			if($key==$k){
				if(strcasecmp($value,"no_html_parse")!= 0){
					$is_have=true;
				}else{
					$is_have=false;
				}
			}
		}
		return $is_have;
	}


	/**
	 * getDataById 根据主键获取详情
	 * @param     string    $id [主键]
	 * @return    [array]                       
	 */
	public function getDataById($id = '')
	{
		$data = $this->get($id);
		if (!$data) {
			$this->error = '暂无此数据';
			return false;
		}
		return $data;
	}

	
	/**
	 * createData 新建
	 * @param     array     $param [description]
	 * @return    [array]   [description]
	 */
	public function createData($param)
	{
		
		// 验证
		$validate = validate($this->name);
		if (!$validate->check($param)) {
			$this->error = $validate->getError();
			return false;
		}
		try {
			$this->data($param)->allowField(true)->save();
			return true;
		} catch(\Exception $e) {
			$this->error = '新增失败';
			return false;
		}
	}

	/**
	 * updateDataById 更新
	 * @param     [type]      $param [description]
	 * @param     [type]      $id    [description]
	 * @return    [type]      [description]
	 */
	public function updateDataById($param, $id)
	{
		$checkData = $this->get($id);
		if (!$checkData) {
			$this->error = '暂无此数据';
			return false;
		}

		// 验证
		$validate = validate($this->name);
		if (!$validate->check($param)) {
			$this->error = $validate->getError();
			return false;
		}

		try {
			$this->allowField(true)->save($param, [$this->getPk() => $id]);
			return true;
		} catch(\Exception $e) {
			$this->error = '更新失败';
			return false;
		}
	}

	/**
	 * delDataById 根据id删除数据
	 * @param     string     $id     [主键]
	 * @param     boolean    $delSon [是否删除子孙数据]
	 * @return    [type]     [description]
	 */
	public function delDataById($id = '', $delSon = false)
	{

		$this->startTrans();
		try {
			$this->where($this->getPk(), $id)->delete();
			if ($delSon && is_numeric($id)) {
				// 删除子孙
				$childIds = $this->getAllChild($id);
				if($childIds){
					$this->where($this->getPk(), 'in', $childIds)->delete();
				}
			}
			$this->commit();
			return true;
		} catch(\Exception $e) {
			$this->error = '删除失败';
			$this->rollback();
			return false;
		}		
	}

	/**
	 * delDatas 批量删除数据
	 * @param     array       $ids    [主键数组]
	 * @param     boolean     $delSon [是否删除子孙数据]
	 * @return    [type]      [description]
	 */
	public function delDatas($ids = [], $delSon = false)
	{
		if (empty($ids)) {
			$this->error = '删除失败';
			return false;
		}
		
		// 查找所有子元素
		if ($delSon) {
			foreach ($ids as $k => $v) {
				if (!is_numeric($v)) continue;
				$childIds = $this->getAllChild($v);
				$ids = array_merge($ids, $childIds);
			}
			$ids = array_unique($ids);
		}

		try {
			$this->where($this->getPk(), 'in', $ids)->delete();
			return true;
		} catch (\Exception $e) {
			$this->error = '操作失败';
			return false;
		}		

	}

	/**
	 * enableDatas 批量启用、禁用
	 * @param     string     $ids    [主键数组]
	 * @param     integer    $status [状态1启用0禁用]
	 * @param     [boolean]  $delSon [是否删除子孙数组]
	 * @return    [type]     [description]
	 */
	public function enableDatas($ids = [], $status = 1, $delSon = false)
	{
		if (empty($ids)) {
			$this->error = '操作失败';
			return false;
		}

		// 查找所有子元素
		if ($delSon && $status === '0') {
			foreach ($ids as $k => $v) {
				$childIds = $this->getAllChild($v);
				$ids = array_merge($ids, $childIds);
			}
			$ids = array_unique($ids);
		}
		try {
			$this->where($this->getPk(),'in',$ids)->setField('status', $status);
			return true;
		} catch (\Exception $e) {
			$this->error = '操作失败';
			return false;
		}
	}

	/**
	 * 获取所有子孙
	 */
	public function getAllChild($id, &$data = [])
	{
		$map['pid'] = $id;
		$childIds = $this->where($map)->column($this->getPk());
		if (!empty($childIds)) {
			foreach ($childIds as $v) {
				$data[] = $v;
				$this->getAllChild($v, $data);
			}
		}
		return $data;
	}	
	
	/**
	 * 列表查询
	 *
	 * @param  $page_index
	 * @param number $page_size
	 * @param string $order
	 * @param string $where
	 * @param string $field
	 */
	public function pageQuery($page_index, $page_size, $condition, $order, $field)
	{
	    $count = $this->where($condition)->count();
	    if ($page_size == 0) {
	        $list = $this->field($field)
	        ->where($condition)
	        ->order($order)
	        ->select();
	        $page_count = 1;
	    } else {
	        $start_row = $page_size * ($page_index - 1);
	        $list = $this->field($field)
	        ->where($condition)
	        ->order($order)
	        ->limit($start_row . "," . $page_size)
	        ->select();
	        if ($count % $page_size == 0) {
	            $page_count = $count / $page_size;
	        } else {
	            $page_count = (int) ($count / $page_size) + 1;
	        }
	    }
	    return array(
	        'data' => $list,
	        'total_count' => $count,
	        'page_count' => $page_count
	    );
	}
	
	/**
	 * 获取一定条件下的列表
	 * @param  $condition
	 * @param  $field
	 */
	public function getConditionQuery($condition, $field, $order)
	{
	    $list = $this->field($field)->where($condition)->order($order)->select();
	    return $list;
	}
	
	/**
	 * 获取单条记录的基本信息
	 *
	 * @param  $condition
	 * @param string $field
	 */
	public function getInfo($condition = '', $field = '*')
	{
	    $info = Db::name($this->name)->where($condition)
	    ->field($field)
	    ->find();
	    return $info;
	}
	
	/**
	 * 查询数据的数量
	 * @param  $condition
	 * @return
	 */
	public function getCount($condition)
	{
	    
	    $count = $this->where($condition)->count();
	    return $count;
	}
	
	/**
	 * 查询条件数量
	 * @param  $condition
	 * @param  $field
	 * @return number|
	 */
	public function getSum($condition, $field)
	{
	    $sum = Db::name($this->name)->where($condition)
	    ->sum($field);
	    if(empty($sum))
	    {
	        return 0;
	    }else
	        return $sum;
	}
	
	/**
	 * 查询数据最大值
	 * @param  $condition
	 * @param  $field
	 * @return number|
	 */
	public function getMax($condition, $field)
	{
	    $max = Db::name($this->name)->where($condition)
	    ->max($field);
	    if(empty($max))
	    {
	        return 0;
	    }else
	        return $max;
	}
	
	/**
	 * 查询数据最小值
	 * @param  $condition
	 * @param  $field
	 * @return number|
	 */
	public function getMin($condition, $field)
	{
	    $min = Db::name($this->name)->where($condition)
	    ->min($field);
	    if(empty($min))
	    {
	        return 0;
	    }else
	        return $min;
	}
	
	/**
	 * 查询数据均值
	 * @param  $condition
	 * @param  $field
	 */
	public function getAvg($condition, $field)
	{
	    $avg = Db::name($this->name)->where($condition)
	    ->avg($field);
	    if(empty($avg))
	    {
	        return 0;
	    }else
	        return $avg;
	}
	
	/**
	 * ok-2ok
	 * 查询第一条数据
	 * @param  $condition
	 */
	public function getFirstData($condition, $order)
	{
	    $data = Db::name($this->name)->where($condition)->order($order)
	    ->limit(1)->select();

	    if(!empty($data))
	    {
	        return $data[0];
	    }else
	        return '';
	}
	
	/**
	 * 修改表单个字段值
	 * @param  $pk_id
	 * @param  $field_name
	 * @param  $field_value
	 */
	public function modifyTableField($pk_name, $pk_id, $field_name, $field_value)
	{
	    $data = array(
	        $field_name => $field_value
	    );
	    $res = $this->save($data,[$pk_name => $pk_id]);
	    return $res;
	}
	
	/**
	 * 获取关联查询列表
	 *
	 * @param  $viewObj
	 *            对应view对象
	 * @param  $page_index
	 * @param  $page_size
	 * @param  $condition
	 * @param  $order
	 * @return :number
	 */
	public function viewPageQuery($viewObj, $page_index, $page_size, $condition, $order)
	{
	    if ($page_size == 0) {
	        $list = $viewObj->where($condition)
	        ->order($order)
	        ->select();
	    } else {
	        $start_row = $page_size * ($page_index - 1);
	        
	        $list = $viewObj->where($condition)
	        ->order($order)
	        ->limit($start_row . "," . $page_size)
	        ->select();
	    }
	    return $list;
	}
	
	/**
	 * 获取关联查询数量
	 *
	 * @param  $viewObj
	 *            视图对象
	 * @param  $condition
	 *            下旬条件
	 * @return
	 */
	public function viewCount($viewObj, $condition)
	{
	    $count = $viewObj->where($condition)->count();
	    return $count;
	}
	
	
	/**
	 * 设置关联查询返回数据格式
	 *
	 * @param  $list
	 *            查询数据列表
	 * @param  $count
	 *            查询数据数量
	 * @param  $page_size
	 *            每页显示条数
	 * @return multitype: number
	 */
	public function setReturnList($list, $count, $page_size)
	{
	    if($page_size == 0)
	    {
	        $page_count = 1;
	    }else{
	        if ($count % $page_size == 0) {
	            $page_count = $count / $page_size;
	        } else {
	            $page_count = (int) ($count / $page_size) + 1;
	        }
	    }
	    return array(
	        'data' => $list,
	        'total_count' => $count,
	        'page_count' => $page_count
	    );
	}
	
}
