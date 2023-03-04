<?php

namespace app\admin\model\merchant;

use think\Model;

class StoreProduct extends Model
{
    protected $pk = 'id';
    protected $name = 'merchant_store_product';

    public static $redis_key = 'merchant_store_product';


    public function store()
    {
        return $this->belongsTo(StoreModel::class, 'store_id', 'id')->field('id,name');
    }

    public function typeInfo()
    {
        return $this->belongsTo(TypeModel::class, 'type_id', 'id')->field('id,name');
    }


    public function storeTypeInfo()
    {
        return $this->belongsTo(StoreType::class, 'store_type_id', 'id')->field('id,name');
    }

    public function getIsShowAttr($value)
    {
        $status = [
            '未上架',
            '已上架',
        ];
        if (!array_key_exists($value, $status)) {
            return '--';
        }
        return $status[$value];
    }

    public function getRecommendAttr($value)
    {
        $status = [
            '未推荐',
            '已推荐',
        ];
        if (!array_key_exists($value, $status)) {
            return '--';
        }
        return $status[$value];
    }

    public function getImageAttr($value)
    {
        if (!is_array($value)) $value = json_decode($value, true) ?: [];

        foreach ($value as &$va) {
            $va = get_upload_path($va);
        }
        return $value;
    }

    public function getAddTimeAttr($value)
    {
        return date('Y-m-d H:i;s', $value);
    }
}