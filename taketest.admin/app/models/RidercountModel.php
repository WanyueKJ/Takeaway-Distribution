<?php

namespace app\models;

use think\Model;

class RidercountModel extends Model
{
    protected $pk = 'id';
    protected $name = 'rider_order_count';

    public static function getAll($where,$field){

        $list=self::field($field)->where($where)->order('id desc')->select()->toArray();

        return $list;
    }

    public static function getOrderNums($where,$field){
        $nums=self::where($where)->sum($field);
        if(!$nums){
            $nums=0;
        }

        return $nums;
    }

}