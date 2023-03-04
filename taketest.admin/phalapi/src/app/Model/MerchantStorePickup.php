<?php

namespace App\Model;
use PhalApi\Model\NotORMModel as NotORM;

/**
 * 店铺自提
 */
class MerchantStorePickup extends NotORM
{
    public function selectList(array $where, $field = '*', $order = 'id ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_store_pickup
            ->select($field)
            ->order($order)
            ->where($where);

        if ($limit > 0) {
            $list->limit($start, $limit);
        }

        $list = $list->fetchAll();

        return $list;
    }

    public function getOne(array $where, $field = '*')
    {
        $info = \PhalApi\DI()->notorm->merchant_store_pickup
            ->select($field)
            ->where($where)
            ->fetchOne();
        return $info;
    }

}