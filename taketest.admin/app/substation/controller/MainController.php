<?php
// +----------------------------------------------------------------------
// | Created by Wanyue
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2019 http://www.sdwanyue.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: https://gitee.com/WanYueKeJi
// +----------------------------------------------------------------------
// | Date: 2020/10/26 09:08
// +----------------------------------------------------------------------
namespace app\substation\controller;

use cmf\controller\SubstationBaseController;
use app\models\OrdersModel;
use app\models\RiderModel;
use app\models\EvaluategentModel;

/*
 * 后台欢迎页
 * Class MainController
 * @package app\admin\controller
 */

class MainController extends SubstationBaseController
{

    /**
     *  后台欢迎页
     */
    public function index()
    {


        $cityid=session("cityid");

        $nowtime=time();
        //当天0点
        $today=date("Ymd",$nowtime);
        $today_start=strtotime($today);
        //当天 23:59:59
        $today_end=strtotime("{$today} + 1 day");

        $yes_start=strtotime("{$today} - 1 day");

        /* 待接单 */
        $status_2=OrdersModel::where(['status'=>2,'cityid'=>$cityid])->count();
        /* 服务中 */
        $status_4=OrdersModel::where([ ['cityid','=',$cityid],['status','in',[3,4] ] ])->count();
        /* 申请转单 */
        $status_7=OrdersModel::where(['istrans'=>2,'cityid'=>$cityid])->count();
        /* 申请退款 */
        $status_5=OrdersModel::where(['status'=>7,'cityid'=>$cityid])->count();

        /* 今日总订单 */
        $order_all_tod=OrdersModel::where([ ['cityid','=',$cityid],['addtime','>=',$today_start],['addtime','<',$today_end],['status','>=',2],['status','<=',10] ])->count();
        $order_all_yes=OrdersModel::where([ ['cityid','=',$cityid],['addtime','>=',$yes_start],['addtime','<',$today_start],['status','>=',2],['status','<=',10] ])->count();
        $order_all_css='icon-triangle-top';
        if($order_all_tod < $order_all_yes){
            $order_all_css='icon-triangle-bottom';
        }
        $order_all_cha=abs($order_all_tod - $order_all_yes);
        $order_all_rate=0;
        if($order_all_cha!=0 && $order_all_yes!=0){
            $order_all_rate=floor( $order_all_cha / $order_all_yes * 100);
        }

        /* 今日完成订单 */
        $order_ok_tod=OrdersModel::where([ ['cityid','=',$cityid],['status','=',6],['addtime','>=',$today_start],['addtime','<',$today_end] ])->count();
        $order_ok_yes=OrdersModel::where([ ['cityid','=',$cityid],['status','=',6],['addtime','>=',$yes_start],['addtime','<',$today_start] ])->count();

        /* 骑手 */
        $rider_all=RiderModel::where([ ['cityid','=',$cityid],['user_status','=',1] ])->count();
        $rider_qz=RiderModel::where([ ['cityid','=',$cityid],['user_status','=',1],['type','=',2] ])->count();
        $rider_jz=RiderModel::where([ ['cityid','=',$cityid],['user_status','=',1],['type','=',1] ])->count();
        $rider_wait=RiderModel::where([ ['cityid','=',$cityid],['user_status','=',2] ])->count();

        /* 今日收入 */
        $income_today=OrdersModel::where([ ['cityid','=',$cityid],['status','=',6],['addtime','>=',$today_start],['addtime','<',$today_end] ])->field('sum(substation_income) as substation_income,sum(rider_income) as rider_income')->find();

        $substation_income_to=$income_today['substation_income'] ?? 0;
        $rider_income_to=$income_today['rider_income'] ?? 0;

        /* 昨日日收入 */
        $income_yes=OrdersModel::where([ ['cityid','=',$cityid],['status','=',6],['addtime','>=',$yes_start],['addtime','<',$today_start] ])->field('sum(substation_income) as substation_income,sum(rider_income) as rider_income')->find();

        $substation_income_yes=$income_yes['substation_income'] ?? 0;
        $rider_income_yes=$income_yes['rider_income'] ?? 0;


        $this->assign(compact('status_2','status_4','status_7','status_5','order_all_tod','order_all_css','order_all_rate','order_ok_tod','order_ok_yes','rider_all','rider_jz','rider_qz','rider_wait','substation_income_to','rider_income_to','substation_income_yes','rider_income_yes'));

        return $this->fetch();

    }


