<?php

namespace Merchant\Model;

use PhalApi\Model\NotORMModel as NotORM;

/**
 * 店铺类型
 */
class MerchantType extends NotORM
{

    public function getTopName($id)
    {
        $topTypelist = $this->getTopList($id);

        $name = '';
        foreach ($topTypelist as $key => $value) {
            $name .= '-';

            if (LANG == 'zh-cn') {
                $name .= $value['name'] ?? '';
            } else if (LANG == 'th') {
                $name .= $value['th_name'] ?? '';
            }
        }
        return ltrim($name, '-');
    }


    public function notInIdselectList($idArr, array $where, $field = '*', $order = 'list_order ASC', $p = 0, $limit = 0)
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
            if (LANG == 'zh-cn') {
                $info['use_name'] = $info['name'] ?? '';
            } else if (LANG == 'th') {
                $info['use_name'] = $info['th_name'] ?? '';
            }
        }

        return $info;

    }

    /**
     * 更新数据
     * @param $where
     * @param $field
     * @return mixed
     */
    public function updateOne($where, $update = [])
    {
        return \PhalApi\DI()->notorm->merchant_type
            ->where($where)
            ->update($update);
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

        foreach ($list as $key => $value) {
            $target[] = $value;
            $target = $this->getTree($value['id'], $level, $target);
        }
        return $target;
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
            ->order('list_order ASC')
            ->where(['id = ?' => $pid])
            ->where(['level <= ?' => $level])
            ->fetchOne();
        if (!$info) {
            return [];
        }

        if ($info['pid'] == 0) {
            return $info;
        } else {
            return $this->getTopTree($info['pid']);
        }
    }

    public function getTopList($id, $level = 3, $target = [])
    {
        $info = \PhalApi\DI()->notorm->merchant_type
            ->select('*')
            ->order('list_order ASC')
            ->where(['id = ?' => $id])
            ->where(['level <= ?' => $level])
            ->fetchOne();
        if (!$info) {
            return [];
        }
        array_push($target, $info);

        if ($info['pid'] == 0) {
            return $target;
        } else {
            return $this->getTopList($info['pid'], 3, $target);
        }
    }
}