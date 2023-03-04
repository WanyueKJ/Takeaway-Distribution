<?php

namespace App\Model;


/**
 * 店铺足迹
 */
class MerchantStoreFoot
{
    public function selectList(array $where, $field = '*', $order = 'id ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_store_foot
            ->select($field)
            ->order($order)
            ->where($where);
        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        $list = $list->fetchAll();
        return $list;
    }

    public function joinSelectList(array $where, $lng, $lat, $field = 'store_like.*,store.type_id', $order = 'id ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_store_foot
            ->select("{$field},
                (
                        6378.138 * 2 * ASIN(
                            SQRT(
                                POW( SIN( ( {$lat} * PI( ) / 180 - lat * PI( ) / 180 ) / 2 ), 2 ) + COS( {$lat} * PI( ) / 180 ) * COS( lat * PI( ) / 180 ) * POW( SIN( ( {$lng}* PI( ) / 180 - lng * PI( ) / 180 ) / 2 ), 2 ) 
                            ) 
                        ) 
                    ) AS distance") // 获取字段
            ->alias('store_like') // 主表别名为store_like
            // 左关联另一张表another_table（不需要加表前缀）
            // 并起一个别名为B，关联条件是A.id = B.user_id
            ->leftJoin('merchant_store', 'store', 'store_like.store_id = store.id')
            ->select($field)
            ->order($order)
            ->where($where);
        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        $list = $list->fetchAll();

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
        $rs = \PhalApi\DI()->notorm->merchant_store_foot
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
        $rs = \PhalApi\DI()->notorm->merchant_store_foot
            ->insert($data);
        return $rs;
    }

    public function getOne($where, $field = "*")
    {
        return \PhalApi\DI()->notorm->merchant_store_foot
            ->select($field)
            ->where($where)
            ->fetchOne();
    }

    /**
     * 删除一条数据
     * @param $where
     * @param $field
     * @return mixed
     */
    public function deleteOne($where)
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_foot
            ->where($where)
            ->delete();
        return $rs;
    }

    /**
     * 删除多条条数据
     * @param $where
     * @param $field
     * @return mixed
     */
    public function inIdDeleteOne($idArr, $where)
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_foot
            ->where($where)
            ->where('id', $idArr)
            ->delete();
        return $rs;
    }
}