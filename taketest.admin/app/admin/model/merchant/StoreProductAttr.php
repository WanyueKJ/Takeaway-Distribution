<?php

namespace app\admin\model\merchant;

use think\Model;

class StoreProductAttr extends Model
{
    protected $pk = 'id';
    protected $name = 'merchant_store_product_attr';

    public static $redis_key = 'merchant_store_product_attr';

}