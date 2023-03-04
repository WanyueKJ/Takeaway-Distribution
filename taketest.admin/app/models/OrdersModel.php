<?php

namespace app\models;

use think\Db;
use think\Model;
use app\substation\service\pay\Alipay;
use app\substation\service\pay\Wxpay;

class OrdersModel extends Model
{
    protected $pk = 'id';
    protected $name = 'orders';

    public static function getAll($where, $field)
    {

        $list = self::field($field)->where($where)->order('id desc')->select()->toArray();

        return $list;
    }

    public static function getInfo($where, $field = '*')
    {

        $list = self::field($field)->where($where)->order('id desc')->find()->toArray();

        return $list;
    }

    public static function getStatus($k = '')
    {
        $status = [
            '1' => '待支付',
            '2' => '已支付',
            '3' => '已接单',
            '4' => '服务中',
            //'5'=>'已送达',
            '6' => '已完成',
            '7' => '退款申请',
            '8' => '退款成功',
            '9' => '退款拒绝',
            '10' => '已取消',
        ];

        if ($k === '') {
            return $status;
        }
        return $status[$k] ?? '';
    }

    public static function getTypes($k = '')
    {

        $status = [
            '6' => '外卖配送',
        ];

        if ($k === '') {
            return $status;
        }
        return $status[$k] ?? '';
    }

    public static function getPayTypes($k = '')
    {
        $status = [
            '1' => '支付宝',
            '2' => '微信',
            
        ];

        if ($k === '') {
            return $status;
        }
        return $status[$k] ?? '';
    }

    public static function getTrans($k = '')
    {
        $status = [
            '0' => '未转单',
            '1' => '转单申请通过',
            '2' => '转单申请中',
            '3' => '转单申请拒绝',
        ];

        if ($k === '') {
            return $status;
        }
        return $status[$k] ?? '';
    }

    public static function getIncomeType($k = '')
    {
        $status = [
            '0' => '未结算',
            '1' => '待结算',
            '2' => '已结算',
        ];

        if ($k === '') {
            return $status;
        }
        return $status[$k] ?? '';
    }

    /* 处理支付订单 */
    public static function handelPay($where, $data = [])
    {

        $orderinfo = self::where($where)->find();
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

        self::where("id='{$orderinfo['id']}'")->update($data);

        /* 大厅新订单提示 */
        $key = 'orders_new';
        hSet($key, $orderinfo['cityid'], $nowtime);

        return 2;
    }

    public static function handleInfo($v, $isrider = 0)
    {

        $type = $v['type'];
        $v['pay_type'] = self::getPayTypes($v['paytype']);
        $v['type_t'] = self::getTypes($type);
        $v['trans_t'] = self::getTrans($v['istrans']);
        $extra = json_decode($v['extra'], true);

        $v['uinfo'] = getUserInfo($v['uid']);
        if ($v['riderid'] > 0) {
            $v['rinfo'] = getRiderInfo($v['riderid']);
            $oldrinfo = $v['rinfo'];
            if ($v['istrans'] <> 0 && $v['riderid'] != $v['oldriderid']) {
                $oldrinfo = getRiderInfo($v['oldriderid']);
            }
            $v['oldrinfo'] = $oldrinfo;
        }


        $expecttime = $v['servicetime'];
        $tips = '';
        if ($type == 1 || $type == 2) {
            $tips = $extra['catename'];
            if ($extra['weight'] > 0) {
                $tips .= $extra['weight'] . 'kg内';
            }
            $length = $extra['length'] ?? 0;
            $expecttime = $expecttime + $length * 60;
        }
        if ($type == 3) {
            if ($extra['prepaid'] > 0) {
                $tips = '预估商品费' . $extra['prepaid'] . '元';
            }
        }
        if ($type == 4) {
            if ($extra['length'] > 0) {
                $cha = $extra['length'] * 60;
                $tips = handellength($cha);
            }
        }
        if ($type == 5) {
            if ($extra['prepaid'] > 0) {
                $tips = '预付服务费' . $extra['prepaid'] . '元';
            }
        }

        $v['tips'] = $tips;

        $v['expect_time'] = date('Y-m-d H:i', $expecttime);
        $v['extra'] = $extra;
        $v['service_time'] = handelsvctm($v['servicetime']);
        $v['add_time'] = date('Y-m-d H:i', $v['addtime']);


        $status = $v['status'];
        $pay_time = '';
        $grap_time = '';
        $pick_time = '';
        $complete_time = '';

        if ($status != 10) {

            if ($status >= 2) {
                $pay_time = date('Y-m-d H:i', $v['paytime']);
            }

            if ($status >= 3) {
                $grap_time = date('Y-m-d H:i', $v['graptime']);
            }

            if ($status >= 4) {
                $pick_time = date('Y-m-d H:i', $v['picktime']);
            }

            if ($status >= 6) {
                $complete_time = date('Y-m-d H:i', $v['completetime']);
            }
        }

        $v['pay_time'] = $pay_time;
        $v['grap_time'] = $grap_time;
        $v['pick_time'] = $pick_time;
        $v['complete_time'] = $complete_time;
        $thumbs = json_decode($v['thumbs'], true);
        if (!$thumbs) {
            $thumbs = [];
        }
        foreach ($thumbs as $k1 => $v1) {
            $thumbs[$k1] = get_upload_path($v1);
        }
        $v['thumbs'] = $thumbs;

        if ($isrider == 1 && $status >= 3 && $status != 10) {

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
            $income = $v['rider_income'];
            if ($status != 6 && $v['isincome'] == 0) {
                $income = RiderlevelModel::getIncome($riderid, $v['type'], $rider_basic, $rider_distance, $distance, $rider_weight, $rider_length, $rider_timemoney, $rider_fee, $rider_prepaid);
            }

            $v['rider_basic'] = $rider_basic;
            $v['rider_distance'] = $rider_distance;
            $v['rider_weight'] = $rider_weight;
            $v['rider_length'] = $rider_length;
            $v['rider_prepaid'] = $rider_prepaid;
            $v['rider_timemoney'] = $rider_timemoney;
            $v['rider_fee'] = $rider_fee;

            $v['income'] = $income;

        }

        return $v;
    }

