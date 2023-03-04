<?php

namespace App\Api;

use App\ApiException;
use PhalApi\Api;
use App\Domain\Login as Domain_Login;
use App\Domain\User as Domain_User;

/**
 * 注册、登录
 */
class Login extends Api
{
    public function getRules()
    {
        return array(

            'getCode' => array(
                'mobile' => array('name' => 'mobile', 'type' => 'string', 'desc' => '手机号码'),
                'type' => array('name' => 'type', 'type' => 'int', 'desc' => '类型，1登录 2注册 3忘记密码 5修改手机号'),
                'sign' => array('name' => 'sign', 'type' => 'string', 'default' => '', 'desc' => '签名 mobile type'),
                'code' => array('name' => 'code', 'type' => 'string', 'default' => '', 'desc' => '国家手机号区号'),
            ),

            'reg' => array(
                'mobile' => array('name' => 'mobile', 'type' => 'string', 'desc' => '手机号码'),
                'code' => array('name' => 'code', 'type' => 'string', 'desc' => '验证码'),
                'pass' => array('name' => 'pass', 'type' => 'string', 'desc' => '密码'),
            ),

            'loginByPass' => array(
                'mobile' => array('name' => 'mobile', 'type' => 'string', 'desc' => '手机号码'),
                'pass' => array('name' => 'pass', 'type' => 'string', 'desc' => '密码'),
            ),

            'loginByCode' => array(
                'mobile' => array('name' => 'mobile', 'type' => 'string', 'desc' => '手机号码'),
                'code' => array('name' => 'code', 'type' => 'string', 'desc' => '验证码'),
                'openid' => array('name' => 'openid', 'type' => 'string', 'desc' => '第三方openid(微信类传openid)'),
            ),

            'forget' => array(
                'mobile' => array('name' => 'mobile', 'type' => 'string', 'desc' => '手机号码'),
                'code' => array('name' => 'code', 'type' => 'string', 'desc' => '验证码'),
                'pass' => array('name' => 'pass', 'type' => 'string', 'desc' => '密码'),
            ),
            'getCountryCode' => array(),

            'loginByMobile' => array(
                'account' => array('name' => 'account', 'type' => 'string', 'desc' => '手机号码'),
                'openid' => array('name' => 'openid', 'type' => 'string', 'desc' => '第三方openid'),
                'sign' => array('name' => 'sign', 'type' => 'string', 'default' => '', 'desc' => '签名 account source'),
            )
        );
    }

    /**
     * 手机号一键登录
     * @desc 用于用户手机号一键登录
     * @return int code 操作码，0表示成功
     * @return array info 用户信息
     * @return string msg 提示信息
     */
    public function loginByMobile()
    {
        $account = \App\checkNull($this->account);
        $source = \App\checkNull($this->source);
        $sign = \App\checkNull($this->sign);

        if ($account == '' || $sign == '') {
            throw new ApiException(\PhalApi\T('信息错误'));
        }
        if (!$account) {
            throw new ApiException(\PhalApi\T('请输入手机号'));
        }

        $checkdata = array(
            'account' => $account,
            'source' => $source
        );
        $issign = \App\checkSign($checkdata, $sign);
//        if (!$issign) {
//            throw new ApiException(\PhalApi\T('签名错误'));
//        }
        \App\checkSign($checkdata, $sign);

        $domain = new Domain_Login();
        $info = $domain->loginByMobile($account, $source);
        return $info;
    }

        /**
     * 登录方式开关信息
     * @desc 用于获取登录方式开关信息
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string info[].name 名称
     * @return string info[].type 标识
     * @return string info[].thumb 图标
     * @return string msg 提示信息
     */
    public function getLoginType()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $list = [];
        $rs['info'] = $list;

