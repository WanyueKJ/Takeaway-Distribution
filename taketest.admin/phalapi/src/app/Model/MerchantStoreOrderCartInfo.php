<?php

namespace App\Model;
use PhalApi\Model\NotORMModel as NotORM;

/**
 * 已购买的店铺订单与购物车的信息
 */
class MerchantStoreOrderCartInfo extends NotORM
{
    /**
     * 查询数量
     * @return array
     */
    public function getCount($where)
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_order_cart_info
            ->where($where)
            ->count();
        return $rs;
    }

    /**
     * 保存数据
     * @return array
     */
    public function saveOne($data)
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_order_cart_info
            ->insert($data);
        $id = \PhalApi\DI()->notorm->merchant_store_order_cart_info->insert_id();
        return $id;
    }

    /**
     * 获取数据
     * @return array
     */
    public function getOne($where,$field)
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_order_cart_info
            ->select($field)
            ->where($where)
            ->fetchOne();
        return $rs;
    }



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