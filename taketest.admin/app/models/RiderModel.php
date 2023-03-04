<?php

namespace app\models;

use think\Model;

class RiderModel extends Model
{
    protected $pk = 'id';
    protected $name = 'rider';

    public static function getAll($where,$field){

        $list=self::field($field)->where($where)->order('id desc')->select()->toArray();

        return $list;
    }

    public static function getTypes($k=''){
        $status=[
            '1'=>'兼职',
            '2'=>'全职',
        ];

        if($k===''){
            return $status;
        }
        return  $status[$k] ?? '' ;
    }

    public static function getStatus($k=''){
        $status=[
            '0'=>'拒绝',
            '1'=>'通过',
            '2'=>'待审核',
        ];

        if($k===''){
            return $status;
        }
        return  $status[$k] ?? '' ;
    }

    public static function clearInfo($uid,$iftoken=false){
        if($iftoken){
            RidertokenModel::where(["user_id"=>$uid])->delete();
            delcache("rider_token_".$uid);
        }
        delcache("rider_userinfo_".$uid);

        return 1;
    }

    public static function del($uid){

        self::clearInfo($uid,true);

        return 1;
    }

    public static function getUidsByCity($cityid){
        return self::where([['cityid','=',$cityid]])->column('id');
    }

    public static function getCityLevel($uid){
        $key='city_level_'.$uid;

        if(isset($GLOBALS[$key])){
            return $GLOBALS[$key];
        }

        $info=self::where(['id'=>$uid])->field('cityid,levelid')->find();

        $GLOBALS[$key]=$info;

        return $info;
    }
}