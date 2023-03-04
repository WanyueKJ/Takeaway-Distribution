<?php

namespace App\Model;

use PhalApi\Model\NotORMModel as NotORM;

/**
 * 店铺类型
 */
class MerchantType extends NotORM
{
    public function selectList(array $where, $field = '*', $order = 'list_order ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_type
            ->select($field)
            ->order($order)
            ->where($where);
        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        $list = $list->fetchAll();
        foreach ($list as $key => &$value) {
            $value['use_name'] = '';
            if (LANG == 'zh-cn') {
                $value['use_name'] = $value['name'] ?? '';
            } else if (LANG == 'th') {
                $value['use_name'] = $value['th_name'] ?? '';
            }
        }
        return $list;
    }


    /**
     * 获取获取当前分类的顶级分类条数据
     * @param $where
     * @param $field
     * @return mixed
     */
    public function getTopOne($where, $field = "*")
    {
        $info = \PhalApi\DI()->notorm->merchant_type
            ->select($field)
            ->where($where)
            ->fetchOne();

        return $info;
    }


    /**
     * 获取一条数据
     * @param $where
     * @param $field
     * @return mixed
     */
    public function getOne($where, $field = "*")
    {
        $info = \PhalApi\DI()->notorm->merchant_type
            ->select($field)
            ->where($where)
            ->fetchOne();
        if ($info) {
            $info['use_name'] = '';
            if (LANG == 'zh-cn') {
                $info['use_name'] = $info['name'] ?? '';
            } else if (LANG == 'th') {
                $info['use_name'] = $info['th_name'] ?? '';
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
        $list = \PhalApi\DI()->notorm->merchant_type
            ->select('*')
            ->order('list_order ASC')
            ->where(['pid = ?' => $id])
            ->where(['level <= ?' => $level])
            ->fetchAll();

        foreach ($list as $key => &$value) {
            $value['use_name'] = '';
            if (LANG == 'zh-cn') {
                $value['use_name'] = $value['name'] ?? '';
            } else if (LANG == 'th') {
                $value['use_name'] = $value['th_name'] ?? '';
            }

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
    public function getTopInfo($id)
    {
        $info = \PhalApi\DI()->notorm->merchant_type
            ->select('*')
            ->where(['id = ?' => $id])
            ->fetchOne();
        if (!$info) {
            return [];
        }
        if ($info['pid'] == 0) {
            return $info;
        }
        return $this->getTopTree($info['pid']);
    }


    /**
     * 获取当前ID的祖宗信息
     * @param $id
     * @param $level
     * @return array|mixed
     */
    public function getTopTree($pid, $level = 3)
    {
        $info = \PhalApi\DI()->notorm->merchant_type
            ->select('*')
            ->where(['id = ?' => $pid])
            ->where(['level <= ?' => $level])
            ->fetchOne();

        if ($info['pid'] == 0) {
            return $info;
        } else {
            return $this->getTopTree($info['pid']);
        }
    }
}