<?php

namespace App\Api;

use App\ApiException;
use PhalApi\Api;

/**
 * (新-1)天气三方(和风天气)
 */
class Weather extends Api
{
    public function getRules()
    {
        return array(
            'read' => array(
                'lng' => array('name' => 'lng', 'type' => 'string', 'desc' => '经度'),
                'lat' => array('name' => 'lat', 'type' => 'string', 'desc' => '纬度'),
            ),
        );
    }


    /**
     * 实时天气
     * @desc 实时天气
     * @return int code 操作码，0表示成功
     * @return array info.is_good 是否是好天气 1:是 0:否
     * @return array info.text 天气状况的文字描述
     * @return array info.wind_dir 风向
     * @return array info.wind_scale 风力等级
     * @return array info.des_title 恶劣天气标题
     * @return array info.des 恶劣天气说明
     * @return string msg 提示信息
     */
    public function read()
    {

        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $lng = \App\checkNull($this->lng);
        $lat = \App\checkNull($this->lat);
        if (!$lng || !$lat) throw new ApiException(\PhalApi\T('经纬度信息错误'), 700);

        $getConfigPri = \App\getConfigPri();
        $weather_public_id = $getConfigPri['weather']['public_id'] ?? '';
        $weather_public_key = $getConfigPri['weather']['key'] ?? '';
        if (!$weather_public_key) {
            throw new ApiException(\PhalApi\T('获取天气信息是失败1'), 700);
        }
        $param = [
            'location' => "$lng,$lat",
            'key' => $weather_public_key,
            'lng' => LANG
        ];
        $url = "https://api.qweather.com/v7/weather/now?" . http_build_query($param);

        $redisKey = "$lng,$lat";
        if (\App\getcaches($redisKey)) {
            $resultArr = \App\getcaches($redisKey);
        } else {
            $result = \App\curl_get($url, ['application/json;charset=UTF-8']);
            $resultArr = json_decode($result, true);
        }

        if (!is_array($resultArr)) {
            throw new ApiException(\PhalApi\T('获取天气信息是失败'), 700);
        }
        \App\setcaches($redisKey, $resultArr, 60 * 10);
        if (!array_key_exists('code', $resultArr) || $resultArr['code'] != 200) {
            throw new ApiException(\PhalApi\T('获取天气信息是失败'), 700);
        }
        $niceWeather = [//参照:https://dev.qweather.com/docs/resource/icons/
            100, 101, 102, 103, 104, 150, 151, 152, 153, 154, 501,502,800, 802, 803, 804, 805, 806, 807, 900, 901
        ];
        $des = \PhalApi\T('因恶劣天气,骑手速度可能会减慢');
        $des_title = \PhalApi\T('在这样的天气条件下,您的订单可能会稍晚送到,感谢您 的理解!');
        $is_good = 0;
        if (in_array($resultArr['now']['icon'], $niceWeather)) {
            $is_good = 1;
            $des = \PhalApi\T('');
            $des_title = \PhalApi\T('');
        }
        $text = $resultArr['now']['text'];
        $wind_dir = $resultArr['now']['windDir'] ?? '';
        $wind_scale = $resultArr['now']['windScale'] ?? '';

        $rs['info'][] = compact('is_good', 'text', 'wind_dir', 'wind_scale', 'des', 'des_title');
        return $rs;
    }

    protected function checkLogin($uid, $token)
    {
        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            throw new ApiException(\PhalApi\T('您的登陆状态失效，请重新登陆！'), 700);
        }
    }
}