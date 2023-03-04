<?php

namespace Merchant\Model;

/**
 * 商家体现记录
 */
class MerchantRecord
{
    public function selectList(array $where, $field = '*', $order = 'id ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_record
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
        return \PhalApi\DI()->notorm->merchant_record
            ->select($field)
            ->where($where)
            ->fetchOne();
    }

    /**
     * 保存数据
     * @return array
     */
    public function saveOne($data)
    {
        $rs = \PhalApi\DI()->notorm->merchant_record
            ->insert($data);
        return $rs;
    }
}