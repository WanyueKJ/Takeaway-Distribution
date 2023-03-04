<?php

namespace app\admin\model\merchant;

use app\models\UsersModel;
use think\Model;


/**
 * 商品评价
 */
class StoreProductReply extends Model
{
    protected $pk = 'id';
    protected $name = 'merchant_store_product_reply';

    public static $redis_key = 'merchant_store_product_reply';

    public function storeOrder()
    {
        return $this->belongsTo(Order::class, 'oid', 'id')->field('id,order_id');
    }

    public function product()
    {
        return $this->belongsTo(StoreProduct::class, 'product_id', 'id')->field('name,id');
    }

    public function users()
    {
        return $this->belongsTo(UsersModel::class, 'uid', 'id')->field('user_nickname,id');
    }

    public function store()
    {
        return $this->belongsTo(StoreModel::class, 'store_id', 'id')->field('name,type_id,th_name,id');
    }

    public function getPicsAttr($value)
    {
        if (!is_array($value)) $value = json_decode($value, true) ?: [];
        foreach ($value as &$va) {
            $va = get_upload_path($va);
        }
        return $value;
    }


    public function getAddtimeAttr($value){
        return date('Y-m-d H:i:s',$value);
    }

}