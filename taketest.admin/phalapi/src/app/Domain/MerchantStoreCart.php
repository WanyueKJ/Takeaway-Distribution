<?php

namespace App\Domain;

use App\ApiException;
use App\Domain\MerchantStoreCart as MerchantStoreCartDomain;
use App\Domain\MerchantType as MerchantTypeDomain;
use App\Model\MerchantStore as MerchantStoreModel;
use App\Domain\MerchantStore as MerchantStoreDomain;
use App\Domain\Helpsend as HelpsendDomain;
use App\Model\MerchantStoreCart as MerchantStoreCartModel;
use App\Model\MerchantStorePickup as MerchantStorePickupModel;
use App\Model\MerchantStoreProduct as MerchantStoreProductModel;
use App\Model\MerchantStoreProductAttr as MerchantStoreProductAttrModel;
use App\Model\MerchantStoreServe as MerchantStoreServeModel;
use App\Model\MerchantStoreOrderCartInfo as MerchantStoreOrderCartInfoModel;
use Rider\Domain\City as Domain_City;

/**
 * 购物车
 */
class MerchantStoreCart
{


    /**
     * 订单生成后 清理购物车
     * @param $cartId
     * @param $orderId
     * @return void
     */
    public function settingUpShoppingCart($cartId, $orderId)
    {
        $MerchantStoreCartModel = new MerchantStoreCartModel();
        $MerchantStoreProductModel = new MerchantStoreProductModel();
        $MerchantStoreOrderCartInfoModel = new MerchantStoreOrderCartInfoModel();
        $MerchantStoreCartDomain = new MerchantStoreCartDomain();


        $cartList = $this->inIdselectList($cartId, [], '*');
        foreach ($cartList as $key => $value) {
            $cartInfo = [];
            $cartInfo['product'] = $MerchantStoreProductModel->getOne(['id = ?' => $value['product_id']]);
            $cartInfo['product_attr'] = $MerchantStoreCartDomain->getAttr($value['product_attr_id'], $cartInfo['product'], $value['cart_num']);;
            $cartInfo['cart'] = $MerchantStoreCartModel->getOne(['id = ?' => $value['id']]);
            $more_product_attr_id = is_array($value['more_product_attr_id']) ? json_encode($value['more_product_attr_id']) : $value['more_product_attr_id'];
            $cartInfo['more_product_attr'] = $this->getCateAttrList(json_decode($more_product_attr_id, true) ?? [], $cartInfo['product'], $value['cart_num']);
            $tmp = [
                'cart_id' => $value['id'],
                'product_id' => $value['product_id'],
                'product_attr_id' => $value['product_attr_id'],
                'more_product_attr_id' => $more_product_attr_id,
                'cart_info' => json_encode($cartInfo),
                'cart_num' => $value['cart_num'],
                'store_id' => $value['store_id'],
                'oid' => $orderId,
            ];
            $addId = $MerchantStoreOrderCartInfoModel->saveOne($tmp);
            if ($addId) {
                $MerchantStoreCartModel->deleteOne(['id = ?' => $value['id']]);
            }

        }
    }

    /**
     * 获取店铺设置的其他服务说明
     * @param ...$param
     * @return array
     */
    public function getServeList(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        [$uid, $store_id] = $param;
        $MerchantStoreServeModel = new MerchantStoreServeModel();
        $list = $MerchantStoreServeModel->selectList(['store_id = ?' => $store_id]);

        $rs['info'][] = $list;
        return $rs;
    }

    /**
     * 订单确认
     * @param ...$param
     * @return array|null
     */
    public function confirm(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        [$uid, $store_id, $cart_id, $type, $address_id, $coupon_id, $pickup_id, $pickup_date, $pickup_phone, $serve_id, $scheduled_time] = $param;
        if (!in_array($type, [1, 2, 3])) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('类型错误!');
            return $rs;
        }

