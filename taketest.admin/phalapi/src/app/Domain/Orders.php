<?php

namespace App\Domain;

use App\ApiException;
use App\Domain\Pay as Domain_Pay;
use Rider\Domain\Ordersrefuse as Domain_Ordersrefuse;
use Rider\Model\Orders as Model_Orders;
use Rider\Model\Ordersrefundrecord as Model_Ordersrefundrecord;
use Rider\Domain\Orders as Domain_Orders;
use App\Domain\MerchantStoreOrder as MerchantStoreOrderDomain;
use Rider\Domain\Location as Domain_Location;
use Rider\Domain\User as Rider_User;
use Rider\Domain\Evaluate as Domain_Evaluate;
use App\Domain\User as Domain_User;
use App\Domain\Balance as Domain_Balance;
use Merchant\Domain\MerchantStoreOrder as MerchantStoreOrder_Domain;

class Orders
{




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


    public function getList($uid, $type, $keyword, $p)
    {

        $where = [
            'uid' => $uid,
            'isdel' => 0,
            'type <> ?' => 6,
        ];
        if ($type == 1) {//待支付
            $where['status'] = 1;
        }
        if ($type == 2) {//待接单
            $where['status'] = 2;
        }
        if ($type == 3) {//进行中
            $where['status'] = [3, 4];
        }
        if ($type == 4) {//已完成
            $where['status'] = 6;
        }
        if ($type == 5) {//取消/退款
            $where['status >=? '] = 7;
        }

        if ($keyword != '') {
            $where["f_name like '%{$keyword}%' or t_name like '%{$keyword}%' or pick_name like '%{$keyword}%' or recip_name like '%{$keyword}%' or orderno like '%{$keyword}%' or pick_phone like '%{$keyword}%' or recip_phone like ?"] = '%' . $keyword . '%';
        }
        $Domain_Orders = new Domain_Orders();

        $model = new Model_Orders();
        $list = $model->getList($where, $p);

        foreach ($list as $k => $v) {
            $v = $Domain_Orders->handleInfo($v);
            $v['status_des'] = $this->getOrderDes($v['status']);
            $list[$k] = $v;
        }

        return $list;
    }


    /**
     * 订单状态说明
     * @param $status
     * @return mixed|void
     */
    public function getOrderText($state)
    {
        $orderState = [//订单提示
            1 => \PhalApi\T('待支付'),
            2 => \PhalApi\T('待接单'),
            3 => \PhalApi\T('进行中'),
            4 => \PhalApi\T('进行中'),
            5 => \PhalApi\T('已送达'),
            6 => \PhalApi\T('已完成'),
            7 => \PhalApi\T('取消/退款中'),
            8 => \PhalApi\T('退款完毕'),
            9 => \PhalApi\T('退款失败'),
            10 => \PhalApi\T('已取消'),
        ];
        if (array_key_exists($state, $orderState)) {
            return $orderState[$state];
        }
        return '--';
    }

    /**
     * 订单状态说明
     * @param $status
     * @return mixed|void
     */
    public function getOrderDes($state)
    {
        $orderState = [//订单提示
            1 => \PhalApi\T('订单等待支付'),
            2 => \PhalApi\T('我们收到您的订单了'),
            3 => \PhalApi\T('订单已接单'),
            4 => \PhalApi\T('骑手正在配送中'),
            5 => \PhalApi\T('订单已送达'),
            6 => \PhalApi\T('已完成'),
            7 => \PhalApi\T('退款中'),
            8 => \PhalApi\T('退款完毕'),
            9 => \PhalApi\T('退款失败'),
            10 => \PhalApi\T('已取消'),
        ];
        if (array_key_exists($state, $orderState)) {
            return $orderState[$state];
        }
        return '--';
    }


