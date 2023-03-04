<?php

namespace Merchant\Model;

use PhalApi\Model\NotORMModel as NotORM;

/**
 * 店铺
 */
class MerchantStore extends NotORM
{


    public function selectList(array $where, $field = '*', $order = 'list_order ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_store
            ->select($field)
            ->order($order)
            ->where($where);
        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        return $list->fetchAll();
    }

    /**
     * IN查询 (type_id) 条件列表搜索
     * @param $typeList
     * @param $where
     * @param $field
     * @param $order
     * @param $page
     * @param $limit
     * @return mixed
     */
    public function inTypeSelectList($typeList, $where, $field = '*', $order = 'list_order ASC', $page = 0, $limit = 0)
    {
        if ($page < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_store
            ->select($field)
            ->order($order)
            ->where('type_id', $typeList)
            ->where($where);
        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        return $list->fetchAll();
    }


    /**
     * 根据距离查询
     * @param $lng 经度
     * @param $lat 维度
     * @param $where 条件
     * @param $typeList 店铺类型条件
     * @param $order 排序
     * @param $limti 个数
     * @return array distance 单位千米(直线距离)
     */
    public function distanceSelectList($lng, $lat, $where = [], $typeList = [], $field = '*', $order = "ASC", $limit = 10)
    {
        $noriko_sakai = \App\getConfigPri()['noriko_sakai'] ?? 50;
        $list = \PhalApi\DI()->notorm->merchant_store
            ->select("{$field},
                (
                        6378.138 * 2 * ASIN(
                            SQRT(
                                POW( SIN( ( {$lat} * PI( ) / 180 - lat * PI( ) / 180 ) / 2 ), 2 ) + COS( {$lat} * PI( ) / 180 ) * COS( lat * PI( ) / 180 ) * POW( SIN( ( {$lng}* PI( ) / 180 - lng * PI( ) / 180 ) / 2 ), 2 ) 
                            ) 
                        ) 
                    ) AS distance")
            ->order($order)
            ->where('type_id', $typeList)
            ->group('id', "distance <= {$noriko_sakai}")
            ->where($where);
        if ($limit > 0) {
            $list->limit($limit);
        }
        return $list->fetchAll();
    }


    public function getOne($where, $field = "*")
    {
        return \PhalApi\DI()->notorm->merchant_store
            ->select($field)
            ->where($where)
            ->fetchOne();
    }

    /**
     * 更新数据
     * @param $where
     * @param $field
     * @return mixed
     */
    public function updateOne($where, $update = [])
    {
        $rs = \PhalApi\DI()->notorm->merchant_store
            ->where($where)
            ->update($update);
        return $rs;
    }

}
