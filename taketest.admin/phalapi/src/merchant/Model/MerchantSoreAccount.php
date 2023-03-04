<?php

namespace merchant\Model;
use PhalApi\Model\NotORMModel as NotORM;

/**
 * 商户管理-商家账号
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