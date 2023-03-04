<?php

namespace App\Model;

use PhalApi\Model\NotORMModel as NotORM;

/**
 * 商圈
 */
class MerchantStoreCircle extends NotORM
{
    public function selectList(array $where, $field = '*', $order = 'id ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_store_circle
            ->select($field)
            ->order($order)
            ->where($where);
        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        $list = $list->fetchAll();
        foreach ($list as $key => &$value) {
            if (LANG == 'zh-cn') {
                $value['use_name'] = $value['name'] ?? '';
            } else if (LANG == 'th') {
                $value['use_name'] = $value['th_name'] ?? '';
            }
        }
        return $list;
    }

    public function getOne($where, $field = "*")
    {
        $info = \PhalApi\DI()->notorm->merchant_store_circle
            ->select($field)
            ->where($where)
            ->fetchOne();
        if ($info) {
            $info['use_name'] = '';
            if (LANG == 'zh-cn') {
                $info['use_name'] = $info['name'] ?? '';
            } else if (LANG == 'th') {
                $info['use_name'] = $info['th_name'] ?? '';
            }
        }

        return $info;
    }
}