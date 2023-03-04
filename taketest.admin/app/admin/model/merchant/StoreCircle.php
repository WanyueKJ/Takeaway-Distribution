<?php

namespace app\admin\model\merchant;
use think\Model;

class StoreCircle extends Model
{
    protected $pk = 'id';
    protected $name = 'merchant_store_circle';

    public static $redis_key = 'merchant_store_circle';

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


    public static function resetcache()
    {
        $key = self::$redis_key;

        $list = self::order("id asc")->select();
        if ($list) {
            setcaches($key, $list);
        } else {
            delcache($key);
        }
        return $list;
    }
}