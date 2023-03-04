<?php

namespace Merchant\Domain;

use App\ApiException;
use App\Domain\Wxpay as Domain_Wxpay;
use App\Domain\Alipay as Domain_Alipay;
use App\Domain\MerchantStoreOrderEvaluate as MerchantStoreOrderEvaluateDomain;
use Merchant\Model\MerchantStoreOrder as MerchantStoreOrderModel;
use Merchant\Model\MerchantRecord as MerchantRecordModel;
use Merchant\Model\Users as UsersModel;
use App\Model\Rider as RiderModel;
use App\Domain\Orders as OrdersDomain;
use App\Domain\MerchantStore as MerchantStoreDomain;
use App\Model\Addr as AddrModel;
use App\Model\MerchantStoreServe as MerchantStoreServeModel;
use App\Model\MerchantStoreOrderCartInfo as MerchantStoreOrderCartInfoModel;
use Rider\Model\Orders as Model_Orders;


/**
 * 店铺订单
 */
class MerchantStoreOrder
{


    /**
     * 查询用户当前店铺中最新的订单号
     * @return void
     */
    public function getUserOrder($uid, $users_id)
    {
        $rs = ['code' => 0, 'msg' => \PhalApi\T(''), 'info' => []];
        $store = $this->checkStoreIdentity($uid);
        $storeId = $store['id'];

        $orders = $this->getOne(['store_id = ?' => $storeId, 'uid = ?' => $users_id], 'id,order_id');
        $rs['info'][] = $orders ?? [];
        return $rs;
    }

    /**
     * 获取小票打印数据
     * @param $id
     * @return void
     */
    public function getOrderPrintData($id)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        $orders = $this->getOne(['id = ?' => $id], 'id,uid,pay_price,cart_id,shipping_type,address_id,total_num,scheduled_time,freight_price,free_shipping,mark,add_time,store_id,serve_id,order_id');
        if (!$orders) throw new ApiException(\PhalApi\T('订单信息不存在'));
        $orders['scheduled_time'] = $orders['scheduled_time'] ? date('m-d H:i', $orders['scheduled_time']) : \PhalApi\T('尽快送达');
        $orders['add_time'] = date('m-d H:i', $orders['add_time']);
        $serve = [];
        if ($orders['serve_id'] > 0) {
            $MerchantStoreServeModel = new MerchantStoreServeModel();
            $serve = $MerchantStoreServeModel->getOne(['id = ?' => $orders['serve_id']]);
        }

        $UsersModel = new UsersModel();
        $users = $UsersModel->getOne(['id = ?' => $orders['uid']], 'id,user_nickname');
        if (!$users) throw new ApiException(\PhalApi\T('购买用户信息错误'));
        $AddrModel = new AddrModel();
        $address = $AddrModel->getInfo(['uid = ?' => $orders['uid'], 'id = ?' => $orders['address_id']], 'place,addr,name,mobile');
        if (!$address) throw new ApiException(\PhalApi\T('收货地址信息错误'));

