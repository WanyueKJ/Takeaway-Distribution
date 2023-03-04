<?php

/**
 * 用户反馈
 */
namespace app\admin\controller;

use app\models\FeedbackModel;
use cmf\controller\AdminBaseController;

class FeedbackController extends AdminbaseController {

    function index(){
        $data = $this->request->param();
        $map=[];
        
        $start_time= $data['start_time'] ?? '';
        $end_time= $data['end_time'] ?? '';
        
        if($start_time!=""){
           $map[]=['addtime','>=',strtotime($start_time)];
        }

        if($end_time!=""){
           $map[]=['addtime','<=',strtotime($end_time) + 60*60*24];
        }
        
        $status= $data['status'] ?? '';
        if($status!=''){
            $map[]=['status','=',$status];
        }
        
        $uid=  $data['uid'] ?? '';
        if($uid!=''){
            $map[]=['uid','=',$uid];
        }
		
        $lists = FeedbackModel::where($map)
			->order("id desc")
			->paginate(20);
        
        $lists->each(function($v,$k){
			$v['userinfo']=getUserInfo($v['uid']);
			$v['thumb']=get_upload_path($v['thumb']);
            return $v;           
        });
        
        $lists->appends($data);
        $page = $lists->render();

    	$this->assign('lists', $lists);

    	$this->assign("page", $page);
        
        $this->assign('status', FeedbackModel::getStatus());
        
    	return $this->fetch();
    }
    
    function setstatus(){
        $id = $this->request->param('id', 0, 'intval');
        
        $rs = FeedbackModel::where("id={$id}")->update(['status'=>1,'uptime'=>time()]);
        if(!$rs){
            $this->error("标记失败！");
        }

        $this->success("标记成功！");
        							  			
    }
    
    function del(){
        $id = $this->request->param('id', 0, 'intval');
        
        $rs = FeedbackModel::where("id={$id}")->delete();
        if(!$rs){
            $this->error("删除失败！");
        }

        $this->success("删除成功！");
        							  			
    }
    
}
