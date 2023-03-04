<?php

namespace app\admin\model\merchant;

use app\models\UsersModel;
use think\Model;
use think\exception\PDOException;

/**
 * 商家管理-店铺
 */
class StoreModel extends Model
{
    protected $pk = 'id';
    protected $name = 'merchant_store';

    public static $redis_key = 'merchant_store';


    public static function getLevelOne()
    {
        return self::order("list_order asc")->select()->toArray();
    }

    public function checkDelete($id)
    {
        try {
            self::where("id", $id)->delete();
            return true;
        } catch (PDOException $PDOException) {
            return $PDOException->getMessage();
        }
    }

    public function updatePutaway($storeId)
    {
        /** @var StoreProduct $StoreProduct */
        $StoreProduct = app()->make(StoreProduct::class);
        $productCount = $StoreProduct->where([
            ['is_show', '=', 1],
            ['is_del', '=', 0],
            ['store_id', '=', $storeId]
        ])->count();
        $this->where('id', $storeId)->update(['putaway' => $productCount]);
    }

    public function updateRemark($storeId)
    {
        /** @var StoreOrderEvaluate $StoreOrderEvaluate */
        $StoreOrderEvaluate = app()->make(StoreOrderEvaluate::class);
        $remarkCount = $StoreOrderEvaluate->where([
            ['is_show', '=', 1],
            ['store_id', '=', $storeId]
        ])->count();
        $this->where('id', $storeId)->update(['remark' => $remarkCount]);
    }

    public static function resetcache()
    {
        $key = self::$redis_key;

        $list = self::order("list_order asc")->select();
        if ($list) {
            setcaches($key, $list);
        } else {
            delcache($key);
        }
        return $list;
    }

    /* 列表 */
    public static function getList()
    {
        $key = self::$redis_key;
        if (isset($GLOBALS[$key])) {
            return $GLOBALS[$key];
        }
        $list = getcaches($key);
        if (!$list) {
            $list = self::resetcache();
        }
        $GLOBALS[$key] = $list;
        return $list;

    }

    /* 某信息 */
    public static function getInfo($id)
    {

        $info = [];

        if ($id < 1) {
            return $info;
        }
        $list = self::getList();

        foreach ($list as $k => $v) {
            if ($v['id'] == $id) {
                unset($v['list_order']);
                $info = $v;
                break;
            }
        }

        return $info;
    }

    public function storeType()
    {
        return $this->belongsTo(TypeModel::class, 'type_id', 'id')->field('name,th_name,id');
    }

    public function storeCircle()
    {
        return $this->belongsTo(StoreCircle::class, 'circle_id', 'id')->field('name,th_name,id');
    }

    public function account()
    {
        return $this->hasOne(UsersModel::class, 'store_id', 'id')->field('mobile,user_nickname,store_id,id');
    }

    public function industry()
    {
        return $this->hasOne(StoreIndustry::class, 'store_id', 'id');
    }

    public function type()
    {
        return $this->hasOne(TypeModel::class, 'type_id', 'id')->field('name,th_name,id');
    }


    public function getTopTypeIdAttr($value)
    {
        $TypeModel = app()->make(TypeModel::class);

        $info = $TypeModel->where('id', $value)->find();
        if (!$info) {
            return "-未知类型-";
        }
        return $info['name'] . "({$info['th_name']})";
    }


    public function getBannerAttr($value)
    {
        if (!is_array($value)) $value = json_decode($value, true) ?: [];
        foreach ($value as &$va) {
            $va = get_upload_path($va);
        }
        return $value;
    }

    public function getThumbAttr($value)
    {
        return get_upload_path($value);
    }

    public function getOperatingStateAttr($value)
    {
        $status = [
            '打样',
            '营业',
        ];
        if (!array_key_exists($value, $status)) return '--';
        return $status[$value];
    }
}