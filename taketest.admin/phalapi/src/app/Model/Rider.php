<?php

namespace App\Model;

use PhalApi\Model\NotORMModel as NotORM;

class Rider extends NotORM
{
    public function getOne($where, $field = "*")
    {
        return \PhalApi\DI()->notorm->rider
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
        $rs = \PhalApi\DI()->notorm->rider
            ->insert($data);
        return $rs;
    }

    /**
     * 保存数据
     * @return array
     */
    public function saveEvaluateOne($data)
    {
        $rs = \PhalApi\DI()->notorm->evaluate
            ->insert($data);
        return $rs;
    }

    /**
     * 查询系统可以推送订单的骑手信息
     * @param $where
     * @param $field
     * @return void
     */
    public function viewSelectList($lng, $lat, $rider_distance,$where, $field = '*')
    {
        $rs = \PhalApi\DI()->notorm->rider
            ->select("{$field},
                (
                    6378.138 * 2 * ASIN(
                        SQRT(
                            POW( SIN( ( {$lat} * PI( ) / 180 - lat * PI( ) / 180 ) / 2 ), 2 ) + COS( {$lat} * PI( ) / 180 ) * COS( lat * PI( ) / 180 ) * POW( SIN( ( {$lng}* PI( ) / 180 - lng * PI( ) / 180 ) / 2 ), 2 ) 
                        ) 
                    ) 
                ) AS distance")
            ->alias('rider') //
            ->leftJoin('rider_location', 'rider_location', 'rider.id = rider_location.uid')
            ->where($where)
            ->group('id', "distance <= {$rider_distance}")
            ->fetchAll();
        return $rs;
    }
}