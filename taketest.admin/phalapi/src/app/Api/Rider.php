<?php
namespace App\Api;

use PhalApi\Api;
use Rider\Domain\Location as Domain_Location;
use Rider\Domain\City as Domain_City;

/**
 * 骑手
 */

class Rider extends Api {

	public function getRules() {
        return array(
            'getNearby' => array(
                'cityid' => array('name' => 'cityid', 'type' => 'int', 'desc' => '城市ID'),
                'lng' => array('name' => 'lng', 'type' => 'string', 'desc' => '经度'),
                'lat' => array('name' => 'lat', 'type' => 'string', 'desc' => '纬度'),
            ),
        );
	}

    /**
     * 附近骑手
     * @desc 用于获取附近骑手列表
     * @return int code 操作码，0表示成功
     * @return array info 
     * @return string info[0].time 预计时间
     * @return array  info[0].list 附近骑手列表
     * @return string msg 提示信息
     */
	public function getNearby() {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $cityid = \App\checkNull($this->cityid);
        $lng = \App\checkNull($this->lng);
        $lat = \App\checkNull($this->lat);

        /*$checkToken=\App\checkToken($uid,$token);
        if($checkToken==700){
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }*/
        $cityid = 1;
        $Domain_Location = new Domain_Location();
		$list = $Domain_Location->getNearby($cityid,$lng,$lat);

		$time=0;
		if($list){
            $Domain_City=new Domain_City();
            $config=$Domain_City->getConfig($cityid);
            if($config){
                $time = $config['rider_time'];
            }
        }

        $info=[
            'time'=>$time,
            'list'=>$list,
        ];
        $rs['info'][0]=$info;
		
        return $rs;
	}
}
