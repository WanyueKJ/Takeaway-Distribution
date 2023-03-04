<?php

namespace app\models;

use think\Model;

class OrdersrefundModel extends Model
{
    protected $pk = 'id';
    protected $name = 'orders_refundrecord';

    public static function getAll($where,$field){

        $list=self::field($field)->where($where)->order('id desc')->select()->toArray();

        return $list;
    }


}