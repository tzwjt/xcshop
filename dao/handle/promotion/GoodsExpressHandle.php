<?php
/**
 * GoodsExpress.php
 *  商品邮费操作
 * -ok
 * @date : 2017.08.17
 * @version : v1.0
 */

namespace dao\handle\promotion;

use dao\handle\ConfigHandle;
use dao\handle\GoodsHandle;
use dao\model\Goods as GoodsModel;
use dao\model\GoodsSku as GoodsSkuModel;
//use dao\model\OrderShippingFeeExtendModel;
use dao\handle\BaseHandle;
use dao\model\OrderShippingFee as OrderShippingFeeModel;
use dao\model\ExpressCompany as OrderExpressCompanyModel;
use dao\model\City as CityModel;
use dao\model\District as DistrictModel;
use think\Log;
//use data\service\Config;


class GoodsExpressHandle extends BaseHandle
{


    function __construct()
    {
        parent::__construct();
    }

    /**
     * ***************************************************************************************订单运费管理开始**************************************************
     */
    /**
     * 获取商品邮费总和
     */
    public function getSkuListExpressFee($goods_sku_list, $express_company_id, $province, $city, $district)
    {
        $config = new ConfigHandle();
        // 查询用户是否选择物流
        $is_able_select = $config->getConfig(0, 'ORDER_IS_LOGISTICS');
        if (! empty($is_able_select)) {
            $is_able = $is_able_select['value'];
        } else {
            $is_able = 0;
        }
        if ($is_able == 1) {
            $fee = $this->getSameExpressSkuListFee($goods_sku_list, $express_company_id, $province, $city, $district);
            return $fee;
        } else {
            $company_list = $this->getGoodsSkuExpressGroup($goods_sku_list);
            if (! empty($company_list)) {
                $fee = 0;
                foreach ($company_list as $k => $v) {
                    if (! empty($v['shipping_sku_list'])) {
                        $same_fee = $this->getSameExpressSkuListFee($v['shipping_sku_list'], $v['id'], $province, $city, $district);
                        if ($same_fee >= 0) {
                            $fee += $same_fee;
                        } else {
                            return null;  //NULL_EXPRESS_FEE;
                        }
                    }
                }
                return $fee;
            } else {
                return null; //NULL_EXPRESS_FEE;
            }
        }
    }

    /**
     * ok-2ok
     * 获取商品邮费总和-NEW-NEW
     */
    public function getGoodsListExpressFee($goods_list, $express_company_id, $province, $city, $district,$user_id)
    {
        $config = new ConfigHandle();
        // 查询用户是否选择物流
        $is_able_select = $config->getConfig(0, 'ORDER_IS_LOGISTICS');
        if (! empty($is_able_select)) {
            $is_able = $is_able_select['value'];
        } else {
            $is_able = 0;
        }
        if ($is_able == 1) {
            $fee = $this->getSameExpressGoodsListFee($goods_list, $express_company_id, $province, $city, $district, $user_id);
            return $fee;
        } else {
            Log::write("group before:".$goods_list);
            $company_list = $this->getGoodsExpressGroup($goods_list);
            Log::write("group after:".$goods_list);
            $goods_handle = new GoodsHandle();
            if (! empty($company_list)) {
                $fee = 0;
                foreach ($company_list as $k => $v) {
                    if (! empty($v['shipping_goods_list'])) {
                        Log::write("shipping_goods_list:".$v['shipping_goods_list']);
                        //getSameExpressGoodsListFee($goods_list, $express_company_id, $province, $city, $district, $user_id)
                        $same_fee = $this->getSameExpressGoodsListFee($v['shipping_goods_list'], $v['id'], $province, $city, $district, $user_id);
                        if ($same_fee >= 0) {
                            $fee += $same_fee;
                        } else {
                            /** 固定运费 */
                          //  return $goods_handle->getFixedShippingFeeByGoodsList($goods_list);
                           return null;  //NULL_EXPRESS_FEE;
                        }
                    }
                }
                return $fee;
            } else {
               return null; //NULL_EXPRESS_FEE;
                /** 固定运费 */
               // return $goods_handle->getFixedShippingFeeByGoodsList($goods_list);
            }
        }
    }

