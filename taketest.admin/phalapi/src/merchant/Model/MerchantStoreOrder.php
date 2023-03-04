<?php

namespace Merchant\Model;

use PhalApi\Model\NotORMModel as NotORM;

class MerchantStoreOrder extends NotORM
{

    public function selectList(array $where, $field = '*', $order = 'id DESC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_store_order
            ->select($field)
            ->order($order)
            ->where($where);
        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        return $list->fetchAll();
    }


    public function getOne($where, $field = "*")
    {
        return \PhalApi\DI()->notorm->merchant_store_order
            ->select($field)
            ->where($where)
            ->order('id DESC')
            ->fetchOne();
    }


    /**
     * 更新数据
     * @param $where
     * @param $field
     * @return mixed
     */
    public function updateOne($where, $update = [])
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_order
            ->where($where)
            ->update($update);
        return $rs;
    }

    public function inStatusGetOne($statusArr, $where, $field = "*")
    {
        return \PhalApi\DI()->notorm->merchant_store_order
            ->select($field)
            ->where('status', $statusArr)
            ->where($where)
            ->fetchOne();
    }


    public function inStatusCount($status,$where, $field = "*")
    {
        return \PhalApi\DI()->notorm->merchant_store_order
            ->select($field)
            ->where('status',$status)
            ->where($where)
            ->count();
    }

    public function inStatusList($status,$where, $field = "*")
    {
        return \PhalApi\DI()->notorm->merchant_store_order
            ->select($field)
            ->where('status',$status)
            ->where($where)
            ->fetchAll();
    }

    public function getCount($where, $field = "*")
    {
        return \PhalApi\DI()->notorm->merchant_store_order
            ->select($field)
            ->where($where)
            ->count();
    }
}