<?php

namespace App\Domain;

use App\Model\City as Model_City;
use App\Model\MerchantStore as MerchantStoreModel;
use Rider\Domain\City as Domain_City;

class City
{

    /**
     * 获取送达时长
     * @param ...$param
     * @return void
     */
    public function getPresetTime(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        [$uid, $store_id, $address_id, $target_lng, $target_lat] = $param;
        $MerchantStoreModel = new MerchantStoreModel();
        $store = $MerchantStoreModel->getOne(['id = ?' => $store_id], 'id,lng,lat');
        if (!$store || (!$store['lng'] || !$store['lat'])) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误!');
            return $rs;
        }

        if ($address_id > 0) {//地址
            $AddrDomain = new Addr();
            $address = $AddrDomain->getInfo(['id = ?' => $address_id, 'uid = ?' => $uid], 'id,lng,lat');
            if (!$address || (!$address['lng']) || !$address['lat']) {
                $rs['code'] = 995;
                $rs['msg'] = \PhalApi\T('地址信息错误!');
                return $rs;
            }

        } else {
            if (!$target_lng || !$target_lat) {
                $rs['code'] = 995;
                $rs['msg'] = \PhalApi\T('经纬度信息错误!');
                return $rs;
            }
            $address['lng'] = $target_lng;
            $address['lat'] = $target_lat;
        }

        $config = $this->getConfig(1);

        //两点间的直线距离
        $lineDistance = \App\getDistance($store['lng'], $store['lat'], $address['lng'], $address['lat']);
        $distance_basic = $config['distance_basic'];//起始距离配送时长

        if ($lineDistance <= $distance_basic) {
            $journeyTime = $config['distance_basic_time'];
        } else {
            $journeyTime = $config['distance_basic_time'] + (($lineDistance - $distance_basic) * $config['distance_more_time']);
        }

        $Domain_City = new Domain_City();
        $list = $Domain_City->getTakeOutServeTimes(1, 6,$store['id'],0);
        if(!$list[0]['list']){
            $res = ['time' => '暂无合适时间配送', 'org_time' => 0];
            $rs['info'][] = $res;
            return $rs;
        }else{
            if(count($list[0]['list']) > 0){
                $time = $list[0]['list'][0]['servetime'];
            }else if(count($list[1]['list']) > 0){
                $time = $list[1]['list'][0]['servetime'];
            }
        }
        $time = ($journeyTime*60)+$time;
        $org_time = $time;

        $allTimeList = array_merge($list[0]['list'],$list[1]['list']);
        foreach ($allTimeList as $key => $value){
            if($time <= $value['servetime']){
                $time = $value['servetime'];
                break;
            }
            if(($key + 1) >= count($allTimeList)){
                $res = ['time' => '地址超过配送范围', 'org_time' => -1];
                $rs['info'][] = $res;
                return $rs;
            }

        }
        $time = date('m-d H:i', $time);

        $res = ['time' => '预计' . $time . '送达', 'org_time' => $org_time];
        $rs['info'][] = $res;
        return $rs;
    }

    /* 某信息 */
    public function getConfig($id)
    {

        $info = [];
        $list = self::getList();

        foreach ($list as $k => $v) {
            if ($id != $v['id']) {
                continue;
            }

            $info = json_decode($v['config'], true);
            break;
        }

        return $info;
    }

    /* 列表 */
    public function getList()
    {

        $key = 'citylist';
        if (isset($GLOBALS[$key])) {
            return $GLOBALS[$key];
        }
        $list = \App\getcaches($key);
        if (!$list) {
            $model = new Model_City();
            $list = $model->getList();
            \App\setcaches($key, $list);
        }

        foreach ($list as $k => $v) {
            unset($v['list_order']);
            $list[$k] = $v;
        }
        //$list=array_values($list);

        $GLOBALS[$key] = $list;
        return $list;
    }

}