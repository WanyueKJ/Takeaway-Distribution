<?php

namespace App\Api;

use App\ApiException;
use PhalApi\Api;
use App\Domain\MerchantStoreCart as MerchantStoreCartDomain;
use App\Domain\City as CityDomain;

/**
 * (新-1)购物车
 */
class MerchantStoreCart extends Api
{
    public function getRules()
    {
        return array(
            'save' => array(
                'product_id' => array('name' => 'product_id', 'type' => 'string', 'desc' => '商品ID'),
                'cart_num' => array('name' => 'cart_num', 'type' => 'string', 'desc' => '商品数量,累加,负值为减,'),
                'product_attr_id' => array('name' => 'product_attr_id', 'type' => 'string', 'desc' => '商品规格id 类似美食选多规格传0'),
                'more_product_attr_id' => array('name' => 'more_product_attr_id', 'type' => 'string', 'desc' => '商品多规格id (选中的美食多规格json:[1,2,3])'),
            ),
            'delete' => array(),
            'update' => array(),
            'index' => array(
                'store_id' => array('name' => 'store_id', 'type' => 'string', 'desc' => '店铺ID'),
            ),
            'getPresetTime' => array(
                'store_id' => array('name' => 'store_id', 'type' => 'string', 'desc' => '店铺ID'),
                'address_id' => array('name' => 'address_id', 'type' => 'string', 'desc' => '用户收货地址'),
                'target_lng' => array('name' => 'target_lng', 'type' => 'string', 'desc' => '目的地经度'),
                'target_lat' => array('name' => 'target_lat', 'type' => 'string', 'desc' => '目的地纬度'),
            ),
            'computed' => array(
                'order_key' => array('name' => 'order_key', 'type' => 'string', 'desc' => '临时订单key'),
                'type' => array('name' => 'type', 'type' => 'string', 'desc' => '取货形式 1:外卖配送 2:到店自提'),
                'address_id' => array('name' => 'address_id', 'type' => 'string', 'desc' => '外卖配送 地址id'),

                'coupon_id' => array('name' => 'coupon_id', 'type' => 'string', 'desc' => '使用的优惠券id'),

                'pickup_id' => array('name' => 'pickup_id', 'type' => 'string', 'desc' => '到店自提 自提点id'),
                'pickup_date' => array('name' => 'pickup_date', 'type' => 'string', 'desc' => '到店自提 自提时间'),
                'pickup_phone' => array('name' => 'pickup_phone', 'type' => 'string', 'desc' => '到店自提 预留电话'),
            ),

            'confirm' => array(
                'store_id' => array('name' => 'store_id', 'type' => 'string', 'desc' => '店铺ID'),

                'cart_id' => array('name' => 'cart_id', 'type' => 'string', 'desc' => '购物车id ["1","2"]'),

                'type' => array('name' => 'type', 'type' => 'string', 'desc' => '取货形式 1:外卖配送 2:到店自提'),
                'address_id' => array('name' => 'address_id', 'type' => 'string', 'desc' => '外卖配送 地址id'),
                'scheduled_time' => array('name' => 'scheduled_time', 'type' => 'string', 'desc' => '约定送达时间'),

                'coupon_id' => array('name' => 'coupon_id', 'type' => 'string', 'desc' => '使用的优惠券id'),

                'pickup_id' => array('name' => 'pickup_id', 'type' => 'string', 'desc' => '到店自提 自提点id'),
                'pickup_date' => array('name' => 'pickup_date', 'type' => 'string', 'desc' => '自提时间'),
                'pickup_phone' => array('name' => 'pickup_phone', 'type' => 'string', 'desc' => '到店自提 预留电话'),
                'serve_id' => array('name' => 'serve_id', 'type' => 'string', 'desc' => '其他服务说明id'),
            ),
            'getServeList' => array(
                'store_id' => array('name' => 'store_id', 'type' => 'string', 'desc' => '店铺ID'),
            ),
            'getCount' => array(
                'product_id' => array('name' => 'product_id', 'type' => 'string', 'desc' => '商品ID'),
                'product_attr_id' => array('name' => 'product_attr_id', 'type' => 'string', 'desc' => '商品规格id 类似美食选多规格传0'),
                'more_product_attr_id' => array('name' => 'more_product_attr_id', 'type' => 'string', 'desc' => '商品多规格id (选中的美食多规格json:[1,2,3])'),
            ),
        );
    }


    /**
     * 获取购物车中商品的数量
     * @return void
     */
    public function getCount()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $this->checkLogin($uid, $token);
        $product_id = \App\checkNull($this->product_id);
        $product_attr_id = \App\checkNull($this->product_attr_id);
        if ($product_attr_id == '') $product_attr_id = 0;
        $more_product_attr_id = \App\checkNull($this->more_product_attr_id);
        $more_product_attr_id = !is_array($more_product_attr_id) ? (json_decode($more_product_attr_id, true) ?? []) : [];
        $MerchantStoreCartDomain = new MerchantStoreCartDomain();
        $res = $MerchantStoreCartDomain->getStatistics(['uid = ?' => $uid, 'product_id = ?' => $product_id, 'product_attr_id' => $product_attr_id, 'more_product_attr_id = ?' => json_encode($more_product_attr_id)]);
        $rs['info'] = $res;
        return $rs;
    }

    protected function checkLogin($uid, $token)
    {
        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            throw new ApiException(\PhalApi\T('您的登陆状态失效，请重新登陆！'), 700);
        }
    }

    /**
     * 订单确认
     * @desc 订单准备
     * @return int code 操作码，0表示成功
     * @return array info
     * @return array info.order_key 临时订单号(用此单号 计算价格 以及支付 有过期时间)
     * @return string msg 提示信息
     */
    public function confirm()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }
        $store_id = \App\checkNull($this->store_id);
        $cart_id = \App\checkNull($this->cart_id);
        $type = \App\checkNull($this->type);
        $address_id = \App\checkNull($this->address_id);
        $scheduled_time = \App\checkNull($this->scheduled_time);
        $coupon_id = \App\checkNull($this->coupon_id);
        $pickup_id = \App\checkNull($this->pickup_id);
        $pickup_date = \App\checkNull($this->pickup_date);
        $pickup_phone = \App\checkNull($this->pickup_phone);
        $serve_id = \App\checkNull($this->serve_id);

