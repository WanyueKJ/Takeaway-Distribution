<?php

namespace Merchant\Model;

class Users
{
    public function getOne($where,$field='*'){

        $info=\PhalApi\DI()->notorm->users
            ->select($field)
            ->where($where)
            ->fetchOne();

        return $info;
    }
}