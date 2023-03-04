<?php

namespace app\admin\model\merchant;
use think\Model;


/**
 * 找店类型自定义的评价选项
 */
class MerchantTypeEvaluate extends Model
{
    protected $pk = 'id';
    protected $name = 'merchant_type_evaluate';

    public static $redis_key = 'merchant_type_evaluate';


}