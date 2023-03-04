<?php

/**
 * 统计报表
 */
namespace app\substation\controller;

use app\models\OrdersModel;
use app\models\RiderModel;
use app\models\CityModel;
use app\models\RidercountModel;
use cmf\controller\SubstationBaseController;

class StatisticalController extends SubstationBaseController {

    function index(){
        $data = $this->request->param();
        $map=[];
        $map[]=['user_status','=',1];
   
        $cityid=session("cityid");
        $map[]=['cityid','=',$cityid];
        
        $uid=isset($data['uid']) ? $data['uid']: '';
        if($uid!=''){
            $map[]=['id','=',$uid];
        }

        $map[]=['type','=',2];
        
        $keyword=isset($data['keyword']) ? $data['keyword']: '';
        if($keyword!=''){
            $map[]=['user_login|user_nickname|mobile','=',$keyword];
        }
		
        $lists = RiderModel::where($map)
			->order("id desc")
			->paginate(20);
        
        $lists->each(function($v,$k){

            $v['orders']=RidercountModel::getOrderNums(['uid'=>$v['id']],'orders');
            $v['cityname']=CityModel::getTwoCityName($v['cityid']);

            return $v;           
        });
        
        $lists->appends($data);
        $page = $lists->render();

    	$this->assign('lists', $lists);

    	$this->assign("page", $page);

        $this->assign('type', RiderModel::getTypes());


    	return $this->fetch();
    }

    function index2(){
        $data = $this->request->param();
        $map=[];

        $cityid=session("cityid");
        $map[]=['cityid','=',$cityid];

        $uid= $data['uid'] ?? 0;
        $map[]=['riderid','=',$uid];

        $type= $data['type'] ?? '';
        if($type!=''){
            $map[]=['type','=',$type];
        }

        $income= $data['income'] ?? '';
        if($income!=''){
            $map[]=['isincome','=',$income];
        }

        $map[]=['status','>=',3];
        $map[]=['status','<=',6];

        $start_time= $data['start_time'] ?? '';
        $end_time= $data['end_time'] ?? '';

        if($start_time!=""){
            $map[]=['addtime','>=',strtotime($start_time)];
        }

        if($end_time!=""){
            $map[]=['addtime','<=',strtotime($end_time) + 60*60*24];
        }


        $keyword= $data['keyword'] ?? '';
        if($keyword!=''){
            $map[]=['orderno|trade_no','=',$keyword];
        }

        $lists = OrdersModel::where($map)
            ->order("id desc")
            ->paginate(20);

        $lists->each(function($v,$k){
            $v['riderinfo']=getRiderInfo($v['riderid']);
            return $v;
        });

        $lists->appends($data);
        $page = $lists->render();

        $this->assign('lists', $lists);

        $this->assign("page", $page);

        $this->assign('type', OrdersModel::getTypes());
        $this->assign('income', OrdersModel::getIncomeType());

        $total=OrdersModel::where($map)->sum('money_total');
        if(!$total){
            $total=0;
        }
        $this->assign("total", $total);

        $no1=OrdersModel::where($map)->where(['isincome'=>1])->sum('rider_income');
        if(!$no1){
            $no1=0;
        }

        $no2=OrdersModel::where($map)->where(['isincome'=>1])->sum('substation_income');
        if(!$no2){
            $no2=0;
        }
        $no=$no1+$no2;
        $this->assign("no", $no);

        $ok1=OrdersModel::where($map)->where(['isincome'=>2])->sum('rider_income');
        if(!$ok1){
            $ok1=0;
        }

        $ok2=OrdersModel::where($map)->where(['isincome'=>2])->sum('substation_income');
        if(!$ok2){
            $ok2=0;
        }
        $ok=$ok1+$ok2;

        $this->assign("ok", $ok);


        return $this->fetch();
    }

    
}
