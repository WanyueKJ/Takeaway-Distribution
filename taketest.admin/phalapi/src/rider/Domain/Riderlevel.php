<?php
namespace Rider\Domain;

use Rider\Model\Riderlevel as Model_Riderlevel;
use Rider\Domain\User as Domain_User;

class Riderlevel {

    public function getList(){

        $key='rider_level';
        if(isset($GLOBALS[$key])){
            return $GLOBALS[$key];
        }
        $list=\App\getcaches($key);
        if(!$list){
            $model = new Model_Riderlevel();
            $list=$model->getList();
            \App\setcaches($key,$list);
        }

        foreach ($list as $k=>$v){
            $v['config']=json_decode($v['config'],true);
            $list[$k]=$v;
        }

        $GLOBALS[$key]=$list;
        return $list;
    }

    public function getListByCity($cityid){

        $info=[];

        if($cityid<1){
            return $info;
        }
        $list=self::getList();

        foreach($list as $k=>$v){
            if($v['cityid']!=$cityid){
                continue;
            }

            $info[]=$v;
        }

        return $info;
    }

    public function getInfo($id){

        $info=[];
        $list=self::getList();

        foreach($list as $k=>$v){
            if($id==$v['id']){
                $info=$v;
                break;
            }
        }

        return $info;
    }

    public function getInfoByCity($levelid,$cityid){

        $info=[];

        $list=self::getListByCity($cityid);
        if(!$list){
            return $info;
        }

        $info=$list[0]['config'];

        return $info;

        foreach ($list as $k=>$v){
            if($v['levelid']!=$levelid){
                continue;
            }
            $info=$v;
            break;
        }

        return $info;
    }

    public function getTransLimit($rid){
        $Domain_User=new Domain_User();

        $rinfo=$Domain_User->getCityLevel($rid);

        $cityid=$rinfo['cityid'] ?? 0;
        $levelid=$rinfo['levelid'] ?? 0;

        $trans_limit=0;

        $levelinfo=self::getInfoByCity($levelid,$cityid);
        if(!$levelinfo){
            return $trans_limit;
        }

        $trans_limit=$levelinfo['trans_nums'];

        return $trans_limit;
    }

    public function getIncome($rid,$type,$money_basic,$money_distance,$distance,$money_weight,$money_length,$money_time,$fee,$prepaid){

        $Domain_User=new Domain_User();

        $rinfo=$Domain_User->getCityLevel($rid);

        $cityid=$rinfo['cityid'] ?? 0;
        $levelid=$rinfo['levelid'] ?? 0;

        $income= $money_weight + $money_time + $fee + $prepaid;

        $config=self::getInfoByCity($levelid,$cityid);
        if(!$config){
            return $income;
        }
        if(in_array($type,[1,2,3,6])){
            if($config['run_mode']==1){
                $fix=$config['run_fix'];
            }
            if($config['run_mode']==2){
                $fix= floor(($money_basic + $money_distance) * $config['run_rate'] ) * 0.01;
            }
            if($config['run_mode']==3){

                $distance=round($distance /1000,1);

                if($config['distance_type']==2){
                    $distance=ceil($distance);
                }

                if($config['distance_type']==3){
                    $distance=floor($distance);
                }

                $fix= $config['distance_basic_money'];

                if($distance > $config['distance_basic']){
                    $distance_more=$distance - $config['distance_basic'];
                    $fix_more= ceil( ($distance_more) / 1) * $config['distance_more_money'];
                    $fix+=$fix_more;
                }

                if($fix > $config['distance_max_money']){
                    $fix= $config['distance_max_money'];
                }
            }

            if($fix> $money_basic + $money_distance){
                $fix= $money_basic + $money_distance;
            }
        }
        if(in_array($type,[4,5])){
            if($config['work_mode']==1){
                $fix=$config['work_fix'];

            }
            if($config['work_mode']==2){
                $fix= floor(($money_basic + $money_length) * $config['work_rate'] ) * 0.01;
            }

            if($fix> $money_basic + $money_length ){
                $fix= $money_basic + $money_length ;
            }
        }

        $income+=$fix;

        return $income;
    }
}