    public function getDetail($uid, $orderid)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        if ($orderid < 1) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('信息错误');
            return $rs;
        }

        $model = new Model_Orders();
        $where = [
            'id' => $orderid,
            'uid' => $uid,
            'isdel' => 0,
        ];
        $oinfo = $model->getInfo($where);
        if (!$oinfo) {
            $rs['code'] = 1002;
            $rs['msg'] = \PhalApi\T('订单信息错误');
            return $rs;
        }
        $Domain_Orders = new Domain_Orders();

        $nowtime = time();

        $type = $oinfo['type'];
        $status = $oinfo['status'];
        $addtime = $oinfo['addtime'];

        if ($status == 1) {
            if ($nowtime - $addtime >= 30 * 60) {
                $oinfo['status'] = 10;
                $status = 10;
                $model->up(['id' => $oinfo['id']], ['status' => 10, 'canceltime' => $nowtime]);
            }
        }

        $prepaytime = '';
        if ($status == 1) {
            $prepaytime = 30 * 60 - (time() - $addtime);
        }

        $oinfo = $Domain_Orders->handleInfo($oinfo);

        $oinfo['prepaytime'] = $prepaytime;

        $rinfo = (object)[];
        $oinfo['rinfo_im'] = (object)[];
        if ($oinfo['riderid'] > 0) {
            $rinfo = \App\getRiderInfo($oinfo['riderid']);

            $Rider_User = new Rider_User();
            $riderinfo = $Rider_User->getInfo(['id' => $oinfo['riderid']], 'star');

            $rinfo['star'] = $riderinfo['star'] ?? '5.0';
            $oinfo['rinfo_im'] = ['user_id' => "rider_" . $rinfo['id'] ?? ''];

        }
        $oinfo['rinfo'] = $rinfo;

        $forecast = $oinfo['servicetime'];
        if ($type == 1 || $type == 2) {
            if ($status >= 2 && $status < 6) {
                $length = $oinfo['extra']['length'] ?? 0;
                $forecast = $oinfo['servicetime'] + $length * 60;
            }
        }
        if ($type == 3) {
        }
        if ($type == 4) {
        }
        if ($type == 5) {
        }

        $today = date("Ymd", $nowtime);
        $today_start = strtotime($today);

        $today_end = strtotime("{$today} + 1 day");

        //能否催单
        $oinfo['is_reminder'] = $this->isReminder($oinfo);

        if ($forecast < $today_start || $forecast >= $today_end) {
            $forecast_time = date('m-d H:i', $forecast);
        } else {
            $forecast_time = date('H:i', $forecast);
        }

        $oinfo['forecast_time'] = $forecast_time;
        $oinfo['status_des'] = $this->getOrderDes($oinfo['status']);
        $oinfo['status_text'] = $this->getOrderText($oinfo['status']);
        $oinfo['way'] = \PhalApi\T('平台配送');

        $rs['info'][] = $oinfo;
        return $rs;
    }

    /**
     * 是否能催单
     * @param $orderInfo
     * @return int
     */
    public function isReminder($orderInfo){
        $type = $orderInfo['type'];
        $status = $orderInfo['status'];

        $forecast = $orderInfo['servicetime'];
        if ($type == 1 || $type == 2) {
            if ($status >= 2 && $status < 6) {
                $length = $orderInfo['extra']['length'] ?? 0;
                $forecast = $orderInfo['servicetime'] + $length * 60;
            }
        }

        if ((time() > $forecast) && ($status <= 4)) {
            return 1;
        }
        return 0;
    }

    public function getLocation($uid, $orderid, $type)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        if ($orderid < 1) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('信息错误');
            return $rs;
        }
        if ($type) {
            $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
            $oinfo = $MerchantStoreOrderDomain->getOne(['id = ?' => $orderid, 'is_del = ?' => 0], 'id,delivery_uid');
        } else {
            $model = new Model_Orders();
            $where = [
                'id' => $orderid,
                'uid' => $uid,
                'isdel' => 0,
            ];
            $oinfo = $model->getInfo($where, 'status,riderid');
            if ($oinfo) {
                if (!in_array($oinfo['status'], [3, 4, 5])) {
                    $rs['code'] = 1003;
                    $rs['msg'] = \PhalApi\T('非服务中，无法查看');
                    return $rs;
                }
            }
        }

        if (!$oinfo) {
            $rs['code'] = 1002;
            $rs['msg'] = \PhalApi\T('订单信息错误');
            return $rs;
        }

        $Domain_Location = new Domain_Location();
        $where = [
            'uid' => $type ? $oinfo['delivery_uid'] : $oinfo['riderid']
        ];
        $linfo = $Domain_Location->getLocation($where);

        $rs['info'][0] = $linfo;

        return $rs;
    }

    public function create($uid, $order_data, $payid, $usercouponid, $openid = '', $is_mer = 0)
    {

        $rs = ['code' => 0, 'msg' => \PhalApi\T('操作成功'), 'info' => []];

        $nowtime = time();

        $money = $order_data['money'];
        $fee = $order_data['fee'];
        if (!$fee) {
            $fee = 0;
        }

        if ($fee > 100) {
            $rs['code'] = 1008;
            $rs['msg'] = \PhalApi\T('小费不能超过100元');
            return $rs;
        }

        $orderid = $uid . '_' . date('ymdHis') . rand(100, 999);
        $order_data['orderno'] = $orderid;
        $order_data['addtime'] = $nowtime;
        $money = $money + $fee;
        $order_data['money_total'] += $fee;
        if ($order_data['type'] == 5) {
            $extra = json_decode($order_data['extra'], true);
            $prepaid = floatval($extra['prepaid']);
            $money = $money + $prepaid;

            $order_data['money_total'] += $prepaid;
        }
        $order_data['money'] = $money;

        $code = \App\random(4);
        $order_data['code'] = $code;

        \PhalApi\DI()->notorm->beginTransaction('db_master');

        if ($money == 0 && $payid != 0) {
            \PhalApi\DI()->notorm->rollback('db_master');
            $rs['code'] = 970;
            $rs['msg'] = \PhalApi\T('价格变动，请重新下单');
            return $rs;
        }

        if ($money != 0) {
            if ($payid < 0 || $payid > 6) {
                \PhalApi\DI()->notorm->rollback('db_master');
                $rs['code'] = 1013;
                $rs['msg'] = \PhalApi\T('请选择正确的支付方式');
                return $rs;
            }
        }

        $model = new Model_Orders();
        $res = $model->add($order_data);

        if ($res === false) {
            \PhalApi\DI()->notorm->rollback('db_master');
            $rs['code'] = 1006;
            $rs['msg'] = \PhalApi\T('下单失败，请重试');
            return $rs;
        }

        /* 定时处理订单过期未支付 */
        $key = 'orders_addtime';
        \App\zAdd($key, $nowtime, $nowtime);
        /* 定时处理订单过期未支付 */

        $oid = is_array($res) ? $res['id'] : $res;

        if ($money == 0) {
            \PhalApi\DI()->notorm->commit('db_master');

            self::handelPay($orderid);

            $rs['info'][0]['ali'] = '';
            $rs['info'][0]['wx'] = '';

            $rs['info'][0]['orderid'] = $orderid;
            $rs['info'][0]['money'] = (string)$money;
            return $rs;
        }
        \PhalApi\DI()->notorm->commit('db_master');

        $res = self::pay($uid, $oid, $orderid, $money, $payid, $openid, $is_mer);
        $res['info'][0]['order_id'] = $oid;
        return $res;
    }

    public function create2($uid, $order_data, $payid, $usercouponid, $openid = '', $store_id, $orderID)
    {

        $rs = ['code' => 0, 'msg' => \PhalApi\T('操作成功'), 'info' => []];

        $nowtime = time();
        $money = $order_data['money'];
        $fee = $order_data['fee'];
        if (!$fee) {
            $fee = 0;
        }

        if ($fee > 100) {
            throw new ApiException(\PhalApi\T('小费不能超过100元'));
        }


        $orderid = $uid . '_' . date('ymdHis') . rand(100, 999);
        $order_data['orderno'] = $orderid;
        $order_data['addtime'] = $nowtime;
        $money = $money + $fee;
        $order_data['money_total'] += $fee;
        if ($order_data['type'] == 5) {
            $extra = json_decode($order_data['extra'], true);
            $prepaid = floatval($extra['prepaid']);
            $money = $money + $prepaid;

            $order_data['money_total'] += $prepaid;
        }
        $order_data['money'] = $money;

        $code = \App\random(4);
        $order_data['code'] = $code;

        if ($money == 0) {
            throw new ApiException(\PhalApi\T('价格变动，请重新下单2'));
        }

        if ($money != 0) {
            if ($payid < 0 || $payid > 6) {
                throw new ApiException(\PhalApi\T('请选择正确的支付方式'));
            }
        }
        $model = new Model_Orders();
        $res = $model->add($order_data);
        if ($res === false) {
            throw new ApiException(\PhalApi\T('下单失败，请重试'));
        }


        /* 新订单通知 */
        $key = 'orders_mer_new';
        \App\hSet($key, $store_id, $orderID);
        /* 新订单通知 */

        $oid = $res;


        $rs['info']['order_id'] = $oid;
        return $rs;
    }

    public function repay($uid, $orderid, $payid, $openid = '', $is_mer = 0)
    {
        $rs = ['code' => 0, 'msg' => \PhalApi\T('操作成功'), 'info' => []];

        if ($orderid < 1) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('信息错误');
            return $rs;
        }

        $model = new Model_Orders();
        $where = [
            'id' => $orderid,
            'uid' => $uid,
            'isdel' => 0,
        ];
        $oinfo = $model->getInfo($where, 'id,orderno,status,money,addtime');
        if (!$oinfo) {
            $rs['code'] = 1002;
            $rs['msg'] = \PhalApi\T('订单信息错误');
            return $rs;
        }

        if ($oinfo['status'] == 10) {
            $rs['code'] = 1003;
            $rs['msg'] = \PhalApi\T('订单已取消，无法支付');
            return $rs;
        }

        if ($oinfo['status'] >= 2) {
            $rs['code'] = 1004;
            $rs['msg'] = \PhalApi\T('订单已支付');
            return $rs;
        }

        $nowtime = time();
        if ($oinfo['addtime'] + 30 * 60 <= $nowtime) {
            $rs['code'] = 1005;
            $rs['msg'] = \PhalApi\T('订单已超时，无法支付');
            return $rs;
        }

        if ($oinfo['money'] == 0) {
            $rs['code'] = 1006;
            $rs['msg'] = \PhalApi\T('订单已支付，无法支付');
            return $rs;
        }

        if ($payid < 0 || $payid > 6) {
            $rs['code'] = 1007;
            $rs['msg'] = \PhalApi\T('请选择正确的支付方式');
            return $rs;
        }

        $orderno = $uid . '_' . date('ymdHis') . rand(100, 999);

        $model = new Model_Orders();

        $res = $model->up(['id' => $orderid], ['paytype' => $payid, 'orderno_pay' => $orderno]);
        if (!$res) {
            $rs['code'] = 1008;
            $rs['msg'] = \PhalApi\T('支付失败，请重试');
            return $rs;
        }

        $res = self::pay($uid, $orderid, $orderno, $oinfo['money'], $payid, $openid, $is_mer);
        return $res;

    }

    public function pay($uid, $oid, $orderno, $money, $payid, $openid, $is_mer = 0)
    {

        $rs = ['code' => 0, 'msg' => \PhalApi\T('支付成功'), 'info' => []];

        if ($payid == 0) {
            $Domain_User = new Domain_User();
            $where = [
                'id' => $uid,
                'balance >= ?' => $money,
            ];
            $total = 0 - $money;
            $isok = $Domain_User->upField($where, 'balance', $total);
            if (!$isok) {
                $rs['code'] = 981;
                $rs['msg'] = \PhalApi\T('余额不足');
                return $rs;
            }

            $Domain_Balance = new Domain_Balance();
            $Domain_Balance->add($uid, 2, 2, $oid, $orderno, 1, $money);

            self::handelPay($orderno);

            $rs['info'][0]['orderid'] = $orderno;
            $rs['info'][0]['money'] = (string)$money;

            return $rs;
        }


        $Domain_Pay = new Domain_Pay();
        $res = $Domain_Pay->pay($orderno, $money, $payid, '下单', 'orderpay', $openid, $is_mer);
        $res['info'][0]['order_id'] = $oid;
        return $res;

    }

    public function getNums($where)
    {
        $model = new Model_Orders();

        $list = $model->getNums($where);

        return (string)$list;
    }

    public function getAll($where, $field = '*')
    {
        $model = new Model_Orders();

        $list = $model->getAll($where, $field);

        return $list;
    }

    public function handelPay($orderno)
    {

        $where = ["orderno = '{$orderno}' or orderno_pay = ?" => $orderno];

        $model = new Model_Orders();
        $orderinfo = $model->getInfo($where);

        if (!$orderinfo) {
            return 0;
        }

        if ($orderinfo['status'] != 1) {
            return 1;
        }

        $nowtime = time();
        /* 更新 订单状态 */
        $status = 2;
        $data['status'] = $status;
        $data['paytime'] = $nowtime;
        $model->up(['id' => $orderinfo['id']], $data);


        if ($orderinfo['type'] != 6) {
            //店铺配送订单需要 商家接单后发
            /* 新订单通知 */
            $key = 'orders_new';
            \App\hSet($key, $orderinfo['cityid'], $nowtime);
            /* 新订单通知 */
        }


        $uid = $orderinfo['uid'];
        /*$Domain_User=new Domain_User();

        $where5=['id'=>$uid];
        $Domain_User->upField($where5,'consumption',$orderinfo['money']);*/

        return 2;

    }

    public function cancel($uid, $orderid)
    {

        $rs = ['code' => 0, 'msg' => \PhalApi\T('操作成功'), 'info' => []];

        if ($orderid < 1) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('信息错误');
            return $rs;
        }

        $model = new Model_Orders();
        $where = [
            'id' => $orderid,
            'uid' => $uid,
            'isdel' => 0,
        ];
        $oinfo = $model->getInfo($where, 'id,orderno,status,money,paytype,is_mer');
        if (!$oinfo) {
            $rs['code'] = 1002;
            $rs['msg'] = \PhalApi\T('订单信息错误');
            return $rs;
        }

        if ($oinfo['status'] == 10) {
            $rs['code'] = 1003;
            $rs['msg'] = \PhalApi\T('订单已取消，请勿重复操作');
            return $rs;
        }

        if ($oinfo['status'] >= 7) {
            $rs['code'] = 1004;
            $rs['msg'] = \PhalApi\T('订单已申请退款');
            return $rs;
        }

        if ($oinfo['status'] >= 3) {
            $rs['code'] = 1005;
            $rs['msg'] = \PhalApi\T('订单服务进行中，无法取消');
            return $rs;
        }


        \PhalApi\DI()->notorm->beginTransaction('db_master');
        try {
            $refund_order_id = $uid . '_' . date('ymdHis') . rand(100, 999);

            $where = [
                'id' => $orderid,
                'uid' => $uid,
                'status' => $oinfo['status'],
            ];
            $up = [
                'status' => 10,
                'canceltime' => time(),
                'refund_order_id' => $refund_order_id,
            ];

            $res = $model->up($where, $up);
            if (!$res) {
                throw new ApiException("取消失败，请重试");
            }

            if ($oinfo['status'] == 2) {
                $money = $oinfo['money'];
                if ($money > 0) {
                    $MerchantStoreOrder_Domain = new MerchantStoreOrder_Domain();
                    $MerchantStoreOrder_Domain->originalRefund($oinfo['paytype'], $oinfo['orderno'], $refund_order_id, $money, '退款', $oinfo['is_mer']);
                }
            }

            $Domain_Ordersrefuse = new Domain_Ordersrefuse();
            $Domain_Ordersrefuse->delorder($oinfo['id']);

        } catch (\Exception $exception) {
            \PhalApi\DI()->notorm->rollback('db_master');
            $rs['code'] = 400;
            $rs['msg'] = \PhalApi\T($exception->getMessage());
            return $rs;
        }
        \PhalApi\DI()->notorm->commit('db_master');

        return $rs;
    }

    public function refund($uid, $orderid, $reason)
    {

        $rs = ['code' => 0, 'msg' => \PhalApi\T('操作成功'), 'info' => []];

        if ($orderid < 1) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('信息错误');
            return $rs;
        }
        if ($reason == '') {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('请选择退款原因');
            return $rs;
        }

        $model = new Model_Orders();
        $where = [
            'id' => $orderid,
            'uid' => $uid,
            'isdel' => 0,
        ];
        $oinfo = $model->getInfo($where, 'id,orderno,status,riderid');
        if (!$oinfo) {
            $rs['code'] = 1002;
            $rs['msg'] = \PhalApi\T('订单信息错误');
            return $rs;
        }

        if ($oinfo['status'] == 10) {
            $rs['code'] = 1003;
            $rs['msg'] = \PhalApi\T('订单已取消，无法申请退款');
            return $rs;
        }

        if ($oinfo['status'] == 1) {
            $rs['code'] = 1004;
            $rs['msg'] = \PhalApi\T('订单未支付，无法申请退款');
            return $rs;
        }

        if ($oinfo['status'] == 7) {
            $rs['code'] = 1004;
            $rs['msg'] = \PhalApi\T('订单已申请退款，请勿重复申请');
            return $rs;
        }

        if ($oinfo['status'] == 8) {
            $rs['code'] = 1004;
            $rs['msg'] = \PhalApi\T('订单已申请退款成功，请勿重复申请');
            return $rs;
        }

        if ($oinfo['status'] != 3 && $oinfo['status'] <= 6) {
            $rs['code'] = 1005;
            $rs['msg'] = \PhalApi\T('订单服务进行中，无法申请退款');
            return $rs;
        }

        $nowtime = time();
        $where = [
            'id' => $orderid,
            'uid' => $uid,
            'status = 3 or status=?' => 9,
        ];
        $up = [
            'status' => 7,
            'refundtime' => $nowtime,
        ];

        $res = $model->up($where, $up);
        if (!$res) {
            $rs['code'] = 1007;
            $rs['msg'] = \PhalApi\T('申请退款失败，请重试');
            return $rs;
        }

        $add = [
            'uid' => $uid,
            'oid' => $oinfo['id'],
            'status' => 7,
            'addtime' => $nowtime,
            'reason' => $reason,
        ];
        $Model_Ordersrefundrecord = new Model_Ordersrefundrecord();
        $Model_Ordersrefundrecord->add($add);

        /* 退款取消订单 通知骑手 */
        $key = 'orders_refund';
        \App\hSet($key, $oinfo['riderid'], $nowtime);
        return $rs;
    }

    public function cancelrefund($uid, $orderid)
    {

        $rs = ['code' => 0, 'msg' => \PhalApi\T('操作成功'), 'info' => []];

        if ($orderid < 1) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('信息错误');
            return $rs;
        }

        $model = new Model_Orders();
        $where = [
            'id' => $orderid,
            'uid' => $uid,
            'isdel' => 0,
        ];
        $oinfo = $model->getInfo($where, 'id,orderno,status');
        if (!$oinfo) {
            $rs['code'] = 1002;
            $rs['msg'] = \PhalApi\T('订单信息错误');
            return $rs;
        }

        if ($oinfo['status'] != 7) {
            $rs['code'] = 1003;
            $rs['msg'] = \PhalApi\T('订单未在申请退款中，无法取消');
            return $rs;
        }

        $where = [
            'id' => $orderid,
            'uid' => $uid,
            'status' => 7,
        ];
        $up = [
            'status' => 3,
        ];

        $res = $model->up($where, $up);
        if (!$res) {
            $rs['code'] = 1007;
            $rs['msg'] = \PhalApi\T('取消申请退款失败，请重试');
            return $rs;
        }

        return $rs;
    }

    public function getRefund($uid, $orderid)
    {

        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        if ($orderid < 1) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('信息错误');
            return $rs;
        }

        $model = new Model_Orders();
        $where = [
            'id' => $orderid,
            'uid' => $uid,
            'isdel' => 0,
        ];
        $oinfo = $model->getInfo($where, 'id,orderno,status,money');
        if (!$oinfo) {
            $rs['code'] = 1002;
            $rs['msg'] = \PhalApi\T('订单信息错误');
            return $rs;
        }

        if (!in_array($oinfo['status'], [7, 8, 9])) {
            $rs['code'] = 1005;
            $rs['msg'] = \PhalApi\T('订单未申请退款');
            return $rs;
        }

        $list = [];
        $where = [
            'uid' => $uid,
            'oid' => $oinfo['id'],
        ];
        $Model_Ordersrefundrecord = new Model_Ordersrefundrecord();
        $record = $Model_Ordersrefundrecord->getAll($where);

        $reason = '';
        foreach ($record as $k => $v) {

            $status = $v['status'];

            $title = '退款申请已提交';
            $des = '您的退款申请已提交成功';
            if ($status == 8) {
                $title = '平台确认退款';
                $des = '平台已确认退款,退款金额已退回至账户余额';
            }

            if ($status == 9) {
                $title = '退款失败';
                $des = '平台审核未通过，退款失败';
            }
            $list[] = [
                'title' => $title,
                'des' => $des,
                'time' => date('Y-m-d H:i', $v['addtime']),
            ];

            if ($status == 7) {
                $reason = $v['reason'];
                break;
            }
        }

        $info = [
            'status' => $oinfo['status'],
            'money' => $oinfo['money'],
            'orderno' => $oinfo['orderno'],
            'reason' => $reason,
            'list' => $list,
        ];
        $rs['info'][0] = $info;

        return $rs;
    }

    public function track($uid, $orderid)
    {

        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        if ($orderid < 1) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('信息错误');
            return $rs;
        }

        $model = new Model_Orders();
        $where = [
            'id' => $orderid,
            'uid' => $uid,
            'isdel' => 0,
        ];
        $oinfo = $model->getInfo($where, 'id,type,orderno,status,riderid,addtime,paytime,canceltime,graptime,picktime,completetime');
        if (!$oinfo) {
            $rs['code'] = 1002;
            $rs['msg'] = \PhalApi\T('订单信息错误');
            return $rs;
        }
        $status = $oinfo['status'];
        $type = $oinfo['type'];

        $list = [];

        if ($status == 10) {
            $list[] = [
                'title' => '订单已取消',
                'des' => '订单取消成功',
                'time' => date('Y-m-d H:i', $oinfo['canceltime']),
            ];

        }

        if ($status >= 7 && $status < 10) {
            $Model_Ordersrefundrecord = new Model_Ordersrefundrecord();
            $where = [
                'uid' => $uid,
                'oid' => $oinfo['id'],
            ];
            $record = $Model_Ordersrefundrecord->getAll($where);
            foreach ($record as $k => $v) {
                $title = '退款中';
                $des = '退款申请已提交，待平台审核';
                if ($v['status'] == 8) {
                    $title = '退款成功';
                    $des = '平台审核已通过';
                }

                if ($v['status'] == 9) {
                    $title = '退款失败';
                    $des = '平台审核未通过';
                }
                $list[] = [
                    'title' => $title,
                    'des' => $des,
                    'time' => date('Y-m-d H:i', $v['addtime']),
                ];
            }

        }


        if ($status != 10) {
            if ($status <= 6) {
                if ($status >= 6) {
                    $list[] = [
                        'title' => '订单已完成',
                        'des' => '感谢您的使用,欢迎再次下单',
                        'time' => date('Y-m-d H:i', $oinfo['completetime']),
                    ];
                }

                if ($status >= 4) {
                    $title = '配送中';
                    $des = '配送员正在为您配送';
                    if (in_array($type, [4, 5])) {
                        $title = '服务中';
                        $des = '配送员正在为您服务';
                    }
                    $list[] = [
                        'title' => $title,
                        'des' => $des,
                        'time' => date('Y-m-d H:i', $oinfo['picktime']),
                    ];
                }
            }


            if ($status >= 3) {
                $rinfo = \App\getRiderInfo($oinfo['riderid']);
                $list[] = [
                    'title' => '骑手已接单',
                    'des' => '平台将订单指派给配送员:' . $rinfo['user_nickname'],
                    'time' => date('Y-m-d H:i', $oinfo['graptime']),
                ];
            }

            if ($status >= 2) {

                $list[] = [
                    'title' => '订单已支付',
                    'des' => '订单支付成功',
                    'time' => date('Y-m-d H:i', $oinfo['paytime']),
                ];
            }
        }


        $list[] = [
            'title' => '订单已提交',
            'des' => '订单提交成功',
            'time' => date('Y-m-d H:i', $oinfo['addtime']),
        ];

        $rs['info'] = $list;

        return $rs;
    }


    /**
     * 订单类型
     * @param $type
     * @return void
     */
    public function getTypeTxt($type)
    {
        $typeStatus = [

            '5' => \PhalApi\T('外卖配送'),
        ];

        if (array_key_exists($type, $typeStatus)) {
            return $typeStatus[$type];
        }
        return '--';
    }

    public function evaluate($uid, $orderid, $star, $content)
    {

        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        if ($orderid < 1) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('信息错误');
            return $rs;
        }

        $model = new Model_Orders();
        $where = [
            'id' => $orderid,
            'uid' => $uid,
            'isdel' => 0,
        ];
        $oinfo = $model->getInfo($where, 'id,type,orderno,status,riderid,cityid');
        if (!$oinfo) {
            $rs['code'] = 1003;
            $rs['msg'] = \PhalApi\T('订单信息错误');
            return $rs;
        }
        $status = $oinfo['status'];

        if ($status == 10) {
            $rs['code'] = 1004;
            $rs['msg'] = \PhalApi\T('订单已取消，无法评价');
            return $rs;
        }

        if ($status >= 7 && $status < 10) {
            $rs['code'] = 1005;
            $rs['msg'] = \PhalApi\T('订单已申请退款，无法评价');
            return $rs;
        }

        if ($status == 1) {
            $rs['code'] = 1004;
            $rs['msg'] = \PhalApi\T('订单未支付，无法评价');
            return $rs;
        }

        if ($status != 6) {
            $rs['code'] = 1004;
            $rs['msg'] = \PhalApi\T('订单服务中，无法评价');
            return $rs;
        }

        $Domain_Evaluate = new Domain_Evaluate();
        return $Domain_Evaluate->set($uid, $oinfo['id'], $oinfo['riderid'], $content, $star, $oinfo['cityid']);
    }

    public function del($uid, $orderid)
    {

        $rs = ['code' => 0, 'msg' => \PhalApi\T('删除成功'), 'info' => []];

        if ($orderid < 1) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('信息错误');
            return $rs;
        }

        $model = new Model_Orders();
        $where = [
            'id' => $orderid,
            'uid' => $uid,
            'isdel' => 0,
        ];
        $oinfo = $model->getInfo($where, 'id,type,orderno,status,isdel');
        if (!$oinfo) {
            $rs['code'] = 1003;
            $rs['msg'] = \PhalApi\T('订单信息错误');
            return $rs;
        }
        $status = $oinfo['status'];

        if ($status != 10 && $status != 8) {
            $rs['code'] = 1004;
            $rs['msg'] = \PhalApi\T('订单进行中，无法删除');
            return $rs;
        }

        $where = [
            'id' => $oinfo['id'],
            'isdel' => 0,
        ];
        $up = [
            'isdel' => 1,
            'deltime' => time(),
        ];

        $model->up($where, $up);

        return $rs;
    }

}
