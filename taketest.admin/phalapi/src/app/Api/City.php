<?php
namespace App\Api;

use PhalApi\Api;
use Rider\Domain\City as Domain_City;

/**
 * 城市
 */

class City extends Api {

	public function getRules() {
        return array(
            'match' => array(
                'adcode' => array('name' => 'adcode', 'type' => 'string', 'desc' => '地区编号'),
            ),
        );
	}

    /**
     * 城市列表
     * @desc 用于获取科目列表
     * @return int code 操作码，0表示成功
     * @return array info 
     * @return string info[].id
     * @return string info[].name 名称
     * @return string msg 提示信息
     */
	public function getList() {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        
        $domain = new Domain_City();
		$list = $domain->getCityList();
        $rs['info']=$list;
		
        return $rs;
	}

	/**
     * 城市匹配
     * @desc 用于城市匹配判断是否开通
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string info[0].cityid 城市ID 0表示未开通
     * @return array  info[0].type 开通的服务 1帮送 2帮取 3帮买 4 帮排队 5帮办
     * @return string msg 提示信息
     */
	public function match() {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $adcode = \App\checkNull($this->adcode);

        $domain = new Domain_City();
        $info = $domain->match($adcode);

        $rs['info'][0]=$info;

        return $rs;
	}
    

}
