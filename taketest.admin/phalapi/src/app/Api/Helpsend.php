<?php

namespace App\Api;

use App\ApiException;
use PhalApi\Api;
use Rider\Domain\City as Domain_City;

/**
 * (商家配送时间)
 */
class Helpsend extends Api
{

    public function getRules()
    {
        return array(

            'getTimes' => array(
                'cityid' => array('name' => 'cityid', 'type' => 'int', 'desc' => '城市ID'),
            ),

            'getTakeOutTimes' => array(
                'cityid' => array('name' => 'cityid', 'type' => 'int', 'desc' => '城市ID'),
                'store_id' => array('name' => 'store_id', 'type' => 'int', 'desc' => '店铺id'),
                'preset_time' => array('name' => 'preset_time', 'type' => 'int', 'desc' => '预计送达时间戳'),
            )
        );
    }




    /**
     * 获取外买配送服务时间列表
     * @desc 用于获取服务时间列表
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string info[].length 时长（分钟）
     * @return string msg 提示信息
     */
    public function getTakeOutTimes()
    {

        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        $cityid = \App\checkNull($this->cityid);
        $storeId = \App\checkNull($this->store_id);
        $presetTime = \App\checkNull($this->preset_time);
        $cityid = 1;

        if ($presetTime > 0) {
            if (strtotime(date('Y-m-d H:i:s', $presetTime) != $presetTime)) {
                throw new ApiException($rs['msg'] = \PhalApi\T('时间戳格式错误'));
            }
        }


        $Domain_City = new Domain_City();
        $list = $Domain_City->getTakeOutServeTimes($cityid, 6, $storeId, (int)$presetTime);

        $rs['info'] = $list;

        return $rs;
    }

    /**
     * 获取服务时间列表
     * @desc 用于获取服务时间列表
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string info[].length 时长（分钟）
     * @return string msg 提示信息
     */
    public function getTimes()
    {

        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        $cityid = \App\checkNull($this->cityid);
        $cityid = 1;

        $Domain_City = new Domain_City();
        $list = $Domain_City->getServeTimes($cityid, 1);

        $rs['info'] = $list;

        return $rs;
    }


}
