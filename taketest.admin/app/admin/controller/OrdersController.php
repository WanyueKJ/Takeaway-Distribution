<?php

/**
 * 订单管理
 */
namespace app\admin\controller;

use app\models\OrdersModel;
use cmf\controller\AdminBaseController;

class OrdersController extends AdminbaseController {

    function index(){
        $data = $this->request->param();
        $map=[];
        //$map[]=['status','',1];
        $start_time=isset($data['start_time']) ? $data['start_time']: '';
        $end_time=isset($data['end_time']) ? $data['end_time']: '';
        
        if($start_time!=""){
           $map[]=['addtime','>=',strtotime($start_time)];
        }

        if($end_time!=""){
           $map[]=['addtime','<=',strtotime($end_time) + 60*60*24];
        }

        $type=isset($data['type']) ? $data['type']: '';
        if($type!=''){
            $map[]=['type','=',$type];
        }
        
        $uid=isset($data['uid']) ? $data['uid']: '';
        if($uid!=''){
            $map[]=['uid','=',$uid];
        }

        $uid=isset($data['uid']) ? $data['uid']: '';
        if($uid!=''){
            $map[]=['uid','=',$uid];
        }
        
        $keyword=isset($data['keyword']) ? $data['keyword']: '';
        if($keyword!=''){
            $map[]=['orderno|trade_no','=',$keyword];
        }
        if(isset($data['paytype'])){
            if($data['paytype']!=''){
                $map[]=['paytype','=',$data['paytype']];
            }
        }
        
		
        $lists = OrdersModel::where($map)
			->order("id desc")
			->paginate(20);
        
        $lists->each(function($v,$k){
			$v['userinfo']=getUserInfo($v['uid']);
            return $v;           
        });
      
        $lists->appends($data);
        $page = $lists->render();

    	$this->assign('lists', $lists);

    	$this->assign("page", $page);

     
        $this->assign('paytype', OrdersModel::getPayTypes());

        $this->assign('status', OrdersModel::getStatus());

    	return $this->fetch();
    }

    function detail(){
        $id   = $this->request->param('id', 0, 'intval');

        $data=OrdersModel::where("id={$id}")->find();
        if(!$data){
            $this->error("信息错误");
        }

        $data=OrdersModel::handleInfo($data);

        $this->assign('data', $data);
        return $this->fetch();
    }
    function del(){
        $id = $this->request->param('id', 0, 'intval');
        
        $rs = OrdersModel::where("id={$id}")->delete();
        if($rs===false){
            $this->error("删除失败！");
        }
                   
        $this->success("删除成功！");
        							  			
    }
    
}
