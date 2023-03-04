<?php
namespace Rider\Api;

use PhalApi\Api;
use Rider\Domain\Evaluate as Domain_Evaluate;

/**
 * 评价
 */

class Evaluate extends Api {

	public function getRules() {
        return array(
            'getRecord' => array(
                'type' => array('name' => 'type', 'type' => 'int', 'desc' => '类型 0全部 1好评 2中评 3差评'),
                'p' => array('name' => 'p', 'type' => 'int', 'desc' => '页码'),
            ),
        );
	}

	/**
     * 评价列表
     * @desc 用于获取记录列表
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string info[].id
     * @return object info[].uinfo 用户信息
     * @return string info[].content 内容
     * @return string info[].star 星数
     * @return string info[].add_time 时间
     * @return string msg 提示信息
     */
	public function getRecord() {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $type = \App\checkNull($this->type);
        $p = \App\checkNull($this->p);

        $checkToken=\App\checkRiderToken($uid,$token);
		if($checkToken==700){
			$rs['code'] = $checkToken;
			$rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
			return $rs;
		}

        $domain = new Domain_Evaluate();
		$list = $domain->getRecord($uid,$type,$p);

        $rs['info']=$list;

        return $rs;
	}

}
