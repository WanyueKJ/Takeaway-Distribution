<?php

namespace App\Model;

use PhalApi\Model\NotORMModel as NotORM;

class MerchantEnter extends NotORM
{
    public function saveOne($data)
    {
        $rs = \PhalApi\DI()->notorm->merchant_enter
            ->insert($data);
        return $rs;
    }

    public function getOne($where, $field = "*")
    {
        return \PhalApi\DI()->notorm->merchant_enter
            ->select($field)
            ->where($where)
            ->fetchOne();
    }

    public function updateOne($where, $data)
    {
        return \PhalApi\DI()->notorm->merchant_enter
            ->where($where)
            ->update($data);
    }
}