    public static function handleRefund($id, $status)
    {

        if ($status != 8 && $status != 9) {
            return "信息错误";
        }

        $info = self::where("id={$id}")->find();
        if (!$info) {
            return "订单信息错误";
        }
        if ($info['status'] != 7) {
            return "订单未处于申请退款中";
        }

        $up = [
            'status' => $status
        ];

        Db::startTrans(); //开启事务
        try {
            $res = self::where(['id' => $info['id'], 'status' => 7])->update($up);
            if (!$res) {
                throw new \Exception("操作失败，请刷新重试");
            }
            $uid = $info['uid'];
            if ($status == 8) {

                $money = $info['money'];
                if ($money > 0) {
                    $refund_order_id = $uid . '_' . date('ymdHis') . rand(100, 999);
                    self::originalRefund($info['paytype'], $info['orderno'], $refund_order_id, $money, '退款', $info['is_mer']);
                }
            }

            $add = [
                'uid' => $uid,
                'oid' => $info['id'],
                'status' => $status,
                'addtime' => time(),
                'reason' => '',
            ];
            OrdersrefundModel::insert($add);

        } catch (\Exception $exception) {
            Db::rollback();
            return $exception->getMessage();
        }
        Db::commit();
        return 1;
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
    public static function originalRefund($payType, $outTradeNo, $outRefundNo, $price, $reason = "", $isMer = 0)
    {
        $Domain_Wxpay = new Wxpay();
        $Domain_Alipay = new Alipay();
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


    public static function presetIncome($oid)
    {

        $where = ['id' => $oid];
        $oinfo = self::where($where)->field('id,uid,orderno,cityid,riderid,status,type,extra,fee,money_total,isincome')->find();
        if (!$oinfo) {
            return 0;
        }

        if ($oinfo['status'] != 6) {
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

        $income = RiderlevelModel::getIncome($oinfo['riderid'], $oinfo['type'], $rider_basic, $rider_distance, $distance, $rider_weight, $rider_length, $rider_timemoney, $rider_fee, $rider_prepaid);
        if ($income > $oinfo['money_total']) {
            $income = $oinfo['money_total'];
        }
        $substation_income = $oinfo['money_total'] - $income;
        if ($substation_income < 0) {
            $substation_income = 0;
        }
        if ($substation_income > 0) {

            $cinfo = CityModel::getInfo($oinfo['cityid']);
            if ($cinfo) {
                $rate = $cinfo['rate'];
                $substation_income = floor($substation_income * (100 - $rate)) * 0.01;
            }
        }

        $where1 = [
            'id' => $oid,
            'isincome' => 0,
        ];
        $up1 = [
            'isincome' => 1,
            'rider_income' => $income,
            'substation_income' => $substation_income,
        ];
        $res1 = self::where($where1)->update($up1);
        if (!$res1) {
            return 0;
        }

        return 1;
    }
}