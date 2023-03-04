<?php

namespace App\Domain;

use App\ApiException;
use App\Domain\City as CityDomain;
use App\Domain\Helpsend as Domain_Helpsend;
use App\Domain\MerchantStoreOrderEvaluate as MerchantStoreOrderEvaluateDomain;
use App\Domain\Pay as Domain_Pay;
use App\Domain\MerchantStore as MerchantStoreDomain;
use App\Model\MerchantStoreOrder as MerchantStoreOrderModel;
use App\Model\MerchantStoreOrderCartInfo as MerchantStoreOrderCartInfoModel;
use App\Domain\MerchantStoreProduct as MerchantStoreProductDomain;
use App\Domain\MerchantStoreCart as MerchantStoreCartDomain;
use App\Model\MerchantStoreProduct as MerchantStoreProductModel;
use App\Model\MerchantStoreProductAttr as MerchantStoreProductAttrModel;
use App\Model\MerchantType as MerchantTypeModel;
use App\Domain\Addr as AddrDomain;
use App\Model\Rider as RiderModel;
use App\Model\MerchantStorePickup as MerchantStorePickupModel;
use Rider\Domain\Orders as riderOrdersDomain;
use Rider\Domain\Ordersrefuse as Domain_Ordersrefuse;
use Rider\Model\Orders as Model_Orders;
use Rider\Domain\Orders as Domain_Orders;
use App\Model\User as UserModel;

/**
 * 店铺订单
 */

/**
 * @method getOne(array $where, string $field = "")
 * @method updateOne(array $where, array $updateData)
 */
class MerchantStoreOrder
{

    /**
     * 订单取消
     * @param $uid
     * @param $id
     * @return void
     */
    public function cancelOrder($uid, $id)
    {
        $rs = ['code' => 0, 'msg' => \PhalApi\T('取消成功'), 'info' => []];
        $where['id = ?'] = $id;

        $info = $this->getOne($where, 'id,status,orderno_pay,uid,verify_code,pay_type,is_verify,shipping_type,pay_price');
        if (!$info) {
            throw new ApiException(\PhalApi\T('订单不存在'));
        }
        if (!in_array($info['status'], [0])) {
            throw new ApiException(\PhalApi\T('当前订单无法取消'));
        }

        \PhalApi\DI()->notorm->beginTransaction('db_master');
        try {
            $update = [
                'status' => 7,
            ];
            $this->updateOne(['id = ?' => $info['id']], $update);
            $this->setRefundOrder($id, 10);

        } catch (\Exception $exception) {
            \PhalApi\DI()->notorm->rollback('db_master');
            $rs['code'] = 400;
            $rs['msg'] = \PhalApi\T($exception->getMessage());
            return $rs;
        }
        \PhalApi\DI()->notorm->commit('db_master');

        return $rs;
    }

    /**
     * 修改骑手订单状态
     * @param $store_order_id
     * @param $status
     * @return void
     */
    public function setRefundOrder($store_order_id, $status)
    {
        $Model_Orders = new Model_Orders();
        $order = $Model_Orders->getInfo(['store_oid = ?' => $store_order_id], 'id,status');
        if ($order) {
            $update = [
                'status' => $status,
                'refundtime' => time(),
            ];
            $Model_Orders->up(['id = ?' => $order['id']], $update);
        }
    }

    /**
     * 待支付订单支付
     * @param $uid
     * @param $id
     * @param $pay_type
     * @param $openid
     * @return void
     */
    public function orderPay($uid, $id, $pay_type, $openid)
    {
        $isExist = $this->getOne(['uid = ?' => $uid, 'id = ?' => $id]);
        if ($isExist) {
            if ($isExist['payid'] == 1) {
                throw new ApiException(\PhalApi\T('订单已支付!'));
            }
            if ($isExist['status'] != 0) {
                throw new ApiException(\PhalApi\T('订单不是待支付状态!'));
            }
        }
        $orderno_pay = 'store_' . (int)(strtotime(date('YmdHis', time()))) . (int)substr(microtime(), 2, 6) . sprintf('%03d', rand(0, 999));
        $updateData = [
            'orderno_pay' => $orderno_pay,
        ];
        if ($pay_type > 0) {
            $updateData['pay_type'] = $pay_type;
        }
        $where = [
            'id = ?' => $isExist['id'],
        ];


        \PhalApi\DI()->notorm->beginTransaction('db_master');
        try {
            $update = $this->updateOne($where, $updateData);
            if (!$update) {
                throw new ApiException(\PhalApi\T('支付异常请重试!'));
            }
            $pay = $this->storeOrderPay($orderno_pay, $isExist['pay_price'], $pay_type, '商品购买', $openid);

        } catch (\Exception $exception) {
            \PhalApi\DI()->notorm->rollback('db_master');
            $rs['code'] = 400;
            $rs['msg'] = \PhalApi\T($exception->getMessage());
            return $rs;
        }
        \PhalApi\DI()->notorm->commit('db_master');
        return $pay;
    }

    /**
     * 店铺支付成功回调 Ali APP
     * @param $data
     * @return void
     */
    public function AliAppStorePaySuccess($data)
    {
        $configpri = \App\getConfigPri();
        $alipay_config = array(
            //应用ID,您的APPID。
            'app_id' => $configpri['aliapp_appid'],
            //商户私钥
            'merchant_private_key' => $configpri['aliapp_key'],
            //编码格式
            'charset' => "UTF-8",
            //签名方式
            'sign_type' => "RSA2",
            //支付宝网关
            'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
            //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
            'alipay_public_key' => $configpri['aliapp_publickey'],
        );
        require_once(API_ROOT . "/../sdk/alipay/pagepay/service/AlipayTradeService.php");
        $alipaySevice = new \AlipayTradeService($alipay_config);
        $checkSign = $alipaySevice->check($data);
        if ($checkSign) {
            $orderno_pay = $data['out_trade_no'] ?? '';
            $orderInfo = $this->getOne(['orderno_pay = ?' => $orderno_pay, 'paid = ?' => 0]);
            if ($orderInfo) {
                $this->orderPayment($orderInfo['order_id']);
                $this->orderCreateSuccess($orderInfo['id']);
            }
        }

    }


