<?php

namespace Merchant\Model;

use PhalApi\Model\NotORMModel as NotORM;

/**
 * 店铺分类
 */
class MerchantStoreType extends NotORM
{
    public function selectList(array $where, $field = '*', $order = 'list_order ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_store_type
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
        $info = \PhalApi\DI()->notorm->merchant_store_type
            ->select($field)
            ->where($where)
            ->fetchOne();

        if ($info) {
            if (LANG == 'zh-cn') {
                $info['use_name'] = $info['name'] ?? '';
            }
        }
        return $info;
    }

    /**
     * 删除一条数据
     * @param $where
     * @param $field
     * @return mixed
     */
    public function deleteOne($where)
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_type
            ->where($where)
            ->delete();
        return $rs;
    }

    public function saveOne($data)
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_type
            ->insert($data);
        return $rs;
    }

    /**
     * 更新数据
     * @param $where
     * @param $field
     * @return mixed
     */
    public function updateOne($where, $update = [])
    {
        return \PhalApi\DI()->notorm->merchant_store_type
            ->where($where)
            ->update($update);
    }


    public function getTopName($id)
    {
        $info = $this->getOne(['id = ?' => $id], 'id,name,pid');
        if (!$info) {
            return '';
        }
        if (LANG == 'zh-cn') {
            $info['use_name'] = $info['name'] ?? '';
        } 

        if ($info['pid'] == 0) {
            return $info['use_name'] ?? '';
        }

        if ($info['pid'] > 0) {
            $pInfo = $this->getOne(['id = ?' => $info['pid']], 'id,name,pid');
            if (LANG == 'zh-cn') {
                $pInfo['use_name'] = $pInfo['name'] ?? '';
            } 

            return $pInfo['use_name'] . '-' . $info['use_name'];
        }

    }

}