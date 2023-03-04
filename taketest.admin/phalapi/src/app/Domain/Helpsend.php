<?php

namespace App\Domain;

use App\ApiException;
use Rider\Domain\City as Domain_City;
use App\Domain\Orders as Domain_Orders;
use App\Domain\MerchantStore as MerchantStoreDomain;
use App\Domain\Addr as AddrDomain;

class Helpsend
{


    /**
     * 外卖配送订单费用计算
     * @return void
     */
    public function deliveryAndDistribution(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        $Domain_City = new Domain_City();
        $cityinfo = $Domain_City->getConfig(1);
        if (!$cityinfo) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('当前城市未开通服务');
            return $rs;
        }
        [$uid, $store_id, $address_id] = $param;
        $MerchantStoreDomain = new MerchantStoreDomain();
        $storeInfo = $MerchantStoreDomain->getOne(['id = ?' => $store_id], 'id,lng,lat');
        if (!$storeInfo) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('店铺信息错误');
            return $rs;
        }
        if (!$storeInfo['lng'] || !$storeInfo['lat']) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('店铺位置错误');
            return $rs;
        }

        $start_lng = $storeInfo['lng'];
        $start_lat = $storeInfo['lat'];

        if (!$address_id) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('地址信息错误');
            return $rs;
        }


        $AddrDomain = new AddrDomain();
        $address = $AddrDomain->getInfo(['uid = ?' => $uid, 'id = ?' => $address_id], 'lng,lat');
        if (!$address) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('地址信息错误');
            return $rs;
        }
        $end_lng = $address['lng'];
        $end_lat = $address['lat'];

        $price = self::computed(6, 1, $start_lng, $start_lat, $end_lng, $end_lat, 0, 0);

        if($price['code'] != 0){
            throw new ApiException($price['msg'],300);
        }
        $price = $price['info'][0];
        $info = [
            'money_basic' => $price['money_basic'],//起始距离内费用
            'money_basic_txt' => $price['money_basic_txt'],//起始距离数 千米
            'money_distance' => $price['money_distance'],//超出距离的费用
            'money_distance_txt' => $price['money_distance_txt'],//超出的距离数
            'distance' => round($price['distance'], 2),//总配送距离
        ];
        $rs['info'][0] = $info;
        return $rs;
    }


    public function computed($type, $cityid, $start_lng, $start_lat, $end_lng, $end_lat, $distance, $weight)
    {

        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        $Domain_City = new Domain_City();
        $cityinfo = $Domain_City->getConfig($cityid);
        if (!$cityinfo) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('当前城市未开通服务');
            return $rs;
        }

        if (!in_array($type, [1, 2, 6])) {//6外卖配送(与帮送帮取一样)
            $rs['code'] = 1000;
            $rs['msg'] = \PhalApi\T('信息错误');
            return $rs;
        }
        if ($type == 1) {//帮送
            if (!in_array(1, $cityinfo['type'])) {
                $rs['code'] = 1002;
                $rs['msg'] = \PhalApi\T('当前城市未开通帮送服务');
                return $rs;
            }
        }
        if ($type == 2) {//帮去
            if (!in_array(2, $cityinfo['type'])) {
                $rs['code'] = 1003;
                $rs['msg'] = \PhalApi\T('当前城市未开通帮取服务');
                return $rs;
            }
        }
        if ($type == 6) {//帮取
            if (!in_array(6, $cityinfo['type'])) {
                $rs['code'] = 1003;
                $rs['msg'] = \PhalApi\T('当前城市未开通外卖配送服务');
                return $rs;
            }
        }

        $config = $cityinfo["type{$type}"];

        $money_basic = 0;
        $money_basic_txt = '';
        $money_distance = 0;
        $money_distance_txt = '';
        $money_weight = 0;
        $money_weight_txt = '';


        if ($config['fee_mode'] == 1) {//固定费用
            $money_basic = $config['fix_money'];

            $info = [
                'money_basic' => $money_basic,
                'money_basic_txt' => $money_basic_txt,
                'money_distance' => $money_distance,
                'money_distance_txt' => $money_distance_txt,
                'money_weight' => $money_weight,
                'money_weight_txt' => $money_weight_txt,
                'distance' => 0,
                'weight' => 0,
            ];
            $rs['info'][0] = $info;
            return $rs;
        }

        $dis_all = 0;
        if ($config['distance_switch'] == 1) {
            $money_basic += $config['distance_basic_money'];
            $money_basic_txt = '(' . $config['distance_basic'] . 'km)';

            if ($config['distance_mode'] == 1) {
                if ($start_lat == '' || $start_lng == '' || $end_lat == '' || $end_lng == '') {
                    $rs['code'] = 1004;
                    $rs['msg'] = \PhalApi\T('地点信息错误');
                    return $rs;
                }
                $dis_all = \App\getDistance($start_lat, $start_lng, $end_lat, $end_lng);
            }
            if ($config['distance_mode'] == 2) {
                $dis_all = round($distance / 1000, 1);
            }

            if ($config['distance_type'] == 2) {
                $dis_all = ceil($dis_all);
            }

            if ($config['distance_type'] == 3) {
                $dis_all = floor($dis_all);
            }
            if ($dis_all > $config['distance_basic']) {
                $distance_more = $dis_all - $config['distance_basic'];
                $money_distance = round(($distance_more) / 1 * $config['distance_more_money'], 2);

                $money_distance_txt = '(' . $distance_more . 'km)';
            }

        }
        $wei_all = 0;
        if ($config['weight_switch'] == 1) {
            $money_basic += $config['weight_basic_money'];

            $wei_all = $weight;

            if ($config['weight_type'] == 2) {
                $wei_all = ceil($wei_all);
            }

            if ($config['weight_type'] == 3) {
                $wei_all = floor($wei_all);
            }

            if ($wei_all > $config['weight_basic']) {
                $wei_more = $wei_all - $config['weight_basic'];
                $money_weight = round(($wei_more) / 1 * $config['weight_more_money'], 2);

                $money_weight_txt = '(' . $wei_more . 'kg)';
            }

        }

        $info = [
            'money_basic' => $money_basic,
            'money_basic_txt' => $money_basic_txt,
            'money_distance' => $money_distance,
            'money_distance_txt' => $money_distance_txt,
            'money_weight' => $money_weight,
            'money_weight_txt' => $money_weight_txt,
            'distance' => $dis_all,
            'weight' => $wei_all,
        ];
        $rs['info'][0] = $info;
        return $rs;
    }

    public function create($uid, $type, $cityid, $start_name, $start_addr, $start_lng, $start_lat, $pick_name, $pick_phone, $end_name, $end_addr, $end_lng, $end_lat, $recip_name, $recip_phone, $distance, $weight, $servicetime, $cateid, $des, $usercouponid, $fee, $payid, $source, $openid,$is_mer = 0)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        $cityid = 1;
        $res = self::computed($type, $cityid, $start_lng, $start_lat, $end_lng, $end_lat, $distance, $weight);
        if ($res['code'] != 0) {
            return $res;
        }

        $computed = $res['info'][0];

        $catename = '';

        $Domain_City = new Domain_City();
        $is_now = 0;
        if($servicetime == 0){
            $servicetime = time();
            $is_now = 1;
        }
        $timemoney = $Domain_City->checkTime($cityid, $type, $servicetime);
        if ($timemoney < 0) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('取件时间不在服务时间内');
            return $rs;
        }

        $length = $Domain_City->getLength($cityid, $distance);

        $extra = [
            'distance' => $distance,
            'weight' => $weight,
            'cateid' => $cateid,
            'catename' => $catename,
            'computed' => $computed,
            'timemoney' => $timemoney,
            'length' => $length,
        ];

        $money_total = $computed['money_basic'] + $computed['money_distance'] + $computed['money_weight'] + $timemoney;

        $order_data = [
            'type' => $type,
            'cityid' => $cityid,
            'uid' => $uid,
            'paytype' => $payid,
            'money' => $money_total,
            'money_total' => $money_total,
            'fee' => $fee,
            'servicetime' => $servicetime,
            'is_now' => $is_now,
            'status' => 1,
            'source' => $source,
            'des' => $des,
            'f_name' => $start_name,
            'f_addr' => $start_addr,
            'f_lng' => $start_lng,
            'f_lat' => $start_lat,
            't_name' => $end_name,
            't_addr' => $end_addr,
            't_lng' => $end_lng,
            't_lat' => $end_lat,
            'pick_name' => $pick_name,
            'pick_phone' => $pick_phone,
            'recip_name' => $recip_name,
            'recip_phone' => $recip_phone,
            'is_mer' => $is_mer,
            'extra' => json_encode($extra),
        ];

        $Domain_Orders = new Domain_Orders();
        $order_res = $Domain_Orders->create($uid, $order_data, $payid, $usercouponid, $openid,$is_mer);

        return $order_res;

    }

    public function create2($uid, $type, $cityid, $start_name, $start_addr, $start_lng, $start_lat, $pick_name, $pick_phone, $end_name, $end_addr, $end_lng, $end_lat, $recip_name, $recip_phone, $distance, $weight, $servicetime, $cateid, $des, $usercouponid, $fee, $payid, $source, $openid,$orderID,$store_id)
    {

        $cityid = 1;
        $res = self::computed($type, $cityid, $start_lng, $start_lat, $end_lng, $end_lat, $distance, $weight);

        if ($res['code'] != 0) {
            return $res;
        }

        $computed = $res['info'][0];

        $catename = '';

        $Domain_City = new Domain_City();

        $timemoney = $Domain_City->checkTime($cityid, $type, $servicetime);
        if ($timemoney < 0) {
            throw new ApiException(\PhalApi\T('取件时间不在服务时间内'),600);
        }

        $length = $Domain_City->getLength($cityid, $distance);

        $extra = [
            'distance' => round($distance*1000,1),
            'weight' => $weight,
            'cateid' => $cateid,
            'catename' => $catename,
            'computed' => $computed,
            'timemoney' => $timemoney,
            'length' => $length,
        ];

        $money_total = $computed['money_basic'] + $computed['money_distance'] + $computed['money_weight'] + $timemoney;

        $order_data = [
            'type' => $type,
            'cityid' => $cityid,
            'uid' => $uid,
            'paytype' => $payid,
            'money' => $money_total,
            'money_total' => $money_total,
            'fee' => $fee,
            'servicetime' => $servicetime,
            'status' => 1,
            'source' => $source,
            'des' => $des,
            'f_name' => $start_name,
            'f_addr' => $start_addr,
            'f_lng' => $start_lng,
            'f_lat' => $start_lat,
            't_name' => $end_name,
            't_addr' => $end_addr,
            't_lng' => $end_lng,
            't_lat' => $end_lat,
            'pick_name' => $pick_name,
            'pick_phone' => $pick_phone,
            'recip_name' => $recip_name,
            'recip_phone' => $recip_phone,
            'extra' => json_encode($extra),
            'is_system' => 1,
            'store_oid' => $orderID,
        ];

        $Domain_Orders = new Domain_Orders();
        $order_res = $Domain_Orders->create2($uid, $order_data, $payid, $usercouponid, $openid,$store_id,$orderID);

        return $order_res;

    }

}
