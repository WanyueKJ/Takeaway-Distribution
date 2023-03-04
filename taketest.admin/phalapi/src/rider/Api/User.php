<?php
namespace Rider\Api;

use App\ApiException;
use PhalApi\Api;
use Rider\Domain\User as Domain_User;
use Rider\Domain\Balance as Domain_Balance;
use Rider\Domain\Orders as Domain_Orders;
use Rider\Domain\Evaluate as Domain_Evaluate;

/**
 * 用户信息
 */
class User extends Api {
    public function getRules() {
        return array(
            'getBaseInfo' => array(
                'ios_version' => array('name' => 'ios_version', 'type' => 'string', 'default'=>'', 'desc' => 'IOS版本号'),
            ),

            'upUserInfo' => array(
                'fields' => array('name' => 'fields', 'type' => 'string', 'default'=>'', 'desc' => '修改信息json串'),
            ),

            'upPass' => array(
                'code' => array('name' => 'code', 'type' => 'string', 'default'=>'', 'desc' => '验证码'),
                'newpass' => array('name' => 'newpass', 'type' => 'string', 'default'=>'', 'desc' => '新密码'),
            ),

            'upMobile' => array(
                'mobile' => array('name' => 'mobile', 'type' => 'string', 'default'=>'', 'desc' => '手机号'),
                'code' => array('name' => 'code', 'type' => 'string', 'default'=>'', 'desc' => '验证码'),
            ),

            'upRest' => array(
                'rest' => array('name' => 'rest', 'type' => 'int', 'default'=>'0', 'desc' => '是否休息 0否1是'),
            ),
            'logOut' =>array(),

        );
    }

