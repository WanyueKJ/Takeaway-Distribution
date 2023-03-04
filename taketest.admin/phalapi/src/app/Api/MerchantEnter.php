<?php

namespace App\Api;

use App\ApiException;
use App\Domain\Login as Domain_Login;
use PhalApi\Api;

use App\Domain\MerchantEnter as MerchantEnterDomain;
use Rider\Domain\Slide as SlideDomain;

/**
 * (新-1)商户管理
 */
class MerchantEnter extends Api
{
    public function getRules()
    {
        return array(
            'save' => array(
                'name' => array('name' => 'name', 'type' => 'string', 'desc' => '姓名'),
                'mobile' => array('name' => 'mobile', 'type' => 'string', 'desc' => '手机号'),
                'code' => array('name' => 'code', 'type' => 'string', 'desc' => '验证码'),
                'type' => array('name' => 'type', 'type' => 'string', 'desc' => '类型: 1商家入驻 2骑手入驻'),
            ),
            'getCode' => array(
                'mobile' => array('name' => 'mobile', 'type' => 'string', 'desc' => '手机号'),
                'sign' => array('name' => 'sign', 'type' => 'string', 'desc' => '签名'),
                'code' => array('name' => 'code', 'type' => 'string', 'desc' => '国家区号'),

            ),
            'read' => array(
                'type' => array('name' => 'type', 'type' => 'string', 'desc' => '类型: 1商家入驻 2骑手入驻'),
            ),
            'getBanner' => array(
                'type' => array('name' => 'type', 'type' => 'string', 'desc' => '轮播图类型: 1 商家入驻/骑手入驻通用'),
            ),
        );
    }


    protected function checkLogin($uid, $token)
    {
        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            throw new ApiException(\PhalApi\T('您的登陆状态失效，请重新登陆！'), 700);
        }
    }

    /**
     * 获取轮播图
     * @desc 用于获取商家入驻/骑手入驻轮播图
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function getBanner()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $this->checkLogin($uid, $token);

        $SlideDomain = new SlideDomain();
        $banner = $SlideDomain->getSlide(16);//轮播图
        $rs['info'][] = $banner;
        return $rs;

    }

    /**
     * 入驻申请
     * @desc 用于提交入驻申请
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function save()
    {

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $this->checkLogin($uid, $token);

        $name = \App\checkNull($this->name);
        $phone = \App\checkNull($this->mobile);
        $code = \App\checkNull($this->code);
        $type = \App\checkNull($this->type);

        if (!in_array($type, [1, 2, 3, 4])) throw new ApiException(\PhalApi\T('类型错误'), 400);
        if ($name == '') throw new ApiException(\PhalApi\T('请输入姓名'), 400);
        if ($phone == '') throw new ApiException(\PhalApi\T('请输入手机号'), 400);
        if ($code == '') throw new ApiException(\PhalApi\T('请输入验证码'), 400);

        $domainLogin = new Domain_Login();
        $res = $domainLogin->checkCode(4, $phone, $code);
        if ($res['code'] != 0) {
            return $res;
        }
//        $action = 'App.MerchantEnter.save';
//        $date = date('Y-m-d H:i:s');
//        file_put_contents('./log.txt', var_export(compact('action','date','uid','token', 'name','phone','code','type'), true) . PHP_EOL, FILE_APPEND);
        $domain = new MerchantEnterDomain();
        $res = $domain->applyFor($uid, $name, $phone, $code, $type);

        return $res;
    }

    /**
     * 入驻申请状态
     * @desc 用于查询提交入驻申请状态
     * @return int code 操作码，0表示成功
     * @return array info['status'] 状态-2未申请  -1未通过 0审核中 1已通过
     * @return string msg 提示信息
     */
    public function read()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $this->checkLogin($uid, $token);
        $type = \App\checkNull($this->type);


        $domain = new MerchantEnterDomain();
        $rs['info'][] = $domain->getStatus($uid, $type);
        return $rs;
    }

    /**
     * (改-1)获取验证码
     * @desc 用于申请店铺时手机号验证
     * @return int code 操作码，0表示成功,2发送失败
     * @return array info
     * @return string msg 提示信息
     */
    public function getCode()
    {
        $rs = array('code' => 0, 'msg' => \PhalApi\T('发送成功，请注意查收'), 'info' => array());

        $mobile = \App\checkNull($this->mobile);
        $type = 4;
        $sign = \App\checkNull($this->sign);
        $country_code = \App\checkNull($this->code);

        $checkdata = array(
            'mobile' => $mobile,
//            'type' => $type,
        );

        $issign = \App\checkSign($checkdata, $sign);
//        if (!$issign) {
//            $rs['code'] = 1001;
//            $rs['msg'] = \PhalApi\T('签名错误');
//            return $rs;
//        }

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
}