    /**
     * 微信小程序
     * @param $data
     * @return void
     */
    public function wechatSmallStorePaySuccess($data)
    {
        $configpri = \App\getConfigPri();
        $key = $configpri['wx_key'];
        ksort($data);//按照字典排序参数数组
        $wxSign = $data['sign'];
        unset($data['sign']);

        $sign = $this->wechatSign($data, $key);//生成签名

        if ($this->wechatCheckSign($wxSign, $sign)) {
            $orderno_pay = $data['out_trade_no'] ?? '';
            $orderInfo = $this->getOne(['orderno_pay = ?' => $orderno_pay, 'paid = ?' => 0]);
            if ($orderInfo) {
                $this->orderPayment($orderInfo['order_id']);
                $this->orderCreateSuccess($orderInfo['id']);
            }
        }
    }

    /**
     * 店铺支付成功回调 微信 APP
     * @param $data
     * @return void
     */
    public function wechatAppStorePaySuccess($data)
    {
        $configpri = \App\getConfigPri();
        $key = $configpri['wx_key'];
        ksort($data);//按照字典排序参数数组
        $wxSign = $data['sign'];
        unset($data['sign']);

        $sign = $this->wechatSign($data, $key);//生成签名
        if ($this->wechatCheckSign($wxSign, $sign)) {
            $orderno_pay = $data['out_trade_no'] ?? '';
            $orderInfo = $this->getOne(['orderno_pay = ?' => $orderno_pay, 'paid = ?' => 0]);
            if ($orderInfo) {
                $this->orderPayment($orderInfo['order_id']);
                $this->orderCreateSuccess($orderInfo['id']);
            }
        }

    }


    //微信签名验证
    private function wechatCheckSign($sign1, $sign2)
    {
        return trim($sign1) == trim($sign2);
    }

    /**
     * 微信 sign拼装获取
     */
    private function wechatSign($param, $key)
    {

        $sign = "";
        foreach ($param as $k => $v) {
            $sign .= $k . "=" . $v . "&";
        }

        $sign .= "key=" . $key;
        $sign = strtoupper(md5($sign));
        return $sign;

    }

    /**
     * 订单待评价
     * @param $uid
     * @return void
     */
    public function cancelToBeEvaluated($uid)
    {
        $storeOrderSql = "UPDATE
                    cmf_merchant_store_order 
                SET
                    is_cancel = 1
                WHERE
                    uid = {$uid}  
                    AND is_del = 0 
                    AND id NOT IN ( SELECT oid FROM cmf_merchant_store_order_evaluate ) 
                    AND STATUS = 4";

        $rs = \PhalApi\DI()->notorm->merchant_store_order->queryAll($storeOrderSql);


        $ordersSql = "UPDATE
                    cmf_merchant_store_order
                    SET
                    is_cancel = 1
                WHERE
                    uid = {$uid}  
                    AND is_del = 0 
                    AND id NOT IN ( SELECT oid FROM cmf_merchant_store_order_evaluate ) 
                    AND STATUS = 4";
        $rs2 = \PhalApi\DI()->notorm->merchant_order->queryAll($ordersSql);


    }

    public function submitOrder($uid, $rider_id, $orderid, $order_type)
    {
        $rs = array('code' => 0, 'msg' => \PhalApi\T('添加成功'), 'info' => array());

        if ($order_type == 0) {
            //跑腿订单
            $Model_Orders = new Model_Orders();
            $orderInfo = $Model_Orders->getInfo(['orderno = ?' => $orderid], 'id');
            if (!$orderInfo) {
                throw new ApiException(\PhalApi\T('跑腿单号不存在'), 600);
            }
        } else {
            //店铺订单
            $orderInfo = $this->getOne(['order_id = ?' => $orderid], 'id');
            if (!$orderInfo) {
                throw new ApiException(\PhalApi\T('店铺订单号不存在'), 600);
            }
        }


        if ($rider_id) {
            $RiderModel = new RiderModel();
            $RiderInfo = $RiderModel->getOne(['id = ?' => $rider_id]);
            if (!$RiderInfo) {
                throw new ApiException(\PhalApi\T('骑手不存在'), 600);
            }
        }

        $isExist = $this->getOrderInfo(['uid = ?' => $uid, 'rider_id = ?' => $rider_id]);
        if ($isExist) {
            $update = [
                'update_time' => time(),
                'orderid' => $orderid,
                'order_type' => $order_type,
            ];
            $set = $this->upOrderInfo(['id = ?' => $isExist['id']], $update);

        } else {
            $addData = [
                'uid' => $uid,
                'rider_id' => $rider_id,
                'orderid' => $orderid,
                'add_time' => time(),
                'update_time' => time(),
                'order_type' => $order_type,
            ];
            $set = $this->saveOrderInfo($addData);

        }
        if (!$set) {
            throw new ApiException(\PhalApi\T('添加失败'), 600);
        }

        return $rs;
    }


    /**
     * 服务订单完成
     * @param $uid
     * @param $id
     * @return array
     * @throws ApiException
     */
    public function setServiceComplete($uid, $id)
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $where = [
            'uid = ?' => $uid,
            'id = ?' => $id,
            'shipping_type = ?' => 3,
        ];

        $orderInfo = $this->getOne($where, 'id,status');
        if (!$orderInfo) {
            throw new ApiException(\PhalApi\T('订单不存在'), 600);
        }