    /**
     * 账号退出
     * @desc 账号退出
     * @return array
     * @throws ApiException
     */
    public function logOut(){
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $checkToken=\App\checkRiderToken($uid,$token);
        if($checkToken==700){
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

        $DomainUser = new Domain_User();
        $DomainUser->logOut($uid);
        return $rs;
    }

	/**
	 * 判断token
	 * @desc 用于判断token
	 * @return int code 操作码，0表示成功
	 * @return array info 
	 * @return string msg 提示信息
	 */
	public function iftoken() {
		$rs = array('code' => 0, 'msg' => '', 'info' => array());
		
        $uid=\App\checkNull($this->uid);
        $token=\App\checkNull($this->token);

		$checkToken=\App\checkRiderToken($uid,$token);
		if($checkToken==700){
			$rs['code'] = $checkToken;
			$rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
			return $rs;
		}
		return $rs;
	}
    
	/**
	 * 获取用户信息
	 * @desc 用于获取用户基本信息
	 * @return int code 操作码，0表示成功， 1表示用户不存在
	 * @return array info
	 * @return string info[0].id 用户ID
	 * @return string info[0].orders 今日完成单数
	 * @return string info[0].income 今日配送费
	 * @return string info[0].star 星级
	 * @return string info[0].evaluates 评价总数
	 * @return string info[0].good 好评数
	 * @return string info[0].average 中评数
	 * @return string info[0].bad 差评数
	 * @return string info[0].mgood 本月好评数
	 * @return string msg 提示信息
	 */
	public function getBaseInfo() {
		$rs = array('code' => 0, 'msg' => '', 'info' => array());
		
        $uid=\App\checkNull($this->uid);
        $token=\App\checkNull($this->token);

        $info=[
            'id'=>'0',
            'user_nickname'=>'',
            'avatar'=>'',
            'avatar_thumb'=>'',
            'sex'=>'0',
            'signature'=>'',
            'balance'=>'0',
            'balancetotal'=>'0',
            'mobile'=>'',
            'orders'=>'0',
            'income'=>'0',
            'star'=>'5.0',
            'evaluates'=>'0',
            'good'=>'0',
            'average'=>'0',
            'bad'=>'0',
            'mgood'=>'0',
        ];



        if($uid<=0){
            $rs['info'][0] = $info;

            return $rs;
        }

        $checkToken=\App\checkRiderToken($uid,$token);
        if($checkToken==700){
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

        $domain = new Domain_User();
        $info = $domain->getBaseInfo($uid);

        if(!$info){
            $rs['code'] = 700;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

        $Domain_Balance=new Domain_Balance();
        $today_income=$Domain_Balance->getToday($uid);

        $info['income']=$today_income;

        $Domain_Orders=new Domain_Orders();
        $info['orders']=$Domain_Orders->getCompleteNums($uid);

        $Domain_Evaluate=new Domain_Evaluate();
        $info['mgood']=$Domain_Evaluate->getMonth($uid);

		$rs['info'][0] = $info;

		return $rs;
	}


    /**
	 * 更新基本信息
	 * @desc 用于用户更新基本信息
	 * @return int code 操作码，0表示成功
	 * @return array info 
	 * @return string msg 提示信息
	 */
	public function upUserInfo() {
		$rs = array('code' => 0, 'msg' => '', 'info' => array());
		
        $uid=\App\checkNull($this->uid);
        $token=\App\checkNull($this->token);
        $fields=$this->fields;
        
        if($fields==''){
            $rs['code'] = 1001;
			$rs['msg'] = \PhalApi\T('信息错误');
			return $rs;
        }

        $fields_a=json_decode($fields,true);
        if(!$fields_a){
            $rs['code'] = 1002;
			$rs['msg'] = \PhalApi\T('信息错误');
			return $rs;
        }

        $checkToken=\App\checkToken($uid,$token);
		if($checkToken==700){
			$rs['code'] = $checkToken;
			$rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
			return $rs;
		}

		$domain = new Domain_User();
		$info = $domain->upUserInfo($uid,$fields_a);
	 
		return $info;
	}

	/**
	 * 更新密码
	 * @desc 更新密码
	 * @return int code 操作码，0表示成功
	 * @return array info
	 * @return string msg 提示信息
	 */
	public function upPass() {
		$rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid=\App\checkNull($this->uid);
        $token=\App\checkNull($this->token);
        $code=\App\checkNull($this->code);
        $newpass=\App\checkNull($this->newpass);

        $checkToken=\App\checkRiderToken($uid,$token);
		if($checkToken==700){
			$rs['code'] = $checkToken;
			$rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
			return $rs;
		}

		$domain = new Domain_User();
		$res = $domain->upPass($uid,$code,$newpass);

		return $res;
	}

	/**
	 * 更新手机号
	 * @desc 更新手机号
	 * @return int code 操作码，0表示成功
	 * @return array info
	 * @return string msg 提示信息
	 */
	public function upMobile() {
		$rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid=\App\checkNull($this->uid);
        $token=\App\checkNull($this->token);
        $mobile=\App\checkNull($this->mobile);
        $code=\App\checkNull($this->code);


        $checkToken=\App\checkRiderToken($uid,$token);
		if($checkToken==700){
			$rs['code'] = $checkToken;
			$rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
			return $rs;
		}

		$domain = new Domain_User();
		$res = $domain->upMobile($uid,$mobile,$code);

		return $res;
	}

	/**
	 * 更新状态
	 * @desc 更新状态
	 * @return int code 操作码，0表示成功
	 * @return array info
	 * @return string msg 提示信息
	 */
	public function upRest() {
		$rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid=\App\checkNull($this->uid);
        $token=\App\checkNull($this->token);
        $rest=\App\checkNull($this->rest);

        $checkToken=\App\checkRiderToken($uid,$token);
		if($checkToken==700){
			$rs['code'] = $checkToken;
			$rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
			return $rs;
		}

		$where=['id'=>$uid];

		if($rest){
            $rest=1;
        }else{
            $rest=0;
        }

		if($rest==1){
		    $Domain_Orders=new Domain_Orders();
		    $where2=[
		        'riderid'=>$uid,
		        'status >=3 and status < ?'=>6,
            ];

		    $nums=$Domain_Orders->getNums($where2);
		    if($nums){
                $rs['code'] = 1001;
                $rs['msg'] = \PhalApi\T('您有尚未完成的订单，无法休息');
                return $rs;
            }
        }

		$up=[
		    'isrest'=>$rest
        ];

		$domain = new Domain_User();
		$res = $domain->up($where,$up);

		return $rs;
	}

} 
