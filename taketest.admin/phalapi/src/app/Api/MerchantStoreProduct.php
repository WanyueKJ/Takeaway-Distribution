<?php

namespace App\Api;

use PhalApi\Api;
use App\Domain\MerchantStoreProduct as MerchantStoreProductDomain;

/**
 * (新-1)美食-商品
 */
class MerchantStoreProduct extends Api
{
    public function getRules()
    {
        return array(
            'read' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => '美食ID'),
            )
        );
    }

    /**
     * 商品详情
     * @desc 用于获取美食商品详情
     * @return int code 操作码，0表示成功
     * @return array info
     * @return array info.use_name 商品名
     * @return array info.use_price 商品价格
     * @return array info.store 商品所属店铺信息
     * @return array info.store.name 店铺名
     * @return array info.cart_num 已加入购物车的数量
     * @return array info.monthly_sales 月销
     * @return array info.attr 美食商品规格
     * @return array info.attr.use_attr_name 美食商品规格名
     * @return array info.attr.use_price 美食商品规格价格
     * @return string msg 提示信息
     */
    public function read()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $id = \App\checkNull($this->id);
        $MerchantStoreProductDomain = new MerchantStoreProductDomain();
        $detail = $MerchantStoreProductDomain->getProductDetail($uid, $id);
        return $detail;
    }
}