<?php

namespace App\Model;

use PhalApi\Model\NotORMModel as NotORM;

/**
 * 订单服务
 */
class MerchantStoreServe extends NotORM
{

    public function selectList(array $where, $field = '*', $order = 'id ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_store_serve
            ->select($field)
            ->order($order)
            ->where($where);
        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        $list = $list->fetchAll();

        foreach ($list as $key => &$value) {
            $value['use_name'] = '';

            if (LANG == 'zh-cn') {
                $value['use_name'] = $value['name'] ?? '';
            } else if (LANG == 'th') {
                $value['use_name'] = $value['th_name'] ?? '';
            }
        }
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
        $rs = \PhalApi\DI()->notorm->merchant_store_serve
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
        $rs = \PhalApi\DI()->notorm->merchant_store_serve
            ->insert($data);
        return $rs;
    }

    public function getOne($where, $field = "*")
    {
        return \PhalApi\DI()->notorm->merchant_store_serve
            ->select($field)
            ->where($where)
            ->fetchOne();
    }
}