    /**ok-2ok
     * 获取相同运费模板运费情况
     */
    public function getSameExpressSkuListFee($goods_sku_list, $express_company_id, $province, $city, $district)
    {
        $fee = 0;
        if (! empty($goods_sku_list)) {
            $shipping_template = $this->getShippingFeeTemplate($express_company_id, $province, $city, $district);
            if ($shipping_template == null) {   //NULL_EXPRESS_FEE) {
                // 当前地址不支持配送
                return null; //NULL_EXPRESS_FEE;
            }

            $goods_express_list_array = $this->getSkuGroup($goods_sku_list);
            // 计算称重方式运费
            $weight_shipping_fee = $this->getWeightShippingExpressFee($shipping_template, $goods_express_list_array['weight_shipping']);
            if ($weight_shipping_fee < 0) {
               return $weight_shipping_fee;
            } else {
                $fee += $weight_shipping_fee;
            }
            // 计算体积方式运费
            $volume_shipping_fee = $this->getVolumeShippingExpressFee($shipping_template, $goods_express_list_array['volume_shipping']);
            if ($volume_shipping_fee < 0) {
                return $volume_shipping_fee;
            } else {
                // var_dump("体积运费".$volume_shipping_fee);
                $fee += $volume_shipping_fee;
            }
            // 计件方式计算运费
            $bynum_shipping_fee = $this->getBynumShippingExpressFee($shipping_template, $goods_express_list_array['bynum_shipping']);
            if ($bynum_shipping_fee < 0) {
                return $bynum_shipping_fee;
            } else {
                $fee += $bynum_shipping_fee;
            }
            return $fee;
        } else {
            return $fee;
        }
    }

    /**ok-2ok
     * 获取相同运费模板运费情况-New
     */
    public function getSameExpressGoodsListFee($goods_list, $express_company_id, $province, $city, $district, $user_id)
    {
        $fee = 0;
        if (! empty($goods_list)) {
            $shipping_template = $this->getShippingFeeTemplate($express_company_id, $province, $city, $district);
            if ($shipping_template == null) {   //NULL_EXPRESS_FEE) {
                // 当前地址不支持配送
                return null; //NULL_EXPRESS_FEE;
            }

            $goods_express_list_array = $this->getGoodsGroup($goods_list,$user_id);
            // 计算称重方式运费
            $weight_shipping_fee = $this->getWeightShippingExpressFee($shipping_template, $goods_express_list_array['weight_shipping']);
            if ($weight_shipping_fee < 0) {
                return $weight_shipping_fee;
               // return 0;
            } else {
                $fee += $weight_shipping_fee;
            }
            // 计算体积方式运费
            $volume_shipping_fee = $this->getVolumeShippingExpressFee($shipping_template, $goods_express_list_array['volume_shipping']);
            if ($volume_shipping_fee < 0) {
                return $volume_shipping_fee;
               // return 0;
            } else {
                // var_dump("体积运费".$volume_shipping_fee);
                $fee += $volume_shipping_fee;
            }
            // 计件方式计算运费
            $bynum_shipping_fee = $this->getBynumShippingExpressFee($shipping_template, $goods_express_list_array['bynum_shipping']);
            if ($bynum_shipping_fee < 0) {
                return $bynum_shipping_fee;
               // return 0;
            } else {
                $fee += $bynum_shipping_fee;
            }
            return $fee;
        } else {
            return $fee;
        }
    }

