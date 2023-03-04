<?php

namespace App\Model;


use PhalApi\Model\NotORMModel as NotORM;


/**
 * 订单评价
 */
class MerchantStoreOrderEvaluate extends NotORM
{

    public function deleteReplyLikeOne($where)
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_order_evaluate_like
            ->where($where)
            ->delete();
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
        $rs = \PhalApi\DI()->notorm->merchant_store_order_evaluate
            ->where($where)
            ->where('is_show',1)
            ->update($update);
        return $rs;
    }

    public function getReplyLikeOne($where, $field = '*')
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_order_evaluate_like
            ->select($field)
            ->where($where)
            ->fetchOne();
        return $rs;
    }


    public function saveReplyLikeOne($data)
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_order_evaluate_like
            ->insert($data);
        return $rs;
    }


    public function getOrderReplyLikeOne($where,$field = "*"){
        $rs = \PhalApi\DI()->notorm->merchant_store_order_evaluate_like
            ->select($field)
            ->where($where)
            ->fetchOne();
        return $rs;
    }


    public function getCount($where)
    {
        $info = \PhalApi\DI()->notorm->merchant_store_order_evaluate
            ->where($where)
            ->where('is_show',1)
            ->count();
        return $info;
    }

    public function getOne(array $where, $field = '*')
    {
        $info = \PhalApi\DI()->notorm->merchant_store_order_evaluate
            ->select($field)
            ->where($where)
            ->where('is_show',1)
            ->fetchOne();
        return $info;
    }


    /**
     * 保存数据
     * @return array
     */
    public function saveOne($data)
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_order_evaluate
            ->insert($data);
        $id = \PhalApi\DI()->notorm->merchant_store_order_evaluate->insert_id();
        return $id;
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
            ->where('is_show',1)
            ->where($where);

        if ($limit > 0) {
            $list->limit($start, $limit);
        }

        $list = $list->fetchAll();

        return $list;
    }
}