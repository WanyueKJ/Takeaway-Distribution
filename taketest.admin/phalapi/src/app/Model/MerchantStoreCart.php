<?php

namespace App\Model;

/**
 * 购物车
 */
class MerchantStoreCart
{

    public function selectList(array $where, $field = '*', $order = 'id ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_store_cart
            ->select($field)
            ->order($order)
            ->where($where);
        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        return $list->fetchAll();
    }

    public function inIdselectList($idArr, $where, $field = '*', $order = 'id ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }
        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_store_cart
            ->select($field)
            ->order($order)
            ->where('id', $idArr)
            ->where($where);
        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        return $list->fetchAll();
    }


    public function getOne(array $where, $field = '*')
    {
        $info = \PhalApi\DI()->notorm->merchant_store_cart
            ->select($field)
            ->where($where)
            ->fetchOne();
        return $info;
    }

    public function deleteOne(array $where, $field = '*')
    {
        $info = \PhalApi\DI()->notorm->merchant_store_cart
            ->select($field)
            ->where($where)
            ->delete();
        return $info;
    }

    public function getCount(array $where)
    {
        $info = \PhalApi\DI()->notorm->merchant_store_cart
            ->where($where)
            ->count();
        return $info;
    }


    /**
     *
     * @return array
     */
    public function saveOne($data)
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_cart
            ->insert($data);
        $id = \PhalApi\DI()->notorm->merchant_store_cart->insert_id();
        return $id;
    }

    /**
     * 更新数据
     * @param $where
     * @param $field
     * @return mixed
     */
    public function updateOne($where, $update = [])
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_cart
            ->where($where)
            ->update($update);
        return $rs;
    }
}