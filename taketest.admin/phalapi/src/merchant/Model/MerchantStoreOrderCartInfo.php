<?php

namespace Merchant\Model;

class MerchantStoreOrderCartInfo
{
    public function selectList(array $where, $field = '*', $order = 'id ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_store_order_cart_info
            ->select($field)
            ->order($order)
            ->where($where);

        if ($limit > 0) {
            $list->limit($start, $limit);
        }

        $list = $list->fetchAll();

        return $list;
    }
}