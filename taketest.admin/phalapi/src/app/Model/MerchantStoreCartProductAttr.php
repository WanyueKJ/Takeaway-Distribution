<?php

namespace App\Model;

/**
 * 购物车商品规格
 */
class MerchantStoreCartProductAttr
{

    public function deleteInId($IdArr)
    {
        $info = \PhalApi\DI()->notorm->merchant_store_cart_product_attr
            ->where('id', $IdArr)
            ->delete();
        return $info;
    }

    public function deleteOne(array $where, $field = '*')
    {
        $info = \PhalApi\DI()->notorm->merchant_store_cart_product_attr
            ->where($where)
            ->delete();
        return $info;
    }


    public function selectList(array $where, $field = '*', $order = 'id ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_store_cart_product_attr
            ->select($field)
            ->order($order)
            ->where($where);
        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        return $list->fetchAll();
    }

    public function inCartIdSelectList($idArr, array $where, $field = '*', $order = 'id ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_store_cart_product_attr
            ->select($field)
            ->order($order)
            ->where('cart_id', $idArr)
            ->where($where);
        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        return $list->fetchAll();
    }

    /**
     *
     * @return array
     */
    public function saveOne($data)
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_cart_product_attr
            ->insert($data);
        $id = \PhalApi\DI()->notorm->merchant_store_cart_product_attr->insert_id();
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
        $rs = \PhalApi\DI()->notorm->merchant_store_cart_product_attr
            ->where($where)
            ->update($update);
        return $rs;
    }

    public function getOne(array $where, $field = '*')
    {
        $info = \PhalApi\DI()->notorm->merchant_store_cart_product_attr
            ->select($field)
            ->where($where)
            ->where($where)
            ->fetchOne();
        return $info;
    }
}