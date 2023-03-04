<?php

namespace app\admin\model\merchant;

use think\Model;

/**
 * 商家管理-店铺分类
 */
class TypeModel extends Model
{
    protected $pk = 'id';
    protected $name = 'merchant_type';

    public static $redis_key = 'merchant_type';

    /* 一级分类 */
    public static function getLevelOne()
    {
        return self::order("list_order asc")->select()->toArray();
    }

    public static function resetcache()
    {
        $key = self::$redis_key;
        $list = self::order("pid asc,list_order asc")->select();
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


    /**
     * 获取当前ID下的所有子分类
     * @param $id
     * @param $level
     * @param $target
     * @return array|mixed
     */
    public function getTree($id, $level = 3, $target = [])
    {
        $list = $this
            ->field('*')
            ->order('list_order ASC')
            ->where('pid', $id)
            ->where('level', '<=', $level)
            ->select()
            ->toArray();

        foreach ($list as $key => &$value) {
            $target[] = $value;
            $target = $this->getTree($value['id'], $level, $target);
        }
        return $target;
    }

    /**
     * 获取当前ID的祖宗信息
     * @param $id
     * @param $level
     * @param $target
     * @return array|mixed
     */
    public static function getTopInfo($id)
    {
        $info = self::field('*')
            ->where('id', '=', $id)
            ->find();
        if (!$info) {
            return [];
        }
        if ($info['pid'] == 0) {
            return $info;
        }
        return self::getTopTree($info['pid']);
    }

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

        }
        krsort($target);
        return $target;

    }


    /**
     * 获取当前ID的祖宗信息
     * @param $id
     * @param $level
     * @return array|mixed
     */
    public static function getTopTree($pid, $level = 3)
    {
        $info = self::field('*')
            ->where('id', '=', $pid)
            ->where('level', '<=', $level)
            ->find();

        if ($info['pid'] == 0) {
            return $info;
        } else {
            return self::getTopTree($info['pid']);
        }
    }


    /**
     * 获取数量
     * @param $where
     * @return void
     */
    public function getCount($where)
    {
        $info = self::field('*')
            ->where($where)
            ->count();
        return $info;
    }
}