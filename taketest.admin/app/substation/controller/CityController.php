<?php

/* 城市管理 */
namespace app\substation\controller;

use app\models\CityModel;
use cmf\controller\SubstationBaseController;
use think\Db;

class CityController extends SubstationBaseController
{

    public function index()
    {
        $data = $this->request->param();
        $map=[];

        $cityid=session("cityid");

        $city_one=CityModel::getLevelOne();

        $map[]=['id','=',$cityid];

        $keyword=$data['keyword'] ?? '';
        if($keyword!=''){
            $map[]=['name','like',"%{$keyword}%"];
        }


        $list = CityModel::where($map)
            ->order("pid asc,list_order asc")
            ->paginate(20);


        $list->each(function($v,$k)use($city_one){

            $v['pname']=$city_one[$v['pid']]['name'] ?? '';

            return $v;
        });

        $list->appends($data);
        // 获取分页显示
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);

        // 渲染模板输出
        return $this->fetch('index');
    }


    public function edit()
    {
        //$id   = $this->request->param('id', 0, 'intval');

        $cityid=session("cityid");
        /*if($id!=$cityid){
            $this->error("信息错误");
        }*/
        $data=CityModel::where("id={$cityid}")
            ->find();
        if(!$data){
            $this->error("信息错误");
        }

        $config=json_decode($data['config'],true);
        $this->assign('data', $data);
        $this->assign('type', $config['type']);
        $this->assign('type1', $config['type1']);

        $this->assign('type3', $config['type3']);
        $this->assign('type4', $config['type4']);
        $this->assign('type5', $config['type5']);
        $this->assign('type6', $config['type6'] ?? []);
        $this->assign('config', $config);
        $pinfo=CityModel::where(['id'=>$data['pid']])->find();
        $this->assign('pinfo', $pinfo);

        $this->assign('time', CityModel::getTimePeriod());

        return $this->fetch();
    }

    public function editPost()
    {
        if ($this->request->isPost()) {

            $data=$this->check();

            $rs = CityModel::update($data);

            CityModel::resetcache();

            $this->success("保存成功！");
        }
    }

    public function check(){

        $data      = $this->request->param();

        $id=$data['id'] ?? 0;

        $cityid=session("cityid");
        if($id!=$cityid){
            $this->error("信息错误");
        }

        $type=$data['type'] ?? [];
        $type1=$data['type1'] ?? [
            'fee_mode'=>1,
            'fix_money'=>0,
            'distance_switch'=>0,
            'distance_basic'=>0,
            'distance_basic_money'=>0,
            'distance_more_money'=>0,
            'distance_mode'=>1,
            'distance_type'=>1,
            'weight_switch'=>0,
            'weight_basic'=>0,
            'weight_basic_money'=>0,
            'weight_more_money'=>0,
            'weight_type'=>1,
            'h'=>0,
            'i'=>0,
        ];
        $type3=$data['type3'] ?? [
            'fee_mode'=>1,
            'fix_money'=>0,
            'nearby_money'=>0,
            'distance_basic'=>0,
            'distance_basic_money'=>0,
            'distance_more_money'=>0,
            'distance_mode'=>1,
            'distance_type'=>1,
            'h'=>0,
            'i'=>0,
        ];
        $type4=$data['type4'] ?? [
            'fee_mode'=>1,
            'fix_money'=>0,
            'time_basic'=>0,
            'time_basic_money'=>0,
            'time_more'=>0,
            'time_more_money'=>0,
            'time_type'=>1,
            'h'=>0,
            'i'=>0,
        ];
        $type5=$data['type5'] ?? [
            'fix_money'=>0,
            'h'=>0,
            'i'=>0,
        ];
        $type6=$data['type6'] ?? [
            'fee_mode'=>1,
            'fix_money'=>0,
            'distance_switch'=>0,
            'distance_basic'=>0,
            'distance_basic_money'=>0,
            'distance_more_money'=>0,
            'distance_mode'=>1,
            'distance_type'=>1,
            'h'=>0,
            'i'=>0,
        ];
        unset($data['type']);
        unset($data['type1']);
        unset($data['type3']);
        unset($data['type4']);
        unset($data['type5']);
        unset($data['type6']);

        $config=[];
        $type1['times']=[];
        $type3['times']=[];
        $type4['times']=[];
        $type5['times']=[];
        $type6['times']=[];

        if(!$type){
            $this->error("请开通至少一项业务");
        }
        if(in_array(1,$type) || in_array(2,$type)){
            if($type1['fee_mode']==1){
                if($type1['fix_money']<=0){
                    $this->error("请设置正确的送取费用-固定配送费");
                }
            }
            if($type1['fee_mode']==2){
                if($type1['distance_switch']==0 && $type1['distance_switch']==0){
                    $this->error("送取费用-动态费用模式至少选择一种");
                }
            }
            if($type1['distance_switch']==1){
                if($type1['distance_basic']<=0){
                    $this->error("请设置正确的送取费用-距离附加费-起始距离");
                }

                if($type1['distance_basic_money']<0){
                    $this->error("请设置正确的送取费用-距离附加费-起始价格");
                }

                if($type1['distance_more_money']<0){
                    $this->error("请设置正确的送取费用-距离附加费-超出价格");
                }
            }
            if($type1['weight_switch']==1){
                if($type1['weight_basic']<=0){
                    $this->error("请设置正确的送取费用-重量附加费-起始重量");
                }

                if($type1['weight_basic_money']<0){
                    $this->error("请设置正确的送取费用-重量附加费-起始价格");
                }

                if($type1['weight_more_money']<0){
                    $this->error("请设置正确的送取费用-重量附加费-超出价格");
                }
            }

            $type1=$this->checkTime($type1);
        }



        if(in_array(6,$type)){
            if($type6['fee_mode']==1){
                if($type6['fix_money']<=0){
                    $this->error("请设置正确的送取费用-固定配送费");
                }
            }
            if($type6['fee_mode']==2){
                if($type6['distance_switch']==0 && $type6['distance_switch']==0){
                    $this->error("送取费用-动态费用模式至少选择一种");
                }
            }
            if($type6['distance_switch']==1){
                if($type6['distance_basic']<=0){
                    $this->error("请设置正确的送取费用-距离附加费-起始距离");
                }

                if($type6['distance_basic_money']<=0){
                    $this->error("请设置正确的送取费用-距离附加费-起始价格");
                }

                if($type6['distance_more_money']<0){
                    $this->error("请设置正确的送取费用-距离附加费-超出价格");
                }
            }

            $type6=$this->checkTime($type6);
        }



        if(in_array(3,$type) ){
            if($type3['fee_mode']==1){
                if($type3['fix_money']<=0){
                    $this->error("请设置正确的帮买费用-固定配送费");
                }
            }
            if($type3['fee_mode']==2){
                if($type3['nearby_money']<0){
                    $this->error("请设置正确的帮买费用-就近购买时每单固定配送费");
                }

                if($type3['distance_basic']<=0){
                    $this->error("请设置正确的帮买费用-距离附加费-起始距离");
                }

                if($type3['distance_basic_money']<0){
                    $this->error("请设置正确的帮买费用-距离附加费-起始价格");
                }

                if($type3['distance_more_money']<0){
                    $this->error("请设置正确的帮买费用-距离附加费-超出价格");
                }
            }
            $type3=$this->checkTime($type3);
        }

        if(in_array(4,$type) ){
            if($type4['fee_mode']==1){
                if($type4['fix_money']<=0){
                    $this->error("请设置正确的排队费用-固定配送费");
                }
            }
            if($type4['fee_mode']==2){
                if($type4['time_basic']<=0){
                    $this->error("请设置正确的排队费用-时间附加费-起始时间");
                }

                if($type4['time_basic_money']<0){
                    $this->error("请设置正确的排队费用-时间附加费-起始价格");
                }

                if($type4['time_more']<=0){
                    $this->error("请设置正确的排队费用-时间附加费-超出时间");
                }

                if($type4['time_more_money']<0){
                    $this->error("请设置正确的排队费用-时间附加费-超出价格");
                }
            }

            $type4=$this->checkTime($type4);
        }

        if(in_array(5,$type) ){
            if($type5['fix_money']<0){
                $this->error("请设置正确的帮办费用-基础费用");
            }
            $type5=$this->checkTime($type5);
        }

        $distance_basic=$data['distance_basic'] ?? 0;
        $distance_basic_time=$data['distance_basic_time'] ?? 0;
        $distance_more_time=$data['distance_more_time'] ?? 0;

        if($distance_basic<=0){
            $this->error("请设置正确的配送时间-起始距离");
        }

        if($distance_basic_time<=0){
            $this->error("请设置正确的配送时间-起始配送时长");
        }

        if($distance_more_time<=0){
            $this->error("请设置正确的配送时间-超出配送时长");
        }

        $rider_distance=$data['rider_distance'] ?? 0;
        $rider_time=$data['rider_time'] ?? 0;

        if($rider_distance<=0){
            $this->error("请设置正确的附近距离");
        }

        if($rider_time<=0){
            $this->error("请设置正确的预计接单时间");
        }

        $config['type']=$type;
        $config['type1']=$type1;
        $config['type2']=$type1;
        $config['type3']=$type3;
        $config['type4']=$type4;
        $config['type5']=$type5;
        $config['type6']=$type6;
        $config['distance_basic']=$distance_basic;
        $config['distance_basic_time']=$distance_basic_time;
        $config['distance_more_time']=$distance_more_time;
        $config['rider_distance']=$rider_distance;
        $config['rider_time']=$rider_time;

        $data['config']=json_encode($config);

        $data['status']=1;
        return $data;
    }

    public function checkTime($type){
        $h=(int)$type['h'];
        $i=(int)$type['i'];
        $timeNum=$h * 60 + $i;
        if($timeNum<30){
            $this->error('服务时段不能少于30分钟');
        }
        $time=$type['time'] ?? [];
        if(!$time){
            $this->error('每种业务至少选择一个服务时段');
        }
        $time_money=$type['time_money'] ?? [];

        $times=[];

        $end_d=60*24;
        for($n=0,$m=0;$n<$end_d;$m++){
            $start=$n;
            $end=$start+$timeNum;
            if($end>$end_d){
                $end=$end_d;
            }
            $n=$end;

            $start_h= floor($start / 60);
            $start_i= $start % 60;
            $start_h=str_pad($start_h,2,0,STR_PAD_LEFT);
            $start_i=str_pad($start_i,2,0,STR_PAD_LEFT);
            $start_txt=$start_h.':'.$start_i;

            $end_h= floor($end / 60);
            $end_i= $end % 60;
            $end_h=str_pad($end_h,2,0,STR_PAD_LEFT);
            $end_i=str_pad($end_i,2,0,STR_PAD_LEFT);
            $end_txt=$end_h.':'.$end_i;

            $isopen=0;
            if(in_array($m,$time)){
                $isopen=1;
            }
            $money=$time_money[$m] ?? 0;

            $times[]=[
                'isopen'=>$isopen,
                'start'=>$start,
                'end'=>$end,
                'money'=>$money,
                'start_txt'=>$start_txt,
                'end_txt'=>$end_txt,
            ];

        }
        $type['times']=$times;
        unset($type['time']);
        unset($type['time_money']);

        return $type;
    }
}