        $update = [
            'status' => 4,
            'end_time' => time(),
        ];
        $updateRes = $this->updateOne(['id = ?' => $id], $update);
        if (!$updateRes) {
            throw new ApiException(\PhalApi\T('操作失败'), 600);
        }
        $rs['msg'] = \PhalApi\T('操作成功');
        return $rs;
    }

    /**
     * 计算店铺人均消费
     * @return int
     */
    public function getPerCost($storeId)
    {
        $where = [
            'store_id = ?' => $storeId,
            'status = ?' => 4
        ];

        $orderInfo = $this->getOne($where, 'sum(pay_price) as pay_price,count(id) as number');
        if (!$orderInfo) {
            return 0;
        }
        if ($orderInfo['number'] > 0) {
            return round($orderInfo['pay_price'] / $orderInfo['number'], 2);
        }
        return 0;
    }

    /**
     * 待评价订单
     * @param $uid
     * @return array
     */
    public function getUnionOrderList($uid, $p)
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $MerchantStoreOrderModel = new MerchantStoreOrderModel();
        $Domain_Orders = new Domain_Orders();
        $MerchantStoreDomains = new MerchantStoreDomain();
        $list = $MerchantStoreOrderModel->getUnionOrderList($uid, $p, 20);
        foreach ($list as $key => &$value) {
            $value['image'] = '';
            $value['top_type_id'] = 0;

            if ($value['order_type'] == 0) {//跑腿订单
                $value['title_txt'] = $Domain_Orders->getTypes($value['type']);
            } else {//店铺订单
                $storeInfo = $MerchantStoreDomains->getOne(['id = ?' => $value['store_id']], 'id,name,th_name,thumb,top_type_id');
                $value['title_txt'] = $storeInfo['use_name'];
                $value['image'] = \App\get_upload_path($storeInfo['thumb']);
                $value['top_type_id'] = $storeInfo['top_type_id'];
            }
            $value['status_txt'] = \PhalApi\T('待评价');
            $value['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
        }
        $rs['info'][] = $list;
        return $rs;
    }

    /**
     * 订单催单
     * @return array
     */
    public function setReminder($uid, $id, $content)
    {
        $rs = array('code' => 0, 'msg' => \PhalApi\T('已催单!'), 'info' => array());

        $orderInfo = $this->getOne(['uid = ?' => $uid, 'id = ?' => $id], 'id,end_time,order_id,preset_time,shipping_type,status,address_id,free_shipping,reminder_count,scheduled_time,delivery_uid,store_id');
        if (!$orderInfo) {
            $rs['code'] = 500;
            $rs['msg'] = \PhalApi\T('订单已不存在!');
            return $rs;
        }
        $ordersModel = new Model_Orders();
        $order = $ordersModel->getInfo(['store_oid' => $orderInfo['id']]);

        if ($orderInfo['shipping_type'] == 2) {//自提订单
            $rs['code'] = 500;
            $rs['msg'] = \PhalApi\T('自提单无法催单!');
            return $rs;
        }

        if ($orderInfo['shipping_type'] == 1) {//外卖配送
            if (($orderInfo['status'] != 3)) {
                $rs['code'] = 500;
                $rs['msg'] = \PhalApi\T('此订单无法催单!');
                return $rs;
            }
        }
        if ($orderInfo['shipping_type'] == 3) {//服务订单
            if (($orderInfo['status'] != 3)) {
                $rs['code'] = 500;
                $rs['msg'] = \PhalApi\T('此订单无法催单!');
                return $rs;
            }
        }

        $is_reminder = $this->testIsReminder($orderInfo);
        if ($is_reminder == 0) {
            $rs['code'] = 500;
            $rs['msg'] = \PhalApi\T('此订单尚未超时!');
            return $rs;
        }
        $MerchantStoreDomain = new MerchantStoreDomain;
        $storeInfo = $MerchantStoreDomain->getOne(['id = ?' => $orderInfo['store_id']], 'id,top_type_id');
        if (!$storeInfo) throw new ApiException(\PhalApi\T('店铺不存在!'));

        $updateData = [
            'reminder_count' => ($orderInfo['reminder_count'] + 1),
            'reminder_content' => $content,
        ];
        $update = $this->updateOne(['id = ?' => $orderInfo['id']], $updateData);

        $model = new Model_Orders();
        $oinfo = $model->getInfo(['store_oid = ?'=>$orderInfo['id']], 'id,status,servicetime,type,reminder_count');
        if($oinfo){
            $update = [
                'reminder_count' => $oinfo['reminder_count']+1,
                'reminder_content' => $content,
            ];
            $model->up(['id = ?'=>$oinfo['id']],$update);
        }

        $rs['info'][] = $update;
        return $rs;
    }

    public function getDetail($uid, $id)
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $orderInfo = $this->getOne(['uid = ?' => $uid, 'id = ?' => $id], 'id,delivery_uid,verify_code,pickup_id,pickup_date,pickup_phone,end_time,scheduled_time,preset_time,shipping_type,status,address_id,free_shipping,freight_price,store_id,uid,status,serve_id,order_id,pay_price,coupon_price,freight_price,free_shipping,add_time,address_id,pay_type,shipping_type');
        if (!$orderInfo) {
            $rs['code'] = 500;
            $rs['msg'] = \PhalApi\T('订单已不存在!');
            return $rs;
        }
        $MerchantStoreDomain = new MerchantStoreDomain();
        $store = $MerchantStoreDomain->getOne(['id = ?' => $orderInfo['store_id']], 'id,name,th_name,lng,lat,thumb,phone,address,banner,environment,top_type_id');

        $store['thumb'] = \App\get_upload_path($store['thumb']);
        $store['banner'] = json_decode($store['banner'], true) ?? [];
        foreach ($store['banner'] as &$value2) {
            $value2 = \App\get_upload_path($value2);
        }
        $store['environment'] = json_decode($store['environment'], true) ?? [];
        foreach ($store['environment'] as &$value3) {
            $value3 = \App\get_upload_path($value3);
        }
        $orderInfo['count_down'] = 30 * 60 - (time() - $orderInfo['add_time']);
        if ($orderInfo['count_down'] <= 0) {
            $orderInfo['count_down'] = 0;
        }
        $orderInfo['add_time'] = date('Y.m.d H:i', $orderInfo['add_time']);
        $service_time = '--';
        if ($orderInfo['scheduled_time'] > 0) {
            $service_time = date('Y.m.d H:i', $orderInfo['scheduled_time']);
        } else if ($orderInfo['preset_time'] > 0) {
            $service_time = date('Y.m.d H:i', $orderInfo['preset_time']);
        }
        if ($orderInfo['status'] == 4) {
            $service_time = date('Y.m.d H:i', $orderInfo['end_time']);
        }

        $orderInfo['service_time'] = '服务(送达时间):' . $service_time;

        $orderInfo['status_txt'] = $this->getOrderStatus($orderInfo['status']);
        $orderInfo['des'] = $this->getOrderDes($orderInfo['status']);
        $MerchantStoreOrderCartInfoModel = new MerchantStoreOrderCartInfoModel();
        $cartInfo = $MerchantStoreOrderCartInfoModel->selectList(['oid = ?' => $orderInfo['id']], 'id,product_id,product_attr_id,cart_num,more_product_attr_id,cart_info');
        if ($orderInfo['free_shipping'] == 1) {
            $orderInfo['freight_price'] = 0;
        }
        $UserModel = new UserModel();
        $account = $UserModel->getInfo(['store_id = ?' => $store['id']], 'id');

        $orderInfo['im'] = ['uid' => $account ? "mer_{$account['id']}" : ''];
        $orderInfo['rider_im'] = (object)[];
        $rider = (object)[];
        if ($orderInfo['delivery_uid'] > 0) {
            $RiderModel = new RiderModel();
            $rider = $RiderModel->getOne(['id = ?' => $orderInfo['delivery_uid']], 'id,user_login,avatar,star,mobile,user_nickname');
            if ($rider) {
                $rider['avatar'] = \App\get_upload_path($rider['avatar']);
                $orderInfo['rider_im'] = ['uid' => 'rider_' . $rider['id']];
            }
        }

        $orderInfo['rider'] = $rider;
        $Model_Orders = new Model_Orders();
        $order = $Model_Orders->getInfo(['store_oid = ?' => $orderInfo['id']], 'thumbs,code');
        $orderInfo['thumbs'] = $order['thumbs'] ? json_decode($order['thumbs'], true) : [];
        $orderInfo['code'] = $order['code'] ?? '';//完成码


        $orderInfo['address'] = [];//送货地址
        if ($orderInfo['address_id'] > 0) {
            $AddrDomain = new AddrDomain();
            $address = $AddrDomain->getInfo(['id = ?' => $orderInfo['address_id']]);
            $orderInfo['address'] = $address;
        }

        $orderInfo['pickup'] = [];//自提地址地址
        if ($orderInfo['pickup_id'] > 0) {
            $MerchantStorePickupModel = new MerchantStorePickupModel();
            $pickup = $MerchantStorePickupModel->getOne(['id = ?' => $orderInfo['pickup_id']]);
            $orderInfo['pickup'] = $pickup;
        }
        $orderInfo['pickup']['pickup_date'] = $orderInfo['pickup_date'] > 0 ? date('Y-m-d H:i:s', $orderInfo['pickup_date']) : '';
        $orderInfo['pickup']['pickup_phone'] = $orderInfo['pickup_phone'];

        //是否展示催单
        $orderInfo['show_reminder'] = $this->testIsReminder($orderInfo);

        $show_verify = 0;//是否展示核销码
        if (in_array($orderInfo['status'], [6]) && $orderInfo['shipping_type'] == 2) {
            $show_verify = 1;//是否展示核销码
        }
        $orderInfo['show_verify'] = $show_verify;
        $orderInfo['is_evaluate'] = $this->isEvaluate($orderInfo['id'], $orderInfo['uid']);

        $orderInfo['pay_type_txt'] = $this->getPayType($orderInfo['pay_type']);//支付类型
        $orderInfo['shipping_type_txt'] = $this->getShippingType($orderInfo['shipping_type']);//配送形式
        $orderInfo['end_time'] = $orderInfo['end_time'] > 0 ? date('m-d H:i', $orderInfo['end_time']) : '';

        $MerchantStoreProductModel = new MerchantStoreProductModel();
