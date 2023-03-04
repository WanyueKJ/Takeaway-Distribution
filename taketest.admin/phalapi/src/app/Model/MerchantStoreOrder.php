<?php

namespace App\Model;

use PhalApi\Model\NotORMModel as NotORM;

/**
 * 店铺订单
 */
class MerchantStoreOrder extends NotORM
{


    public function getOrderInfo($where){
        $info = \PhalApi\DI()->notorm->rider_orderid
            ->where($where)
            ->fetchOne();
        return $info;
    }

    public function upOrderInfo($where,$data){
        $info = \PhalApi\DI()->notorm->rider_orderid
            ->where($where)
            ->update($data);
        return $info;
    }

    public function saveOrderInfo($data){
        $info = \PhalApi\DI()->notorm->rider_orderid
            ->insert($data);
        return $info;
    }


    /**
     * 联合订单表查询(跑腿订单,店铺订单)
     * @param $uid
     * @param $p
     * @param $limit
     * @return array
     */
    public function getUnionOrderList($uid, $p = 0, $limit = 0)
    {

        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $uid = (int)$uid;

        $sql = "SELECT
                    `id`,
                    0 as order_type,
                    `status`,
                    type,
                    NULL AS store_id,
                    is_cancel,
                    money AS pay_price,
                    addtime,
                    0 AS total_num,
                    store_oid,
                    t_name
                FROM
                    cmf_orders 
                WHERE
                    uid = {$uid} 
                    AND STATUS = 6 
                    AND id NOT IN ( SELECT oid FROM cmf_evaluate ) 
                    AND type IN (1,2,3,4,5)
                    AND deltime = 0 UNION ALL
                SELECT
                    `id`,
                    1 as order_type,
                    `status`,
                    NULL AS type,
                    store_id,
                    is_cancel,
                    pay_price,
                    add_time AS addtime,
                    total_num,
                    NULL AS store_oid,
                    '' as t_name
                FROM
                    cmf_merchant_store_order 
                WHERE
                    uid = {$uid}  
                    AND is_del = 0 
                    AND is_cancel = 0 
                    AND id NOT IN ( SELECT oid FROM cmf_merchant_store_order_evaluate ) 
                    AND STATUS = 4";


        if ($p && $limit) {
            $sql .= " limit $start,$limit";
        }

        $rs = \PhalApi\DI()->notorm->view_store_order->queryAll($sql);
        return $rs;
    }


    public function getCount($where)
    {
        $info = \PhalApi\DI()->notorm->view_store_order_product_attr
            ->where($where)
            ->count();
        return $info;
    }

    public function getCountOne($where,$filed = "")
    {
        $info = \PhalApi\DI()->notorm->view_store_order_product_attr
            ->select($filed)
            ->where($where)
            ->fetchOne();
        return $info;
    }

    /**
     * 视图表数量
     * @param array $where
     * @param $field
     * @return int|string
     */
    public function viewCount(array $where, $field = '*')
    {

        $count = \PhalApi\DI()->notorm->view_store_order_product_attr
            ->select($field)
            ->where($where)
            ->where(['is_del = ?' => 0])
            ->count();

        return $count;
    }


    /**
     * 视图表信息
     * @param array $where
     * @param $field
     * @param $order
     * @param $p
     * @param $limit
     * @return mixed
     */
    public function selectViewList(array $where, $field = 'v_s_p_a.*,store.name as store_name,store.th_name as store_th_name,product.name as product_name,product.th_name as product_th_name', $order = 'v_s_p_a.id ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->view_store_order_product_attr
            ->select($field)
            ->alias('v_s_p_a') // 主表别名为view_store_order_product_attr
            ->leftJoin('merchant_store', 'store', 'v_s_p_a.store_id = store.id')
            ->leftJoin('merchant_store_product', 'product', 'v_s_p_a.product_id = product.id')
            ->order($order)
            ->group("v_s_p_a.id")
            ->where($where)
            ->where(['v_s_p_a.is_del = ?' => 0]);

        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        $list = $list->fetchAll();
        return $list;
    }


    /**
     * 保存数据
     * @return array
     */
    public function saveOne($data)
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_order
            ->insert($data);

        $id = \PhalApi\DI()->notorm->merchant_store_order->insert_id();
        return $id;
    }

    public function getOne(array $where, $field = '*')
    {
        $info = \PhalApi\DI()->notorm->merchant_store_order
            ->select($field)
            ->where($where)
            ->where(['is_del = ?' => 0])
            ->fetchOne();
        return $info;
    }

    public function selectList(array $where, $field = '*')
    {
        $info = \PhalApi\DI()->notorm->merchant_store_order
            ->select($field)
            ->where($where)
            ->where(['is_del = ?' => 0])
            ->fetchAll();
        return $info;
    }

    public function updateOne($where, $data)
    {
        return \PhalApi\DI()->notorm->merchant_store_order
            ->where($where)
            ->update($data);
    }
}