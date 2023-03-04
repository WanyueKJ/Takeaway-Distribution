<?php

namespace app\models;

use think\Model;

class RidertokenModel extends Model
{
    protected $pk = 'id';
    protected $name = 'rider_token';

    public static function getAll($where,$field){

        $list=self::field($field)->where($where)->order('id desc')->select()->toArray();

        return $list;
    }



}