<?php

namespace app\admin\model\merchant;

use think\Model;

/**
 * 店铺工商局注册信息
 */
class StoreIndustry extends Model
{
    protected $pk = 'id';
    protected $name = 'merchant_store_industry';

    public static $redis_key = 'merchant_store_industry';


    public function getIdCardImageAttr($value)
    {
        $array = json_decode($value, true) ?? [];

        foreach ($array as $key => &$value){
            $value = get_upload_path($value);
        }
        return  $array;
    }

    public function getBusinessImageAttr($value)
    {
        $array = json_decode($value, true) ?? [];

        foreach ($array as $key => &$value){
            $value = get_upload_path($value);
        }
        return  $array;
    }

    public function getLicenseImageAttr($value)
    {
        $array = json_decode($value, true) ?? [];

        foreach ($array as $key => &$value){
            $value = get_upload_path($value);
        }
        return  $array;
    }

}