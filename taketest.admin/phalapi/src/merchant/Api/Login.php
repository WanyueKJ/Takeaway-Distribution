<?php

namespace Merchant\Api;

use Merchant\Domain\Users as UsersDomain;
use PhalApi\Api;

/**
 * (新-1)商户登录
 */
class Login extends Api
{
    public function getRules()
    {
        return array(
            'getCode' => array(
                'mobile' => array('name' => 'mobile', 'type' => 'string', 'desc' => '手机号码'),
//                'type' => array('name' => 'type', 'type' => 'int', 'desc' => '类型，1登录 2注册 3忘记密码'),
                'sign' => array('name' => 'sign', 'type' => 'string', 'default' => '', 'desc' => '签名 mobile type'),
                'code' => array('name' => 'code', 'type' => 'string', 'default' => '', 'desc' => '国家手机号区号'),
            ),
            'loginByCode' => array(
                'mobile' => array('name' => 'mobile', 'type' => 'string', 'desc' => '手机号码'),
                'code' => array('name' => 'code', 'type' => 'string', 'desc' => '验证码'),
            ),
        );
    }

    /**
     * (新-1)获取验证码
     * @desc 用于商户登录验证码
     * @return int code 操作码，0表示成功,2发送失败
     * @return array info
     * @return string msg 提示信息
     */
    public function getCode()
    {
        $rs = array('code' => 0, 'msg' => \PhalApi\T('发送成功，请注意查收'), 'info' => array());

        $mobile = \App\checkNull($this->mobile);
        $sign = \App\checkNull($this->sign);
        $country_code = \App\checkNull($this->code);
        $type = 1;
        $checkdata = array(
            'mobile' => $mobile,
            'type' => $type,
            'code' => $country_code,
        );

        $issign = \App\checkSign($checkdata, $sign);
        if (!$issign) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('签名错误');
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

        $Domain_User = new UsersDomain();
        $isexist = $Domain_User->getOne(['mobile = ?' => $mobile], 'id,store_id,user_pass,user_status,type');
        if (!$isexist) {
            if (!$isexist) {
                $rs['code'] = 1003;
                $rs['msg'] = \PhalApi\T('该手机号尚未注册，请先注册');
                return $rs;
            }
        }
        if ($isexist['user_status'] == 0) {
            if (!$isexist) {
                $rs['code'] = 1003;
                $rs['msg'] = \PhalApi\T('当前账号已被禁用');
                return $rs;
            }
        }
        if($isexist['type'] !== 1){
            if (!$isexist) {
                $rs['code'] = 1003;
                $rs['msg'] = \PhalApi\T('当前账号类型错误');
                return $rs;
            }
        }

        //取redis验证码
        $sms_key = 'sms_merchant_' . $type . '_' . $mobile;
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
     * 验证码登录
     * @desc 用于用户通过验证码登录
     * @return int code 操作码，0表示成功
     * @return array info 用户信息
     * @return string info[0].id 用户ID
     * @return string info[0].token 用户token
     * @return string info[0].store 店铺信息
     * @return string info[0].im 腾旭云im
     * @return string msg 提示信息
     */
    public function loginByCode(){
        $mobile = \App\checkNull($this->mobile);
        $code = \App\checkNull($this->code);

        $domain = new UsersDomain();

        $res = $domain->checkCode(1, $mobile, $code);
        if ($res['code'] != 0) {
            return $res;
        }
        $info = $domain->loginByCode($mobile);

        if ($info['code'] == 0) {
            $domain->clearCode(1, $mobile);
        }
        return $info;
    }
}