<?php

namespace Rider\Domain;

use App\ApiException;
use App\Domain\MerchantStore as MerchantStoreDomain;
use App\Domain\MerchantStoreCart as MerchantStoreCartDomain;
use Rider\Domain\Balance as Domain_Balance;
use Rider\Domain\User as Domain_User;
use Rider\Model\Orders as Model_Orders;
use Rider\Domain\Ordersrefuse as Domain_Ordersrefuse;
use Rider\Domain\Orderscount as Domain_Orderscount;
use Rider\Domain\Riderlevel as Domain_Riderlevel;
use Rider\Domain\City as Domain_City;
use Rider\Domain\Evaluate as Domain_Evaluate;
use App\Domain\User as App_User;
use App\Domain\MerchantStoreOrder as MerchantStoreOrderDomain;
use App\Model\MerchantStoreOrder as MerchantStoreOrderModel;
use App\Model\MerchantStoreProduct as MerchantStoreProductModel;
use App\Domain\MerchantStoreProduct as MerchantStoreProductDomain;
use App\Model\MerchantStoreProductAttr as MerchantStoreProductAttrModel;
use App\Model\MerchantStoreOrderCartInfo as MerchantStoreOrderCartInfoModel;


class Orders
{

    /**
     * 骑手用户订单关联关系
     * @param $uid
     * @param $users_id
     * @return void
     */
    public function getSubmitOrder($uid, $users_id)
    {
        $rs = ['code' => 0, 'msg' => [], 'info' => []];

        $MerchantStoreOrderModel = new MerchantStoreOrderModel();
        $order = $MerchantStoreOrderModel->getOrderInfo(['rider_id = ?' => $uid, 'uid' => $users_id]);
        if (!$order) {
            throw new ApiException(\PhalApi\T('信息不存在'), 600);
        }

        if ($order['order_type'] == 0) {
            //跑腿订单直接返回
            $rs['info'][] = ['order_id' => $order['orderid']];
            return $rs;
        } else {
            //店铺订单
            $storeOrder = $MerchantStoreOrderModel->getOne(['order_id = ?' => $order['orderid']]);
            if (!$storeOrder) {
                throw new ApiException(\PhalApi\T('店铺订单不存在'), 600);
            }
            $Model_Orders = new Model_Orders();

            $orders = $Model_Orders->getInfo(['store_oid = ?' => $storeOrder['id']]);
            if (!$orders) {
                throw new ApiException(\PhalApi\T('店铺跑腿订单不存在'), 600);
            }

            $rs['info'][] = ['order_id' => $orders['orderno']];
            return $rs;
        }

    }

    /* 支付方式 */
    public function getPayType($k = '')
    {
        $type = [
            '0' => '余额支付',
            '1' => '支付宝',
            '2' => '微信',
            '3' => '微信小程序',
            '4' => '微信H5',
            '5' => '微信内H5',
            '6' => '苹果支付',
        ];
        if ($k == '') {
            return $type;
        }
        return isset($type[$k]) ? $type[$k] : '';
    }

    public function getTypes($k = '')
    {
        $type = [
            '1' => '帮我送',
            '2' => '帮我取',
            '3' => '帮我买',
            '4' => '帮我排队',
            '5' => '帮我办',
            '6' => '外卖配送',
        ];
        if ($k == '') {
            return $type;
        }
        return isset($type[$k]) ? $type[$k] : '';
    }

    /* 订单配送距离 */
    public function getDistance($oinfo)
    {
        $distance = 0;
        $type = $oinfo['type'];
        $extra = json_decode($oinfo['extra'], true);
        if ($type == 1 || $type == 2 || $type == 6) {
            $distance = $extra['distance'] ?? 0;
        }
        if ($type == 3) {
            $distance = 3000;
            $r_type = $extra['type'] ?? 0;
            if ($r_type == 1) {
                $distance = $extra['distance'] ?? 0;
            }
        }

        return $distance;
    }

