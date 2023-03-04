<?php

namespace app\admin\model\merchant;
use think\Model;

class StoreProductReplyLike extends Model
{
    protected $pk = 'id';
    protected $name = 'merchant_store_product_reply_like';

    public static $redis_key = 'merchant_store_product_reply_like';
}