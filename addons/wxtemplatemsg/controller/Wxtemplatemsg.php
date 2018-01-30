<?php
/**
 * @date : 2018年1月13日
 * @version : v1.0.0.0
 */
namespace addons\wxtemplatemsg\controller;

use addons\wxtemplatemsg\Wxtemplatemsg as baseWxtemplatemsg;
use dao\extend\WchatOauth;

class Wxtemplatemsg extends baseWxtemplatemsg
{

    /**
     * ok-2ok
     * 获取模板id
     */
    public function getTemplateId()
    {
        $condition['template_id'] = '';
        $list = \think\Db::table("$this->table")->where($condition)->select();
        if (! empty($list)) {
            foreach ($list as $k => $v) {
                $template_id = $this->getTemplateIdByTemplateNo($v['template_no']);
                if ($template_id) {
                    \think\Db::table("$this->table")->where('id', $v['id'])->update([
                        'template_id' => $template_id
                    ]);
                }
            }
        }
        return json(resultArray(0, '操作成功',1));
      //  return AjaxReturn(1);
    }

    /**
     * ok-2ok
     * 设置模板消息是否启用
     */
    public function changeIsEnable()
    {
        $id = request()->post('id');
        $is_enable = request()->post('is_enable');
        $res = \think\Db::table("$this->table")->where([
            'id' => $id
        ])->update([
            'is_enable' => $is_enable
        ]);

        if ($res == 1) {
            return json(resultArray(0, '操作成功'));
        } else {
            return json(resultArray(2, '操作失败'));
        }
      //  return AjaxReturn($res);
    }

    /**
     * ok-2ok
     * 根据模板编号 获取 模板id
     */
    protected function getTemplateIdByTemplateNo($template_no)
    {
        $wchat = new WchatOauth();
        $json = $wchat->templateID($template_no);
        $array = json_decode($json, true);
        $template_id = '';
        if ($array) {
            $template_id = $array['template_id'];
        }
       // return json(resultArray(0, '操作成功'));
        return $template_id;
    }
}