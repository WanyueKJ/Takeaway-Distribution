<?php
namespace Rider\Api;

use PhalApi\Api;
use Rider\Domain\Location as Domain_Location;

/**
 * 位置
 */

class Location extends Api {

	public function getRules() {
        return array(
            'set' => array(
                'lng' => array('name' => 'lng', 'type' => 'string', 'desc' => '经度'),
                'lat' => array('name' => 'lat', 'type' => 'string', 'desc' => '纬度'),
            ),
        );
	}

	/**
     * 更新位置
     * @desc 用于配送员更新位置
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
	public function set() {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $lng = \App\checkNull($this->lng);
        $lat = \App\checkNull($this->lat);

        $checkToken=\App\checkRiderToken($uid,$token);
		if($checkToken==700){
			$rs['code'] = $checkToken;
			$rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
			return $rs;
		}

        $domain = new Domain_Location();
        return $domain->setLocation($uid,$lng,$lat);

	}

}
