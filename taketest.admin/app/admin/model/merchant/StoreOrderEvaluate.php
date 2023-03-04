<?php

namespace app\admin\model\merchant;

use think\Model;
use app\models\UsersModel;

/**
 * 商品订单评价
 */
class StoreOrderEvaluate extends Model
{
    protected $pk = 'id';
    protected $name = 'merchant_store_order_evaluate';

    public static $redis_key = 'merchant_store_order_evaluate';

    /**
     * 平均分
     * @param $where
     * @param $field
     * @return array|\PDOStatement|string|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAverage($where, $field = "count(id) as count,(sum(taste_star)/count(id)) as taste_star,(sum(packaging_star)/count(id)) as packaging_star,(sum(distribution_star)/count(id)) as distribution_star,(sum(overall_star)/count(id)) as overall_star")
    {
        return $this->field($field)
            ->where($where)
            ->find();
    }

    public function evaluate()
    {
   
        return $this->hasOne(Evaluate::class, 'store_oid', 'id')->field('star,id,store_oid');
    }

    public function storeOrder()
    {
        return $this->belongsTo(Order::class, 'oid', 'id')->field('total_num,id,order_id,add_time');
    }

    public function store()
    {
        return $this->belongsTo(StoreModel::class, 'store_id', 'id')->field('id,name,top_type_id');
    }

    public function userinfo()
    {
        return $this->belongsTo(UsersModel::class, 'uid', 'id')->field('id,user_nickname');
    }

    public function getMerchantReplyTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    public function getAddtimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    public function getPicsAttr($value)
    {
        if (!is_array($value)) $value = json_decode($value, true) ?: [];
        foreach ($value as &$va) {
            $va = get_upload_path($va);
        }
        return $value;
    }

    public function getVideoAttr($value)
    {
        if (!is_array($value)) $value = json_decode($value, true) ?: [];
        if (!isset($value['url'])) return '';
        return get_upload_path($value['url']);
    }


    public function getIsShowAttr($value)
    {
        $status = [
            '不展示',
            '展示'
        ];
        if (!array_key_exists($value, $status)) {
            return '--';
        }
        return $status[$value];
    }


}