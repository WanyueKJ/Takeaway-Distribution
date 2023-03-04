<?php

namespace app\models;

use think\Model;

class RiderlocalModel extends Model
{
    protected $pk = 'id';
    protected $name = 'rider_location';

    public static function getAll($where,$field){

        $list=self::field($field)->where($where)->order('id desc')->select()->toArray();

        return $list;
    }

    public static function getLocal($uid){
        return self::where(['uid'=>$uid])->find();
    }

    public static function del($uid){
        return self::where(['uid'=>$uid])->delete();
    }

}