//        $action = 'App.MerchantStoreCart.confirm';
//        $date = date('Y-m-d H:i:s');
//        file_put_contents('./log.txt', var_export(compact('action','date','uid','token', 'cart_id','type'), true) . PHP_EOL, FILE_APPEND);

        /** @var MerchantStoreCartDomain $MerchantStoreCartDomain */
        $MerchantStoreCartDomain = new MerchantStoreCartDomain();
        $res = $MerchantStoreCartDomain->confirm($uid, $store_id, $cart_id, $type, $address_id, $coupon_id, $pickup_id, $pickup_date, $pickup_phone, $serve_id, $scheduled_time);
        return $res;
    }

    /**
     * 购物车费用计算
     * @desc 用于购物车费用计算
     * @return int code 操作码，0表示成功
     * @return array info
     * @return array info.product[] 商品信息
     * @return array info.product.count 商品数量
     * @return array info.product.price 商品价格
     * @return array info.take_out[] 外卖配送费用
     * @return array info.take_out.money_basic 基础配送费
     * @return array info.take_out.money_basic_txt 基础配送距离
     * @return array info.take_out.money_distance 超出的配送费金额
     * @return array info.take_out_price 总共配送费
     * @return array info.product_price 商品价格
     * @return array info.pay_price 需要支付的价格
     * @return array info.order_price 订单金额(不包括优惠卷减免)
     * @return array info.up_to_send <=0时符合购买要求  >0时 即为距离起送还差多少钱
     *
     * @return array info.take_out.distance 总共配送距离
     * @return string msg 提示信息
     */
    public function computed()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }
        $order_key = \App\checkNull($this->order_key);
        $type = \App\checkNull($this->type);
        $address_id = \App\checkNull($this->address_id);
        $coupon_id = \App\checkNull($this->coupon_id);
        $pickup_id = \App\checkNull($this->pickup_id);
        $pickup_date = \App\checkNull($this->pickup_date);
        $pickup_phone = \App\checkNull($this->pickup_phone);

        $MerchantStoreCartDomain = new MerchantStoreCartDomain();
        $priceDetail = $MerchantStoreCartDomain->computed($uid, $order_key, $type, $address_id, $coupon_id, $pickup_id, $pickup_date, $pickup_phone);
        return $priceDetail;
    }

    /**
     * 获取店铺设置的其他服务说明
     * @desc 其他服务说明
     * @return int code 操作码，0表示成功
     * @return array info
     * @return array info.id
     * @return array info.use_name
     * @return string msg 提示信息
     */
    public function getServeList()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }
        $store_id = \App\checkNull($this->store_id);
        $MerchantStoreCartDomain = new MerchantStoreCartDomain();
        $res = $MerchantStoreCartDomain->getServeList($uid, $store_id);
        return $res;

    }


    /**
     * 获取预计配送时间(收货地址和目的地经纬度二选一)
     * @desc 用于购物车列表
     * @return int code 操作码，0表示成功
     * @return array info
     * @return array info.time 预计送达时间
     * @return string msg 提示信息
     */
    public function getPresetTime()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

        $store_id = \App\checkNull($this->store_id);
        $address_id = \App\checkNull($this->address_id);
        $target_lng = \App\checkNull($this->target_lng);
        $target_lat = \App\checkNull($this->target_lat);

        $CityDomain = new CityDomain();

        $res = $CityDomain->getPresetTime($uid, $store_id, $address_id, $target_lng, $target_lat);

        return $res;
    }


    /**
     * 购物车列表
     * @desc 用于购物车列表
     * @return int code 操作码，0表示成功
     * @return array info.product[] 商品信息
     * @return array info.attr[] 规格信息
     * @return array info.id 购物车id
     * @return array info.use_price 总价 = 商品价格+规格价格
     * @return array info.more_product_attr 美食规格信息
     * @return string msg 提示信息
     */
    public function index()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

        $store_id = \App\checkNull($this->store_id);
        $MerchantStoreCartDomain = new MerchantStoreCartDomain();
        $list = $MerchantStoreCartDomain->getCateCartList($uid, $store_id);
        return $list;
    }


    /**
     * 加购物车
     * @desc 用加入美食加入购物车
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function save()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $checkToken = \App\checkToken($uid, $token);
        // if ($checkToken == 700) {
        //     $rs['code'] = $checkToken;
        //     $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
        //     return $rs;
        // }

        $product_id = \App\checkNull($this->product_id);
        $cart_num = \App\checkNull($this->cart_num);
        $product_attr_id = \App\checkNull($this->product_attr_id);
        $more_product_attr_id = \App\checkNull($this->more_product_attr_id);

        $MerchantStoreCartDomain = new MerchantStoreCartDomain();
        $add = $MerchantStoreCartDomain->addCateCart($uid, $product_id, $cart_num, $product_attr_id, $more_product_attr_id);
        return $add;
    }
}
