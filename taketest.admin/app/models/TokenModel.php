<?php

namespace app\models;

use think\Model;

class TokenModel extends Model
{
    protected $pk = 'id';
    protected $name = 'users_token';

    public static function getAll($where,$field){

        $list=self::field($field)->where($where)->order('id desc')->select()->toArray();

        return $list;
    }



}