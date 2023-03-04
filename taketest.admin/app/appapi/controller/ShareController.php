<?php

/* 分享页面 */
namespace app\appapi\controller;

use cmf\controller\HomeBaseController;


class ShareController extends HomebaseController{

	public function index() {

        $data      = $this->request->param();
        $code=$data['code'] ?? '';
        $userinfo=[];

        if(!$userinfo){
            $code='';
        }

        $this->assign('uid', '');
        $this->assign('token', '');
        $this->assign('code', $code);
        $this->assign('userinfo', $userinfo);

        return $this->fetch('index');
	}

}