        return $rs;
    }

    /**
     * (新-1)获取登录国家区号
     * @desc 用于获手机号国家区号
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string info[].name 名称
     * @return string info[].type 标识
     * @return string info[].thumb 图标
     * @return string msg 提示信息
     */
    public function getCountryCode()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $Domain_User = new Domain_User();
        $list = $Domain_User->getCountryCode();

        $rs['info'] = $list;

        return $rs;
    }


    /**
     * (改-1)获取验证码
     * @desc 用于注册验证码
     * @return int code 操作码，0表示成功,2发送失败
     * @return array info
     * @return string msg 提示信息
     */
    public function getCode()
    {
        $rs = array('code' => 0, 'msg' => \PhalApi\T('发送成功，请注意查收'), 'info' => array());

        $mobile = \App\checkNull($this->mobile);
        $type = \App\checkNull($this->type);
        $sign = \App\checkNull($this->sign);
        $country_code = \App\checkNull($this->code);

        $action = 'App.Login.getCode';
        $date = date('Y-m-d H:i:s');
        file_put_contents('./log.txt', var_export(compact('action','date','mobile','type', 'country_code'), true) . PHP_EOL, FILE_APPEND);


        $checkdata = array(
            'mobile' => $mobile,
            'type' => $type,
        );

        $issign = \App\checkSign($checkdata, $sign);
        if (!$issign) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('签名错误');
            return $rs;
        }

        $types = [1, 2, 3, 5];//4已经被用过了
        if (!in_array($type, $types)) {
            $rs['code'] = 1000;
            $rs['msg'] = \PhalApi\T('信息错误');
            return $rs;
        }

        if ($mobile == '') {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('请输入手机号');
            return $rs;
        }

        $isok = \App\checkMobile($mobile);
        if (!$isok) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('请输入正确的手机号');
            return $rs;
        }

        if (!$country_code) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('请选择国家区号');
            return $rs;
        }

        $where = ['mobile' => $mobile];
        $Domain_User = new Domain_User();

        $isexist = $Domain_User->getInfo($where, 'id');
        if ($type == 1) {
            /* 登陆 */
            /*if (!$isexist) {
                $rs['code'] = 1004;
                $rs['msg']  = \PhalApi\T('该手机号尚未注册，请先注册');
                return $rs;
            }*/
        }

        if ($type == 2) {
            /* 注册 */
            if ($isexist) {
                $rs['code'] = 1002;
                $rs['msg'] = \PhalApi\T('该手机号已注册，请登录');
                return $rs;
            }
        }

        if ($type == 3) {
            /* 忘记密码 */
            if (!$isexist) {
                $rs['code'] = 1003;
                $rs['msg'] = \PhalApi\T('该手机号尚未注册，请先注册');
                return $rs;
            }
        }

        if ($type == 5) {
            /* 修改手机号 */
            if ($isexist) {
                $rs['code'] = 1002;
                $rs['msg'] = \PhalApi\T('该手机号已注册');
                return $rs;
            }
        }

        //取redis验证码
        $sms_key = 'sms_' . $type . '_' . $mobile;
        $sms_account = \App\getcaches($sms_key);

        if ($sms_account && $sms_account['expiretime'] > time()) {
            $rs['code'] = 996;
            $rs['msg'] = \PhalApi\T('验证码5分钟有效，请勿多次发送');
            return $rs;
        }

        $code = \App\random(6);

        /* 发送验证码 */
        $result = \App\sendCode($mobile, $code, $country_code);
        $currentTime = time();

        $sms_code = '';
        if ($result['code'] == 0) {
            $sms_code = $code;

        } else if ($result['code'] == 667) {
            $sms_code = $result['msg'];
            $rs['code'] = 1002;
            $rs['msg'] = \PhalApi\T('验证码为：{n}', ['n' => $result['msg']]);
        } else {
            $rs['code'] = 1002;
            $rs['msg'] = $result['msg'];
        }

        if ($sms_code) {
            $sms_value = [
                'mobile' => $mobile,
                'code' => $sms_code,
                'expiretime' => $currentTime + 300, //超时5分钟
            ];
            \App\setcaches($sms_key, $sms_value, 300);
        }

        return $rs;

    }

    /**
     * 手机号注册
     * @desc 用于用户通过手机号注册
     * @return int code 操作码，0表示成功
     * @return array info 用户信息
     * @return string info[0].id 用户ID
     * @return string msg 提示信息
     */
    public function reg()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $mobile = \App\checkNull($this->mobile);
        $pass = \App\checkNull($this->pass);
        $code = \App\checkNull($this->code);
        $source = \App\checkNull($this->source);

        $domain = new Domain_Login();

        $res = $domain->checkCode(2, $mobile, $code);
        if ($res['code'] != 0) {
            return $res;
        }

        $info = $domain->regbypass($mobile, $pass, $source);

        if ($info['code'] == 0) {
            $domain->clearCode(2, $mobile);
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
     * @return string msg 提示信息
     */
    public function loginByPass()
    {

        $mobile = \App\checkNull($this->mobile);
        $pass = \App\checkNull($this->pass);

        $domain = new Domain_Login();
        $info = $domain->loginByPass($mobile, $pass);

        return $info;
    }

    /**
     * 验证码登录
     * @desc 用于用户通过验证码登录
     * @return int code 操作码，0表示成功
     * @return array info 用户信息
     * @return string info[0].id 用户ID
     * @return string info[0].im IM信息
     * @return string info[0].im.userId  userId
     * @return string info[0].im.UserSig
     * @return string msg 提示信息
     */
    public function loginByCode()
    {

        $mobile = \App\checkNull($this->mobile);
        $code = \App\checkNull($this->code);
        $source = \App\checkNull($this->source);

        $domain = new Domain_Login();

        $res = $domain->checkCode(1, $mobile, $code);
        if ($res['code'] != 0) {
            return $res;
        }
        $info = $domain->loginByCode($mobile, $source);

        if ($info['code'] == 0) {
            $domain->clearCode(1, $mobile);
        }

        return $info;
    }



    /**
     * 忘记密码
     * @desc 用于用户忘记密码时重置密码
     * @return int code 操作码，0表示成功
     * @return array info 用户信息
     * @return string msg 提示信息
     */
    public function forget()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $mobile = \App\checkNull($this->mobile);
        $pass = \App\checkNull($this->pass);
        $code = \App\checkNull($this->code);

        $domain = new Domain_Login();

        $res = $domain->checkCode(3, $mobile, $code);
        if ($res['code'] != 0) {
            return $res;
        }

        $info = $domain->forget($mobile, $pass);

        if ($info['code'] == 0) {
            $domain->clearCode(3, $mobile);
        }

        return $info;
    }
}
