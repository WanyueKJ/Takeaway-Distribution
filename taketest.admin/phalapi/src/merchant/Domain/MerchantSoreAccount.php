<?php

namespace merchant\Domain;

use Merchant\Model\MerchantSoreAccount as MerchantSoreAccountModel;
use Merchant\Model\MerchantStore as MerchantStoreModel;

/**
 * 店铺管理-商家管理
 */
class MerchantSoreAccount
{

    public function loginByCode($mobile)
    {

        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        if ($mobile == '') {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('请输入手机号');
            return $rs;
        }

        $accountInfo = $this->getOne(['account = ?' => $mobile], 'id as uid,store_id,status,user_nickname');
        if (!$accountInfo) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('账号不存在');
            return $rs;
        }

        $MerchantSoreModel = new MerchantStoreModel();
        $MerchantSore = $MerchantSoreModel->getOne(['id = ?' => $accountInfo['store_id']], 'id,name,thumb,operating_state,type_id');
        $MerchantTypeDomain = new \App\Domain\MerchantType();
        $top_type_id = $MerchantTypeDomain->getTopInfo($MerchantSore['type_id'])['id'] ?? 0;
        $MerchantSore['top_type_id'] = $top_type_id;

        if (!$MerchantSore) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺不存在');
            return $rs;
        }
        $token = $this->updateToken($accountInfo['uid']);

        $accountInfo['im']['UserSig'] = \App\txim_setSig("mer_{$accountInfo['uid']}");
        $accountInfo['im']['userId'] = "mer_{$accountInfo['uid']}";

        $accountInfo['store'] = $MerchantSore;
        $accountInfo['token'] = $token;
        $rs['info'] = $accountInfo;
        return $rs;

    }

    /* 更新token 登陆信息 */
    public function updateToken($uid)
    {

        $token = md5(md5($uid . time() . rand(1000, 9999)));

        $nowtime = time();
        $expiretime = $nowtime + 60 * 60 * 24 * 150;

        $MerchantStoreModel = new MerchantStoreModel();
        $MerchantStore = $MerchantStoreModel->getOne(['id = ?' => $uid], 'id,name,thumb,operating_state');

        $token_info = array(
            'user_id' => $uid,
            'token' => $token,
            'expire_time' => $expiretime,
            'create_time' => $nowtime,
            'store' => $MerchantStore,
        );
        \App\setcaches("merchant_token_" . $uid, $token_info);

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

    public function clearCode($type, $mobile)
    {
        $sms_key = 'sms_merchant_' . $type . '_' . $mobile;
        \App\delcache($sms_key);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {

        $MerchantSoreAccountModel = new MerchantSoreAccountModel();
        // TODO: Implement __call() method.
        return call_user_func_array([$MerchantSoreAccountModel, $name], $arguments);
    }
}