        $MerchantStoreOrderCartInfoModel = new MerchantStoreOrderCartInfoModel();
        $product = [];
        $cartInfo = $MerchantStoreOrderCartInfoModel->selectList(['oid = ?' => $id], 'id,product_id,product_attr_id,cart_info');
        foreach ($cartInfo as $key => &$value) {
            $value['cart_info'] = json_decode($value['cart_info'], true);
            $product_price = $value['cart_info']['product']['use_price'] ?? 0;
            $attr_price = $value['cart_info']['attr']['use_price'] ?? 0;
            $price = $product_price + $attr_price;
            $cart_num = $value['cart_info']['cart']['cart_num'];
            $productTemp = [
                'use_name' => $value['cart_info']['product']['use_name'],
                'cart_num' => $cart_num,
                'attr_name' => $this->getAttrName($value['cart_info']),
                'use_price' => $price * $cart_num,
            ];
            array_push($product, $productTemp);
        }
        $MerchantStoreDomain = new MerchantStoreDomain();
        $store = $MerchantStoreDomain->getOne(['id = ?' => $orders['store_id']], 'id,auto_print,tohes,automatic_order,name,th_name');
        $rs['info'][] = compact('orders', 'users', 'address', 'product', 'store', 'serve');
        return $rs;
    }

    public function getAttrName($cartInfo)
    {
        $str = '';
        if ($cartInfo['product_attr']) {
            $str = $value['cart_info']['product_attr']['use_name'] ?? '';
        } else {
            foreach ($cartInfo['more_product_attr'] ?? [] as $value) {
                $str .= $value['parentAttr']['use_attr_name']  . '-' . $value['attr']['use_attr_name'].',';
            }
        }
        return $str;
    }

    public function takeOrders($uid, $id)
    {
        $rs = ['code' => 0, 'msg' => \PhalApi\T('接单成功'), 'info' => []];
        $store = $this->checkStoreIdentity($uid);
        $storeId = $store['id'] ?? 0;

        $where['id = ?'] = $id;
        $where['store_id = ?'] = $storeId;
        $info = $this->getOne($where, 'id,status,uid,verify_code,is_verify,shipping_type,pay_price,order_id,scheduled_time,store_id,address_id,status');
        if (!$info) {
            throw new ApiException(\PhalApi\T('订单不存在'));
        }
        if (!in_array($info['status'], [1])) {
            throw new ApiException(\PhalApi\T('当前订单无法接单'));
        }

        $MerchantStoreDomain = new MerchantStoreDomain();
        $store = $MerchantStoreDomain->getOne(['id = ?' => $storeId], 'top_type_id');
        if (!$store) {
            throw new ApiException(\PhalApi\T('店铺不存在'));
        }

        if ($info['shipping_type'] == 1) {//外卖配送
            //修改店铺订单状态
            $update = [
                'status' => 2
            ];
            $Model_Orders = new Model_Orders();

            //同步修改跑腿订单状态
            $up = [
                'status' => 2,
                'paytime' => time(),
            ];
            $Model_Orders->up(['store_oid = ?' => $id], $up);

            /* 新订单通知 */
            $key = 'orders_new';
            \App\hSet($key, 1, time());
            /* 新订单通知 */

        } else if ($info['shipping_type'] == 2) {//门店自提
            $update = [
                'status' => 6
            ];
        } else if ($info['shipping_type'] == 3) {//上门服务
            $update = [
                'status' => 3
            ];
        }

        $this->updateOne(['id = ?' => $info['id']], $update);

        return $rs;
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

    /**
     * 获取微信小程序 订阅消息 跳转地址(店铺订单)
     * @param $status 订单状态
     * @param $orderId 订单id
     * @param $topTypeId 店铺类型
     * @return string
     */
    public function getStorePageUrl($status, $orderId, $topTypeId)
    {
        $url = [
            'showMap' => "/package-mine/pages/order/order-map-detaile",
            'noMap' => "/package-mine/pages/order/order-map-detaile",
        ];
        if (!in_array($topTypeId, [1])) {
            return "";
        }
        if (in_array($status, [3])) {
            $strUrl = $url['showMap'];
        } else {
            $strUrl = $url['noMap'];
        }
        return $strUrl . '?' . http_build_query(['order' => $orderId, 'top_type_id' => $topTypeId]);
    }

    /**
     * 获取微信小程序 订阅消息 跳转地址(跑腿订单)
     * @param $status 订单状态
     * @param $orderId 订单id
     * @param $topTypeId 店铺类型
     * @return string
     */
    public function getRunPageUrl($status, $orderId, $topTypeId = 2)
    {
        $url = [
            'showMap' => "/package-mine/pages/order/ordermap-ss",
            'noMap' => "/package-mine/pages/order/ordermap-ss",
        ];
        if (in_array($status, [3, 4])) {
            $strUrl = $url['showMap'];
        } else {
            $strUrl = $url['noMap'];
        }
        return $strUrl . '?' . http_build_query(['order' => $orderId, 'top_type_id' => $topTypeId]);
    }

    /**
     * 订单取消
     * @param $uid
     * @param $id
     * @return void
     */
    public function cancelOrder($uid, $id)
    {
        $rs = ['code' => 0, 'msg' => \PhalApi\T('取消成功'), 'info' => []];
        $store = $this->checkStoreIdentity($uid);
        $storeId = $store['id'];

        $where['id = ?'] = $id;
        $where['store_id = ?'] = $storeId;

        $info = $this->getOne($where, 'id,status,orderno_pay,uid,verify_code,pay_type,is_verify,shipping_type,pay_price');
        if (!$info) {
            throw new ApiException(\PhalApi\T('订单不存在'));
        }
        if (!in_array($info['status'], [1])) {
            throw new ApiException(\PhalApi\T('当前订单无法取消'));
        }

        $refund_order_id = 'store_' . (int)(strtotime(date('YmdHis', time()))) . (int)substr(microtime(), 2, 6) . sprintf('%03d', rand(0, 999));

        \PhalApi\DI()->notorm->beginTransaction('db_master');
        try {
            $update = [
                'status' => 7,
                'refund_reason_time' => time(),
                'refund_price' => $info['pay_price'],
                'refund_order_id' => $refund_order_id,
            ];
            $this->updateOne(['id = ?' => $info['id']], $update);
            $this->setRefundOrder($id, 10);
            if ($info['pay_price'] > 0) {
                $this->originalRefund($info['pay_type'], $info['orderno_pay'], $refund_order_id, $info['pay_price'], '退款');
            }

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
     * 原生支付退款
     * @param $payType 退款类型
     * @param $out_trade_no 商户订单号
     * @param $out_refund_no 商户退款单号
     * @param $price 退款金额
     * @param $is_mer 是否商家付款
     * @param $reason 退款原因
     * @return void
     */
    public function originalRefund($payType, $outTradeNo, $outRefundNo, $price, $reason = "", $isMer = 0)
    {
        $Domain_Wxpay = new Domain_Wxpay();
        $Domain_Alipay = new Domain_Alipay();
        if ($payType == 1) {
            //支付宝APP退款
            $Domain_Alipay->aliAppRefund($outTradeNo, $price, $outRefundNo, $reason);
        } else if ($payType == 2) {
            //微信APP退款
            $Domain_Wxpay->wxAppRefund($outTradeNo, $price, $outRefundNo, $reason, $isMer);
        } else if ($payType['pay_type'] == 3) {
            //微信小程序退款
            $Domain_Wxpay->smallRefund($outTradeNo, $price, $outRefundNo, $reason, $isMer);
        }
    }


    /**
     * 订单退款
     * @param $uid
     * @param $id
     * @return void
     */
    public function refundOrder($uid, $id)
    {
        $rs = ['code' => 0, 'msg' => \PhalApi\T('退款成功'), 'info' => []];
        $store = $this->checkStoreIdentity($uid);
        $storeId = $store['id'];

        $where['id = ?'] = $id;
        $where['store_id = ?'] = $storeId;

        $info = $this->getOne($where, 'id,status,orderno_pay,uid,verify_code,pay_type,is_verify,shipping_type,pay_price');
        if (!$info) {
            throw new ApiException(\PhalApi\T('订单不存在'));
        }
        if (!in_array($info['status'], [1, 2])) {
            throw new ApiException(\PhalApi\T('当前订单无法退款'));
        }

        $refund_order_id = 'store_' . (int)(strtotime(date('YmdHis', time()))) . (int)substr(microtime(), 2, 6) . sprintf('%03d', rand(0, 999));
        \PhalApi\DI()->notorm->beginTransaction('db_master');
        try {
            $update = [
                'status' => 5,
                'refund_reason_time' => time(),
                'refund_price' => $info['pay_price'],
                'refund_order_id' => $refund_order_id,
            ];
            $this->setRefundOrder($id, 8);
            $this->updateOne(['id = ?' => $info['id']], $update);
            if ($info['pay_price'] > 0) {
                $this->originalRefund($info['pay_type'], $info['orderno_pay'], $refund_order_id, $info['pay_price'], '退款');
            }

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
     * 订单核销
     * @param $uid
     * @param $id
     * @param $verify_code
     * @return array
     * @throws ApiException
     */
    public function verificationSheet($uid, $verify_code)
    {
        $rs = ['code' => 0, 'msg' => \PhalApi\T('核销成功'), 'info' => []];
        $store = $this->checkStoreIdentity($uid);
        $storeId = $store['id'];

        $where['verify_code = ?'] = $verify_code;
        $where['store_id = ?'] = $storeId;

        $info = $this->getOne($where, 'id,status,uid,verify_code,is_verify,shipping_type');

        if (!$info) {
            throw new ApiException(\PhalApi\T('订单不存在'));
        }
        if ($info['shipping_type'] != 2) {
            throw new ApiException(\PhalApi\T('此订单非自提订单'));
        }
        if ($info['status'] != 6) {
            throw new ApiException(\PhalApi\T('此订单暂时无法核销'));
        }
        if ($info['is_verify'] == 1) {
            throw new ApiException(\PhalApi\T('此订单已被核销'));
        }

        $update = [
            'is_verify' => 1,
            'status' => 4,
            'end_time' => time(),
        ];
        $this->updateOne(['id = ?' => $info['id']], $update);
        return $rs;

    }

    /**
     * 订单详情
     * @param $uid
     * @param $id
     * @return array
     * @throws ApiException
     */
    public function getDetail($uid, $id)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        $store = $this->checkStoreIdentity($uid);
        $storeId = $store['id'];
        $where['store_id = ?'] = $storeId;
        $where['id = ?'] = $id;
        $info = $this->getOne($where, 'id,status,uid,delivery_uid,
        total_num,pay_price,preset_time,scheduled_time,shipping_type,
        address_id,reminder_count,free_shipping,freight_price,coupon_price,order_id,add_time,pickup_date,shipping_type,pay_type,add_time,verify_code,reminder_content,serve_id');
        if (!$info) throw new ApiException(\PhalApi\T('订单不存在'));

        $RiderModel = new RiderModel();
        $UsersModel = new UsersModel();
        $AddrModel = new AddrModel();
        $Model_Orders = new Model_Orders();
        $OrdersDomain = new OrdersDomain();
        $MerchantStoreOrderEvaluateDomain = new MerchantStoreOrderEvaluateDomain();

        $rider = [];
        $rider['statue_txt'] = '';//骑手配送状态
        if ($info['delivery_uid'] > 0) {
            $rider = $RiderModel->getOne(['id= ?' => $info['delivery_uid']], 'id,user_nickname,avatar,mobile');
            if ($rider) $rider['avatar'] = \App\get_upload_path($rider['avatar'] ?? '');
            $orders = $Model_Orders->getInfo(['store_oid = ?' => $id], 'status');
            $rider['statue_txt'] = $OrdersDomain->getOrderDes($orders['status'] ?? -1);
        }
        $info['rider'] = $rider;

        $address = [];
        if ($info['address_id'] > 0) {
            $address = $AddrModel->getInfo(['id = ?' => $info['address_id']], 'id,place,lng,lat,addr');
        }
        $info['address'] = $address;

        $serve = [];
        if ($info['serve_id'] > 0) {
            $MerchantStoreServeModel = new MerchantStoreServeModel();
            $serve = $MerchantStoreServeModel->getOne(['id = ?' => $info['serve_id']]);
        }
        $info['serve'] = $serve;

        $users = [];
        $users_im = [];
        if ($info['uid'] > 0) {
            $users = $UsersModel->getOne(['id = ?' => $info['uid']], 'id,user_nickname,avatar,mobile');
            if ($users) $users['avatar'] = \App\get_upload_path($users['avatar'] ?? '');
            if ($users) $users_im = ['user_id' => "users_{$users['id']}"];
        }
        $info['users'] = $users;
        $info['users_im'] = $users_im;

        $status_txt = $this->getOrderStatus($info['status']);
        $info['status_txt'] = $status_txt;
        $info['delivery_time'] = $this->getDeliveryTime($info);

        $MerchantStoreOrderCartInfoModel = new MerchantStoreOrderCartInfoModel();

        $product = $MerchantStoreOrderCartInfoModel->selectList(['oid = ?' => $id], 'product_id,product_id,product_attr_id,cart_info,more_product_attr_id,cart_num');
        foreach ($product as $key => &$value) {
            $value['cart_info'] = json_decode($value['cart_info'], true);
            $value['use_price'] = bcadd($value['cart_info']['product']['use_price'] ?? 0, $value['cart_info']['product_attr']['use_price'] ?? 0, 2);
            $value['cart_num'] = $value['cart_info']['cart']['cart_num'];
            $value['use_name'] = $value['cart_info']['product']['use_name'];
            $value['product_attr'] = $value['cart_info']['product_attr'] ?: [];
//            $value['more_product_attr'] = $MerchantStoreCartDomain->getCateAttrList(!is_array($value['more_product_attr_id']) ? json_decode($value['more_product_attr_id'], true) : [], $value['cart_info']['product'], $value['cart_num']);
            $value['more_product_attr'] = $value['cart_info']['more_product_attr'] ?: [];

            unset($value['cart_info']);
        }
        $info['product'] = $product;
        if ($info['free_shipping'] == 1) {
            $info['freight_price'] = 0;
        }

        $info['order_evaluate'] = [];
        $orderEvaluate = $MerchantStoreOrderEvaluateDomain->getOne(['oid = ?' => $info['id']], 'id');
        if ($orderEvaluate) {
            $info['order_evaluate'] = $orderEvaluate;
        }

        //展示待接单
        $info = $this->showButton($info);


        $info['shipping_type_txt'] = $this->getShippingType($info['shipping_type']);
        $info['status_txt'] = $this->getOrderStatus($info['status']);
        $info['add_time'] = date('Y-m-d H:i:s', $info['add_time']);
        $info['pay_type_txt'] = $this->getPayType($info['pay_type']);
        $rs['info'][] = $info;
        return $rs;
    }

    /**
     * 应该展示的按钮
     * @param $orderInfo
     * @return mixed
     */
    public function showButton($orderInfo)
    {
        //展示待接单按钮
        $show_receie = 0;
        if (in_array($orderInfo['status'], [1])) {
            $show_receie = 1;
        }
        $orderInfo['show_receie'] = $show_receie;

        //是否展示退款按钮
        $show_refund = 0;
        if (in_array($orderInfo['status'], [2, 6])) {
            $show_refund = 1;
        }
        $orderInfo['show_refund'] = $show_refund;

        //是否展示取消按钮
        $show_cancel = 0;
        if (in_array($orderInfo['status'], [1])) {
            $show_cancel = 1;
        }
        $orderInfo['show_cancel'] = $show_cancel;

        //是否展示待评价按钮
        $show_evaluate = 0;
        if (in_array($orderInfo['status'], [4])) {
            $show_evaluate = 1;
        }
        $orderInfo['show_evaluate'] = $show_evaluate;
        return $orderInfo;
    }


    /**
     * 获取支付方式
     * @param $payType
     * @return void
     */
    public function getPayType($payType)
    {
        $status = [
            1 => \PhalApi\T('支付宝支付'),
            2 => \PhalApi\T('微信支付'),
            3 => \PhalApi\T('微信小程序')
        ];
        if (array_key_exists($payType, $status)) {
            return $status[$payType];
        }
        return '--';
    }

    /**
     * 获取配送服务
     * @param $shipping_type
     * @return void
     */
    public function getShippingType($shipping_type)
    {
        $status = [
            1 => \PhalApi\T('外卖配送'),
            2 => \PhalApi\T('门店自提')
        ];
        if (array_key_exists($shipping_type, $status)) {
            return $status[$shipping_type];
        }
        return '--';
    }

    /**
     * 获取店铺订单统计
     * @param $uid
     * @return array
     * @throws ApiException
     */
    public function getStoreNumber($uid)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        $store = $this->checkStoreIdentity($uid);
        $storeId = $store['id'];

//        类型 (1:待接单 2:待配送 3:配送中 4:已完成 5:退款 6:已备货(自提单)
        $type1 = $this->getCount(['store_id = ?' => $storeId, 'status = ?' => 1]);
        $type2 = $this->getCount(['store_id = ?' => $storeId, 'status = ?' => 2]);
        $type3 = $this->getCount(['store_id = ?' => $storeId, 'status = ?' => 3]);
        $type4 = $this->getCount(['store_id = ?' => $storeId, 'status = ?' => 4]);
        $type5 = $this->getCount(['store_id = ?' => $storeId, 'status = ?' => 5]);
        $type6 = $this->getCount(['store_id = ?' => $storeId, 'status = ?' => 6]);
        $rs['info'][] = compact('type1', 'type2', 'type3', 'type4', 'type5', 'type6');
        return $rs;
    }

    /**
     * 订单状态
     * @param $status
     * @return mixed|void
     */
    public function getOrderStatus($status)
    {
        $orderStauts = [//订单状态
            0 => \PhalApi\T('待付款'),
            1 => \PhalApi\T('待接单'),
            2 => \PhalApi\T('待配送'),
            3 => \PhalApi\T('配送中'),
            4 => \PhalApi\T('已完成'),
            5 => \PhalApi\T('已退款'),
            6 => \PhalApi\T('待取货'),//自提备货
        ];
        if (array_key_exists($status, $orderStauts)) {
            return $orderStauts[$status];
        }
    }

    /**
     * 获取店铺订单列表
     * @return void
     */
    public function getOrderList($uid, $type, $p)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        $store = $this->checkStoreIdentity($uid);
        $storeId = $store['id'];
        $where['store_id = ?'] = $storeId;
        $where['status = ?'] = $type;
        $list = $this->selectList($where, 'id,status,uid,delivery_uid,total_num,pay_price,pickup_date,preset_time,scheduled_time,end_time,shipping_type,address_id,reminder_count', 'id DESC', $p, 20);

        $RiderModel = new RiderModel();
        $UsersModel = new UsersModel();
        $AddrModel = new AddrModel();
        $MerchantStoreOrderEvaluateDomain = new MerchantStoreOrderEvaluateDomain();

        foreach ($list as $key => &$value) {
            $value['order_evaluate'] = [];

            $value = $this->showButton($value);
            $rider = [];
            if ($value['delivery_uid'] > 0) {
                $rider = $RiderModel->getOne(['id= ?' => $value['delivery_uid']], 'id,user_nickname,avatar,mobile');
                if ($rider) $rider['avatar'] = \App\get_upload_path($value['avatar'] ?? '');

            }
            $value['rider'] = $rider;

            $address = [];
            if ($value['address_id'] > 0) {
                $address = $AddrModel->getInfo(['id = ?' => $value['address_id']], 'id,place,lng,lat,addr');
            }
            $value['address'] = $address;

            $users = [];
            $users_im = [];
            if ($value['uid'] > 0) {
                $users = $UsersModel->getOne(['id = ?' => $value['uid']], 'id,user_nickname,avatar,mobile');
                if ($users) $users['avatar'] = \App\get_upload_path($users['avatar'] ?? '');
                if ($users) $users_im = ['user_id' => "users_{$users['id']}"];
            }
            $value['users'] = $users;
            $value['users_im'] = $users_im;

            $orderEvaluate = $MerchantStoreOrderEvaluateDomain->getOne(['oid = ?' => $value['id']], 'id');
            if ($orderEvaluate) {
                $value['order_evaluate'] = $orderEvaluate;
            }

            $status_txt = $this->getOrderStatus($value['status']);
            $value['status_txt'] = $status_txt;
            $value['delivery_time'] = $this->getDeliveryTime($value);
            if ($value['status'] == 4) {
                $value['delivery_time'] = date('m-d H:i', $value['end_time']) . \PhalApi\T('送达');
            }
        }
        $rs['info'][] = $list;
        return $rs;
    }

    /**
     * 送达时间获取
     * @param $orderInfo
     * @return void
     */
    public function getDeliveryTime($orderInfo)
    {
        $time = '--';
        if ($orderInfo['shipping_type'] == 1) {
            //外买配送
            if ($orderInfo['scheduled_time'] > 0) {//预约时间
                $time = date('m-d H:i', $orderInfo['scheduled_time']);
                $time = $time . \PhalApi\T('送达');
            } else if ($orderInfo['preset_time'] > 0) {//立即送达
                $time = \PhalApi\T('立即送达');
            }
        } else if ($orderInfo['shipping_type'] == 2) {
            //门店自提
            if ($orderInfo['pickup_date'] > 0) {//预约时间
                $time = date('m-d H:i', $orderInfo['pickup_date']);
            }
            $time = $time . \PhalApi\T('提货');

        } else if ($orderInfo['shipping_type'] == 3) {
            //上门服务
            if ($orderInfo['scheduled_time'] > 0) {//预约时间
                $time = date('m-d H:i', $orderInfo['scheduled_time']);
                $time = $time . \PhalApi\T('送达');
            } else if ($orderInfo['preset_time'] > 0) {//立即送达
                $time = \PhalApi\T('立即送达');
            }
        }

        return $time;
    }


    /**
     * 体现记录
     * @param $uid
     * @param $start_time
     * @param $p
     * @return array
     * @throws ApiException
     */
    public function reconciliationList($uid, $start_time, $p)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        $store = $this->checkStoreIdentity($uid);
        $storeId = $store['id'];

        $where = [];
        $where['store_id = ?'] = $storeId;
        $where['status > ?'] = 1;
        $where['status < ?'] = 7;
        if ($start_time) {
            $where['add_time >= ?'] = strtotime($start_time . ' 00:00:00');
            $where['add_time <= ?'] = strtotime($start_time . ' 23:59:59');
        }
        $list = $this->selectList($where, 'id,add_time,total_num,status,pay_price', 'id desc', $p, 20);

        foreach ($list as &$value) {
            $status_txt = '';
            switch ($value['status']) {
                case 1:
                case 2:
                case 3:
                case 6:
                    $status_txt = \PhalApi\T('进行中');
                    break;
                case 5:
                    $status_txt = \PhalApi\T('已退款');
                    break;
                case 4:
                    $status_txt = \PhalApi\T('已入账');
                    break;
            }

            $value['addtime'] = date('Y/m/d H:i:s', $value['add_time']);
            unset($value['add_time']);
            $value['status_txt'] = $status_txt;
        }
        $rs['info'] = $list;
        return $rs;

    }


    /**
     * 商家收益统计
     * @param $uid
     * @return void
     * @throws ApiException
     */
    public function incomeStatistics($uid)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        $store = $this->checkStoreIdentity($uid);
        $storeId = $store['id'];

        $startTime = strtotime(date('Y-m-d ' . "00:00:00"));
        $endtTime = strtotime(date('Y-m-d ' . "23:59:59"));

        $all_price_where['paid = ?'] = 1;
        $all_price_where['store_id = ?'] = $storeId;
        //(订单状态（0:待付款 1:已付款(待接单)  2:待配送  3:配送中 4:已完成 5:退款 6:已备货 7:已取消）)

        //总收益(1:已付款(待接单)  2:待配送  3:配送中 4:已完成  6:已备货)
        $allPrice = $this->inStatusGetOne([2, 2, 3, 4, 6], $all_price_where, 'sum(pay_price) as pay_price');
        $all_price = $allPrice['pay_price'] ?? 0;
//        var_dump('总收益:'.$all_price);
        //总可提现(订单已完成)
        $have_price_where['store_id = ?'] = $storeId;
        $have_price_where['paid = ?'] = 1;
        $havePrice = $this->inStatusGetOne([4], $have_price_where, 'sum(pay_price) as pay_price');
        $have_price = $havePrice['pay_price'] ?? 0;
//        var_dump('总可提现:'.$have_price);


        //免运费的金额(订单配送满减金额)
        $freight_free_where['store_id = ?'] = $storeId;
        $freight_free_where['free_shipping = ?'] = 1;
        $freight_free_where['paid = ?'] = 1;
        $freightFree = $this->inStatusGetOne([4], $freight_free_where, 'sum(freight_price) as freight_price');
        $freight_free = $freightFree['freight_price'] ?? 0;
//        var_dump('免运费的金额:'.$freight_free);

        $MerchantRecordModel = new MerchantRecordModel();
        //已经提现(审核通过的)
        $already_price_where['store_id = ?'] = $storeId;
        $already_price_where['status = ?'] = 1;

        $alreadyPrice = $MerchantRecordModel->getOne($already_price_where, 'sum(money) as money');
        $already_price = $alreadyPrice['money'] ?? 0;
//        var_dump('已经提现:'.$already_price);

        //可提现(可提现-已提现-运费减免)
        $for_withdrawal = bcsub(bcsub($have_price, $already_price, 2), $freight_free, 2);
        //待提现(总收益-已提现-运费减免)
        $unread_price = bcsub(bcsub($all_price, $already_price, 2), $freight_free, 2);

        $rs['info'] = compact('all_price', 'for_withdrawal', 'unread_price', 'freight_free');
        return $rs;
    }

    /**
     * 获取订单数及营业额
     * @param $uid
     * @return array
     * @throws ApiException
     */
    public function getStatistical($uid)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        $store = $this->checkStoreIdentity($uid);
        $storeId = $store['id'];
        $startTime = strtotime(date('Y-m-d' . " 00:00:00"));
        $endTime = strtotime(date('Y-m-d' . " 23:59:59"));
        $count = $this->inStatusCount([1, 2, 3, 4, 6], ['store_id = ?' => $storeId, 'add_time >= ?' => $startTime, 'add_time <= ?' => $endTime]);
        $price = $this->inStatusGetOne([1, 2, 3, 4, 6], ['store_id = ?' => $storeId, 'add_time >= ?' => $startTime, 'add_time <= ?' => $endTime], 'sum(pay_price) as pay_price')['pay_price'] ?? 0;
        $rs['info'][] = compact('count', 'price');
        return $rs;
    }


    /**
     * 检测用户身份
     * @param $uid
     * @return array
     * @throws ApiException
     */
    public function checkStoreIdentity($uid)
    {
        $loginInfo = \App\getcaches("merchant_token_{$uid}");
        $store_id = $loginInfo['store']['id'] ?? 0;
        if (!$store_id) {
            throw new ApiException(\PhalApi\T('店铺信息错误!'), 995);
        }
        return $loginInfo['store'] ?? [];
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
