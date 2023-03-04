<?php

namespace App\Model;

/**
 * 店铺账号
 */
class MerchantSoreAccount
{
    public function getOne($where, $field = "*")
    {
        return \PhalApi\DI()->notorm->merchant_store_account
            ->select($field)
            ->where($where)
            ->fetchOne();
    }
}