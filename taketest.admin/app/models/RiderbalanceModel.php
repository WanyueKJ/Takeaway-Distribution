<?php

namespace app\models;

use think\Model;

class RiderbalanceModel extends Model
{
    protected $pk = 'id';
    protected $name = 'rider_balance';

    public static function getAll($where,$field){

        $list=self::field($field)->where($where)->order('id desc')->select()->toArray();

        return $list;
    }

    public static function add($uid,$type,$action,$actionid,$orderno,$nums,$total){

        $uinfo=RiderModel::where(['id'=>$uid])->field('balance')->find();

        $balance=$uinfo['balance'];

        $data=[
            'type'=>$type,
            'action'=>$action,
            'uid'=>$uid,
            'actionid'=>$actionid,
            'nums'=>$nums,
            'total'=>$total,
            'balance'=>$balance,
            'orderno'=>$orderno,
            'addtime'=>time(),
        ];

        return self::insert($data);

    }

}