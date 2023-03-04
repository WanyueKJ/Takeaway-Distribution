<?php

namespace App\Api;

use App\ApiException;
use App\Domain\Login as Domain_Login;
use PhalApi\Api;
use App\Domain\User as Domain_User;

/**
 * (改-1)用户信息
 */
class User extends Api
{
    public function getRules()
    {
        return array(
            'getBaseInfo' => array(
                'ios_version' => array('name' => 'ios_version', 'type' => 'string', 'default' => '', 'desc' => 'IOS版本号'),
            ),

            'upUserInfo' => array(
                'fields' => array('name' => 'fields', 'type' => 'string', 'default' => '', 'desc' => '修改信息json串'),
            ),

            'upPass' => array(
                'oldpass' => array('name' => 'oldpass', 'type' => 'string', 'default' => '', 'desc' => '旧密码'),
                'newpass' => array('name' => 'newpass', 'type' => 'string', 'default' => '', 'desc' => '新密码'),
            ),
            'upMobile' => array(
                'code' => array('name' => 'code', 'type' => 'string', 'default' => '', 'desc' => '验证码(验证码用:App.Login.LoginByCode)'),
                'new_mobile' => array('name' => 'new_mobile', 'type' => 'string', 'default' => '', 'desc' => '新手机号(带国家代码:+86-110)'),
            ),
            'thirdList' => array(),
            'writeOff' =>array(),
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
        $this->checkLogin($uid, $token);
        $DomainUser = new Domain_User();
        $DomainUser->logOut($uid);
        return $rs;
    }


    /**
     * (新-1)账号注销
     * @desc 账号注销
     * @return array
     * @throws ApiException
     */
    public function writeOff(){
        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $this->checkLogin($uid, $token);
        $DomainUser = new Domain_User();
        $res = $DomainUser->setWriteOff($uid);
        return $res;

    }



    /**
     * 检测登录状态
     * @param $uid
     * @param $token
     * @return void
     * @throws ApiException
     */
    protected function checkLogin($uid, $token)
    {
        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            throw new ApiException(\PhalApi\T('您的登陆状态失效，请重新登陆！'), 700);
        }
    }

    /**
     * (新-1)修改手机号(绑定手机号,修改绑定)
     * @desc 修改手机号
     * @return array
     * @throws ApiException
     */
    public function upMobile()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());
        $uid = \App\checkNull($this->uid);

        $token = \App\checkNull($this->token);
        $this->checkLogin($uid, $token);

        $code = \App\checkNull($this->code);
        $new_mobile = \App\checkNull($this->new_mobile);
        $new_mobile = explode('-',$new_mobile)[1] ?? '';

//        $action = 'App.Login.getCode';
//        $date = date('Y-m-d H:i:s');
//        file_put_contents('./log.txt', var_export(compact('action','date','new_mobile','code'), true) . PHP_EOL, FILE_APPEND);

        $domain = new Domain_Login();
        $res = $domain->checkCode(5, $new_mobile, $code);
        if ($res['code'] != 0) {
            return $res;
        }
        $domain = new Domain_User();
        $domain->upMobile($uid, $new_mobile);
        return $rs;
    }


    /**
     * 判断token
     * @desc 用于判断token
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function iftoken()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }
        return $rs;
    }

    /**
     * (改-1)获取用户信息
     * @desc 用于获取单个用户基本信息
     * @return int code 操作码，0表示成功， 1表示用户不存在
     * @return array info
     * @return array info[0] 用户信息
     * @return int info[0].id 用户ID
     * @return int info[0].welcome (改-1)欢迎语
     * @return string info[0].type 类型0学员1讲师
     * @return string info[0].noread 是否有未读 0否1是
     * @return array  info[0].list 下部列表
     * @return string msg 提示信息
     */
    public function getBaseInfo()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $info = [
            'id' => '0',
            'user_nickname' => '',
            'avatar' => '',
            'avatar_thumb' => '',
            'sex' => '0',
            'signature' => '',
            'balance' => '0',
            'consumption' => '0',
            'mobile' => '',
        ];

        if ($uid > 0) {
            $checkToken = \App\checkToken($uid, $token);
            if ($checkToken == 700) {
                $rs['code'] = $checkToken;
                $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
                return $rs;
            }

            $domain = new Domain_User();
            $info = $domain->getBaseInfo($uid);
            if (!$info) {
                $rs['code'] = 700;
                $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
                return $rs;
            }
        }

        $configpub = \App\getConfigPub();
        $ios_shelves = $configpub['ios_shelves'];

        /* 个人中心菜单 */
        $ios_version = \App\checkNull($this->ios_version);

        $list = [];
        $orders = [];
        $img_time = '?t=1640153141';
        $shelves = 1;
        if ($ios_version && $ios_version == $ios_shelves) {
            $shelves = 0;
        }

        $configpri = \App\getConfigPri();



        $one = [
            ['id' => '1', 'name' => \PhalApi\T('地点'), 'thumb' => \App\get_upload_path("/static/app/person2/diDian.png") . $img_time, 'href' => '', 'nums' => '0', 'status' => '1'],
            ['id' => '2', 'name' => \PhalApi\T('待支付'), 'thumb' => \App\get_upload_path("/static/app/person/order_1.png") . $img_time, 'href' => '', 'nums' => '0', 'status' => '1'],
            ['id' => '3', 'name' => \PhalApi\T('待评价 '), 'thumb' => \App\get_upload_path("/static/app/person2/pingJia.png") . $img_time, 'href' => '', 'nums' => '0', 'status' => '1'],

        ];
        $two = [
            ['id' => '5', 'name' => \PhalApi\T('商户入驻'), 'thumb' => \App\get_upload_path("/static/app/person2/shangHuRuZhu.png") . $img_time, 'href' => '', 'nums' => '0', 'status' => '1'],
            ['id' => '7', 'name' => \PhalApi\T('骑手加盟 '), 'thumb' => \App\get_upload_path("/static/app/person2/qiShouJiaMeng.png") . $img_time, 'href' => '', 'nums' => '0', 'status' => '1'],
            ['id' => '8', 'name' => \PhalApi\T('意见反馈'), 'thumb' => \App\get_upload_path("/static/app/person2/yiJianFanKui.png") . $img_time, 'href' => '', 'nums' => '0', 'status' => '1'],

        ];
        $info['one'] = $one;
        $info['two'] = $two;
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
    public function upUserInfo()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $fields = $this->fields;

        if ($fields == '') {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('信息错误');
            return $rs;
        }
//        file_put_contents('./log.txt', var_export(compact('uid', 'token', 'fields'), true) . PHP_EOL, FILE_APPEND);

        $fields_a = json_decode($fields, true);
        if (!$fields_a) {
            $rs['code'] = 1002;
            $rs['msg'] = \PhalApi\T('信息错误');
            return $rs;
        }

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

        $domain = new Domain_User();
        $info = $domain->upUserInfo($uid, $fields_a);

        return $info;
    }

    /**
     * 更新密码
     * @desc 更新密码
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function upPass()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $oldpass = \App\checkNull($this->oldpass);
        $newpass = \App\checkNull($this->newpass);

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

        $domain = new Domain_User();
        $info = $domain->upPass($uid, $oldpass, $newpass);

        return $info;
    }

}
