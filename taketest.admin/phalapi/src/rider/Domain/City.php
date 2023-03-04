<?php

namespace Rider\Domain;

use App\ApiException;
use Rider\Model\City as Model_City;
use App\Domain\MerchantStore as MerchantStoreDomain;

class City
{


    /**
     * 获取店铺梯自提时间列表
     * @param $cityid
     * @param $storeId
     * @return void
     */
    public function getPickUpTimes($cityid, $storeId)
    {

        $MerchantStoreDomain = new MerchantStoreDomain();
        $storeInfo = $MerchantStoreDomain->getOne(['id = ?' => $storeId], 'operating_state,open_date,open_time');
        if (!$storeInfo) throw new ApiException(\PhalApi\T('店铺不存在'));
        if ($storeInfo['operating_state'] == 0) throw new ApiException(\PhalApi\T('当前店铺已打样'));
        $times = [
            'start' => '00:00',
            'end' => '23:59',
        ];

        $openTimeArr = json_decode($storeInfo['open_time'], true) ?? [];
        if (count($openTimeArr) > 0) {
            $times = [
                'start' => $openTimeArr[0],
                'end' => $openTimeArr[1],
            ];
        }

        $todayStartTime = strtotime(date('Y-m-d') . ' ' . $times['start']);
        $todayEndTime = strtotime(date('Y-m-d') . ' ' . $times['end']);

        $tomStartTime = strtotime("+1 day",strtotime(date('Y-m-d') . ' ' . $times['start']));
        $tomendTime = strtotime("+1 day",strtotime(date('Y-m-d') . ' ' . $times['end']));

        $today_list = [];
        $tomo_list = [];

        //间隔十分钟
        $interval = 60 * 60;
//        1665740463
        $number = 0;
        while (true) {
            $servertime = $todayStartTime + ($interval * $number);
            if ($servertime > $todayEndTime) {
                break;
            }

            if($servertime > time()){
                $today_list[] = [
                    'time' => date('H:i', $servertime),
                    'servetime' => $servertime,
                ];
            }
            $number++;
        }

        $number = 0;
        while (true) {

            $servertime = $tomStartTime + ($interval * $number);
            if ($servertime > $tomendTime) {
                break;
            }
            $tomo_list[] = [
                'time' => date('H:i', ($servertime)),
                'servetime' => ($servertime),
            ];

            $number++;
        }


        $list = [
            [
                'name' => '今天',
                'list' => $today_list,
            ],

            [
                'name' => '明天',
                'list' => $tomo_list,
            ],
        ];

        return $list;
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

    /* 某信息 */
    public function getInfo($id)
    {

        $info = [];
        $list = self::getList();

        foreach ($list as $k => $v) {
            if ($id == $v['id']) {
                $info = $v;
                break;
            }
        }

        return $info;
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

    public function getCityList()
    {
        $list = self::getList();
        foreach ($list as $k => $v) {

            if ($v['status'] != 1) {
                unset($list[$k]);
                continue;
            }

            if ($v['pid'] == 0) {
                unset($list[$k]);
                continue;
            }

            unset($v['config']);
            unset($v['rate']);

            $list[$k] = $v;

        }

        return array_values($list);
    }

    public function match($adcode)
    {

        $rs = [
            'cityid' => 1,
            'type' => [],
        ];

        if ($adcode == '') {
            return $rs;
        }

//        $adcode=substr($adcode,0,4);
//
//        $adcode=substr(str_pad($adcode,8,0,STR_PAD_RIGHT),0,8);

        $list = self::getList();

        foreach ($list as $k => $v) {

            if ($v['status'] != 1) {
                continue;
            }

            if ($v['pid'] == 0) {
                continue;
            }

            if ($v['area_code'] != $adcode) {
                continue;
            }

            $rs['cityid'] = $v['id'];

            $config = json_decode($v['config'], true);
            $rs['type'] = $config['type'] ?? [];
            break;
        }

        return $rs;
    }

    public function getServeTimes($cityid, $type)
    {

        $list = [];

        $times = self::getTimes($cityid, $type);
        if (!$times) {
            return $list;
        }
        $nowtime = time();
        $t_s = strtotime(date('Y-m-d', $nowtime));
        $tomo_s = $t_s + 60 * 60 * 24;

        $today_list = [];
        $tomo_list = [];

        foreach ($times as $k => $v) {
            if ($v['isopen'] != 1) {
                continue;
            }

            for ($i = $v['start']; $i < $v['end']; $i += 10) {

                $s = $i * 60;
                $s1 = $t_s + $s;
                if ($s1 > $nowtime) {
                    $today_list[] = [
                        'time' => date('H:i', $s1),
                        'servetime' => $s1,
                    ];
                }


                $s2 = $tomo_s + $s;
                $tomo_list[] = [
                    'time' => date('H:i', $s2),
                    'servetime' => $s2,
                ];
            }

        }

        $list = [
            [
                'name' => '今天',
                'list' => $today_list,
            ],

            [
                'name' => '明天',
                'list' => $tomo_list,
            ],
        ];

        return $list;
    }


    public function getTakeOutServeTimes($cityid, $type, $storeId,$presetTime)
    {

        $list = [];

        $times = self::getTimes($cityid, $type);
        if (!$times) {
            return $list;
        }
        $nowtime = time();

        if($presetTime == -1){
            $list = [
                [
                    'name' => '今天',
                    'list' => [],
                ],

                [
                    'name' => '明天',
                    'list' => [],
                ],
            ];
            return $list;
        }

        if($presetTime > 0){
            $nowtime = $presetTime;
        }



        $t_s = strtotime(date('Y-m-d', $nowtime));
        $tomo_s = $t_s + 60 * 60 * 24;

        $today_list = [];
        $tomo_list = [];

        foreach ($times as $k => $v) {
            if ($v['isopen'] != 1) {
                continue;
            }

            for ($i = $v['start']; $i < $v['end']; $i += 10) {

                $s = $i * 60;
                $s1 = $t_s + $s;
                if ($s1 >= $nowtime) {
                    $today_list[] = [
                        'time' => date('H:i', $s1),
                        'servetime' => $s1,
                    ];
                }

                $s2 = $tomo_s + $s;
                $tomo_list[] = [
                    'time' => date('H:i', $s2),
                    'servetime' => $s2,
                ];
            }

        }

        $list = [
            [
                'name' => '今天',
                'list' => $today_list,
            ],

            [
                'name' => '明天',
                'list' => $tomo_list,
            ],
        ];

        if ($storeId > 0) {
            $MerchantStoreDomain = new MerchantStoreDomain();
            $storeInfo = $MerchantStoreDomain->getOne(['id = ?' => $storeId], 'operating_state,open_date,open_time');
            if (!$storeInfo) throw new ApiException(\PhalApi\T('店铺不存在'));
            if ($storeInfo['operating_state'] == 0) throw new ApiException(\PhalApi\T('当前店铺已打样'));
            $list = $this->screenStoreTime($list, $storeInfo['open_date'], $storeInfo['open_time']);

        }

        return $list;
    }


    /**
     * 骑手服务时间 店铺营业日期,营业时间取交集
     * @param $list
     * @param $openDate
     * @param $openTime
     * @return array
     */
    public function screenStoreTime($list, $openDate, $openTime)
    {


        $openDateArr = json_decode($openDate, true) ?? [];
        $openTimeArr = json_decode($openTime, true) ?? [];
        if (count($openDateArr) > 0) {
            foreach ($list as &$value) {
                foreach ($value['list'] as $key => &$value2) {
                    $week = date('w', $value2['servetime']);
                    if ($week == 0) $week = 7;
                    if (!in_array($week, $openDateArr)) {
                        unset($value['list'][$key]);
                    }

                }
            }
        }

        if (count($openTimeArr) > 0) {
            foreach ($list as &$value) {
                foreach ($value['list'] as $key => &$value2) {
                    $week = date('w', $value2['servetime']);
                    if ($week == 0) $week = 7;
                    $listTime = strtotime(date('Y-m-d') . " " . $value2['time']);
                    $storeStartTime = strtotime(date('Y-m-d') . " " . $openTimeArr[0]);
                    $storeEndTime = strtotime(date('Y-m-d') . " " . $openTimeArr[1]);

                    if (($listTime < $storeStartTime) || ($storeEndTime > $storeEndTime)) {
                        unset($value['list'][$key]);
                    }

                }
            }
        }
        return $list;
    }


    public function getTimes($cityid, $type)
    {

        $cityinfo = $this->getConfig($cityid);
        if (!$cityinfo) {
            return [];
        }

        $config = $cityinfo['type' . $type] ?? [];
        if (!$config) {
            return [];
        }
        $times = $config['times'] ?? [];

        return $times;
    }

    public function checkTime($cityid, $type, $servicetime)
    {

        $money = -1;

        $nowtime = time();
        if ($servicetime == 0) {
            $servicetime = $nowtime;
        }

        if ($servicetime < $nowtime) {
            return $money;
        }

        $service_h = date('H', $servicetime);
        $service_i = date('i', $servicetime);

        $i_total = $service_h * 60 + $service_i;

        $times = self::getTimes($cityid, $type);
        foreach ($times as $k => $v) {
            if ($v['isopen'] != 1) {
                continue;
            }

            if ($v['start'] > $i_total) {
                continue;
            }
            if ($v['end'] <= $i_total) {
                continue;
            }

            $money = $v['money'];
            break;
        }
        if ($money == '') {
            $money = 0;
        }
        return (string)$money;
    }

    public function getLength($cityid, $distance)
    {
        $cityinfo = self::getConfig($cityid);
        if (!$cityinfo) {
            return 0;
        }

        $distance_basic = $cityinfo['distance_basic'];
        $distance_basic_time = $cityinfo['distance_basic_time'];
        $distance_more_time = $cityinfo['distance_more_time'];

        $length = $distance_basic_time;

        $distance = round($distance / 1000, 1);
        if ($distance > $distance_basic) {
            $length += (($distance - $distance_basic) / 1) * $distance_more_time;
        }

        return $length;
    }
}