    /**
     * ok-2okkk
     * 获取相同运费模板运费情况
     */
    public function getSameExpressListFee($goods_list, $express_company_id, $province, $city, $district,$user_id)
    {
        $fee = 0;
        if (! empty($goods_list)) {
            $shipping_template = $this->getShippingFeeTemplate($express_company_id, $province, $city, $district);
            if ($shipping_template == null) {   //NULL_EXPRESS_FEE) {
                // 当前地址不支持配送
                return null; //NULL_EXPRESS_FEE;
            }

            $goods_express_list_array = $this->getGoodsGroup($goods_list,$user_id);
          //  getGoodsGroup($goods_list,$user_id)
            // 计算称重方式运费
            $weight_shipping_fee = $this->getWeightShippingExpressFee($shipping_template, $goods_express_list_array['weight_shipping']);
            if ($weight_shipping_fee < 0) {
                return $weight_shipping_fee;
            } else {
                $fee += $weight_shipping_fee;
            }
            // 计算体积方式运费
            $volume_shipping_fee = $this->getVolumeShippingExpressFee($shipping_template, $goods_express_list_array['volume_shipping']);
            if ($volume_shipping_fee < 0) {
                return $volume_shipping_fee;
            } else {
                // var_dump("体积运费".$volume_shipping_fee);
                $fee += $volume_shipping_fee;
            }
            // 计件方式计算运费
            $bynum_shipping_fee = $this->getBynumShippingExpressFee($shipping_template, $goods_express_list_array['bynum_shipping']);
            if ($bynum_shipping_fee < 0) {
                return $bynum_shipping_fee;
            } else {
                $fee += $bynum_shipping_fee;
            }
            return $fee;
        } else {
            return $fee;
        }
    }

    /**
     * 根据地址获取运费模板-ok-2okok
     */
    private function getShippingFeeTemplate($express_company_id, $province, $city, $district = 0)
    {
        $shipping_fee = new OrderShippingFeeModel();
        $fee_array = $shipping_fee->getConditionQuery([
            'co_id' => $express_company_id
        ], '*', '');
        $district_model = new DistrictModel();
        // 检测城市是否有区概念
        $count = $district_model->getCount([
            'city_id' => $city
        ]);
        $temp = array();
        $default = array();
        foreach ($fee_array as $k => $v) {
            if ($v['is_default'] == 1) {
                $default = $v;
            }
            if ($count == 0) {

                if (! empty($v['city_id_array'])) {
                    $city_array = explode(',', $v['city_id_array']);
                    if (in_array($city, $city_array)) {
                        $temp = $v;
                    }
                }
            } else {
                $district_array = explode(',', $v['district_id_array']);
                if (in_array($district, $district_array)) {
                    $temp = $v;
                }
            }
        }
        // 如果模板为空，找到默认模板
        if (empty($temp)) {
            if (! empty($default)) {
                $temp = $default;
                return $temp;
            } else {
                return null;// 返回表示该地址不支持配送
                   // NULL_EXPRESS_FEE; // 返回表示该地址不支持配送
            }
        } else {
            return $temp;
        }
    }

