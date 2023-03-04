<?php

namespace App\Model;

use PhalApi\Model\NotORMModel as NotORM;

/**
 * 店铺打卡
 */
class MerchantStoreCard extends NotORM
{
    public function saveOne($data)
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_card
            ->insert($data);
        return $rs;
    }

    public function getOne($where, $field = '*')
    {
        $info = \PhalApi\DI()->notorm->merchant_store_card
            ->select($field)
            ->where($where)
            ->fetchOne();
        return $info;
        return $rs;
    }
}