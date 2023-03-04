<?php

namespace app\models;

use think\Model;

class RiderlevelModel extends Model
{
    protected $pk = 'id';
    protected $name = 'rider_level';
    public static $redis_key = 'rider_level';

    public static function resetcache(){
        $key=self::$redis_key;

        $list=self::order("levelid asc")->select();
        if($list){
            setcaches($key,$list);
        }else{
            delcache($key);
        }

        return $list;
    }
    /* 列表 */
    public static function getList(){
        $key=self::$redis_key;

        if(isset($GLOBALS[$key])){
            return $GLOBALS[$key];
        }
        $list=getcaches($key);
        if(!$list){
            $list=self::resetcache();
        }

        $GLOBALS[$key]=$list;
        return $list;

    }


    public static function getListByCity($cityid){

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

    /* 某信息 */
    public static function getInfo($id){

        $info=[];

        if($id<1){
            return $info;
        }
        $list=self::getList();

        foreach($list as $k=>$v){
            if($v['id']==$id){
                unset($v['list_order']);
                $info=$v;
                break;
            }
        }

        return $info;
    }


    public static function getInfoByCity($levelid,$cityid){

        $info=[];

        $list=self::getListByCity($cityid);
        if(!$list){
            return $info;
        }

        $info=$list[0]['config'];

        return json_decode($info,true);

        foreach ($list as $k=>$v){
            if($v['levelid']!=$levelid){
                continue;
            }
            $info=$v;
            break;
        }

        return json_decode($info,true);
    }

    public static function getIncome($rid,$type,$money_basic,$money_distance,$distance,$money_weight,$money_length,$money_time,$fee,$prepaid){


        $rinfo=RiderModel::getCityLevel($rid);

        $cityid=$rinfo['cityid'] ?? 0;
        $levelid=$rinfo['levelid'] ?? 0;

        $income= $money_weight + $money_time + $fee + $prepaid;

        $config=self::getInfoByCity($levelid,$cityid);
        if(!$config){
            return $income;
        }

        if(in_array($type,[1,2,3])){
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