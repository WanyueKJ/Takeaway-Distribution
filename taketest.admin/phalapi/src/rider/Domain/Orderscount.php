<?php
namespace Rider\Domain;

use Rider\Model\Orderscount as Model_Orderscount;

class Orderscount {

    public function getLocation($where){
        $location=[
            'lng'=>'',
            'lat'=>'',
        ];
        $info= self::getInfo($where);
        if(!$info){
            return $location;
        }

        unset($info['uid']);
        unset($info['addtime']);

        return $info;
    }

    public function getInfo($where,$field='*'){
        $model = new Model_Orderscount();

        return $model->getInfo($where,$field);

    }

    public function getMonthCount($uid,$year){

        $nowtime=time();
        $month_start=strtotime( date('Y-m',$nowtime) );

        if($year==0){
            $year=date('Y',$nowtime);
        }
        $year=$year.'-01-01';
        $year_start=strtotime($year);
        $year_end=strtotime("{$year} + 1 year");


        $where=[
            'uid'=>$uid,
            'time >= ?'=>$year_start,
            'time < ?'=>$year_end,
        ];
        $model = new Model_Orderscount();
        $list=$model->getAll($where);
        foreach ($list as $k=>$v){
            $title=date('n月',$v['time']);
            $des='';
            if($v['time']==$month_start){
                $title='本月';
                $des=date('m-01',$nowtime).'至'.date('m-d',$nowtime);
            }

            $v['title']=$title;
            $v['des']=$des;
            $list[$k]=$v;
        }
        return $list;
    }

    public function upCount($uid,$orders,$distance,$transfers){

        $nowtime=time();

        $month_start=strtotime( date('Y-m',$nowtime) );

        $where=[
            'uid'=>$uid,
            'time'=>$month_start,
        ];

        $model = new Model_Orderscount();
        $info=$model->getInfo($where,'id');

        if(!$info){
            $add=[
                'uid'=>$uid,
                'time'=>$month_start,
                'orders'=>$orders,
                'transfers'=>$transfers,
                'distance'=>$distance,
            ];
            $model->add($add);
        }else{
            $where2=[
                'id'=>$info['id']
            ];
            $model->upNums($where2,$orders,$transfers,$distance);
        }

        return 1;
    }


}
