<?php

namespace app\models;

use think\Model;

class EvaluategentModel extends Model
{
    protected $pk = 'id';
    protected $name = 'evaluate';

    public static function getAll($where,$field){

        $list=self::field($field)->where($where)->order('id desc')->select()->toArray();

        return $list;
    }



}