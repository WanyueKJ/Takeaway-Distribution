<?php

namespace app\admin\model\merchant;

use think\Model;

class Enter extends Model
{
    protected $pk = 'id';
    protected $name = 'merchant_enter';

    public static $redis_key = 'merchant_enter';

    public function getTypeAttr($value)
    {
        $status = [
            1 => '商家入驻',
            2 => '骑手入驻',
            3 => '商务合作'
        ];

        if (!array_key_exists($value, $status)) {
            return '-未知类型-';
        }

        return $status[$value];
    }

    public function getStatusAttr($value)
    {
//        $status = [
//            0 => '审核中',
//            -1 => '未通过',
//            1 => '已通过'
//        ];

        $status = [
            0 => '未处理',
            -1 => '已处理',
            1 => '已处理'
        ];

        if (!array_key_exists($value, $status)) {
            return '-未知类型-';
        }

        return $status[$value];
    }
}