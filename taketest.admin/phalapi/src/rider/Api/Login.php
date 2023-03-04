<?php
namespace Rider\Api;

use PhalApi\Api;
use Rider\Domain\Login as Domain_Login;
use Rider\Domain\User as Domain_User;

/**
 * 注册、登录
 */

class Login extends Api {
    public function getRules() {
        return array(

            'getCode' => array(
                'account' => array('name' => 'account', 'type' => 'string', 'desc' => '手机号码'),
                'type' => array('name' => 'type', 'type' => 'int', 'desc' => '类型，1登录 2注册 3忘记密码 4更换手机号 5更换密码 6申请'),
                'sign' => array('name' => 'sign', 'type' => 'string',  'default'=>'', 'desc' => '签名 account type'),
                'code' => array('name' => 'code', 'type' => 'string',  'default'=>'', 'desc' => '手机号国家区号'),
            ),

            'apply' => array(
                'name' => array('name' => 'name', 'type' => 'string', 'desc' => '姓名'),
                'mobile' => array('name' => 'mobile', 'type' => 'string', 'desc' => '手机号码'),
                'code' => array('name' => 'code', 'type' => 'string', 'desc' => '验证码'),
                'pass' => array('name' => 'pass', 'type' => 'string', 'desc' => '密码'),
                'idno' => array('name' => 'idno', 'type' => 'string', 'desc' => '身份证号'),
                'thumb' => array('name' => 'thumb', 'type' => 'string', 'desc' => '手持照片'),
                'cityid' => array('name' => 'cityid', 'type' => 'int', 'desc' => '城市ID'),
            ),

            'loginByPass' => array(
                'username' => array('name' => 'username', 'type' => 'string', 'desc' => '用户名'),
                'pass' => array('name' => 'pass', 'type' => 'string', 'desc' => '密码'),
            ),

            'loginByCode' => array(
                'username' => array('name' => 'username', 'type' => 'string', 'desc' => '用户名'),
                'code' => array('name' => 'code', 'type' => 'string', 'desc' => '验证码'),
            ),

            'forget' => array(
                'mobile' => array('name' => 'mobile', 'type' => 'string', 'desc' => '手机号码'),
                'code' => array('name' => 'code', 'type' => 'string', 'desc' => '验证码'),
                'pass' => array('name' => 'pass', 'type' => 'string', 'desc' => '密码'),
            ),

        );
    }

    /**
     * (改-1)获取验证码
     * @desc 用于注册验证码
     * @return int code 操作码，0表示成功,2发送失败
     * @return array info
     * @return string msg 提示信息
     */
    public function getCode() {
        $rs = array('code' => 0, 'msg' => \PhalApi\T('发送成功，请注意查收'), 'info' => array());

        $account = \App\checkNull($this->account);
        $type    = \App\checkNull($this->type);
        $sign    = \App\checkNull($this->sign);
        $country_code    = \App\checkNull($this->code);

        $checkdata = array(
            'account' => $account,
            'type' => $type,
        );

        $issign = \App\checkSign($checkdata, $sign);
//        if (!$issign) {
//            $rs['code'] = 1001;
//            $rs['msg']  = \PhalApi\T('签名错误');
//            return $rs;
//        }

        $types=[1,3,4,5,6];
        if(!in_array($type,$types)){
            $rs['code'] = 1000;
            $rs['msg']  = \PhalApi\T('信息错误');
            return $rs;
        }

        if ($account == '') {
            $rs['code'] = 995;
            $rs['msg']  = \PhalApi\T('请输入手机号');
            return $rs;
        }

        $isok = \App\checkMobile($account);
        if (!$isok) {
            $rs['code'] = 995;
            $rs['msg']  = \PhalApi\T('请输入正确的手机号');
            return $rs;
        }

        if (!$country_code) {
            $rs['code'] = 995;
            $rs['msg']  = \PhalApi\T('请选择国家区号');
            return $rs;
        }

        $where   = ['mobile' => $account];
        $Domain_User=new Domain_User();

        $isexist = $Domain_User->getInfo($where,'id,mobile');
        if ($type == 1) {
            /* 登陆 */
            if (!$isexist) {
                $rs['code'] = 1004;
                $rs['msg']  = \PhalApi\T('该手机号尚未注册，请先注册');
                return $rs;
            }
        }

        if ($type == 2) {
            /* 注册 */
            if ($isexist) {
                $rs['code'] = 1002;
                $rs['msg']  = \PhalApi\T('该手机号已注册，请登录');
                return $rs;
            }
        }

        if ($type == 3) {
            /* 忘记密码 */
            if (!$isexist) {
                $rs['code'] = 1003;
                $rs['msg']  = \PhalApi\T('该手机号尚未注册，请先注册');
                return $rs;
            }
        }

        if ($type == 4) {
            /* 更换手机号 */
            if ($isexist) {
                $rs['code'] = 1004;
                $rs['msg']  = \PhalApi\T('该手机号已绑定账号，请更换');
                return $rs;
            }
        }

        if ($type == 5) {
            if($isexist['mobile'] != $account){
                $rs['code'] = 1004;
                $rs['msg']  = \PhalApi\T('与绑定的手机号一致');
                return $rs;
            }
            /* 更换密码 */
            if (!$isexist) {
                $rs['code'] = 1004;
                $rs['msg']  = \PhalApi\T('该手机号尚未注册，请先注册');
                return $rs;
            }
        }

        if ($type == 6) {
            /* 申请 */
            if ($isexist) {
                $rs['code'] = 1004;
                $rs['msg']  = \PhalApi\T('该手机号已申请，请更换');
                return $rs;
            }
        }

        //取redis验证码
        $sms_key = 'rider_sms_'.$type.'_' . $account;
        $sms_account = \App\getcaches($sms_key);

        if ($sms_account && $sms_account['expiretime'] > time()) {
            $rs['code'] = 996;
            $rs['msg']  = \PhalApi\T('验证码5分钟有效，请勿多次发送');
            return $rs;
        }

        $code = \App\random(6);

        /* 发送验证码 */
        $result = \App\sendCode($account, $code,$country_code);
        $currentTime = time();

        $sms_code = '';
        if ($result['code'] == 0) {
            $sms_code = $code;

        } else if ($result['code'] == 667) {
            $sms_code = $result['msg'];
            $rs['code'] = 1002;
            $rs['msg']  = \PhalApi\T('验证码为：{n}', ['n' => $result['msg']]);
        } else {
            $rs['code'] = 1002;
            $rs['msg']  = $result['msg'];
        }

        if ($sms_code) {
            $sms_value = [
                'mobile' => $account,
                'code' => $sms_code,
                'expiretime' => $currentTime + 300, //超时5分钟
            ];
            \App\setcaches($sms_key, $sms_value, 300);
        }

        return $rs;

    }

