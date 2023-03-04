<?php
namespace App\Api;

use PhalApi\Api;
use App\Domain\Balance as Domain_Balance;

/**
 * 余额记录
 */

class Balance extends Api {

	public function getRules() {
        return array(
            'getRecord' => array(
                'type' => array('name' => 'type', 'type' => 'int', 'desc' => '类型 1充值记录 2消费记录 3退款记录'),
                'time' => array('name' => 'time', 'type' => 'string', 'desc' => '日期 格式：2022-01 留空为本月'),
                'p' => array('name' => 'p', 'type' => 'int', 'desc' => '页码'),
            ),
        );
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
        $type = \App\checkNull($this->type);
        $time = \App\checkNull($this->time);
        $p = \App\checkNull($this->p);

        $checkToken=\App\checkToken($uid,$token);
		if($checkToken==700){
			$rs['code'] = $checkToken;
			$rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
			return $rs;
		}

        $domain = new Domain_Balance();
		$list = $domain->getRecord($uid,$type,$time,$p);
        
        $rs['info']=$list;
		
        return $rs;
	}

}