    /**
     * 商品进行邮费分组(考虑满减送活动)
     *ok-2ok
     * @param  $goods_sku_list
     *            skuid:1,skuid:2,skuid:3
     */
    private function getSkuGroup($goods_sku_list)
    {
        // 分离商品
        $goods_sku_list_array = explode(",", $goods_sku_list);
        // 获取商品列表满减送活动,方便查询是否满邮情况
        $goods_mansong = new GoodsMansongHandle();
        $mansong_goods_sku_array = $goods_mansong->getFreeExpressGoodsSkuList($goods_sku_list);
        // 获取整体数据
        $goods_express_list_array = array();
        // 获取免运费商品列表
        $free_express_list = array();
        // 获取非免费商品列表
        // 按照重量计算运费
        $goods_sku_weight_array = array();
        // 按照体积计算运费
        $goods_sku_volume_array = array();
        // 按照计算方式计算运费
        $goods_sku_bynum_array = array();
        foreach ($goods_sku_list_array as $k => $goods_sku_array) {
            $goods_sku = explode(':', $goods_sku_array);
            $goods_sku_model = new GoodsSkuModel();
            $goods_id = $goods_sku_model->getInfo([
                'id' => $goods_sku[0]
            ], 'goods_id');
            $goods = new GoodsModel();
            $shipping_fee = $goods->getInfo([
                'id' => $goods_id['goods_id']
            ], 'dispatch_price, is_send_free, weight,volume ,shipping_fee_type');
            if ($shipping_fee['is_send_free'] == 1) {
                $free_express_list[] = $goods_sku_array;
            } else {
                if (in_array($goods_sku[0], $mansong_goods_sku_array)) {
                    $free_express_list[] = $goods_sku_array;
                } else {
                    $shipping_array = array(
                        'goods_sku_id' => $goods_sku[0],
                        'goods_sku_num' => $goods_sku[1],
                        'goods_id' => $goods_id['goods_id'],
                        'goods_weight' => $shipping_fee['weight'],
                        'goods_volume' => $shipping_fee['volume']
                    );
                    // var_dump($shipping_array);
                    if ($shipping_fee['shipping_fee_type'] == 1) {
                        // 按照计件方式计算运费
                        $goods_sku_bynum_array[] = $shipping_array;

                    } elseif ($shipping_fee['shipping_fee_type'] == 2) {
                        // 按照重量计算运费
                        $goods_sku_weight_array[] = $shipping_array;

                    } elseif ($shipping_fee['shipping_fee_type'] == 3) {
                        // 按照体积计算运费
                        $goods_sku_volume_array[] = $shipping_array;
                    }
                }
            }
        }
        $goods_express_list_array = array(
            'free_shipping' => $free_express_list,
            'weight_shipping' => $goods_sku_weight_array,
            'volume_shipping' => $goods_sku_volume_array,
            'bynum_shipping' => $goods_sku_bynum_array
        );
        return $goods_express_list_array;
    }

    /**
     * ok-2ok
     * 商品进行邮费分组(考虑满减送活动)
     *
     */
    private function getGoodsGroup($goods_list,$user_id)
    {
        // 分离商品
        $goods_list_array = explode(",", $goods_list);
        // 获取商品列表满减送活动,方便查询是否满邮情况
        $goods_mansong = new GoodsMansongHandle();
        $mansong_goods_array = $goods_mansong->getFreeExpressGoodsList($user_id, $goods_list);
      //  getFreeExpressGoodsList($user_id, $goods_list)
        // 获取整体数据
        $goods_express_list_array = array();
        // 获取免运费商品列表
        $free_express_list = array();
        // 获取非免费商品列表
        // 按照重量计算运费
        $goods_weight_array = array();
        // 按照体积计算运费
        $goods_volume_array = array();
        // 按照计算方式计算运费
        $goods_bynum_array = array();
        foreach ($goods_list_array as $k => $goods_array) {
            $goods = explode(':', $goods_array);
            /*
            $goods_sku_model = new GoodsSkuModel();
            $goods_id = $goods_sku_model->getInfo([
                'id' => $goods_sku[0]
            ], 'goods_id');
            */
            $goods_id =$goods[0];
            $goods_model = new GoodsModel();
            $shipping_fee = $goods_model->getInfo([
                'id' => $goods_id
            ], 'dispatch_price, is_send_free, weight,volume ,shipping_fee_type');
            if ($shipping_fee['is_send_free'] == 1) {
                $free_express_list[] = $goods_array;
            } else {
                if (in_array($goods[0], $mansong_goods_array)) {
                    $free_express_list[] = $goods_array;
                } else {
                    $shipping_array = array(
                        'goods_id' => $goods[0],
                        'goods_sku_id' => $goods[1],
                        'goods_sku_num' => $goods[2],
                        'goods_weight' => $shipping_fee['weight'],
                        'goods_volume' => $shipping_fee['volume']
                    );
                    // var_dump($shipping_array);
                    if ($shipping_fee['shipping_fee_type'] == 1) {
                        // 按照计件方式计算运费
                        $goods_bynum_array[] = $shipping_array;

                    } elseif ($shipping_fee['shipping_fee_type'] == 2) {
                        // 按照重量计算运费
                        $goods_weight_array[] = $shipping_array;

                    } elseif ($shipping_fee['shipping_fee_type'] == 3) {
                        // 按照体积计算运费
                        $goods_volume_array[] = $shipping_array;
                    }
                }
            }
        }
        $goods_express_list_array = array(
            'free_shipping' => $free_express_list,
            'weight_shipping' => $goods_weight_array,
            'volume_shipping' => $goods_volume_array,
            'bynum_shipping' => $goods_bynum_array
        );
        return $goods_express_list_array;
    }

