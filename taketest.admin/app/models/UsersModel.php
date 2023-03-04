<?php

namespace app\models;

use think\Model;

class UsersModel extends Model
{
    protected $pk = 'id';
    protected $name = 'users';

    public static function getAll($where,$field){

        $list=self::field($field)->where($where)->order('id desc')->select()->toArray();

        return $list;
    }

    public static function clearInfo($uid,$iftoken=false){
        if($iftoken){
            TokenModel::where(["user_id"=>$uid])->delete();
            delcache("token_".$uid);
        }
        delcache("userinfo_".$uid);

        return 1;
    }

    public static function del($uid){

        self::clearInfo($uid,true);

        return 1;
    }

}