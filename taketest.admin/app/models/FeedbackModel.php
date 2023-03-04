<?php

namespace app\models;

use think\Model;

class FeedbackModel extends Model
{
    protected $pk = 'id';
    protected $name = 'feedback';

    public static function getAll($where,$field){

        $list=self::field($field)->where($where)->order('id desc')->select()->toArray();

        return $list;
    }

    public static function getStatus($k=''){
        $status=array(
            '0'=>'未处理',
            '1'=>'已处理',
        );
        if($k===''){
            return $status;
        }

        return isset($status[$k]) ? $status[$k]: '';
    }


}