    /**
     * 计算称重方式运费总和
     *ok-2ok
     * @param  $temp
     *            //运费模板
     * @param  $goods_sku_weight_array
     *
     */
    private function getWeightShippingExpressFee($temp, $goods_sku_weight_array)
    {
        if (empty($goods_sku_weight_array)) {
            return 0;
        }
        if ($temp['weight_is_use'] == 0) {
            // 没有启用重量
            return   null; //NULL_EXPRESS_FEE;
        } else {
            $weight = 0;
            foreach ($goods_sku_weight_array as $k => $v) {
                // 计算总重量
                $weight += $v['goods_weight'] * $v['goods_sku_num'];
            }
            if ($weight > 0) {
                if ($weight <= $temp['weight_snum']) {
                    return $temp['weight_sprice'];
                } else {
                    $ext_weight = $weight - $temp['weight_snum'];
                    if ($temp['weight_xnum'] == 0) {
                        $temp['weight_xnum'] = 1;
                    }
                    if (($ext_weight * 100) % ($temp['weight_xnum'] * 100) == 0) {
                        $ext_data = $ext_weight / $temp['weight_xnum'];
                    } else {
                        $ext_data = floor($ext_weight / $temp['weight_xnum']) + 1;
                    }
                    return $temp['weight_sprice'] + $ext_data * $temp['weight_xprice'];
                }
            } else {
                return 0;
            }
        }
    }

    /**ok-2ok
     * 计算体积方式运费总和
     *
     *
     */
    private function getVolumeShippingExpressFee($temp, $goods_sku_volume_array)
    {
        if (empty($goods_sku_volume_array)) {
            return 0;
        }

        if ($temp['volume_is_use'] == 0) {
            // 没有启用体积
            return null; //NULL_EXPRESS_FEE;
        } else {
            $volume = 0;
            foreach ($goods_sku_volume_array as $k => $v) {
                // 计算总重量
                $volume += $v['goods_volume'] * $v['goods_sku_num'];
            }
            if ($volume > 0) {

                if ($volume <= $temp['volume_snum']) {
                    return $temp['volume_sprice'];
                } else {
                    $ext_volume = $volume - $temp['volume_snum'];
                    if ($temp['volume_xnum'] == 0) {
                        $temp['volume_xnum'] = 1;
                    }
                    if (($ext_volume * 100) % ($temp['volume_xnum'] * 100) == 0) {
                        $ext_data = $ext_volume / $temp['volume_xnum'];
                    } else {
                        $ext_data = floor($ext_volume / $temp['volume_xnum']) + 1;
                    }

                    return $temp['volume_sprice'] + $ext_data * $temp['volume_xprice'];
                }
            } else {
                return 0;
            }
        }
    }

    /**
     * ok-2ok
     * 计算计件方式运费总和
     *
     * @param  $temp
     *            //运费模板
     * @param  $goods_sku_bynum_array
     *
     */
    private function getBynumShippingExpressFee($temp, $goods_sku_bynum_array)
    {
        if (empty($goods_sku_bynum_array)) {
            return 0;
        }
        if ($temp['bynum_is_use'] == 0) {
            // 没有启用计件
            return null; //NULL_EXPRESS_FEE;
        } else {
            $num = 0;
            foreach ($goods_sku_bynum_array as $k => $v) {
                // 计算总数量
                $num += $v['goods_sku_num'];
            }
            if ($num > 0) {
                if ($num <= $temp['bynum_snum']) {
                    return $temp['bynum_sprice'];
                } else {
                    $ext_num = $num - $temp['bynum_snum'];
                    if ($temp['bynum_xnum'] == 0) {
                        $temp['bynum_xnum'] = 1;
                    }
                    if ($ext_num % $temp['bynum_xnum'] == 0) {
                        $ext_data = $ext_num / $temp['bynum_xnum'];
                    } else {
                        $ext_data = floor($ext_num / $temp['bynum_xnum']) + 1;
                    }

                    return $temp['bynum_sprice'] + $ext_data * $temp['bynum_xprice'];
                }
            } else {
                return 0;
            }
        }
    }