    /**
     * 申请
     * @desc 用于用户申请成为配送员
     * @return int code 操作码，0表示成功
     * @return array info 用户信息
     * @return string info[0].id 用户ID
     * @return string msg 提示信息
     */
    public function apply() {

        $name = \App\checkNull($this->name);
        $mobile = \App\checkNull($this->mobile);
        $pass = \App\checkNull($this->pass);
        $code = \App\checkNull($this->code);
        $idno = \App\checkNull($this->idno);
        $thumb = \App\checkNull($this->thumb);
        $source = \App\checkNull($this->source);
        $cityid = \App\checkNull($this->cityid);

        $domain = new Domain_Login();

        $res=$domain->checkCode(6,$mobile,$code);
        if($res['code']!=0){
            return $res;
        }
        $cityid = 1;
        $info = $domain->apply($name,$mobile,$pass,$idno,$thumb,$cityid,$source);

        if($info['code']==0){
            $domain->clearCode(6,$mobile);
        }

        return $info;
    }

    /**
     * 密码登录
     * @desc 用于用户通过密码登录
     * @return int code 操作码，0表示成功
     * @return array info 用户信息
     * @return string info[0].id 用户ID
     * @return string info[0].user_nickname 昵称
     * @return string info[0].avatar 头像
     * @return string info[0].avatar_thumb 头像缩略图
     * @return string info[0].sex 性别
     * @return string info[0].signature 签名
     * @return string info[0].coin 用户余额
     * @return string info[0].login_type 注册类型
     * @return string info[0].token 用户Token
     * @return string info[0].type 类型 1兼职 2全职
     * @return string info[0].isrest 是否休息 0否1是
     * @return string msg 提示信息
     */
    public function loginByPass() {

        $username = \App\checkNull($this->username);
        $pass = \App\checkNull($this->pass);

        $domain = new Domain_Login();
        $info = $domain->loginByPass($username,$pass);

        return $info;
    }

    /**
     * 验证码登录
     * @desc 用于用户通过验证码登录
     * @return int code 操作码，0表示成功
     * @return array info 用户信息
     * @return string info[0].id 用户ID
     * @return string info[0].user_nickname 昵称
     * @return string info[0].avatar 头像
     * @return string info[0].avatar_thumb 头像缩略图
     * @return string info[0].sex 性别
     * @return string info[0].signature 签名
     * @return string info[0].coin 用户余额
     * @return string info[0].login_type 注册类型
     * @return string info[0].type 类型，0学生1讲师
     * @return string info[0].token 用户Token
     * @return string msg 提示信息
     */
    public function loginByCode() {

        $username = \App\checkNull($this->username);
        $code = \App\checkNull($this->code);
        $source = \App\checkNull($this->source);


        $domain = new Domain_Login();

        $res=$domain->checkCode(1,$username,$code);
        if($res['code']!=0){
            return $res;
        }
        $info = $domain->loginByCode($username,$source);

        if($info['code']==0){
            $domain->clearCode(1,$username);
        }

        return $info;
    }
    /**
     * 忘记密码
     * @desc 用于用户忘记密码时重置密码
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function forget() {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $mobile = \App\checkNull($this->mobile);
        $pass = \App\checkNull($this->pass);
        $code = \App\checkNull($this->code);

        $domain = new Domain_Login();

        $res=$domain->checkCode(3,$mobile,$code);
        if($res['code']!=0){
            return $res;
        }

        $info = $domain->forget($mobile,$pass);

        if($info['code']==0){
            $domain->clearCode(3,$mobile);
        }

        return $info;
    }
}