    /* 订单处理 */
    public function handleInfo($v, $isrid = 0, $rid = 0)
    {

        $type = $v['type'];
        $v['pay_type'] = $this->getPayType($v['paytype']);
        $v['type_t'] = $this->getTypes($type);
        $extra = json_decode($v['extra'], true);

        $users_im = [];
        $store_im = [];

        $UsersDomain = new App_User();
        $users = $UsersDomain->getInfo(['id = ?' => $v['uid']], 'id');
        if ($users) $users_im['userId'] = "users_{$users['id']}";

        $v['users_im'] = $users_im;
        if ($v['store_oid'] > 0) {
            $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
            $storeOrder = $MerchantStoreOrderDomain->getOne(['id = ?' => $v['store_oid']], 'store_id');
            $storeAccount = $UsersDomain->getInfo(['store_id = ?' => $storeOrder['store_id']], 'id');
            $store_im['userId'] = "mer_{$storeAccount['id']}";
        }
        $v['store_im'] = $store_im;


        $tips = '';
        if ($type == 1 || $type == 2) {
            $tips = $extra['catename'];
            if ($extra['weight'] > 0) {
                $tips .= $extra['weight'] . 'kg内';
            }
        }
        if ($type == 3) {
            if ($extra['prepaid'] > 0) {
                $tips = '预估商品费' . $extra['prepaid'] . '元';
            }
        }
        if ($type == 4) {
            if ($extra['length'] > 0) {
                $cha = $extra['length'] * 60;
                $tips = \App\handellength($cha);
            }
        }
        if ($type == 5) {
            if ($extra['prepaid'] > 0) {
                $tips = '预付服务费' . $extra['prepaid'] . '元';
            }
        }


        $v['tips'] = $tips;

        $v['extra'] = $extra;
        $v['service_time'] = \App\handelsvctm($v['servicetime']);
        if ($v['is_now'] == 1) {
            $v['service_time'] = \PhalApi\T('立即取件');
        }
        $v['add_time'] = date('Y-m-d H:i', $v['addtime']);

        $status = $v['status'];
        $grap_time = '';
        $grap_time2 = '';
        $pick_time = '';
        $pick_time2 = '';
        $complete_time = '';
        $complete_time2 = '';

        if ($status != 10) {
            if ($status >= 3) {
                $grap_time = date('Y-m-d H:i', $v['graptime']);
                $grap_time2 = date('H:i', $v['graptime']);
            }

            if ($status >= 4) {
                $pick_time = date('Y-m-d H:i', $v['picktime']);
                $pick_time2 = date('H:i', $v['picktime']);
            }

            if ($status >= 6) {
                $complete_time = date('Y-m-d H:i', $v['completetime']);
                $complete_time2 = date('H:i', $v['completetime']);
            }
        }
        $transtime = '';
        if ($v['transtime'] > 0) {
            $transtime = date('Y-m-d H:i', $v['transtime']);
        }

        $v['trans_time'] = $transtime;
        $v['grap_time'] = $grap_time;
        $v['pick_time'] = $pick_time;
        $v['complete_time'] = $complete_time;
        $v['grap_time2'] = $grap_time2;
        $v['pick_time2'] = $pick_time2;
        $v['complete_time2'] = $complete_time2;
        $thumbs = json_decode($v['thumbs'], true);
        if (!$thumbs) {
            $thumbs = [];
        }
        foreach ($thumbs as $k1 => $v1) {
            $thumbs[$k1] = \App\get_upload_path($v1);
        }
        $v['thumbs'] = $thumbs;

        $isevaluate = '0';
        if ($status == 6 && $isrid == 0) {
            $Domain_Evaluate = new Domain_Evaluate();
            $isevaluate = $Domain_Evaluate->isEvaluate($v['uid'], $v['id']);
        }
        $v['isevaluate'] = $isevaluate;

        $ispre = '0';
        $nowtime = time();
        $today_start = strtotime(date('Ymd', $nowtime));
        $svctm_start = strtotime(date('Ymd', $v['servicetime']));
        if ($svctm_start > $today_start) {
            $ispre = '1';
        }
        $v['ispre'] = $ispre;

        if ($isrid == 1) {
            $v['add_time'] = date('H:i', $v['addtime']);

            $rider_basic = $extra['computed']['money_basic'] ?? 0;
            $rider_distance = $extra['computed']['money_distance'] ?? 0;
            $rider_weight = $extra['computed']['money_weight'] ?? 0;
            $rider_length = $extra['computed']['money_length'] ?? 0;
            $rider_prepaid = $extra['prepaid'] ?? 0;
            $distance = $extra['distance'] ?? 0;
            $rider_timemoney = $extra['timemoney'] ?? 0;
            $rider_fee = $v['fee'] ?? 0;

            if ($v['type'] == 3) {
                $rider_prepaid = 0;

                $buy_type = $extra['type'] ?? 0;
                if ($buy_type == 2) {
                    $distance = 0;
                }
            }

            if ($rider_prepaid <= 0) {
                $rider_prepaid = 0;
            }

            $riderid = $v['riderid'];
            if (!$riderid) {
                $riderid = $rid;
            }
            $income = $v['rider_income'];

            if ($status != 6 && $v['isincome'] == 0) {
                $Domain_Riderlevel = new Domain_Riderlevel();
                $income = $Domain_Riderlevel->getIncome($riderid, $v['type'], $rider_basic, $rider_distance, $distance, $rider_weight, $rider_length, $rider_timemoney, $rider_fee, $rider_prepaid);

            }

            $v['rider_basic'] = $rider_basic;
            $v['rider_distance'] = $rider_distance;
            $v['rider_weight'] = $rider_weight;
            $v['rider_length'] = $rider_length;
            $v['rider_prepaid'] = $rider_prepaid;
            $v['rider_timemoney'] = $rider_timemoney;
            $v['rider_fee'] = $rider_fee;

            $v['income'] = round($income, 2);


            unset($v['uid']);
            unset($v['trade_no']);
            unset($v['orderno_pay']);
            unset($v['couponid']);
            unset($v['usercouponid']);
            unset($v['money']);
            unset($v['money_total']);
            unset($v['discount_money']);
            unset($v['code']);
        }

        unset($v['addtime']);
        unset($v['paytime']);
        unset($v['graptime']);
        unset($v['picktime']);
        if ($v['sendtime'] > 0) {
            $v['sendtime'] = date('Y-m-d H:i', $v['sendtime']);
        }
//        unset($v['sendtime']);
        unset($v['completetime']);
        unset($v['substation_income']);
        unset($v['rider_income']);
        unset($v['isincome']);

        return $v;
    }

