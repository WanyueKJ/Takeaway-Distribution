<?php

namespace app\models;

use think\Model;

class SubstationModel extends Model
{
    protected $pk = 'id';
    protected $name = 'substation';

    public static function getAll($where,$field){

        $list=self::field($field)->where($where)->order('id desc')->select()->toArray();

        return $list;
    }

    public static function handleCity($list){

        foreach ($list as $k=>$v){

            $where=[
                'cityid'=>"[{$v['id']}]"
            ];
            $isexist=self::field('id')->where($where)->find();
            if(!$isexist){
                continue;
            }

            unset($list[$k]);
        }

        return array_values($list);
    }

}