//        $MerchantStoreCartDomain = new MerchantStoreCartDomain();
        foreach ($cartInfo as $key => &$value) {
            $value['cart_info'] = json_decode($value['cart_info'],true) ?: [];
            $product = $MerchantStoreProductModel->getOne(['id = ?' => $value['product_id']], 'id,name,th_name,price,image');
            if ($product) {
                $product['image'] = \App\get_upload_path(json_decode($product['image'], true)[0] ?? '');
            }
            $value['product'] = $product;

//            $value = array_merge($value, $MerchantStoreCartDomain->getAttr($value['product_attr_id'], $product, $value['cart_num']));
//            $value['more_product_attr'] = $MerchantStoreCartDomain->getCateAttrList(!is_array($value['more_product_attr_id']) ? json_decode($value['more_product_attr_id'], true) : [], $product, $value['cart_num']);
            $value = array_merge($value, $value['cart_info']['product_attr'] ?: []);
            $value['more_product_attr'] = $value['cart_info']['more_product_attr'] ?? [];
            unset($value['cart_info']);
        }
        if (isset($orderInfo['coupon_price'])) $orderInfo['coupon_price'] = abs($orderInfo['coupon_price']);
        $orderInfo['store'] = $store;
        $orderInfo['cart_info'] = $cartInfo;
        $rs['info'][] = $orderInfo;
        return $rs;
    }

    /**
     * 检测能否催单
     * @param $orderInfo
     * @return int
     */
    public function testIsReminder($orderInfo)
    {
        if (in_array($orderInfo['shipping_type'], [2, 3])) {
            return 0;
        }

        $show_reminder = 0;
        if (($orderInfo['scheduled_time'] > 0) && (time() > $orderInfo['scheduled_time']) && (in_array($orderInfo['status'], [2, 3]))) {
            $show_reminder = 1;
        } else if (($orderInfo['preset_time'] > 0) && (time() > $orderInfo['preset_time']) && (in_array($orderInfo['status'], [2, 3]))) {
            $show_reminder = 1;
        }
        return $show_reminder;
    }


    /**
     * 再来一单
     * @param $uid
     * @param $id
     * @return array|null
     * @throws \Exception
     */
    public function againOrder($uid, $id)
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());
        $orderInfo = $this->getOne(['uid = ?' => $uid, 'id = ?' => $id], 'id,shipping_type,status,address_id,store_id,uid,serve_id,shipping_type');
        if (!$orderInfo) {
            throw new ApiException(\PhalApi\T('订单已不存在!'));
        }

        $MerchantStoreOrderCartInfoModel = new MerchantStoreOrderCartInfoModel();
        $cartInfoList = $MerchantStoreOrderCartInfoModel->selectList(['oid = ?' => $id]);
        if (!$cartInfoList) {
            throw new ApiException(\PhalApi\T('此订单无法再次购买!'));
        }

        if ($orderInfo['shipping_type'] == 1) {
            $AddrDomain = new AddrDomain();
            $address = $AddrDomain->getInfo(['uid = ?' => $orderInfo['uid'], 'id = ?' => $orderInfo['address_id']]);
            if (!$address) {
                throw new ApiException(\PhalApi\T('当前订单地址已不存在,无法购买!'));
            }
        }

        $cartList = [];
        foreach ($cartInfoList as $key => &$value) {
            $value['cart_info'] = json_decode($value['cart_info'], true);
            $tmp = [
                'product_id' => $value['product_id'],
                'cart_num' => $value['cart_num'],
                'product_attr_id' => $value['product_attr_id'],
                'more_product_attr_id' => !is_array($value['more_product_attr_id']) ? json_decode($value['more_product_attr_id'], true) ?? [] : $value['more_product_attr_id'],
            ];
            array_push($cartList, $tmp);
        }
        $MerchantStoreProductModel = new MerchantStoreProductModel();
        $MerchantStoreDomain = new MerchantStoreDomain();

        foreach ($cartList as $value2) {
            $product_id = $value2['product_id'];
            $product_attr_id = $value2['product_attr_id'];
            $cart_num = $value2['cart_num'];
            $more_product_attr_id = !is_array($value2['more_product_attr_id']) ? json_decode($value2['more_product_attr_id'], true) ?? [] : [];

            $product = $MerchantStoreProductModel->getOne(['id = ?' => $product_id], 'id,name,th_name,store_id,repertory');
            if (!$product) {
                throw new \Exception(\PhalApi\T('商品不存在!'));
            }

            if ($product_attr_id > 0) $this->checkAttrExist($product_attr_id, $product_id);
            foreach ($more_product_attr_id as $v) {
                $this->checkAttrExist($v, $product_id);
            }

            $storeOfType5 = $MerchantStoreDomain->getTypeOfStore(5);//超市下的所有店铺
            if (in_array($product['store_id'], array_column($storeOfType5, 'id'))) {
                if ($cart_num > $product['repertory']) {
                    throw new \Exception(\PhalApi\T('商品库存不足!'));
                }
            }
        }
        $MerchantStoreCartDomain = new MerchantStoreCartDomain();
        foreach ($cartList as $key2 => $value2) {
            $add = $MerchantStoreCartDomain->addCateCart($uid, $value2['product_id'], $value2['cart_num'], $value2['product_attr_id'], $value2['more_product_attr_id']);
            if ($add['code'] != 0) {
                return $add;
            }
        }

        $rs['msg'] = \PhalApi\T('添加购物车成功');
        return $rs;
    }

    /**
     * 检测规格是否存在
     * @param $attrId
     * @return void
     */
    public function checkAttrExist($attrId, $productId)
    {
        $MerchantStoreProductAttrModel = new MerchantStoreProductAttrModel();
        $attr = $MerchantStoreProductAttrModel->getOne(['id = ?' => $attrId], 'product_id');
        if (!$attr || ($attr['product_id'] != $productId)) {
            throw new ApiException(\PhalApi\T('商品规格已不存在!'));
        }
    }

    /**
     * 订单状态
     * @param $status
     * @return mixed|void
     */
    public function getOrderStatus($status)
    {
        //订单状态
        $orderStauts = [
            0 => \PhalApi\T('待付款'),
            1 => \PhalApi\T('待接单'),
            2 => \PhalApi\T('待配送'),
            3 => \PhalApi\T('配送中'),
            4 => \PhalApi\T('已完成'),
            5 => \PhalApi\T('退款'),
            6 => \PhalApi\T('待取货'),
            7 => \PhalApi\T('已取消'),
        ];
        if (array_key_exists($status, $orderStauts)) {
            return $orderStauts[$status];
        }
    }

    /**
     * 订单状态说明
     * @param $status
     * @return mixed|void
     */
    public function getOrderDes($state)
    {
        //订单提示
        $orderState = [
            1 => \PhalApi\T('订单准备中'),
            2 => \PhalApi\T('订单等待配送'),
            3 => \PhalApi\T('骑手正在路上'),
            4 => \PhalApi\T('您的订单已完成'),
            5 => \PhalApi\T('已退款'),
            6 => \PhalApi\T('商品已备好,请前往店铺提货'),
            7 => \PhalApi\T('订单已取消'),
        ];
        if (array_key_exists($state, $orderState)) {
            return $orderState[$state];
        }
    }

    /**
     * 订单配送服务
     * @param $status
     * @return mixed|void
     */
    public function getShippingType($state)
    {
        $orderState = [//订单提示
            1 => \PhalApi\T('外卖配送'),
            2 => \PhalApi\T('门店自提'),
            3 => \PhalApi\T('上门服务'),
        ];
        if (array_key_exists($state, $orderState)) {
            return $orderState[$state];
        }
    }

    /**
     * 订单支付方式
     * @param $status
     * @return mixed|void
     */
    public function getPayType($state)
    {
        $orderState = [//订单提示
            1 => \PhalApi\T('支付宝支付'),
            2 => \PhalApi\T('微信APP'),
            3 => \PhalApi\T('微信小程序'),
        ];
        if (array_key_exists($state, $orderState)) {
            return $orderState[$state];
        }
    }

    /**
     * 搜索订单
     * @param $uid
     * @param $keywords
     * @param $type_id
     * @param $p
     * @return array
     */
    public function searchOrder($uid, $keywords, $type_id, $p)
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());


        $where = [];
        if ($keywords != '') {
            if (LANG == 'zh-cn') {
                $where["(store.name LIKE \"%$keywords%\" ) OR (product.name LIKE ?)"] = "%$keywords%";

            } else if (LANG == 'th') {
                $where["(store.th_name LIKE \"%$keywords%\" ) OR (product.th_name LIKE ?)"] = "%$keywords%";
            }
        }
        if ($type_id > 0) {
            $where['v_s_p_a.top_type_id = ?'] = $type_id;
        }

        $where['v_s_p_a.uid = ?'] = $uid;
        $list = $this->selectViewList($where, 'v_s_p_a.*,GROUP_CONCAT(v_s_p_a.product_id) as product_id_str,store.operating_state,store.thumb,store.name as store_name,store.th_name as store_th_name,product.name as product_name,product.th_name as product_th_name', 'v_s_p_a.id DESC', $p, 20);
        $MerchantStoreProductDomain = new MerchantStoreProductDomain();

        foreach ($list as $key => &$value) {
            $store = [];
            if (LANG == 'zh-cn') {
                $store['use_name'] = $value['store_name'];
            } else if (LANG == 'th') {
                $store['use_name'] = $value['store_th_name'];
            }
            $store['thumb'] = \App\get_upload_path($value['thumb']);
            $store['operating_state'] = ($value['operating_state']);
            $value['store'] = $store;

            $value['is_evaluate'] = $this->isEvaluate($value['id'],$value['uid']);
            unset($value['operating_state']);
            unset($value['thumb']);
            unset($value['store_name']);
            unset($value['store_th_name']);
            unset($value['is_del']);
            unset($value['product_name']);
            unset($value['product_th_name']);

            $value['status_txt'] = $this->getOrderStatus($value['status']);
            $value['des'] = $this->getOrderDes($value['status']);

            $procudtIdArr = array_filter(array_unique(explode(',', $value['product_id_str'])));
            $productInfo = $MerchantStoreProductDomain->inIdselectList($procudtIdArr, [], 'name,th_name,image');
            foreach ($productInfo as $key2 => &$value2) {
                $image = json_decode($value2['image'], true);
                array_walk($image, function (&$value2, $key) {
                    $value2 = \App\get_upload_path($value2);
                });
                $value2['image'] = $image[0] ?? '';
                if (LANG == 'zh-cn') {
                    $value2['use_name'] = $value2['name'];
                } else if (LANG == 'th') {
                    $value2['use_name'] = $value2['th_name'];
                }
            }
            $value['product'] = $productInfo;
        }
        $rs['info'][] = $list;
        return $rs;
    }

    /**
     * 订单是否评价
     * @param $orderid
     * @param $orderType
     * @return int
     */
    public function isEvaluate($orderid,$uid){
        $MerchantStoreOrderEvaluateDomain = new MerchantStoreOrderEvaluateDomain();
        $evaluate = $MerchantStoreOrderEvaluateDomain->getOne(['oid = ?' => $orderid, 'uid = ?' => $uid], 'id');
        if ($evaluate) {
            return 1;
        }
        return 0;
    }

    /**
     * 订单支付后 修改状态
     * @param $orderkey
     * @return void
     */
    public function orderPayment($orderkey)
    {
        $orderInfo = $this->getOne(['order_id = ?' => $orderkey]);
        if ($orderInfo) {
            $CityDomain = new CityDomain();
            $service_time = $CityDomain->getPresetTime($orderInfo['uid'], $orderInfo['store_id'], $orderInfo['address_id'], 0, 0)['info'][0];

            $updateData = [
                'paid' => 1,
                'status' => 1,
                'preset_time' => $service_time['org_time'],
                'pay_time' => time(),
            ];


            $update = $this->updateOne(['id = ?' => $orderInfo['id']], $updateData);
        }


    }


    /**
     * 创建订单
     * @param ...$param
     * @return array|null
     */
    public function createOrder(...$param)
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());
        [$uid, $orderKey, $source, $pay_type, $openid] = $param;

        if (!$orderKey) {
            $rs['code'] = 500;
            $rs['msg'] = \PhalApi\T('订单号错误!');
            return $rs;
        }

        $orderno_pay = 'store_' . (int)(strtotime(date('YmdHis', time()))) . (int)substr(microtime(), 2, 6) . sprintf('%03d', rand(0, 999));

        $isExist = $this->getOne(['uid = ?' => $uid, 'order_id = ?' => $orderKey]);
        if ($isExist) {
            if ($isExist['payid'] == 0) {
                throw new ApiException(\PhalApi\T('订单已生成,请完成支付'));
            }
            if ($isExist['payid'] == 1) {
                throw new ApiException(\PhalApi\T('订单已支付!'));
            }
        }

        $orderConfirm = \App\getcaches($orderKey);
        if (!$orderConfirm) {
            throw new ApiException(\PhalApi\T('订单已过期!'));
        }
        $store_id = $orderConfirm['store']['id'];
        $type = $orderConfirm['type'];
        $address_id = $orderConfirm['address']['id'] ?? 0;
        $couponId = $orderConfirm['coupon']['info']['id'] ?? 0;
        $scheduled_time = $orderConfirm['scheduled_time'];
        $pickup_date = $orderConfirm['pickup_date'];
        $pickup_phone = $orderConfirm['pickup']['pickup_phone'];

        $MerchantStoreCartDomain = new MerchantStoreCartDomain();
        $price = $MerchantStoreCartDomain->computed($uid, $orderKey, $type, $address_id, $couponId, $pickup_date, $pickup_phone);
        if ($price['code'] != 0) {
            return $price;
        }
        $price = $price['info'][0];
        $MerchantTypeModel = new MerchantTypeModel();
        $MerchantStoreProductDomain = new MerchantStoreProductDomain();

        $topTypeId = $MerchantTypeModel->getTopInfo($orderConfirm['store']['type_id'])['id'] ?? 0;
        $verify_code = '';
        if ($type == 2) {
            $verify_code = \App\random(6);
        }
        $order_id = (int)(strtotime(date('YmdHis', time()))) . (int)substr(microtime(), 2, 6) . sprintf('%03d', rand(0, 999));
        $orderInfo = [
            'order_id' => $order_id,
            'orderno_pay' => $orderno_pay,
            'uid' => $uid,
            'cart_id' => json_encode(array_column($orderConfirm['cart_list'], 'id')),
            'total_num' => $price['product']['count'],
            'total_price' => $price['product']['price'],
            'pay_price' => $price['pay_price'],
            'coupon_id' => $couponId,
            'coupon_price' => $price['coupon']['price'],
            'is_channel' => $source,
            'freight_price' => $price['freight'],
            'add_time' => time(),
            'store_id' => $store_id,
            'shipping_type' => $type,
            'address_id' => $address_id,
            'top_type_id' => $topTypeId,
            'verify_code' => $verify_code,
            'serve_id' => $orderConfirm['serve']['serve_id'],
            'pickup_id' => $orderConfirm['pickup']['id'] ?? 0,
            'free_shipping' => $orderConfirm['free_shipping'],
            'pay_type' => $pay_type,
            'pickup_date' => (int)strtotime($orderConfirm['pickup']['pickup_date'] ?? 0),
            'pickup_phone' => $orderConfirm['pickup']['pickup_phone'] ?? '',
            'scheduled_time' => (int)strtotime($scheduled_time) ?? 0,
        ];

        \PhalApi\DI()->notorm->beginTransaction('db_master');
        try {

            if ($price['pay_price'] <= 0) {
                $orderId = $this->addOrder($orderInfo);//新增订单
                $MerchantStoreCartDomain->settingUpShoppingCart(array_column($orderConfirm['cart_list'], 'id'), $orderId);//新增订单后 清空购物车


                foreach ($orderConfirm['cart_list'] as $value) {
                    $MerchantStoreProductDomain->inventoryReduction($value['product_id'], $value['cart_num']);//新增订单后 减去商品库存
                }
                $this->orderPayment($order_id);
                $this->orderCreateSuccess($orderId);

            } else {
                $orderId = $this->addOrder($orderInfo);//新增订单
                $pay = $this->storeOrderPay($orderno_pay, $price['pay_price'], $pay_type, '商品购买', $openid);

                foreach ($orderConfirm['cart_list'] as $value) {
                    $MerchantStoreProductDomain->inventoryReduction($value['product_id'], $value['cart_num']);//新增订单后 减去商品库存
                }
                $MerchantStoreCartDomain->settingUpShoppingCart(array_column($orderConfirm['cart_list'], 'id'), $orderId);//新增订单后 清空购物车
            }


        } catch (\Exception $exception) {
            \PhalApi\DI()->notorm->rollback('db_master');
            $rs['code'] = 400;
            $rs['msg'] = \PhalApi\T($exception->getMessage());
            return $rs;
        }
        \App\delcache($orderKey);
        \PhalApi\DI()->notorm->commit('db_master');
        $pay['info'][0]['order_id'] = $orderId;
        $rs['info'] = $pay['info'];
        $rs['msg'] = \PhalApi\T('');
        return $rs;

    }

    /**
     * 支付宝微信支付支付
     * @param $orderId
     * @param $amount
     * @param $payid
     * @param $note
     * @param $openid
     * @return array
     */
    public function storeOrderPay($orderId, $amount, $payid, $note = '', $openid = '')
    {
        $Domain_Pay = new Domain_Pay();
        $res = $Domain_Pay->pay($orderId, $amount, $payid, $note, 'orderpay', $openid);
        return $res;
    }

    /**
     * 店铺订单创建成功后
     * @param $orderID
     * @return void
     */
    public function orderCreateSuccess($orderID)
    {
        $orderInfo = $this->getOne(['id = ?' => $orderID], 'id,order_id,uid,store_id,paid,delivery_uid,preset_time,scheduled_time,shipping_type,top_type_id,address_id,pay_type,freight_price,is_channel,mark');
        $MerchantStoreDomain = new MerchantStoreDomain();
        $store = $MerchantStoreDomain->getOne(['id = ?' => $orderInfo['store_id']], 'id,name,th_name,automatic_order,address,lng,lat,top_type_id');
        $AddrDomain = new AddrDomain();
        $address = $AddrDomain->getInfo(['id = ?' => $orderInfo['address_id']]);

        if (in_array($orderInfo['shipping_type'], [1, 3])) {
            /* 新订单通知 */
            $key = 'orders_mer_new';
            \App\hSet($key, $store['id'], $orderID);
            /* 新订单通知 */
        }

        if ($orderInfo['shipping_type'] != 1) {//不是外卖订单不处理
            return;
        }
        $orderCreate = $this->addRiderOrder2($orderID);
        $insertId = $orderCreate['order_id'];

        if ($orderInfo['shipping_type'] == 1 && $store['automatic_order']) {//自动接单(系统配送订单)

            $riderInfo = $this->getRider($address['lng'], $address['lat']);

            if ($riderInfo) {
                $model = new Model_Orders();
                $up = [
                    'riderid' => $riderInfo['id'],
                    'status' => 3,
                    'graptime' => time(),
                ];
                $orderId = $model->getInfo(['store_oid = ?' => $orderID], 'id');

                $res1 = $model->up(['store_oid = ?' => $orderID], $up);
                $this->updateOne(['id = ?' => $orderID], ['delivery_uid' => $riderInfo['id'], 'status' => 2]);
                $riderOrdersDomain = new riderOrdersDomain();
                $riderOrdersDomain->presetIncome($orderId['id'] ?? 0);

                $Domain_Ordersrefuse = new Domain_Ordersrefuse();
                $Domain_Ordersrefuse->delorder($orderId['id'] ?? 0);
            }
        }
    }

    public function getPayload($orderType, $noticeType, $storeOrderId, $runOrderId, $topTypeId)
    {
        $payload = [
            'order_type' => $orderType,//store(外卖订单)|run(跑腿订单)
            'notice_type' => $noticeType,//chat(聊天)|system(系统通知)|mer_take_order(商家接单)|rider_take_order(骑手接单)|order_complete(订单完成)|mer_new_order(商家新订单)|order_reminder(订单催单)|order_time_out(订单超时)
            'store_order_id' => $storeOrderId,//店铺订单id
            'run_order_id' => $runOrderId,//跑腿订单id
            'notice_id' => 0,//系统通知id
            'top_type_id' => $topTypeId,//店铺类型
        ];
        return $payload;
    }


    public function addRiderOrder2($orderID)
    {
        $orderInfo = $this->getOne(['id = ?' => $orderID], 'id,order_id,uid,store_id,paid,delivery_uid,preset_time,scheduled_time,shipping_type,top_type_id,address_id,pay_type,freight_price,is_channel,mark');
        if (!$orderInfo) throw new ApiException(\PhalApi\T('订单信息不存在!'), 600);
        $MerchantStoreDomain = new MerchantStoreDomain();
        $storeInfo = $MerchantStoreDomain->getOne(['id = ?' => $orderInfo['store_id']], 'id,name,th_name,lng,lat,address,phone');
        if (!$storeInfo) throw new ApiException(\PhalApi\T('店铺信息不存在!'), 600);
        if (!$storeInfo['lng'] || !$storeInfo['lat'] || !$storeInfo['address']) throw new ApiException(\PhalApi\T('店铺地址不完善,暂时无法下单!'));

        $AddrDomain = new AddrDomain();
        $addrInfo = $AddrDomain->getInfo(['id = ?' => $orderInfo['address_id']]);
        if (!$addrInfo) throw new ApiException(\PhalApi\T('收货地址信息错误!'));
        if (!$addrInfo['lng']) throw new ApiException(\PhalApi\T('收货地址信息错误!'));
        $UserModel = new UserModel();
        $usersinfo = $UserModel->getInfo(['id = ?' => $orderInfo['uid']], 'id,user_nickname');

        $uid = $orderInfo['uid'];
        $cityid = 1;
        $type = 6;
        $start_name = $storeInfo['use_name'];
        $start_addr = $storeInfo['address'];
        $start_lng = $storeInfo['lng'];
        $start_lat = $storeInfo['lat'];
        $pick_name = $storeInfo['use_name'] ?? '';//取件人名
        $pick_phone = $storeInfo['phone'] ?? '';//取件人电话
        $end_name = $addrInfo['name'];
        $end_addr = $addrInfo['place'] . ' ' . $addrInfo['addr'];
        $end_lng = $addrInfo['lng'];
        $end_lat = $addrInfo['lat'];
        $recip_name = $usersinfo['user_nickname'] ?? '';//收件人名
        $recip_phone = $addrInfo['mobile'];//收件人电话
        $distance = \App\getDistance($start_lng, $start_lat, $end_lng, $end_lat);
        $weight = 0;
        $servicetime = $orderInfo['scheduled_time'] ?: $orderInfo['preset_time'];//先取约定时间 其次预计送达时间
        $cateid = 0;
        $des = $orderInfo['mark'];
        $usercouponid = 0;
        $fee = 0;

        if (!in_array($orderInfo['pay_type'], [1, 2, 3])) throw new ApiException(\PhalApi\T('未知的支付方式!'));
        $payid = $orderInfo['pay_type'];
        $source = $orderInfo['is_channel'];
        $openid = '';
        $domain = new Domain_Helpsend();
        $res = $domain->create2($uid, $type, $cityid, $start_name, $start_addr, $start_lng, $start_lat, $pick_name, $pick_phone, $end_name, $end_addr, $end_lng, $end_lat, $recip_name, $recip_phone, $distance, $weight, $servicetime, $cateid, $des, $usercouponid, $fee, $payid, $source, $openid, $orderID, $orderInfo['store_id']);

        if ($res['code'] != 0) {
            throw new ApiException($res['msg']);
        }
        return $res;
    }

    /**
     * 获取符合自动推送订单要求的骑手
     * @return array
     */
    public function getRider($lng, $lat)
    {
        $CityDomain = new CityDomain();
        $CityInfo = $CityDomain->getConfig(1);
        $auto_order_number = $CityInfo['type6']['auto_order_number'];//自动推送上限
        $rider_distance = $CityInfo['rider_distance'];//自动距离范围

        $RiderModel = new RiderModel();
        $where['user_status = ?'] = 1;
        $where['isrest = ?'] = 0;
        $list = $RiderModel->viewSelectList($lng, $lat, $rider_distance, $where);
        shuffle($list);
        foreach ($list as $key => $value) {
            $orderCount = $this->getSystemCount($value['id']);
            if ($orderCount < $auto_order_number) {
                return $value;
            }
        }
        return [];
    }


    /**
     * 获取 骑手目前正在进行整的系统派送订单数量
     * @return void
     */
    public function getSystemCount($uid)
    {
        $Model_Orders = new Model_Orders();
        $where['riderid = ?'] = $uid;
        $where['paytime > ?'] = 0;
        $count = $Model_Orders->inStatusGetCount([3, 4], $where);
        return $count;
    }


    /**
     * 新增订单记录
     * @return int
     */
    public function addOrder($addData)
    {
        return $this->saveOne($addData);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $MerchantStoreOrderModel = new MerchantStoreOrderModel();
        // TODO: Implement __call() method.
        return call_user_func_array([$MerchantStoreOrderModel, $name], $arguments);
    }
}
