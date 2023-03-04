<?php

namespace cmf\controller;
use app\models\SubstationModel;
use think\Db;

class SubstationBaseController extends HomeBaseController
{

    public function initialize()
    {
        parent::initialize();
        self::checkLogin();

        //connectionRedis();

        $siteInfo = cmf_get_site_info();
        $this->assign("configpub", $siteInfo);
    }

    public function checkLogin()
    {

        $userId = session('substationid');
        if (empty($userId)) {
            $userId=0;
        }
    
        if (empty($userId)) {
            if ($this->request->isAjax()) {
                $this->error("您尚未登录", cmf_url("public/login"));
            } else {
                $this->redirect(cmf_url("public/login"));
            }
        }
        
        $userinfo=SubstationModel::where(['id'=>$userId])->field('id,user_nickname,avatar,user_status,cityid')->find();
        if(!$userinfo){
            session("substationid", null);
            $this->error("账号不存在，禁止访问", "/" );
        }

        if($userinfo['user_status']==0){
            session("substationid", null);
            $this->error("该账号已被禁用", "/" );
            return !1;
        }

        session("cityid", $userinfo['cityid']);

        $this->assign("admin", $userinfo);
    }    
}