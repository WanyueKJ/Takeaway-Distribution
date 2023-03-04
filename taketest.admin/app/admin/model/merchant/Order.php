<?php

namespace app\admin\model\merchant;

use think\Model;
use app\models\UsersModel;
use app\models\OrdersModel;

/**
 * 店铺订单
 */
class Order extends Model
{
    protected $pk = 'id';
    protected $name = 'merchant_store_order';

    public static $redis_key = 'merchant_store_order';


    public function userinfo()
    {
        return $this->belongsTo(UsersModel::class, 'uid', 'id')->field('id,user_nickname');
    }

    public function evaluate()
    {
        return $this->hasOne(Evaluate::class, 'store_oid', 'id')->field('star,id,store_oid');
    }

    public function orderEvaluate()
    {
        return $this->hasOne(StoreOrderEvaluate::class, 'oid', 'id')->field('id,taste_star,overall_star,packaging_star,distribution_star');
    }

    public function orders()
    {
        return $this->hasOne(OrdersModel::class, 'store_oid', 'id');
    }

    public static function getTypes($k = '')
    {
        $status = [
            '1' => '外卖配送',
        
        ];

        if ($k === '') {
            return $status;
        }
        return $status[$k] ?? '--';
    }

    public static function getPayTypes($k = '')
    {
        $status = [
            '1' => '支付宝',
            '2' => '微信',
            
        ];

        if ($k === '') {
            return $status;
        }
        return $status[$k] ?? '';
    }

    public static function getStatus($k = '')
    {
        $status = [
            '0' => '待付款',
            '1' => '已付款(待接单)',
            '2' => '待配送',
            '3' => '配送中',
            '4' => '已完成',
            '5' => '退款',
            '6' => '已备货',
            '7' => '已取消'
        ];

        if ($k === '') {
            return $status;
        }
        return $status[$k] ?? '';
    }

    public function getPayTypeAttr($value)
    {
        $status = [
            '1' => '支付宝',
            '2' => '微信',
        ];

        if (!array_key_exists($value, $status)) return '--';
        return $status[$value];
    }

    public function getStatusAttr($value)
    {
        $status = [
            '0' => '待付款',
            '1' => '已付款(待接单)',
            '2' => '待配送',
            '3' => '配送中',
            '4' => '已完成',
            '5' => '退款',
            '6' => '已备货',
            '7' => '已取消'
        ];

        if (!array_key_exists($value, $status)) return '--';

        return $status[$value] ?? '';
    }

    public function getAddTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    public function getEndTimeAttr($value)
    {
        if($value <= 0) return '--';
        return date('Y-m-d H:i:s', $value);
    }


}