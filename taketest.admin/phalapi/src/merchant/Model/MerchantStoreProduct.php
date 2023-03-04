<?php

namespace Merchant\Model;

use PhalApi\Model\NotORMModel as NotORM;

/**
 * 店铺管理-商品管理
 */
class MerchantStoreProduct extends NotORM
{
    public function getCount($where)
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_product
            ->where($where)
            ->count();
        return $rs;
    }

    public function saveOne($data)
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_product
            ->insert($data);
        return $rs;
    }

    public function getOne(array $where, $field = '*')
    {
        $info = \PhalApi\DI()->notorm->merchant_store_product
            ->select($field)
            ->where($where)
            ->where(['is_del = ?' => 0])
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
     * 更新数据
     * @param $where
     * @param $field
     * @return mixed
     */
    public function updateOne($where, $update = [])
    {
        $info = \PhalApi\DI()->notorm->merchant_store_product
            ->where($where)
            ->update($update);

        return $info;

    }


    public function selectList(array $where, $field = '*', $order = 'id ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_store_product
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
                $value['use_price'] = $value['price'] ?? '';
            } else if (LANG == 'th') {
                $value['use_name'] = $value['th_name'] ?? '';
                $value['use_price'] = $value['price'] ?? '';
            }
        }

        return $list;
    }


    public function inIdselectList($idArr, array $where = [], $field = '*', $order = 'list_order ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_store_product
            ->select($field)
            ->order($order)
            ->where($where)
            ->where('id', $idArr);
        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        $list = $list->fetchAll();
        foreach ($list as $key => &$value) {
            $value['use_name'] = '';

            if (LANG == 'zh-cn') {
                $value['use_name'] = $value['name'] ?? '';
                $value['use_price'] = $value['price'] ?? '';
            } else if (LANG == 'th') {
                $value['use_name'] = $value['th_name'] ?? '';
                $value['use_price'] = $value['price'] ?? '';
            }
        }
        return $list;
    }
}