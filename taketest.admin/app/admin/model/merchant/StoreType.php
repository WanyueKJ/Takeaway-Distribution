<?php

namespace app\admin\model\merchant;

use think\Model;

class StoreType extends Model
{
    protected $pk = 'id';
    protected $name = 'merchant_store_type';

    public static $redis_key = 'merchant_store_type';

    public function getTopTreeList($id, $level = 3, $target = [])
    {
        while (true) {
            $info = self::field('*')
                ->where('id', '=', $id)
                ->where('level', '<=', $level)
                ->find();
            if (!$info) break;
            $target[] = $info;
            if ($info['pid'] <= 0) {
                break;
            } else {
                return $this->getTopTreeList($info['pid'], 3, $target);
            }
            break;

        }
        krsort($target);
        return $target;

    }
}