    /**
     * 获取商品运费模板名称
     *
     * @param  $shipping_fee_id
     */
    public function getGoodsExpressName($id)
    {
        $name = '';
        $shipping_fee = new OrderShippingFeeExtendModel();
        $shipping_fee_info = $shipping_fee->getInfo([
            'id' => $id
        ], 'snum,sprice,xnum,xprice');
        if (! empty($shipping_fee_info)) {
            $name = $shipping_fee_info['snum'] . '件以下' . $shipping_fee_info['sprice'] . '元,' . '超过每' . $shipping_fee_info['xnum'] . '件' . $shipping_fee_info['xprice'] . '元';
        }

        return $name;
    }

    /**
     * 获取商品运费-ok-2ok
     */
    public function getGoodsExpressTemplate($goods_id, $province_id, $city_id, $district_id, $user_id)
    {
        $goods = new GoodsModel();
        $shipping_fee = $goods->getInfo([
            'id' => $goods_id
        ], 'is_send_free, dispatch_id, dispatch_price');
        if ($shipping_fee['is_send_free'] == 1) {
            return "免邮";
        } else {
            $goods_sku_model = new GoodsSkuModel();
            $goods_sku = $goods_sku_model->getConditionQuery([
                'goods_id' => $goods_id
            ], 'id', '');
         //   getExpressCompanyByGoods($goods_list, $province_id, $city_id, $district_id)
         //   $express_company_list = $this->getExpressCompanyByGoods( $goods_sku[0]['sku_id'] . ':1', $province_id, $city_id, $district_id);
            $express_company_list = $this->getExpressCompanyByGoods($goods_id.':0' . ':1', $province_id, $city_id, $district_id, $user_id);

            $config = new ConfigHandle();
            $is_able_select = $config->getConfig(0, 'ORDER_IS_LOGISTICS');
            if (! empty($is_able_select)) {
                $is_able = $is_able_select['value'];
            } else {
                $is_able = 0;
            }
            $is_able = 0;
            if ($is_able == 1) {
                return $express_company_list;
            } else {
                // 如果禁用选择物流公司查询默认或者第一条，只显示运费即可
                if (! empty($express_company_list)) {
                   // return "￥" . $express_company_list[0]['express_fee'];
                    return $express_company_list[0]['express_fee'] .' 元';
                } else {
                  //  if (!empty($shipping_fee['dispatch_price'])) {
                      //  return "￥" .$shipping_fee['dispatch_price'];
                       // 固定运费
                        return $shipping_fee['dispatch_price'] .' 元';
                  //  }

                }
            }
        }
    }

