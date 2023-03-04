<?php

namespace Merchant\Domain;
use App\Model\User as Model_User;
use App\Model\Usertoken as Model_Usertoken;
use Merchant\Model\MerchantStore as MerchantStoreModel;
use Merchant\Model\Users as UsersModel;

class Users
{

    /**
     * 退出登录
     * @param $uid
     * @return void
     */
    public function logOut($uid)
    {

    }

    public function clearCode($type, $mobile)
    {
        $sms_key = 'sms_merchant_' . $type . '_' . $mobile;
        \App\delcache($sms_key);
    }


    public function loginByCode($mobile)
    {

        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        if ($mobile == '') {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('请输入手机号');
            return $rs;
        }

        $accountInfo = $this->getOne(['mobile = ?' => $mobile], 'id as uid,store_id,user_status,user_nickname,type,mobile');
        if (!$accountInfo) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('账号不存在');
            return $rs;
        }

        if ($accountInfo['type'] != 1) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('账号类型错误');
            return $rs;
        }

        $MerchantSoreModel = new MerchantStoreModel();
        $MerchantSore = $MerchantSoreModel->getOne(['id = ?' => $accountInfo['store_id']], 'id,name,thumb,operating_state,type_id');

        if (!$MerchantSore) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺不存在');
            return $rs;
        }
        $MerchantTypeDomain = new \App\Domain\MerchantType();
        $top_type_id = $MerchantTypeDomain->getTopInfo($MerchantSore['type_id'])['id'] ?? 0;
        $MerchantSore['top_type_id'] = $top_type_id;

        $token = $this->updateToken($accountInfo['uid'],$accountInfo['store_id']);

        $accountInfo['im']['UserSig'] = \App\txim_setSig("mer_{$accountInfo['uid']}");
        $accountInfo['im']['userId'] = "mer_{$accountInfo['uid']}";

        $accountInfo['store'] = $MerchantSore;
        $accountInfo['token'] = $token;
        $rs['info'] = $accountInfo;
        return $rs;

    }


    /* 更新token 登陆信息 */
    public function updateToken($uid,$store_id)
    {

        $token=md5(md5($uid.time().rand(1000,9999) ));

        $nowtime=time();
        $expiretime=$nowtime+60*60*24*150;

        $Model_User=new Model_User();

        $where=['id'=>$uid];
        $up=[
            'last_login_time'=>$nowtime,
            'last_login_ip'=>\PhalApi\Tool::getClientIp(),
        ];
        $Model_User->up($where,$up);


        $token_info=array(
            'user_id'=>$uid,
            'token'=>$token,
            'expire_time'=>$expiretime,
            'create_time'=>$nowtime,
        );

        $Model_Usertoken=new Model_Usertoken();
        $where2=['user_id'=>$uid];

        $isexist=$Model_Usertoken->up($where2,$token_info);
        if(!$isexist){
            $Model_Usertoken->add($token_info);
        }


        $MerchantStoreModel = new MerchantStoreModel();
        $MerchantStore = $MerchantStoreModel->getOne(['id = ?' => $store_id], 'id,name,thumb,operating_state');

        $token_info = array(
            'user_id' => $uid,
            'token' => $token,
            'expire_time' => $expiretime,
            'create_time' => $nowtime,
            'store' => $MerchantStore,
        );


        \App\setcaches("merchant_token_" . $uid, $token_info);
        \App\delcache("token_" . $uid);

        return $token;
    }

    public function checkCode($type, $mobile, $code)
    {

        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        if ($mobile == '') {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('请输入手机号');
            return $rs;
        }
        if ($code == '') {
            $rs['code'] = 996;
            $rs['msg'] = \PhalApi\T('请输入验证码');
            return $rs;
        }

        $sms_key = 'sms_merchant_' . $type . '_' . $mobile;
        $sms_account = \App\getcaches($sms_key);
        if (!$sms_account) {
            $rs['code'] = 996;
            $rs['msg'] = \PhalApi\T('请先获取验证码');
            return $rs;
        }

        if ($sms_account['mobile'] != $mobile) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('手机号码错误');
            return $rs;
        }

        if ($sms_account['code'] != $code) {
            $rs['code'] = 996;
            $rs['msg'] = \PhalApi\T('验证码错误');
            return $rs;
        }

        if (time() > $sms_account['expiretime']) {
            $rs['code'] = 996;
            $rs['msg'] = \PhalApi\T('验证码已过期，请重新获取');
            return $rs;
        }

        return $rs;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {

        $UsersModel = new UsersModel();
        // TODO: Implement __call() method.
        return call_user_func_array([$UsersModel, $name], $arguments);
    }
}
