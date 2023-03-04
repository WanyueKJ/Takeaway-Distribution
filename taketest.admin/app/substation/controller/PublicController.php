<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\substation\controller;


use app\models\SubstationModel;
use cmf\controller\SubstationBaseController;

class PublicController extends SubstationBaseController
{
    public function initialize()
    {
        connectionRedis();
		$siteInfo = cmf_get_site_info();
        $this->assign("configpub", $siteInfo);
    }

    /**
     * 后台登陆界面
     */
    public function login()
    {

        $admin_id = session('substationid');
        if (!empty($admin_id)) {//已经登录
            return redirect(url("Index/index"));
        }

        return $this->fetch(":login");

    }

    /**
     * 登录验证
     */
    public function doLogin()
    {

        $account = $this->request->param("account");
        if (empty($account)) {
            $this->error('请输入账号');
        }
        $pass = $this->request->param("pass");
        if (empty($pass)) {
            $this->error('请输入密码');
        }
        $uinfo=SubstationModel::where(['user_login'=>$account])->field('id,user_pass,user_status')->find();
        if(!$uinfo){
            $this->error('账号错误');
        }


        if($uinfo['user_pass']!=cmf_password($pass)){
            $this->error('密码错误');
        }

        if($uinfo['user_status']==0){
            $this->error('账号已被禁用');
        }

        //登入成功页面跳转
        session('substationid', $uinfo['id']);

        $this->success('登录成功', url("Index/index"));
    }

    /**
     * 后台管理员退出
     */
    public function logout()
    {
        session('substationid', null);
        return redirect(url('public/login', [], false, true));
    }
}