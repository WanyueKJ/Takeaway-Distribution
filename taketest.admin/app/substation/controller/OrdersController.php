<?php

/**
 * 订单管理
 */
namespace app\substation\controller;

use app\models\OrdersModel;
use cmf\controller\SubstationBaseController;

class OrdersController extends SubstationBaseController {

    function index(){
        $data = $this->request->param();
        $map=[];


        $type=isset($data['type']) ? $data['type']: '';
        if($type!=''){
            $map[]=['type','=',$type];
        }

        $status= $data['status'] ?? '';
        if($status!=''){
            $map[]=['status','=',$status];
        }

        $paytype=$data['paytype'] ?? '';
        if($paytype!=''){
            $map[]=['paytype','=',$paytype];
        }
        
        $uid=isset($data['uid']) ? $data['uid']: '';
        if($uid!=''){
            $map[]=['uid','=',$uid];
        }

        $riderid= $data['riderid'] ?? '';
        if($riderid!=''){
            $map[]=['riderid','=',$riderid];
        }

        $active= $data['active'] ?? '';
        if($active!=''){
            if($active==1){

            }
            if($active==2){
                $map[]=['status','=',3];
            }
            if($active==3){
                $map[]=['status','in',[3,4]];
            }
            if($active==4){
                $map[]=['status','=',6];
            }
            if($active==5){
                $map[]=['status','>=',7];
                $map[]=['status','<=',9];
            }
            if($active==6){

            }
            if($active==7){
                $map[]=['status','in',[3,4]];
                $map[]=['istrans','<>',0];
            }
        }

        
        $keyword=isset($data['keyword']) ? $data['keyword']: '';
        if($keyword!=''){
            $map[]=['orderno|trade_no','=',$keyword];
        }

        $start_time=isset($data['start_time']) ? $data['start_time']: '';
        $end_time=isset($data['end_time']) ? $data['end_time']: '';

        if($start_time!=""){
            $map[]=['addtime','>=',strtotime($start_time)];
        }

        if($end_time!=""){
            $map[]=['addtime','<=',strtotime($end_time) + 60*60*24];
        }

        $cityid=session("cityid");
        $map[]=['cityid','=',$cityid];

        $lists = OrdersModel::where($map)
			->order("id desc")
			->paginate(20);
        
        $lists->each(function($v,$k){

            $v=OrdersModel::handleInfo($v);

            $from=$v['f_name'].' '.$v['f_addr'];
            $to=$v['t_name'].' '.$v['t_addr'];

			if($v['type']==3){
                if($v['extra']['type']==2){
                    $from='就近购买';
                }
            }
			$v['from']=$from;
			$v['to']=$to;

            return $v;
        });
        
        $lists->appends($data);
        $page = $lists->render();

    	$this->assign('lists', $lists);

    	$this->assign("page", $page);

    	$this->assign("active", $active);

        $this->assign('type', OrdersModel::getTypes());
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

        $cityid=session("cityid");
        if($data['cityid']!=$cityid){
            $this->error('订单信息错误');
        }

        $data=OrdersModel::handleInfo($data);

        $this->assign('data', $data);
        return $this->fetch();
    }

    function setrefund(){
        $id = $this->request->param('id', 0, 'intval');
        $status = $this->request->param('status', 0, 'intval');

        $info=OrdersModel::where("id={$id}")->find();
        if(!$info){
            $this->error('订单信息错误');
        }

        $cityid=session("cityid");
        if($info['cityid']!=$cityid){
            $this->error('订单信息错误');
        }

        $res=OrdersModel::handleRefund($id,$status);
        if($res!=1){
            $this->error($res);
        }

        $this->success("操作成功！");
    }


    function getList(){
        $data = $this->request->param();
        $map=[];

        $status=$data['status'] ?? 2;
        $map[]=['status','=',$status];

        $cityid= session("cityid");
        $map[]=['cityid','=',$cityid];

        $keyword=$data['keyword'] ?? '';
        if($keyword!=''){
            $map[]=['orderno','like',"%{$keyword}%"];
        }


        $list=OrdersModel::where($map)->order('id desc')->paginate(20);

        $list->each(function($v,$k){
            $v=OrdersModel::handleInfo($v);
            return $v;
        });

        $list->appends($data);

        $this->success('','',$list);
    }

    function designate(){
        $data = $this->request->param();
        $rid=$data['rid'] ?? 0;
        $oid=$data['oid'] ?? 0;
        //$this->error('信息错误');
        if($rid<1 || $oid<1){
            $this->error('信息错误');
        }

        $oinfo=OrdersModel::where(['id'=>$oid])->field('id,riderid,status,istrans,oldriderid')->find();
        if(!$oinfo){
            $this->error('订单信息错误');
        }

        if($oinfo['status']==1){
            $this->error('订单未支付，无法指派');
        }
        if($oinfo['status']==10){
            $this->error('订单已取消，无法指派');
        }
        if($oinfo['status']!=2){
            $this->error('订单已服务中，无法指派');
        }

        if($oinfo['istrans']==1 && $oinfo['oldriderid']==$rid){
            $this->error('此订单为该骑手转单订单，无法指派给该骑手');
        }

        $nowtime=time();
        $where=[
            'id'=>$oid,
            'status'=>2,
            'riderid'=>0,
        ];
        $up=[
            'riderid'=>$rid,
            'status'=>3,
            'graptime'=>$nowtime,
        ];
        if($oinfo['oldriderid']==0){
            $up['oldriderid']=$rid;
        }

        $res=OrdersModel::where($where)->update($up);
        if(!$res){
            $this->error('指派失败，请重试');
        }

        OrdersModel::presetIncome($oid);

        $key='orders_dispatch';
        hSet($key,$rid,$nowtime);
        $this->success('指派成功');

    }

    function setTrans(){
        $data = $this->request->param();
        $trans=$data['trans'] ?? 0;
        $oid=$data['id'] ?? 0;
        //$this->error('信息错误');
        if($oid<1){
            $this->error('信息错误');
        }

        $oinfo=OrdersModel::where(['id'=>$oid])->field('id,riderid,status,istrans,oldriderid')->find();
        if(!$oinfo){
            $this->error('订单信息错误');
        }

        if($oinfo['status']<> 3){
            $this->error('订单状态错误，无法操作');
        }

        if($oinfo['istrans']==0){
            $this->error('订单未申请转单，无法操作');
        }


        $nowtime=time();
        $where=[
            'id'=>$oid,
            'istrans'=>2,
        ];
        $up=[
            'istrans'=>$trans,
        ];

        if($trans==1){
            $up['status']=2;
            $up['graptime']=0;
            $up['riderid']=0;
            $up['isincome']=0;
            $up['rider_income']=0;
            $up['substation_income']=0;
        }

        $res=OrdersModel::where($where)->update($up);
        if(!$res){
            $this->error('操作失败，请重试');
        }

        $key='orders_trans';
        hSet($key,$oinfo['riderid'],$trans);
        $this->success('操作成功');

    }
}
