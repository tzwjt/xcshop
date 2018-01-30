<?php
/**
 *
 * Created by PhpStorm.
 * User: jhon
 * Date: 2018-01-18
 * Time: 11:44
 */

namespace dao\handle;

use dao\model\ShopWeixinShare as ShopWeixinShareModel;


class ShopHandle extends BaseHandle
{
    /**
     * ok-2ok
     * 修改店铺分享设置
     * @param $shop_id
     * @param $goods_param_1
     * @param $goods_param_2
     * @param $shop_param_1
     * @param $shop_param_2
     * @param $shop_param_3
     * @param $qrcode_param_1
     * @param $qrcode_param_2
     * @return bool
     */
    public function updateShopShareCinfig($shop_id, $goods_param_1, $goods_param_2, $shop_param_1, $shop_param_2, $shop_param_3, $qrcode_param_1, $qrcode_param_2)
    {
        $shop_share = new ShopWeixinShareModel();
        $data = array(
            'goods_param_1' => $goods_param_1,
            'goods_param_2' => $goods_param_2,
            'shop_param_1' => $shop_param_1,
            'shop_param_2' => $shop_param_2,
            'shop_param_3' => $shop_param_3,
            'qrcode_param_1' => $qrcode_param_1,
            'qrcode_param_2' => $qrcode_param_2
        );
        $retval = $shop_share->save($data, [
            'shop_id' => $shop_id
        ]);

        if ( $retval == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ok-2ok
     * 获取店铺分享设置
     * @param $shop_id
     * @return null|static
     */
    public function getShopShareConfig($shop_id)
    {
        $shop_share = new ShopWeixinShareModel();
        $count = $shop_share->getCount([
            'shop_id' => $shop_id
        ]);
        if ($count > 0) {
            $info = $shop_share->get(['shop_id' => $shop_id]);
        } else {
            $data = array(
                'shop_id' => $shop_id,
                'goods_param_1' => '优惠价：',
                'goods_param_2' => '全场正品',
                'shop_param_1' => '欢迎打开',
                'shop_param_2' => '分享赚佣金',
                'shop_param_3' => '',
                'qrcode_param_1' => '向您推荐',
                'qrcode_param_2' => '注册有优惠'
            );
            $shop_share->save($data);
            $info = $shop_share->get(['shop_id' => $shop_id]);
        }
        return $info;
    }


}