    /* 订单列表 */
    public function getList($cityid, $uid, $type, $p)
    {

        $where = [
            'cityid' => $cityid,

        ];
        if (!in_array($type, [1, 2, 3])) {
            return [];
        }
        if ($type == 1) {
            $where['status'] = 2;
            $where['isdel'] = 0;
            $where['oldriderid <> ?'] = $uid;

            $Domain_Ordersrefuse = new Domain_Ordersrefuse();
            $orderids = $Domain_Ordersrefuse->getRefuseids($uid);
            if ($orderids) {
                $where['id not in (?)'] = implode(',', $orderids);
            }
        }
        if ($type == 2) {
            $where['riderid'] = $uid;
            $where['status'] = 3;
        }

        if ($type == 3) {
            $where['riderid'] = $uid;
            $where['status'] = 4;
        }
        $model = new Model_Orders();
        $list = $model->getList($where, $p);

        foreach ($list as $k => $v) {
            $v = self::handleInfo($v, 1, $uid);
            $list[$k] = $v;
        }

        return $list;
    }

    public function getDetail($uid, $oid)
    {
        $rs = ['code' => 0, 'msg' => [], 'info' => []];

        $model = new Model_Orders();
        $where = ['id' => $oid];
        $oinfo = $model->getInfo($where);

        if (!$oinfo) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('订单信息错误');
            return $rs;
        }

        if ($oinfo['riderid'] != $uid) {
            $rs['code'] = 1002;
            $rs['msg'] = \PhalApi\T('订单信息错误');
            return $rs;
        }

        if ($oinfo['istrans'] == 1 && $oinfo['oldriderid'] == $uid) {
            $rs['code'] = 1003;
            $rs['msg'] = \PhalApi\T('转单的订单无法查看详情');
            return $rs;
        }

        $oinfo = self::handleInfo($oinfo, 1);
        $product = $this->getProductInfo($oinfo['store_oid']);
        $oinfo['product'] = $product;

        $expect_time = '';
        $type = $oinfo['type'];
        if ($type == 1 || $type == 2) {
            $length = $oinfo['extra']['length'] ?? 0;
            $expecttime = $oinfo['servicetime'] + $length * 60;

            $expect_time = date('Y-m-d H:i', $expecttime);
        }

        if ($type == 3) {
            $expect_time = date('Y-m-d H:i', $oinfo['servicetime']);
        }

        $oinfo['expect_time'] = $expect_time;

        $rs['info'][0] = $oinfo;

