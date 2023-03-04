<?php

namespace Merchant\Model;

use PhalApi\Model\NotORMModel as NotORM;

/**
 * 店铺工商信息
 */
class MerchantStoreIndustry extends NotORM
{

    /**
     * 更新数据
     * @param $where
     * @param $field
     * @return mixed
     */
    public function updateOne($where, $update = [])
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_industry
            ->where($where)
            ->update($update);
        return $rs;
    }

    /**
     * 新增店铺-易联云-配置信息
     * @return array
     */
    public function saveOne($data)
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_industry
            ->insert($data);
        return $rs;
    }

    public function getOne($where, $field = "*")
    {
        return \PhalApi\DI()->notorm->merchant_store_industry
            ->select($field)
            ->where($where)
            ->fetchOne();
    }
}