    /**
     * 查询可用物流公司
     *
     * @param  $province_id
     * @param  $city_id
     */
    public function getExpressCompany($shop_id, $goods_sku_list, $province_id, $city_id, $district_id)
    {
        $express_company_model = new OrderExpressCompanyModel();
        // 查询设置如果禁用只查询默认或者第一条
        $config = new ConfigHandle();
        // 查询用户是否选择物流
        $is_able_select = $config->getConfig(0, 'ORDER_IS_LOGISTICS');
        if (! empty($is_able_select)) {
            $is_able = $is_able_select['value'];
        } else {
            $is_able = 0;
        }
        if ($is_able == 1) {
            $list = $express_company_model->getConditionQuery([
                'is_enabled' => 1
            ], 'id,company_name,is_default', 'orders desc, id desc');
        } else {
            $list = $express_company_model->getConditionQuery([
                'is_enabled' => 1,
                'is_default' => 1
            ], 'id,company_name,is_default', 'orders');
            if (empty($list)) {
                $new_list = $express_company_model->getFirstData([
                    'is_enabled' => 1
                ], 'orders');
                if(!empty($new_list)){
                    $list = array();
                    $list[0] = $new_list;
                }
            }
        }

        if (! empty($list)) {
            $new_list = array();
            foreach ($list as $k => $v) {
                $express_fee = $this->getSkuListExpressFee($goods_sku_list, $v['co_id'], $province_id, $city_id, $district_id);
                if ($express_fee >= 0) {
                    $new_list[] = array(
                        'co_id' => $v['co_id'],
                        'company_name' => $v['company_name'],
                        'is_default' => $v['is_default'],
                        'express_fee' => $express_fee
                    );
                }
            }
            return $new_list;
        } else {
            return '';
        }
    }

    /**
     * 查询可用物流公司-OK-2OK
     * -NEW
     */
    public function getExpressCompanyByGoods($goods_list, $province_id, $city_id, $district_id, $user_id)
    {
        $express_company_model = new OrderExpressCompanyModel();
        // 查询设置如果禁用只查询默认或者第一条
        $config = new ConfigHandle();
        // 查询用户是否选择物流
        $is_able_select = $config->getConfig(0, 'ORDER_IS_LOGISTICS');
        if (! empty($is_able_select)) {
            $is_able = $is_able_select['value'];
        } else {
            $is_able = 0;
        }
        if ($is_able == 1) {
            $list = $express_company_model->getConditionQuery([
                'is_enabled' => 1
            ], 'id,company_name,is_default', 'orders desc, id desc');
        } else {
            $list = $express_company_model->getConditionQuery([
                'is_enabled' => 1,
                'is_default' => 1
            ], 'id,company_name,is_default', 'orders');
            if (empty($list)) {
                $new_list = $express_company_model->getFirstData([
                    'is_enabled' => 1
               ],
                    'orders');

                if(!empty($new_list)){
                    $list = array();
                    $list[0] = $new_list;
                }
            }
        }

        if (! empty($list)) {
            $new_list = array();
            foreach ($list as $k => $v) {
                $express_fee = $this->getGoodsListExpressFee($goods_list, $v['id'], $province_id, $city_id, $district_id, $user_id);
                if ($express_fee >= 0) {
                    $new_list[] = array(
                        'co_id' => $v['id'],
                        'company_name' => $v['company_name'],
                        'is_default' => $v['is_default'],
                        'express_fee' => $express_fee
                    );
                }
            }
            return $new_list;
        } else {
            return '';
        }
    }

    /**
     * 获取店铺所有物流公司
     *
     * @param  $shop_id
     */
    public function getAllExpressCompany()
    {
        $express_company_model = new OrderExpressCompanyModel();
        $list = $express_company_model->getConditionQuery([
           // 'shop_id' => $shop_id
        ], '*', '');
        return $list;
    }

    /**
     * ok-2ok
     * 获取店铺物流公司
     *
     * @param  $shop_id
     * @return 
     */
    public function getExpressCompanyCount()
    {
        $express_company_model = new OrderExpressCompanyModel();
        $count = $express_company_model->getCount([
          //  'shop_id' => $shop_id
        ]);
        if (empty($count)) {
            $count = 0;
        }
        return $count;
    }















