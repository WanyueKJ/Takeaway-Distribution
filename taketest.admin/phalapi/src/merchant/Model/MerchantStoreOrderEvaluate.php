<?php

namespace Merchant\Model;

/**
 * 店铺评论
 */
class MerchantStoreOrderEvaluate
{

    public function getOne($where, $field = "*")
    {
        return \PhalApi\DI()->notorm->merchant_store_order_evaluate
            ->select($field)
            ->where($where)
            ->fetchOne();
    }

    public function getCount($where)
    {
        return \PhalApi\DI()->notorm->merchant_store_order_evaluate
            ->where($where)
            ->count();
    }


    public function selectList(array $where, $field = '*', $order = 'id ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_store_order_evaluate
            ->select($field)
            ->order($order)
            ->where($where);
        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        return $list->fetchAll();
    }

    /**
     * 更新数据
     * @param $where
     * @param $field
     * @return mixed
     */
    public function updateOne($where, $update = [])
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_order_evaluate
            ->where($where)
            ->update($update);
        return $rs;
    }
}