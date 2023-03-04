<?php
namespace Rider\Api;

use PhalApi\Api;
use Rider\Domain\Balance as Domain_Balance;
use Rider\Domain\User as Domain_User;

/**
 * 余额记录
 */

class Balance extends Api {

	public function getRules() {
        return array(
            'getRecord' => array(
                'time' => array('name' => 'time', 'type' => 'string', 'desc' => '日期 格式：2022-01-01 留空为全部'),
                'p' => array('name' => 'p', 'type' => 'int', 'desc' => '页码'),
            ),
        );
	}
    /**
     * 收入信息
     * @desc 用于获取收入信息
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string info[0].balance 账户余额
     * @return string info[0].balancetotal 总收益
     * @return string info[0].today_income 今日收益
     * @return string msg 提示信息
     */
	public function getInfo() {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $checkToken=\App\checkRiderToken($uid,$token);
		if($checkToken==700){
			$rs['code'] = $checkToken;
			$rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
			return $rs;
		}

        $Domain_User=new Domain_User();

        $uinfo=$Domain_User->getInfo(['id'=>$uid],'balance,balancetotal');

        $domain = new Domain_Balance();
		$today_income = $domain->getToday($uid);

        $uinfo['today_income']=$today_income;

        $rs['info'][0]=$uinfo;
		
        return $rs;
	}

	/**
     * 记录列表
     * @desc 用于获取记录列表
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string info[].id
     * @return string info[].type 收支 1收 2支
     * @return string info[].action_txt 行为
     * @return string info[].total 金额
     * @return string info[].add_time 时间
     * @return string info[].balance 余额
     * @return string info[].orderno 订单号
     * @return string msg 提示信息
     */
	public function getRecord() {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $time = \App\checkNull($this->time);
        $p = \App\checkNull($this->p);

        $checkToken=\App\checkRiderToken($uid,$token);
		if($checkToken==700){
			$rs['code'] = $checkToken;
			$rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
			return $rs;
		}

        $domain = new Domain_Balance();
		$list = $domain->getRecord($uid,$time,$p);

        $rs['info']=$list;

        return $rs;
	}

}