    /**
     * ok-2ok-3ok
     * 商品邮费的sku分组
     */
    public function getGoodsSkuExpressGroup($goods_sku_list)
    {
        // 分离商品
        $goods_sku_list_array = explode(",", $goods_sku_list);
        // 获取默认物流公司
        $express_company_model = new OrderExpressCompanyModel();
        $express_company_list = $express_company_model->getConditionQuery([
            'is_enabled' => 1,
        ], '*', '');
        if (! empty($express_company_list)) {
            $default_company = $express_company_model->getInfo([
                'is_default' => 1,
                'is_enabled' => 1,
            ]);
            if (empty($default_company)) {
                $default_company = $express_company_list[0];
            }
            if (! empty($express_company_list)) {
                foreach ($express_company_list as $k_company => $v) {
                    $sku_list = '';
                    foreach ($goods_sku_list_array as $k => $goods_sku_array) {
                        $goods_sku = explode(':', $goods_sku_array);
                        $goods_sku_model = new GoodsSkuModel();
                        $goods_id = $goods_sku_model->getInfo([
                            'id' => $goods_sku[0]
                        ], 'goods_id');
                        $goods = new GoodsModel();
                        $shipping_fee = $goods->getInfo([
                            'id' => $goods_id['goods_id']
                        ], 'is_send_free, dispatch_price, dispatch_id');
                        if ($shipping_fee['is_send_free'] == 0) {
                            if ($shipping_fee['dispatch_id'] == 0) {
                                // 商品未设置物流公司
                                if ($v['id'] == $default_company['id']) {
                                    $sku_list = $sku_list . $goods_sku_array . ',';
                                }
                            } else {
                             //   $order_shipping_fee_model = new OrderShippingFeeModel();
                             //   $order_shipping_fee = $order_shipping_fee_model->get($shipping_fee['dispatch_id']);
                                if ($v['id'] == $shipping_fee['dispatch_id']) {
                               // if ($v['id'] == $order_shipping_fee['co_id']) {
                                    $sku_list = $sku_list . $goods_sku_array . ',';
                                }
                            }
                        }
                    }
                    $express_company_list[$k_company]['shipping_sku_list'] = $sku_list;
                }
            }
            return $express_company_list;
        } else {
            return '';
        }
    }

    /**
     * ok-2ok-3ok
     * 商品邮费的goods分组
     */
    public function getGoodsExpressGroup($goods_list)
    {
        // 分离商品
        $goods_list_array = explode(",", $goods_list);
        // 获取默认物流公司
        $express_company_model = new OrderExpressCompanyModel();
        $express_company_list = $express_company_model->getConditionQuery([
            'is_enabled' => 1,
        ], '*', '');
        if (! empty($express_company_list)) {
            $default_company = $express_company_model->getInfo([
                'is_default' => 1,
                'is_enabled' => 1,
            ]);
            if (empty($default_company)) {
                $default_company = $express_company_list[0];
            }
            if (! empty($express_company_list)) {
                foreach ($express_company_list as $k_company => $v) {
                    $goods_list = '';
                    foreach ($goods_list_array as $k => $goods_array) {
                        $goods = explode(':', $goods_array);

                        $goods_id = $goods[0];
                        $goods = new GoodsModel();
                        $shipping_fee = $goods->getInfo([
                            'id' => $goods_id
                        ], 'is_send_free, dispatch_price, dispatch_id');
                        if ($shipping_fee['is_send_free'] == 0) {
                            if ($shipping_fee['dispatch_id'] == 0) {
                                // 商品未设置物流公司
                                if ($v['id'] == $default_company['id']) {
                                    $goods_list = $goods_list . $goods_array . ',';
                                }
                            } else {
                              //  $order_shipping_fee_model = new OrderShippingFeeModel();
                              //  $order_shipping_fee = $order_shipping_fee_model->get($shipping_fee['dispatch_id']);
                                if ($v['id'] == $shipping_fee['dispatch_id']) {
                               // if ($v['id'] == $order_shipping_fee['co_id']) {
                                    $goods_list = $goods_list . $goods_array . ',';
                                }
                            }
                        }
                    }
                    if (!empty($goods_list) && substr($goods_list,-1,1) == ',') {
                        $goods_list = substr($goods_list, 0, strlen($goods_list) - 1);
                    }
                    $express_company_list[$k_company]['shipping_goods_list'] = $goods_list;
                }
            }
            return $express_company_list;
        } else {
            return '';
        }
    }
}