        $cartIdArr = json_decode($cart_id, true) ?? [];
        if (!is_array($cartIdArr) || (count($cartIdArr) <= 0)) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('购物车参数错误!');
            return $rs;
        }
        $MerchantStoreModel = new MerchantStoreModel();
        $storeInfo = $MerchantStoreModel->getOne(['id = ?' => $store_id], 'id,operating_state,name,th_name,free_shipping,free_shipping,type_id,top_type_id');
        if (!$storeInfo) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误!');
            return $rs;
        }

        if ($type == 1) {//外卖配送
            $AddrDomain = new Addr();
            $address = $AddrDomain->getInfo(['id = ?' => $address_id, 'uid = ?' => $uid], 'id,lng,lat,place,addr,name,mobile');
            if (!$address) {
                $rs['code'] = 995;
                $rs['msg'] = \PhalApi\T('地址信息错误!');
                return $rs;
            }
            if (!$scheduled_time) {
                $rs['code'] = 995;
                $rs['msg'] = \PhalApi\T('暂无合适时间配送!');
                return $rs;
            }
        }

        if ($serve_id > 0) {
            $MerchantStoreServe = new MerchantStoreServeModel();
            $serveInfo = $MerchantStoreServe->getOne(['id = ?' => $serve_id, 'store_id = ?' => $store_id]);
            if (!$serveInfo) {
                $rs['code'] = 995;
                $rs['msg'] = \PhalApi\T('其他服务类型选择错误!');
                return $rs;
            }
        }

        if ($type == 2) {//到店自提
            $MerchantStorePickupModel = new MerchantStorePickupModel();
            $pickup = $MerchantStorePickupModel->getOne(['id = ?' => $pickup_id, 'store_id = ?' => $store_id]);
            if (!$pickup) {
                $rs['code'] = 995;
                $rs['msg'] = \PhalApi\T('自提点位置错误!');
                return $rs;
            }
            if (!$pickup_date) {
                $rs['code'] = 995;
                $rs['msg'] = \PhalApi\T('请输入自提时间!');
                return $rs;
            }

            $pickup['pickup_date'] = date("Y-m-d H:i:s", (int)$pickup_date);
            $pickup['pickup_phone'] = $pickup_phone;

        }

        if ($storeInfo['top_type_id'] == 3) {
            $type = 3;
        }

        $price = $this->getStatistics(['store_id = ?' => $store_id, 'uid = ?' => $uid], $cartIdArr);
        $cartProductList = $this->inIdselectList($cartIdArr, ['store_id = ?' => $store_id, 'uid = ?' => $uid], 'id,product_id,cart_num,product_attr_id');

        if (count($cartProductList) <= 0) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('您购物车里没有商品!');
            return $rs;
        }
        $orderKey = (int)(strtotime(date('YmdHis', time()))) . (int)substr(microtime(), 2, 6) . sprintf('%03d', rand(0, 999));
        $orderComfirm = [
            'cart_list' => $cartProductList,
            'store' => $storeInfo,
            'coupon' =>  [],
            'address' => $address ?? [],
            'type' => $type,
            'pickup' => $pickup ?? [],
            'order_key' => $orderKey,
            'scheduled_time' => date('Y-m-d H:i:s', (int)$scheduled_time),
            'serve' => [
                'serve_id' => (int)$serve_id
            ],
        ];
        \App\setcaches($orderKey, $orderComfirm, 60 * 60);
        $rs['info'][] = $orderComfirm;
        return $rs;
    }

    /**
     * 获取购物车费用计算
     * @param ...$param
     * @return void
     */
    public function computed(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        [$uid, $order_key, $type, $address_id, $couponId, $pickup_id, $pickup_date, $pickup_phone] = $param;
        $orderConfirm = \App\getcaches($order_key);
        if (!$orderConfirm) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('订单已过期,请刷新页面!');
            return $rs;
        }
        if ($type && !in_array($type, [1, 2, 3])) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('提货类型错误');
            return $rs;
        }

        if (!$address_id) {
            $address_id = $orderConfirm['address']['id'];
        }
        if (!$couponId) {
            $couponId = $orderConfirm['coupon']['id'] ?? 0;
        }
        if (!$pickup_id) {
            $pickup_id = $orderConfirm['pickup']['id'] ?? 0;
        }
        if (!$pickup_date) {
            $pickup_date = $orderConfirm['pickup']['pickup_date'] ?? '';
        }
        if (!$pickup_phone) {
            $pickup_phone = $orderConfirm['pickup']['pickup_phone'] ?? '';
        }
        if (!$type) {
            $type = $orderConfirm['type'];
        }

        $store_id = $orderConfirm['store']['id'];

        $MerchantStoreModel = new MerchantStoreModel();
        $storeInfo = $MerchantStoreModel->getOne(['id = ?' => $store_id], 'id,operating_state,name,th_name,free_shipping,free_shipping,up_to_send,top_type_id');
        if (!$storeInfo) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误');
            return $rs;
        }
        if ($storeInfo['operating_state'] != 1) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺已打样');
            return $rs;
        }

        //外卖配送费用
        $take_out_price = 0;
        if ($type == 1) {//外卖配送
            $AddrDomain = new Addr();
            $address = $AddrDomain->getInfo(['id = ?' => $address_id, 'uid = ?' => $uid], 'id,lng,lat,place,addr,name,mobile');
            if (!$address) {
                $rs['code'] = 995;
                $rs['msg'] = \PhalApi\T('地址信息错误');
                return $rs;
            }
            $orderConfirm['address'] = $address;
            \App\setcaches($order_key, $orderConfirm, 60 * 60);

            $HelpsendDomain = new HelpsendDomain();
            $takeOut = $HelpsendDomain->deliveryAndDistribution($uid, $store_id, $address_id);
            $Domain_City = new Domain_City();
            $timemoney = $Domain_City->checkTime(1, 6, strtotime($orderConfirm['scheduled_time']) ?? time());

            if ($timemoney < 0) {
                throw new ApiException(\PhalApi\T('时间不在骑手服务范围内'), 600);
            }
            if ($takeOut['code'] != 0) {
                return $takeOut;
            }
            $take_out_price = bcadd($takeOut['info'][0]['money_basic'], $takeOut['info'][0]['money_distance'], 2);
            $take_out_price = bcadd($take_out_price, $timemoney, 2);
        }
        $freight = $take_out_price;//所需运费

        if ($type == 2) {//上门自取
            $MerchantStorePickupModel = new MerchantStorePickupModel();
            $pickup = $MerchantStorePickupModel->getOne(['id = ?' => $pickup_id, 'store_id = ?' => $store_id]);
            if (!$pickup) {
                $rs['code'] = 995;
                $rs['msg'] = \PhalApi\T('自提点位置错误!');
                return $rs;
            }
            if (!$pickup_date) {
                $rs['code'] = 995;
                $rs['msg'] = \PhalApi\T('请输入自提时间!');
                return $rs;
            }

            $pickup['pickup_date'] = $pickup_date;
            $pickup['pickup_phone'] = $pickup_phone;
            $orderConfirm['pickup'] = $pickup;
            \App\setcaches($order_key, $orderConfirm);
        }

        if ($type) {
            $orderConfirm['type'] = $type;
        }

        \App\setcaches($order_key, $orderConfirm, 60 * 60);
        //购物车购买商品费用
        $statistics = $this->getStatistics(['store_id = ?' => $store_id, 'uid = ?' => $uid], array_column($orderConfirm['cart_list'], 'id'));

        $free_shipping = 0;//是否免运费
        $pay_price = 0;

        if (($storeInfo['free_shipping'] != 0) && $statistics['price'] >= $storeInfo['free_shipping']) {//免运费
            $take_out_price = \PhalApi\T('免运费');
            $free_shipping = 1;
        } else {//不免运费
            $pay_price = bcadd($pay_price, $take_out_price, 2);
        }
        $orderConfirm['free_shipping'] = $free_shipping;
        \App\setcaches($order_key, $orderConfirm, 60 * 60);

        $pay_price = bcadd($pay_price, $statistics['price'], 2);//加商品金额
        $order_price = $pay_price;


        //距离起送相差的价格( <= 0为达到要求)
        $up_to_send = 0;
        if ($storeInfo['up_to_send'] > 0 && in_array($storeInfo['top_type_id'], [1, 5, 6, 7])) {
            $up_to_send = bcsub($order_price, $storeInfo['up_to_send'], 2);
        }
        $up_to_send_staus = $order_price <=> $storeInfo['up_to_send'];
        if ($up_to_send_staus >= 0) {
            $up_to_send = 0;
        }

        $price = [
            'product' => $statistics,
            'coupon' => ['price' =>  0],
            'take_out' => $takeOut['info'][0] ?? '0',
            'take_out_price' => $take_out_price,

            'coupon_price' => abs( 0),
            'freight' => $freight,
            'product_price' => $statistics['price'],
            'pay_price' => $pay_price,
            'order_price' => $order_price,
            'free_shipping' => $free_shipping,
            'up_to_send' => abs($up_to_send),
        ];
        $rs['info'][] = $price;

        return $rs;

    }

    /**
     * 美食 获取购物车数据
     * @param ...$param
     * @return void
     */
    public function getCateCartList(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        [$uid, $store_id] = $param;

        $MerchantStoreModel = new MerchantStoreModel();
        $store = $MerchantStoreModel->getOne(['id = ?' => $store_id]);
        if (!$store) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误!');
            return $rs;
        }
        $MerchantStoreCartModel = new MerchantStoreCartModel();
        $MerchantStoreProductModel = new MerchantStoreProductModel();
        $MerchantStoreProductAttrModel = new MerchantStoreProductAttrModel();

        $list = $MerchantStoreCartModel->selectList(['store_id = ?' => $store_id, 'uid = ?' => $uid], 'id,product_attr_id,product_id,cart_num,status,status_des');
        foreach ($list as $value2) {//检测购物车商品
            $product = $MerchantStoreProductModel->getOne(['id = ?' => $value2['product_id']], 'id,name,th_name,price,image');
            if (!$product) {
                $this->checkProduct($value2['product_id']);
            }
            $productAttr = $MerchantStoreProductAttrModel->getOne(['id = ?' => $value2['product_attr_id']]);
            if (!$productAttr) {
                $this->checkProduct($value2['product_id']);
            }
        }

        $list = $MerchantStoreCartModel->selectList(['store_id = ?' => $store_id, 'uid = ?' => $uid], 'id,product_attr_id,product_id,cart_num,status,status_des,more_product_attr_id');
        foreach ($list as $key => &$value) {
            $value['product_attr_id'] = json_decode($value['product_attr_id'], true);
            $product = $MerchantStoreProductModel->getOne(['id = ?' => $value['product_id']], 'id,name,th_name,price,image');

            $newImageArr = [];
            $imageArr = json_decode($product['image'] ?? [], true);
            array_walk($imageArr, function ($value, $index) use (&$newImageArr) {
                array_push($newImageArr, \App\get_upload_path($value));
            });
            $product['image'] = $newImageArr[0] ?? '';
            $value['use_name'] = $product['use_name'];
            $value['product'] = $product;

            $value = array_merge($value, $this->getAttr($value['product_attr_id'], $product, $value['cart_num']));
            $value['more_product_attr'] = $this->getCateAttrList(!is_array($value['more_product_attr_id']) ? json_decode($value['more_product_attr_id'], true) : [], $product, $value['cart_num']);
        }

        $rs['info'][] = $list;
        return $rs;
    }

    /**
     * 获取美食多规格信息
     * @param array $more_product_attr_id
     * @return array
     */
    public function getCateAttrList(array $more_product_attr_id, $product, $cart_num)
    {

        $list = [];
        foreach ($more_product_attr_id as $value) {
            $tmp = $this->getAttr($value, $product, $cart_num);
            if ($tmp) array_push($list, $tmp);
        }
        return $list;
    }

    /**
     * 获取商品规格
     * @param $product_attr_id 规格id
     * @param $product 商品信息
     * @param $cart_num 购物车价格
     * @return array
     */
    public function getAttr($product_attr_id, $product, $cart_num)
    {
        $MerchantStoreProductAttrModel = new MerchantStoreProductAttrModel();
        $productAttr = $MerchantStoreProductAttrModel->getOne(['id = ?' => $product_attr_id], 'id,product_id,attr_name,th_attr_name,price,th_price,is_main,pid');
        if ($productAttr && $productAttr['pid'] > 0) {
            $parentProductAttr = $MerchantStoreProductAttrModel->getOne(['id = ?' => $productAttr['pid']]);
        }
        $parentAttr = $parentProductAttr ?? [];
        $attr = $productAttr ?: [];
        $use_price = bcmul(bcadd($productAttr['use_price'] ?? 0, $product['use_price'], 2), $cart_num, 2);

        return compact('parentAttr', 'attr', 'use_price');
    }

    /**
     * 美食添加购物车
     * @param ...$param
     * @return void
     */
    public function addCateCart(...$param)
    {
        $rs = ['code' => 0, 'msg' => \PhalApi\T('添加成功!'), 'info' => []];

        [$uid, $product_id, $cart_num, $product_attr_id, $more_product_attr_id] = $param;
        if ((int)$cart_num == 0) {
            $cart_num = 1;
        }
        $product = $this->checkProductExist($product_id);
        $MerchantTypeDomain = new MerchantTypeDomain();
        $top_type_id = $MerchantTypeDomain->getTopTree($product['type_id'])['id'] ?? 0;
        if (!$top_type_id) throw new ApiException(\PhalApi\T('商品类型错误!'));

        $productMoreAttrIdArr = [];
        if ($top_type_id == 1) {
            //美食多选规格
            $productMoreAttrIdArr = !is_array($more_product_attr_id) ? json_decode($more_product_attr_id, true) ?? [] : $more_product_attr_id;
            if (count($productMoreAttrIdArr) > 0) {
                sort($productMoreAttrIdArr);
                foreach ($productMoreAttrIdArr as $value) {
                    $this->checkProductAttr($product_id, $value);
                }
                $this->checkCateProduct($product_id, $productMoreAttrIdArr);
            }
        }

        if (in_array($top_type_id, [5, 6, 7])) {
            //检查库存
            $this->checkRepertory($product_id, $product_attr_id, $uid, $cart_num, $product['repertory']);
        }

        //检查规格
        $this->checkProductAttr($product_id, $product_attr_id);
        $cartId = $this->updateCart($uid, $product_id, $product_attr_id, $productMoreAttrIdArr, $cart_num, $product['store_id']);//修改购物车数
        if (!$cartId) throw new ApiException(\PhalApi\T('添加失败!'));
        $statistics = $this->getStatistics(['store_id = ?' => $product['store_id'], 'uid = ?' => $uid]);

        $rs['info'][] = $statistics;

        return $rs;
    }


    /**
     * 检查商品你库存
     * @param $product_id
     * @param $product_attr_id
     * @param $uid
     * @param $cart_num
     * @param $repertory
     * @return void
     * @throws ApiException
     */
    public function checkRepertory($product_id, $product_attr_id, $uid, $cart_num, $repertory)
    {
        //检查库存
        $MerchantStoreCartModel = new MerchantStoreCartModel();
        $cartExist = $MerchantStoreCartModel->getOne(['product_id = ?' => $product_id, 'product_attr_id = ?' => $product_attr_id, 'uid = ?' => $uid], 'id,cart_num,store_id');
        if ($cartExist) {
            if (($cartExist['cart_num'] + $cart_num) > $repertory) throw new ApiException(\PhalApi\T('库存不足!'));
        } else {
            if ($cart_num > $repertory) throw new ApiException(\PhalApi\T('库存不足!'));
        }
    }

    /**
     * 检测商品是否存在
     * @param $productId
     * @return mixed
     */
    public function checkProductExist($productId)
    {
        $MerchantStoreProductModel = new MerchantStoreProductModel();
        $product = $MerchantStoreProductModel->getOne(['id = ?' => $productId], 'id,name,th_name,store_id,repertory,type_id');
        if (!$product) throw new ApiException(\PhalApi\T('商品不存在!'));
        return $product;
    }

    /**
     * 检测商品规格是否存在
     * @param $productId
     * @param $productAttrId
     * @return mixed
     */
    public function checkProductAttr($productId, $productAttrId)
    {
        if ($productAttrId <= 0) return true;
        $MerchantStoreProductAttrModel = new MerchantStoreProductAttrModel();
        $attr = $MerchantStoreProductAttrModel->getOne(['id = ?' => $productAttrId, 'product_id = ?' => $productId], 'product_id');
        if (!$attr) throw new ApiException(\PhalApi\T('规格不存在!'));
    }


    /**
     * 检测美食类每个规格是否最少选择一个
     * @param $productId
     * @param array $useAttr
     * @return void
     * @throws ApiException
     */
    public function checkCateProduct($productId, array $useAttr = [])
    {
        $MerchantStoreProductAttrModel = new MerchantStoreProductAttrModel();
        $list = $MerchantStoreProductAttrModel->getTreeAttr($productId);

        $children = [];
        foreach ($list as $value) {
            $count = count($children);
            $tmp = array_filter(array_column($value['children'], 'id'));
            if ($tmp) $children[$count] = $tmp;
        }

        foreach ($children as $value2) {
            $exist = 0;
            foreach ($useAttr as $v) {
                if (in_array($v, $value2)) {
                    $exist = 1;
                    break;
                }
            }
            if ($exist === 0) {
                throw new ApiException(\PhalApi\T("规格选择不全"));
            }
        }
    }

    /**
     * 修改购物车数量
     * @param $uid
     * @param $product_id
     * @param $cart_num
     * @param $store_id
     * @return int
     */
    public function updateCart($uid, $product_id, $product_attr_id, $productMoreAttrIdArr, $cart_num, $store_id)
    {
        $MerchantStoreCartModel = new MerchantStoreCartModel();

        $cartExist = $MerchantStoreCartModel->getOne(['product_id = ?' => $product_id, 'product_attr_id = ?' => $product_attr_id, 'uid = ?' => $uid, 'more_product_attr_id = ?' => json_encode($productMoreAttrIdArr)], 'id,cart_num,store_id');
        if ($cartExist) {//修改购物车数量
            if (((int)$cart_num + (int)$cartExist['cart_num']) <= 0) {
                $MerchantStoreCartModel->deleteOne(['id = ?' => $cartExist['id']]);
            } else {
                $update = [
                    'cart_num' => ((int)$cartExist['cart_num'] + (int)$cart_num),
                    'more_product_attr_id' => json_encode($productMoreAttrIdArr),
                ];
                $MerchantStoreCartModel->updateOne(['id = ?' => $cartExist['id']], $update);
            }
            return $cartExist['id'];

        } else {//新增购物车数量
            $insertData = [
                'product_id' => $product_id,
                'product_attr_id' => $product_attr_id,
                'more_product_attr_id' => json_encode($productMoreAttrIdArr),
                'uid' => $uid,
                'cart_num' => $cart_num,
                'addtime' => time(),
                'store_id' => $store_id,
            ];
            $save = $MerchantStoreCartModel->saveOne($insertData);
            return $save;
        }

    }

    /**
     * 订单再来一单 计算价格
     * @param $cartProductList
     * @return array
     */
    public function getAgainStatistics($cartProductList)
    {
        $MerchantStoreProductAttrModel = new MerchantStoreProductAttrModel();
        $price = 0;//商品价格
        $count = array_sum(array_column($cartProductList, 'cart_num'));
        $MerchantStoreProductModel = new MerchantStoreProductModel();
        array_walk($cartProductList, function ($value, $index) use ($MerchantStoreProductModel, $MerchantStoreProductAttrModel, &$price) {

            $unitPriceInfo = $MerchantStoreProductModel->getOne(['id = ?' => $value['product_id']], 'price');//商品价格
            $attrPriceInfo = $MerchantStoreProductAttrModel->getOne(['id = ?' => $value['product_attr_id']], 'price');//商品规格价格

            if ($unitPriceInfo) {
                $this->checkProduct($value['product_id']);
            }
            if (($value['product_attr_id'] > 0) && !$attrPriceInfo) {
                $this->checkProduct($value['product_id']);
            }
            $unitPrice = ($unitPriceInfo['use_price'] + ($attrPriceInfo['use_price'] ?? 0));
            $price = bcadd($price, bcmul($value['cart_num'], $unitPrice, 2), 2);
        });

        return compact('count', 'price');
    }

    /**
     * 统计购物车 数量和价格
     * @param $where 条件
     * @param $idArr in id
     * @return array
     */
    public function getStatistics($where, $idArr = [])
    {
        $MerchantStoreProductAttrModel = new MerchantStoreProductAttrModel();
        $MerchantStoreCartModel = new MerchantStoreCartModel();
        if (count($idArr) > 0) {
            $count = $MerchantStoreCartModel->inIdselectList($idArr, $where, 'sum(cart_num) as cart_num')[0]['cart_num'] ?? 0;//商品数量
            $cartProductList = $MerchantStoreCartModel->inIdselectList($idArr, $where, 'id,product_id,cart_num,product_attr_id');
        } else {
            $count = $MerchantStoreCartModel->getOne($where, 'sum(cart_num) as cart_num')['cart_num'] ?? 0;//商品数量
            $cartProductList = $MerchantStoreCartModel->selectList($where, 'id,product_id,cart_num,product_attr_id');
        }

        $price = 0;//商品价格
        $MerchantStoreProductModel = new MerchantStoreProductModel();
        array_walk($cartProductList, function ($value, $index) use ($MerchantStoreProductModel, $MerchantStoreProductAttrModel, &$price) {

            $unitPriceInfo = $MerchantStoreProductModel->getOne(['id = ?' => $value['product_id']], 'price');//商品价格
            $attrPriceInfo = $MerchantStoreProductAttrModel->getOne(['id = ?' => $value['product_attr_id']], 'price');//商品规格价格

            if (!$unitPriceInfo) {
                throw new ApiException(\PhalApi\T("商品不存在"));
            }
            $this->checkProduct($value['product_id']);

            if($value['product_attr_id'] > 0 && !$attrPriceInfo){
                throw new ApiException(\PhalApi\T("商品规格不存在"));
            }
            if (($value['product_attr_id'] > 0) && $attrPriceInfo) {
                $this->checkProduct($value['product_id']);
            }
            $unitPrice = ($unitPriceInfo['use_price'] + ($attrPriceInfo['use_price'] ?? 0));
            $price = bcadd($price, bcmul($value['cart_num'], $unitPrice, 2), 2);
        });

        return compact('count', 'price');
    }


    /**
     * 检测商品在购物车中的状态
     * @param $product_id
     * @return int|void
     */
    public function checkProduct($product_id)
    {
        $MerchantStoreProductModel = new MerchantStoreDomain();
        $storeOfType5 = $MerchantStoreProductModel->getTypeOfStore(5);//超市下的所有店铺
        $storeOfType5Id = array_column($storeOfType5, 'id');

        $MerchantStoreProductModel = new MerchantStoreProductModel();
        $MerchantStoreCartModel = new MerchantStoreCartModel();
        $cartInfo = $MerchantStoreCartModel->selectList(['product_id = ?' => $product_id]);
        if (!$cartInfo) {
            return 0;
        }
        foreach ($cartInfo as $key => $value) {
            $productInfo = $MerchantStoreProductModel->getOne2(['id = ?' => $value['product_id']], 'price,store_id,repertory,is_show');

            if (!$productInfo) {
                //商品已不存在
                $MerchantStoreCartModel->updateOne(['id = ?' => $value['id']], ['status' => 1, 'status_des' => '商品已被删除']);
            } else if (in_array($productInfo['store_id'], $storeOfType5Id)) {
                //超市商品
                if ($productInfo['repertory'] <= 0) {
                    $MerchantStoreCartModel->updateOne(['id = ?' => $value['id']], ['status' => 1, 'status_des' => '库存不足']);
                }
            } else if ($productInfo['is_show'] == 0) {
                //商品下架
                $MerchantStoreCartModel->updateOne(['id = ?' => $value['id']], ['status' => 1, 'status_des' => '商品下架']);
            }
        }

    }


    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {

        $MerchantStoreCartModel = new MerchantStoreCartModel();
        // TODO: Implement __call() method.
        return call_user_func_array([$MerchantStoreCartModel, $name], $arguments);
    }

}