    function getTodayOrderData(){

         $data = $this->request->param();

        $cityid=session("cityid");

         $type=$data['type'] ?? 'hour';

         $nowtime=time();
         //当天0点
         $today=date("Ymd",$nowtime);
         $today_start=strtotime($today);
         //当天 23:59:59
         $today_end=strtotime("{$today} + 1 day");

        $data=[];
        switch ($type){
            case 'hour':
                $x=[];
                $y=[];
                $s=$today_start;
                for ($i=1;$i<=24;$i++){
                    $x[]=$i;
                    if($s>$nowtime){
                        continue;
                    }
                    $e=$s+60*60;
                    $nums=OrdersModel::where([ ['cityid','=',$cityid],['status','=',6],['addtime','>=',$today_start],['addtime','<',$today_end] ])->count();
                    $y[]=$nums;

                    $s=$e;
                }

                $data['x']=$x;
                $data['y']=$y;
                break;
            default:

        }

        $this->success('','',$data);
    }

    function getService(){

        $data = $this->request->param();

        $cityid=session("cityid");

        $type=$data['type'] ?? '1';

        $nowtime=time();
        //当天0点
        $today=date("Ymd",$nowtime);
        $today_start=strtotime($today);
        //当天 23:59:59
        $today_end=strtotime("{$today} + 1 day");

        $data=[
            [
                'name'=>'5分',
                'start'=>'4',
                'end'=>'5',
                'value'=>0,
            ],
            [
                'name'=>'4分',
                'start'=>'3',
                'end'=>'4',
                'value'=>0,
            ],
            [
                'name'=>'3分',
                'start'=>'2',
                'end'=>'3',
                'value'=>0,
            ],
            [
                'name'=>'2分',
                'start'=>'1',
                'end'=>'2',
                'value'=>0,
            ],
            [
                'name'=>'1分',
                'start'=>'0',
                'end'=>'1',
                'value'=>0,
            ],
        ];

        $start=$today_start;
        $end=$today_end;

        switch ($type){
            case '2':

                $start=$today_start - 60*60*24*6;
                break;
            case '3':
                $start=$today_start - 60*60*24*30;
                break;
            default:

        }

        foreach ($data as $k=>$v){
            $nums=EvaluategentModel::where([ ['cityid','=',$cityid],['addtime','>=',$start],['addtime','<',$end],['star','>',$v['start']],['star','<=',$v['end']] ])->count();

            $v['value']=$nums;
            unset($v['start']);
            unset($v['end']);
            $data[$k]=$v;
        }

        $this->success('','',$data);
    }

    function getRiderOrder(){

        $data = $this->request->param();

        $cityid=session("cityid");

        $type=$data['type'] ?? '1';

        $nowtime=time();
        //当天0点
        $today=date("Ymd",$nowtime);
        $today_start=strtotime($today);
        //当天 23:59:59
        $today_end=strtotime("{$today} + 1 day");

        $data_all=[
            [
                'name'=>'0单',
                'start'=>'0',
                'end'=>'0',
                'nums'=>0,
            ],
            [
                'name'=>'1-5单',
                'start'=>'1',
                'end'=>'5',
                'nums'=>0,
            ],
            [
                'name'=>'6-10单',
                'start'=>'6',
                'end'=>'10',
                'nums'=>0,
            ],
            [
                'name'=>'11-15单',
                'start'=>'11',
                'end'=>'15',
                'nums'=>0,
            ],
            [
                'name'=>'15单以上',
                'start'=>'15',
                'end'=>'0',
                'nums'=>0,
            ],
        ];


        $start=$today_start;
        $end=$today_end;

        switch ($type){
            case '2':
                $start=$today_start - 60*60*24*6;
                break;
            case '3':
                $start=$today_start - 60*60*24*30;
                break;
            default:

        }

        $rider_all=RiderModel::where([ ['cityid','=',$cityid],['user_status','=',1] ])->count();

        $order_nums=OrdersModel::where([ ['cityid','=',$cityid],['status','=',6],['addtime','>=',$start],['addtime','<',$end] ])->field('count(*) as nums')->group('riderid')->select()->toArray();

        foreach ($order_nums as $k2=>$v2){
            $nums=$v2['nums'];
            foreach ($data_all as $k=>$v){
                $isok=0;
                if($v['start']==$v['end'] ){
                    if($v['start']==$nums){
                        $v['nums']++;
                    }
                }

                if($v['start'] < $v['end']){
                    if($v['start']<=$nums && $v['end'] >= $nums){
                        $v['nums']++;
                    }
                }

                if($v['start'] > $v['end']){
                    if($v['start'] < $nums){
                        $v['nums']++;
                    }
                }

                if($isok){
                    $data_all[$k]=$v;
                    break;
                }
            }
        }

        $x=array_column($data_all,'nums');
        $y=array_column($data_all,'name');

        $zero= $rider_all - count($order_nums);

        $x[0]=$zero;
        $data=[
            'x'=>$x,
            'y'=>$y,
        ];

        $this->success('','',$data);
    }

}
