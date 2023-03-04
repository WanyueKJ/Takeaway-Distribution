<?php

namespace App\Model;

use PhalApi\Model\NotORMModel as NotORM;

/**
 * 店铺
 */
class MerchantStore extends NotORM
{
    /**
     * 店铺商圈排名(没有并列情况)
     * @param $store_id
     * @param $circle_id
     * @return int|mixed
     */
    public function getCircleRanking($store_id, $circle_id)
    {
        $sql = "SELECT
                    * 
                FROM
                    (
                    SELECT
                        `id`,
                        `stars`,
                        `type_id`,
                        @rank := @rank + 1 AS `rank` 
                    FROM
                        cmf_merchant_store,
                        ( SELECT @rank := 0 AS `rank` ) t 
                    WHERE
                        circle_id = {$circle_id} 
                    ORDER BY
                        stars DESC 
                    ) AS rand_tab WHERE rand_tab.id = {$store_id}";

        $rs = \PhalApi\DI()->notorm->merchant_store->queryAll($sql);
        return $rs[0]['rank'] ?? 0;
    }


    /**
     * 店铺类型排名(没有并列情况)
     * @param $store_id
     * @param $type_id
     * @return int|mixed
     */
    public function getRanking($store_id, $type_id)
    {
        $sql = "SELECT
                    * 
                FROM
                    (
                    SELECT
                        `id`,
                        `stars`,
                        `type_id`,
                        @rank := @rank + 1 AS `rank` 
                    FROM
                        cmf_merchant_store,
                        ( SELECT @rank := 0 AS `rank` ) t 
                    WHERE
                        type_id = {$type_id} 
                    ORDER BY
                        stars DESC 
                    ) AS rand_tab WHERE rand_tab.id = {$store_id}";

        $rs = \PhalApi\DI()->notorm->merchant_store->queryAll($sql);
        return $rs[0]['rank'] ?? 0;
    }


    public function updateOne($where, $update = [])
    {
        $rs = \PhalApi\DI()->notorm->merchant_store
            ->where($where)
            ->update($update);
        return $rs;
    }

    public function selectList(array $where, $field = '*', $order = 'list_order ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_store
            ->select($field)
            ->order($order)
//            ->where(['putaway > ?' => 0])
            ->where($where);
        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        $list = $list->fetchAll();
        foreach ($list as $key => &$value) {
            $value['use_name'] = 'use_name';
            if (LANG == 'zh-cn') {
                $value['use_name'] = $value['name'] ?? '';
            } else if (LANG == 'th') {
                $value['use_name'] = $value['th_name'] ?? '';
            }
        }
        return $list;
    }

    /**
     * IN查询 (type_id) 条件列表搜索
     * @param $typeList
     * @param $where
     * @param $field
     * @param $order
     * @param $p
     * @param $limit
     * @return mixed
     */
    public function inTypeSelectList($typeList, $where, $field = '*', $order = 'list_order ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_store
            ->select($field)
            ->order($order)
            ->where(['putaway > ?' => 0])
            ->where('type_id', $typeList)
            ->where($where);
        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        $list = $list->fetchAll();

        foreach ($list as $key => &$value) {
            $value['use_name'] = 'use_name';
            if (LANG == 'zh-cn') {
                $value['use_name'] = $value['name'] ?? '';
            } else if (LANG == 'th') {
                $value['use_name'] = $value['th_name'] ?? '';
            }
        }
        return $list;
    }


    /**
     * 根据距离查询店铺
     * @param $lng 经度
     * @param $lat 维度
     * @param $where 条件
     * @param $typeList 店铺类型条件
     * @param $order 排序
     * @param $limti 个数
     * @return array distance 单位千米(直线距离)
     */
    public function distanceSelect($lng, $lat, $where = [], $field = '*', $order = "list_order ASC", $p = 0, $limit = 10)
    {
        if ($p < 1) {
            $p = 1;
        }
        $noriko_sakai = \App\getConfigPri()['noriko_sakai'] ?? 50;

        $start = ($p - 1) * $limit;
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
            ->group('id', "distance <= {$noriko_sakai}")
            ->where(['putaway > ?' => 0])
            ->where($where);
        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        $list = $list->fetchAll();
        foreach ($list as $key => &$value) {
            $value['use_name'] = 'use_name';
            if (LANG == 'zh-cn') {
                $value['use_name'] = $value['name'] ?? '';
            } else if (LANG == 'th') {
                $value['use_name'] = $value['th_name'] ?? '';
            }
        }
        return $list;
    }


