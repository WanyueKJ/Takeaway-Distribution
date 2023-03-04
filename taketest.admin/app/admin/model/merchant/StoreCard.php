<?php

namespace app\admin\model\merchant;
use think\Model;
use app\models\UsersModel;


class StoreCard extends Model
{
    protected $pk = 'id';
    protected $name = 'merchant_store_card';

    public static $redis_key = 'merchant_store_card';

    public function store()
    {
        return $this->belongsTo(StoreModel::class, 'store_id', 'id')->field('name,th_name,id');
    }

    public function users()
    {
        return $this->belongsTo(UsersModel::class, 'uid', 'id')->field('user_nickname,id');
    }


    public function getAddtimeAttr($value)
    {
        return date('Y-m-d H:i:s',$value);
    }
}