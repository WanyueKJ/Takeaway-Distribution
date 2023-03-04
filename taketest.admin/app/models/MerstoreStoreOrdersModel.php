<?php

namespace app\models;

use think\Model;
use think\Db;

/**
 * 店铺订单
 */
class MerstoreStoreOrdersModel extends Model
{
    protected $pk = 'id';
    protected $name = 'merchant_store_order';


    public static function getInfo($where,$field = '*'){

        $list=self::field($field)->where($where)->order('id desc')->select()->toArray();

        return $list;
    }

    public static function updateInfo($where,$update){

        return self::where($where)->update($update);
    }
}