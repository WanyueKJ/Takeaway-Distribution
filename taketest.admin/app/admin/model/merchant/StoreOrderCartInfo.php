<?php

namespace app\admin\model\merchant;
use think\Model;


class StoreOrderCartInfo extends Model
{
    protected $pk = 'id';
    protected $name = 'merchant_store_order_cart_info';

    public static $redis_key = 'merchant_store_order_cart_info';
}