        return $rs;
    }

    public function getProductInfo($storeOid)
    {
        $cartInfo = [];
        $MerchantStoreCartDomain = new MerchantStoreCartDomain();

        if ($storeOid > 0) {
            $MerchantStoreOrderModel = new MerchantStoreOrderModel();
            $storeOrder = $MerchantStoreOrderModel->getOne(['id = ?' => $storeOid], 'id');
            if (!$storeOrder) {
                return [];
            }
            $MerchantStoreOrderCartInfoModel = new MerchantStoreOrderCartInfoModel();
            $MerchantStoreProductModel = new MerchantStoreProductModel();
            $MerchantStoreProductAttrModel = new MerchantStoreProductAttrModel();
            $cartInfo = $MerchantStoreOrderCartInfoModel->selectList(['oid = ?' => $storeOid], 'product_id,cart_num,cart_info,product_attr_id,more_product_attr_id');

            foreach ($cartInfo as &$value) {
                $product = [];
                $productAttr = [];
                if ($value['product_id'] > 0) {
                    $product = $MerchantStoreProductModel->getOne(['id = ?' => $value['product_id']], 'id,name,th_name,price');
                }
                if ($value['product_attr_id'] > 0) {
                    $productAttr = $MerchantStoreProductAttrModel->getOne(['id = ?' => $value['product_attr_id']], 'id,attr_name,th_attr_name,price');
                }
                $value['product'] = $product;
                $value['productAttr'] = $productAttr;
                $value['cart_info'] = json_decode($value['cart_info'], true);
                $value['more_product_attr'] = $MerchantStoreCartDomain->getCateAttrList(!is_array($value['more_product_attr_id']) ? json_decode($value['more_product_attr_id'], true) : [], $value['cart_info']['product'], $value['cart_num']);
                unset($value['cart_info']);
            }
        }
        return $cartInfo;
    }

    /* 订单数量 */
    public function getNums($where)
    {
        $model = new Model_Orders();

        $list = $model->getNums($where);

        return (string)$list;
    }

    public function addFee($orderid, $fee)
    {
        $model = new Model_Orders();

        return $model->addFee($orderid, $fee);

    }

    /* 全部订单 */
    public function getAll($where, $field = '*')
    {
        $model = new Model_Orders();

        $list = $model->getAll($where, $field);

        return $list;
    }

    public function refuse($cityid, $uid, $oid)
    {

        $rs = ['code' => 0, 'msg' => \PhalApi\T('拒接成功'), 'info' => []];

        $model = new Model_Orders();
        $where = ['id' => $oid];
        $oinfo = $model->getInfo($where, 'cityid,riderid');
        if (!$oinfo) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('订单信息错误');
            return $rs;
        }

        if ($oinfo['cityid'] != $cityid) {
            $rs['code'] = 1002;
            $rs['msg'] = \PhalApi\T('订单信息错误');
            return $rs;
        }

        if ($oinfo['riderid'] != 0) {
            $rs['code'] = 1003;
            $rs['msg'] = \PhalApi\T('订单已被接');
            return $rs;
        }

        $add = [
            'riderid' => $uid,
            'oid' => $oid,
            'addtime' => time(),
        ];

        $Domain_Ordersrefuse = new Domain_Ordersrefuse();
        $res = $Domain_Ordersrefuse->add($add);

        return $rs;
    }

    public function grap($cityid, $uid, $oid)
    {

        $rs = ['code' => 0, 'msg' => \PhalApi\T('抢单成功'), 'info' => []];

        $istrans = self::checkTrans($uid, $cityid);
        if ($istrans) {
            $rs['code'] = 1000;
            $rs['msg'] = \PhalApi\T('今日转单已超上限，无法接单');
            return $rs;
        }

        $model = new Model_Orders();
        $where = ['id' => $oid];
        $oinfo = $model->getInfo($where, 'cityid,riderid,status,istrans,oldriderid,type,store_oid,orderno,uid,type,status');

        if ($oinfo['store_oid'] > 0) {//外卖订单
            $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
            $orderInfo = $MerchantStoreOrderDomain->getOne(['id = ?' => $oinfo['store_oid']], 'status');
            if ($orderInfo['status'] != 2) {//检测商家是否接单
                $rs['code'] = 1001;
                $rs['msg'] = \PhalApi\T('暂时无法抢单');
                return $rs;
            }
        }

        if (!$oinfo) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('订单信息错误');
            return $rs;
        }

        if ($oinfo['cityid'] != $cityid) {
            $rs['code'] = 1002;
            $rs['msg'] = \PhalApi\T('订单信息错误');
            return $rs;
        }

        if ($oinfo['status'] < 2) {
            $rs['code'] = 1003;
            $rs['msg'] = \PhalApi\T('订单信息错误');
            return $rs;
        }

        if ($oinfo['riderid'] != 0) {
            $rs['code'] = 980;
            $rs['msg'] = \PhalApi\T('抢单失败1');
            return $rs;
        }

        if ($oinfo['istrans'] == 1 && $oinfo['oldriderid'] == $uid) {
            $rs['code'] = 980;
            $rs['msg'] = \PhalApi\T('抢单失败2');
            return $rs;
        }

        $where = [
            'id' => $oid,
            'riderid' => 0,
            'status' => 2,
        ];

        $up = [
            'riderid' => $uid,
            'status' => 3,
            'graptime' => time(),
        ];
        if ($oinfo['oldriderid'] == 0) {
            $up['oldriderid'] = $uid;
        }

        $res = $model->up($where, $up);
        if (!$res) {
            $rs['code'] = 980;
            $rs['msg'] = \PhalApi\T('抢单失败3');
            return $rs;
        }
        $this->checkTakeoutOrders($oinfo['store_oid'], $uid);
        self::presetIncome($oid);

        $Domain_Ordersrefuse = new Domain_Ordersrefuse();
        $Domain_Ordersrefuse->delorder($oid);

        return $rs;
    }

    /**
     * 骑手接单(外卖订单)处理店铺外卖订单 状态
     * @param $store_oid
     * @param $riderid
     * @return void
     */
    public function checkTakeoutOrders($store_oid, $riderid)
    {
        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $orderInfo = $MerchantStoreOrderDomain->getOne(['id = ?' => $store_oid]);

        if (!$orderInfo) {
            return;
        }
        if ($orderInfo['shipping_type'] == 1 && $orderInfo['status'] == 2) {//待配送的外卖订单
            $update = [
                'delivery_uid' => $riderid,
                'status' => 3,
            ];
            $MerchantStoreOrderDomain->updateOne(['id = ?' => $store_oid], $update);

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


    public function start($uid, $oid, $thumbs)
    {

        $rs = ['code' => 0, 'msg' => \PhalApi\T('操作成功'), 'info' => []];

        if ($thumbs == '') {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('请上传照片');
            return $rs;
        }

        $thumbs = json_decode($thumbs, true);
        if (!$thumbs) {
            $rs['code'] = 1002;
            $rs['msg'] = \PhalApi\T('请上传照片');
            return $rs;
        }

        if (count($thumbs) > 3) {
            $rs['code'] = 1003;
            $rs['msg'] = \PhalApi\T('最多上传3张照片');
            return $rs;
        }

        $model = new Model_Orders();
        $where = ['id' => $oid];
        $oinfo = $model->getInfo($where, 'cityid,riderid,status,store_oid');
        if (!$oinfo) {
            $rs['code'] = 1004;
            $rs['msg'] = \PhalApi\T('订单信息错误');
            return $rs;
        }

        if ($oinfo['riderid'] != $uid) {
            $rs['code'] = 1005;
            $rs['msg'] = \PhalApi\T('订单信息错误');
            return $rs;
        }

        if ($oinfo['status'] != 3) {
            $rs['code'] = 1006;
            $rs['msg'] = \PhalApi\T('订单信息错误');
            return $rs;
        }


        $where = [
            'id' => $oid,
            'riderid' => $uid,
            'status' => 3,
        ];
        $up = [
            'thumbs' => json_encode($thumbs),
            'status' => 4,
            'picktime' => time(),
        ];
        $this->storeOrderStart($oinfo['store_oid']);
        $res = $model->up($where, $up);
        if (!$res) {
            $rs['code'] = 980;
            $rs['msg'] = \PhalApi\T('操作失败');
            return $rs;
        }


        return $rs;
    }

    public function complete($uid, $oid, $code)
    {

        $rs = ['code' => 0, 'msg' => \PhalApi\T('操作成功'), 'info' => []];

        if ($code == '') {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('请输入完成码');
            return $rs;
        }

        $model = new Model_Orders();
        $where = ['id' => $oid];
        $oinfo = $model->getInfo($where, 'cityid,uid,riderid,status,code,money,type,extra,store_oid,orderno,type,status');
        if (!$oinfo) {
            $rs['code'] = 1004;
            $rs['msg'] = \PhalApi\T('订单信息错误');
            return $rs;
        }

        if ($oinfo['riderid'] != $uid) {
            $rs['code'] = 1005;
            $rs['msg'] = \PhalApi\T('订单信息错误');
            return $rs;
        }




        if ($oinfo['status'] != 4) {
            $rs['code'] = 1006;
            $rs['msg'] = \PhalApi\T('订单信息错误');
            return $rs;
        }

        if ($oinfo['code'] != $code) {
            $rs['code'] = 1007;
            $rs['msg'] = \PhalApi\T('完成码错误');
            return $rs;
        }

        $where = [
            'id' => $oid,
            'riderid' => $uid,
            'status' => 4,
        ];
        $up = [
            'status' => 6,
            'completetime' => time(),
        ];

        $res = $model->up($where, $up);
        if (!$res) {
            $rs['code'] = 1008;
            $rs['msg'] = \PhalApi\T('操作失败');
            return $rs;
        }
        $this->storeOrderComplete($oinfo['store_oid']);
        /* 收益 */
        self::setIncome($oid);

        /* 订单统计 */
        $distance = self::getDistance($oinfo);
        $Domain_Orderscount = new Domain_Orderscount();
        $Domain_Orderscount->upCount($uid, 1, $distance, 0);

        /* 用户总消费 */
        $App_User = new App_User();
        $App_User->upField(['id' => $oinfo['uid']], 'consumption', $oinfo['money']);

        return $rs;

    }

    /**
     * 店铺外卖订单取件时 修改店铺订单状态
     * @param $store_oid
     * @return void
     */
    public function storeOrderStart($store_oid)
    {
        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $orderInfo = $MerchantStoreOrderDomain->getOne(['id = ?' => $store_oid]);
        if (!$orderInfo) {
            return;
        }
        if ($orderInfo['shipping_type'] == 1 && $orderInfo['status'] == 2) {//待配送的外卖订单
            $update = [
                'status' => 3,
                'end_time' => time(),
            ];
            $MerchantStoreOrderDomain->updateOne(['id = ?' => $store_oid], $update);
        }
    }

    /**
     * 店铺外卖订单完成是 修改店铺订单状态
     * @param $store_oid
     * @return void
     */
    public function storeOrderComplete($store_oid)
    {
        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $MerchantStoreDomain = new MerchantStoreDomain();
        $orderInfo = $MerchantStoreOrderDomain->getOne(['id = ?' => $store_oid]);
        if (!$orderInfo) {
            return;
        }
        $MerchantStoreOrderCartInfoModel = new MerchantStoreOrderCartInfoModel();
        $cartInfo = $MerchantStoreOrderCartInfoModel->selectList(['oid = ?' => $store_oid]);
        $MerchantStoreProductDomain = new MerchantStoreProductDomain();
        foreach ($cartInfo as $value) {
            $MerchantStoreProductDomain->updateSales($value['product_id']);
            $MerchantStoreDomain->updateScore($value['store_id']);
        }

        if ($orderInfo['shipping_type'] == 1 && $orderInfo['status'] == 3) {//待配送的外卖订单
            $update = [
                'status' => 4,
                'end_time' => time(),
            ];
            $MerchantStoreOrderDomain->updateOne(['id = ?' => $store_oid], $update);
            $orderInfo = $MerchantStoreOrderDomain->getOne(['id = ?' => $store_oid]);

        }
    }

    public function getTransNums($uid)
    {

        $nowtime = time();
        $today = date("Ymd", $nowtime);
        $today_start = strtotime($today);
        //当天 23:59:59
        $today_end = strtotime("{$today} + 1 day");

        $model = new Model_Orders();
        $where = [
            'oldriderid' => $uid,
            'istrans' => 1,
            'transtime >= ?' => $today_start,
            'transtime < ?' => $today_end,
        ];
        $nums = $model->getNums($where);

        return $nums;
    }

    public function checkTrans($uid, $cityid)
    {

        $istip = 0;

        $Domain_Riderlevel = new Domain_Riderlevel();
        $trans_limit = $Domain_Riderlevel->getTransLimit($uid);

        if ($trans_limit == 0) {
            return $istip;
        }

        $trans_nums = self::getTransNums($uid);
        if ($trans_nums >= $trans_limit) {
            $istip = 1;
        }

        return $istip;
    }

    public function trans($uid, $oid)
    {

        $rs = ['code' => 0, 'msg' => \PhalApi\T('操作成功'), 'info' => []];

        if ($oid < 1) {
            $rs['code'] = 1000;
            $rs['msg'] = \PhalApi\T('信息错误');
            return $rs;
        }

        $model = new Model_Orders();
        $where = ['id' => $oid];
        $oinfo = $model->getInfo($where, 'cityid,riderid,status,code,istrans,store_oid');
        if (!$oinfo) {
            $rs['code'] = 1004;
            $rs['msg'] = \PhalApi\T('该订单已经转过单，不能在转单了');
            return $rs;
        }

        if ($oinfo['riderid'] != $uid) {
            $rs['code'] = 1005;
            $rs['msg'] = \PhalApi\T('该订单已经转过单，不能在转单了');
            return $rs;
        }

        if ($oinfo['status'] != 3) {
            $rs['code'] = 1006;
            $rs['msg'] = \PhalApi\T('该订单已经转过单，不能在转单了');
            return $rs;
        }

        if ($oinfo['istrans'] == 1) {
            $rs['code'] = 1007;
            $rs['msg'] = \PhalApi\T('该订单已经转过单，不能在转单了');
            return $rs;
        }

        $where = [
            'id' => $oid,
            'riderid' => $uid,
            'status' => 3,
        ];
        $up = [
            //'status'=>2,
            'istrans' => 2,
            //'riderid'=>0,
            //'graptime'=>0,
            'transtime' => time(),
        ];

        $res = $model->up($where, $up);
        if (!$res) {
            $rs['code'] = 1008;
            $rs['msg'] = \PhalApi\T('操作失败');
            return $rs;
        }

        if ($oinfo['store_oid'] > 0) {
            $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();

            $storeOrder = $MerchantStoreOrderDomain->getOne(['id = ?' => $oinfo['store_oid']], 'status');
            if ($storeOrder && in_array($storeOrder['status'], [3])) {
                //修改店铺订单
                $orderWhere = [
                    'id = ?' => $oinfo['store_oid']
                ];
                $orderUpdate = [
                    'status' => 2,
                    'delivery_uid' => 0,
                ];
                $MerchantStoreOrderDomain->updateOne($orderWhere, $orderUpdate);
            }
        }

        $Domain_Orderscount = new Domain_Orderscount();
        $Domain_Orderscount->upCount($uid, 0, 0, 1);

        return $rs;
    }

    public function getCompleteNums($uid)
    {

        $nowtime = time();
        $today = date("Ymd", $nowtime);
        $today_start = strtotime($today);
        //当天 23:59:59
        $today_end = strtotime("{$today} + 1 day");

        $model = new Model_Orders();
        $where = [
            'riderid' => $uid,
            'status' => 6,
            'graptime >= ?' => $today_start,
            'graptime < ?' => $today_end,
        ];
        $nums = $model->getNums($where);

        return $nums;
    }

    public function getGrapNums($uid)
    {

        $nowtime = time();
        $today = date("Ymd", $nowtime);
        $today_start = strtotime($today);
        //当天 23:59:59
        $today_end = strtotime("{$today} + 1 day");

        $model = new Model_Orders();
        $where = [
            'oldriderid' => $uid,
            'graptime >= ?' => $today_start,
            'graptime < ?' => $today_end,
        ];
        $nums = $model->getNums($where);

        return $nums;
    }

    public function getAllDistance($uid)
    {

        $nowtime = time();
        $today = date("Ymd", $nowtime);
        $today_start = strtotime($today);
        //当天 23:59:59
        $today_end = strtotime("{$today} + 1 day");

        $model = new Model_Orders();

        $where4 = [
            'riderid' => $uid,
            'status' => 6,
            'graptime >= ?' => $today_start,
            'graptime < ?' => $today_end,
            'type' => [1, 2, 3, 6],
        ];
        $list = $model->getAll($where4, 'type,extra');
        $distance = 0;
        foreach ($list as $k => $v) {
            $dis = self::getDistance($v);
            $distance += $dis;
        }

        return round($distance, 2);
    }

    public function getCount($uid)
    {

        $rs = [
            'orders' => 0,
            'trans' => 0,
            'graps' => 0,
            'distance' => 0,
        ];

        //完成订单
        $orders = self::getCompleteNums($uid);
        //转单
        $trans = self::getTransNums($uid);

        //已经订单
        $graps = self::getGrapNums($uid);

        //配送历程
        $distance = self::getAllDistance($uid);


        $rs['orders'] = $orders;
        $rs['trans'] = $trans;
        $rs['graps'] = $graps;
        $rs['distance'] = $distance;

        return $rs;
    }

    public function getCountList($uid, $type, $p)
    {

        $where = [
            'oldriderid' => $uid,

        ];
        if (!in_array($type, [0, 1, 2, 3])) {
            return [];
        }
        if ($type == 0) {
            $where['(status=6 and istrans=0) or istrans=?'] = 1;
        }
        if ($type == 1) {
            $where['status'] = 6;
            $where['istrans'] = 0;
        }
        if ($type == 2) {
            $where['istrans'] = 1;
        }

        $model = new Model_Orders();
        $list = $model->getList($where, $p);

        foreach ($list as $k => $v) {
            $v = self::handleInfo($v, 1);
            $list[$k] = $v;
        }

        return $list;
    }

    public function presetIncome($oid)
    {
        $model = new Model_Orders();
        $where = ['id' => $oid];
        $oinfo = $model->getInfo($where, 'id,uid,orderno,cityid,riderid,status,type,extra,fee,money_total,isincome');
        if (!$oinfo) {
            return 0;
        }

        if ($oinfo['status'] == 6) {
            return 0;
        }

        if ($oinfo['isincome'] == 1) {
            return 0;
        }

        $extra = json_decode($oinfo['extra'], true);

        $rider_basic = $extra['computed']['money_basic'] ?? 0;
        $rider_distance = $extra['computed']['money_distance'] ?? 0;
        $rider_weight = $extra['computed']['money_weight'] ?? 0;
        $rider_length = $extra['computed']['money_length'] ?? 0;
        $rider_prepaid = $extra['prepaid'] ?? 0;
        $distance = $extra['distance'] ?? 0;
        $rider_timemoney = $extra['timemoney'] ?? 0;
        $rider_fee = $oinfo['fee'] ?? 0;

        if ($oinfo['type'] == 3) {
            $rider_prepaid = 0;

            $buy_type = $extra['type'] ?? 0;
            if ($buy_type == 2) {
                $distance = 0;
            }
        }

        if ($rider_prepaid <= 0) {
            $rider_prepaid = 0;
        }

        $Domain_Riderlevel = new Domain_Riderlevel();

        $income = $Domain_Riderlevel->getIncome($oinfo['riderid'], $oinfo['type'], $rider_basic, $rider_distance, $distance, $rider_weight, $rider_length, $rider_timemoney, $rider_fee, $rider_prepaid);
        if ($income > $oinfo['money_total']) {
            $income = $oinfo['money_total'];
        }
        $substation_income = $oinfo['money_total'] - $income;
        $rate = 0;
        if ($substation_income < 0) {
            $substation_income = 0;
        }
        if ($substation_income > 0) {
            $Domain_City = new Domain_City();
            $cinfo = $Domain_City->getInfo($oinfo['cityid']);
            if ($cinfo) {
                $rate = $cinfo['rate'];

                $substation_income = floor($substation_income * (100 - $rate)) * 0.01;
            }
        }

        $where1 = [
            'id' => $oid,
            'isincome' => 0,
        ];
        $rider_add = $rider_weight + $rider_timemoney + $rider_fee + $rider_prepaid;
        $up1 = [
            'isincome' => 1,
            'rider_income' => $income,
            'substation_income' => $substation_income,
            'substation_rate' => $rate,
            'rider_add' => $rider_add,
        ];
        $res1 = $model->up($where1, $up1);
        if (!$res1) {
            return 0;
        }

        return 1;
    }

    public function setIncome($oid)
    {
        $model = new Model_Orders();
        $where = ['id' => $oid];
        $oinfo = $model->getInfo($where, 'id,uid,orderno,cityid,riderid,status,extra,isincome,rider_income,substation_income');
        if (!$oinfo) {
            return 0;
        }

        if ($oinfo['status'] != 6) {
            return 0;
        }

//        if ($oinfo['isincome'] == 1) {
//            return 0;
//        }

        $income = $oinfo['rider_income'];
        $substation_income = $oinfo['substation_income'];

        \PhalApi\DI()->notorm->beginTransaction('db_master');
        $where1 = [
            'id' => $oid,
            'isincome' => 1,
        ];
        $up1 = [
            'isincome' => 2,
        ];
        $res1 = $model->up($where1, $up1);
        if (!$res1) {
            return 0;
        }

        if ($income <= 0) {
            \PhalApi\DI()->notorm->commit('db_master');
            return 1;
        }

        $Domain_User = new Domain_User();
        $where2 = [
            'id' => $oinfo['riderid'],
        ];
        $res2 = $Domain_User->addBalance($where2, $income);
        if (!$res2) {
            \PhalApi\DI()->notorm->rollback('db_master');
            return 0;
        }

        $Domain_Balance = new Domain_Balance();
        $res3 = $Domain_Balance->add($oinfo['riderid'], 1, 1, $oinfo['id'], $oinfo['orderno'], 1, $income);
        if (!$res3) {
            \PhalApi\DI()->notorm->rollback('db_master');
        }

        /* 分销 */
        $extra = json_decode($oinfo['extra'], true);
        $rider_basic = $extra['computed']['money_basic'] ?? 0;



        \PhalApi\DI()->notorm->commit('db_master');

        return 1;
    }

}
