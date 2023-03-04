<?php

namespace Merchant\Model;

use PhalApi\Model\NotORMModel as NotORM;

/**
 * 店铺银行信息
 */
class MerchantStoreBank extends NotORM
{


    /**
     * 更新数据
     * @param $where
     * @param $field
     * @return mixed
     */
    public function deleteOne($where)
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_bank
            ->where($where)
            ->delete();
        return $rs;
    }

    public function selectList(array $where, $field = '*', $order = 'id ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_store_bank
            ->select($field)
            ->order($order)
            ->where($where);
        if ($limit > 0) {
            $list->limit($start, $limit);
        }

        $list = $list->fetchAll();
        return $list;
    }


    /**
     * 更新数据
     * @param $where
     * @param $field
     * @return mixed
     */
    public function updateOne($where, $update = [])
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_bank
            ->where($where)
            ->update($update);
        return $rs;
    }

    /**
     * 保存数据
     * @return array
     */
    public function saveOne($data)
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_bank
            ->insert($data);
        return $rs;
    }

    public function getOne($where, $field = "*")
    {
        return \PhalApi\DI()->notorm->merchant_store_bank
            ->select($field)
            ->where($where)
            ->fetchOne();
    }
}