    /**
     * 根据距离查询(固定数量)
     * @param $lng 经度
     * @param $lat 维度
     * @param $where 条件
     * @param $typeList 店铺类型条件
     * @param $order 排序
     * @param $limti 个数
     * @return array distance 单位千米(直线距离)
     */
    public function distanceSelectList($lng, $lat, $where = [], $typeList = [], $field = '*', $order = "list_order ASC", $p = 0, $limit = 10)
    {
        if ($p < 1) {
            $p = 1;
        }
        $noriko_sakai = \App\getConfigPri()['noriko_sakai'] ?? 50;

        $start = ($p - 1) * $limit;
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
            ->where(['putaway > ?' => 0])
            ->where('type_id', $typeList)
            ->group('id', "distance <= {$noriko_sakai}")
            ->where($where);
        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        $list = $list->fetchAll();
        foreach ($list as $key => &$value) {
            $value['use_name'] = 'use_name';
            if (LANG == 'zh-cn') {
                $value['use_name'] = $value['name'] ?? '';
            } else if (LANG == 'th') {
                $value['use_name'] = $value['th_name'] ?? '';
            }
        }
        return $list;
    }

    /**
     * 根据距离查询(分页)
     * @param $lng 经度
     * @param $lat 维度
     * @param $where 条件
     * @param $typeList 店铺类型条件
     * @param $order 排序
     * @param $limti 个数
     * @return array distance 单位千米(直线距离)
     */
    public function distanceSelectListPage(float $lng, float $lat, array $type_id_arr, string $overall, string $distanc, string $evaluate, string $keywords = '', $circle_id, $p = 0, $limit = 0, $field = "*")
    {
        if ($p < 1) {
            $p = 1;
        }
        $start = ($p - 1) * $limit;
        $where = [];
        if ($keywords != '') {
            if (LANG == 'zh-cn') {
                $where['name LIKE ?'] = "%{$keywords}%";
            } else if (LANG == 'th') {
                $where['th_name LIKE ?'] = "%{$keywords}%";
            }

        }
        if ($overall) {//店铺访问量排序
            $order = "views_day {$overall}";
        } else if ($distanc) {//店铺距离排序
            $order = "distance {$distanc}";
        } else if ($evaluate) {//店铺评分排序
            $order = "stars {$evaluate}";
        } else {
            $order = "views_day {$overall}";
        }
        if ($circle_id > 0) {
            $where['circle_id = ?'] = $circle_id;
        }
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
            ->group('id', "distance <= {$noriko_sakai}")
            ->where('type_id', $type_id_arr)
            ->where(['putaway > ?' => 0])
            ->where($where);
        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        $list = $list->fetchAll();
        foreach ($list as $key => &$value) {
            $value['use_name'] = 'use_name';
            if (LANG == 'zh-cn') {
                $value['use_name'] = $value['name'] ?? '';
            } else if (LANG == 'th') {
                $value['use_name'] = $value['th_name'] ?? '';
            }
        }
        return $list;
    }


    public function getOne($where, $field = "*")
    {

        $info = \PhalApi\DI()->notorm->merchant_store
            ->select($field)
            ->where($where)
            ->fetchOne();
        if ($info) {
            $info['use_name'] = '';
            if (LANG == 'zh-cn') {
                $info['use_name'] = $info['name'] ?? '';
            } else if (LANG == 'th') {
                $info['use_name'] = $info['th_name'] ?? '';
            }
        }
        return $info;

    }


    /**
     * 美食店铺搜索
     * @param $lng 经度
     * @param $lat 维度
     * @param $type_id 平台店铺分类
     * @param $keywords 店铺关键词
     * @param $p 页码
     * @param $limit 数量
     * @param $field
     * @return array
     */
    public function cateSelectList(float $lng, float $lat, array $type_id_arr, string $keywords = '', $p = 0, $limit = 0, $field = "store.id,store.name,store.th_name,store.thumb,store.stars,store.lng,store.lat,store.sales,store.type_id,store.up_to_send")
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;

        $where = [];
        if ($keywords != '') {
            if (LANG == 'zh-cn') {
                $where['store.name LIKE ?'] = "%{$keywords}%";
            } else if (LANG == 'th') {
                $where['store.th_name LIKE ?'] = "%{$keywords}%";
            }
        }
        $noriko_sakai = \App\getConfigPri()['noriko_sakai'] ?? 50;

        $list = \PhalApi\DI()->notorm->merchant_store
            ->select("{$field},(
                        6378.138 * 2 * ASIN(
                            SQRT(
                                POW( SIN( ( {$lat} * PI( ) / 180 - lat * PI( ) / 180 ) / 2 ), 2 ) + COS( {$lat} * PI( ) / 180 ) * COS( lat * PI( ) / 180 ) * POW( SIN( ( {$lng}* PI( ) / 180 - lng * PI( ) / 180 ) / 2 ), 2 ) 
                            ) 
                        ) 
                    ) AS distance") // 获取字段
            ->alias('store') //
            ->leftJoin('merchant_store_product', 'store_product', 'store_product.store_id = store.id')
            ->where('store.type_id', $type_id_arr)
            ->where($where)
            ->group('store.id')
            ->where(['store.putaway > ?' => 0])
            ->group('id', "distance <= {$noriko_sakai}")
            ->order("distance ASC");

        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        $list = $list->fetchAll();
        foreach ($list as $key => &$value) {
            $value['use_name'] = '';
            if (LANG == 'zh-cn') {
                $value['use_name'] = $value['name'] ?? '';
            } else if (LANG == 'th') {
                $value['use_name'] = $value['th_name'] ?? '';
            }
        }
        return $list;
    }
}
