<?php

namespace App\Model;

use PhalApi\Model\NotORMModel as NotORM;


/**
 * 店铺管理-店铺商品
 */
class MerchantStoreProduct extends NotORM
{


    public function updateOne($where, $data)
    {
        return \PhalApi\DI()->notorm->merchant_store_product
            ->where($where)
            ->update($data);
    }

    /**
     * 店铺类型搜索
     * @param $idArr
     * @param array $where
     * @param $field
     * @param $order
     * @param $p
     * @param $limit
     * @return mixed
     */
    public function inTypeIdselectList($typeIdArr, array $where = [], $field = '*', $order = 'list_order ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_store_product
            ->select($field)
            ->order($order)
            ->where($where)
            ->where('type_id', $typeIdArr)
            ->where(['is_del = ?' => 0]);

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
            $value['use_price'] = $value['price'] ?? '';

        }
        return $list;
    }

    public function saveOne($data)
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_product
            ->insert($data);
        return $rs;
    }

    public function getOne(array $where, $field = '*')
    {
        $info = \PhalApi\DI()->notorm->merchant_store_product
            ->select($field)
            ->where($where)
            ->where(['is_del = ?' => 0])
            ->where(['is_show = ?' => 1])
            ->fetchOne();
        if ($info) {
            $info['use_name'] = '';

            if (LANG == 'zh-cn') {
                $info['use_name'] = $info['name'] ?? '';
                $info['use_price'] = $info['price'] ?? '';
            } else if (LANG == 'th') {
                $info['use_name'] = $info['th_name'] ?? '';
                $info['use_price'] = $info['price'] ?? '';
            }
        }
        return $info;
    }

    public function getOne2(array $where, $field = '*')
    {
        $info = \PhalApi\DI()->notorm->merchant_store_product
            ->select($field)
            ->where($where)
            ->where(['is_del = ?' => 0])
            ->fetchOne();
        if ($info) {
            $info['use_name'] = '';

            if (LANG == 'zh-cn') {
                $info['use_name'] = $info['name'] ?? '';
                $info['use_price'] = $info['price'] ?? '';
            } else if (LANG == 'th') {
                $info['use_name'] = $info['th_name'] ?? '';
                $info['use_price'] = $info['price'] ?? '';
            }
        }
        return $info;
    }


    public function inIdselectList($idArr, array $where = [], $field = '*', $order = 'list_order ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_store_product
            ->select($field)
            ->order($order)
            ->where($where)
            ->where('id', $idArr)
            ->where(['is_del = ?' => 0])
            ->where(['is_show = ?' => 1]);
        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        $list = $list->fetchAll();
        return $list;
    }

    public function selectList(array $where, $field = '*', $order = 'list_order ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_store_product
            ->select($field)
            ->order($order)
            ->where($where)
            ->where(['is_del = ?' => 0])
            ->where(['is_show = ?' => 1]);

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
            $value['use_price'] = $value['price'] ?? '';

        }

        return $list;
    }

    /**
     * 店铺-美食搜索
     * @param $lng 经度
     * @param $lat 维度
     * @param $type_id 平台店铺分类
     * @param $overall 综合排序
     * @param $price 价格排序
     * @param $distanc 距离排序
     * @param $evaluate 评分排序
     * @param $keywords 商品关键词
     * @param $keywords 页码
     * @param $limit 数量
     * @param $field
     * @return array
     */
    public function cateSelectList(float $lng, float $lat, array $type_id_arr, string $overall, string $price, string $distanc, string $evaluate, string $keywords = '', $p = 0, $limit = 0, $field = "store_product.id, store_product.name,store_product.image,store_product.starts,store_product.good_starts,store_product.price,store.id as store_id,store.lng,store.lat,store.views_day")
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;

        $where = [];
        if ($keywords != '') {
            if(LANG == 'zh-cn'){
                $where['store_product.name LIKE ?'] = "%{$keywords}%";
            }else if(LANG == 'th'){
                $where['store_product.th_name LIKE ?'] = "%{$keywords}%";
            }

        }
        if ($overall) {//店铺访问量排序
            $order = "store.views_day {$overall}";
        } else if ($price) {//商品价格排序
            $order = "store_product.price {$price}";
        } else if ($distanc) {//店铺距离排序
            $order = "distance {$distanc}";
        } else if ($evaluate) {//店铺评分排序
            $order = "store.stars {$distanc}";
        } else {
            $order = "store.views_day {$overall}";
        }

        $list = \PhalApi\DI()->notorm->merchant_store_product
            ->select("{$field},
                (
                        6378.138 * 2 * ASIN(
                            SQRT(
                                POW( SIN( ( {$lat} * PI( ) / 180 - lat * PI( ) / 180 ) / 2 ), 2 ) + COS( {$lat} * PI( ) / 180 ) * COS( lat * PI( ) / 180 ) * POW( SIN( ( {$lng}* PI( ) / 180 - lng * PI( ) / 180 ) / 2 ), 2 ) 
                            ) 
                        ) 
                    ) AS distance") // 获取字段
            ->alias('store_product') // 主表别名为store_product
            // 左关联另一张表another_table（不需要加表前缀）
            // 并起一个别名为B，关联条件是A.id = B.user_id
            ->leftJoin('merchant_store', 'store', 'store_product.store_id = store.id')
            ->where('store.type_id', $type_id_arr)
            ->where(['store_product.is_del = ?' => 0])
            ->where(['store_product.is_show = ?' => 1])
            ->where($where)
            ->order($order);

        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        $list = $list->fetchAll();
        foreach ($list as $key => &$value) {
            $value['use_name'] = '';

            if (LANG == 'zh-cn') {
                $value['use_name'] = $value['name'] ?? '';
                $value['use_price'] = $value['price'] ?? '';
            } else if (LANG == 'th') {
                $value['use_name'] = $value['th_name'] ?? '';
                $value['use_price'] = $value['price'] ?? '';
            }
        }

        return $list;
    }

    /**
     * 店铺-美食搜索
     * @param $storeIdArr 店铺id
     * @param $lng 经度
     * @param $lat 维度
     * @param $type_id 平台店铺分类
     * @param $overall 综合排序
     * @param $price 价格排序
     * @param $distanc 距离排序
     * @param $evaluate 评分排序
     * @param $keywords 商品关键词
     * @param $keywords 页码
     * @param $limit 数量
     * @param $field
     * @return array
     */
    public function notInStoreIdSelectList($storeIdArr, float $lng, float $lat, string $overall, string $price, string $distanc, string $evaluate, string $keywords = '', $p = 0, $limit = 0, $field = "store_product.id, store_product.name,store_product.image,store_product.starts,store_product.good_starts,store_product.price,store.id as store_id,store.type_id,store.lng,store.lat,store.views_day")
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;

        $where = [];
        if ($keywords != '') {
            if(LANG == 'zh-cn'){
                $where['store_product.name LIKE ?'] = "%{$keywords}%";
            }else if(LANG == 'th'){
                $where['store_product.th_name LIKE ?'] = "%{$keywords}%";
            }
        }

        if ($overall) {//店铺访问量排序
            $order = "store.views_day {$overall}";
        } else if ($price) {//商品价格排序
            $order = "store_product.price {$price}";
        } else if ($distanc) {//店铺距离排序
            $order = "distance {$distanc}";
        } else if ($evaluate) {//店铺评分排序
            $order = "store.stars {$distanc}";
        } else {
            $order = "store.views_day {$overall}";
        }

        $list = \PhalApi\DI()->notorm->merchant_store_product
            ->select("{$field},
                (
                        6378.138 * 2 * ASIN(
                            SQRT(
                                POW( SIN( ( {$lat} * PI( ) / 180 - lat * PI( ) / 180 ) / 2 ), 2 ) + COS( {$lat} * PI( ) / 180 ) * COS( lat * PI( ) / 180 ) * POW( SIN( ( {$lng}* PI( ) / 180 - lng * PI( ) / 180 ) / 2 ), 2 ) 
                            ) 
                        ) 
                    ) AS distance") // 获取字段
            ->alias('store_product') // 主表别名为store_product
            // 左关联另一张表another_table（不需要加表前缀）
            // 并起一个别名为B，关联条件是A.id = B.user_id
            ->leftJoin('merchant_store', 'store', 'store_product.store_id = store.id')
            ->where('top_type_id = ?',1)
            ->where('store_product.is_del', 0)
            ->where(['store_product.is_del = ?' => 0])
            ->where(['store_product.is_show = ?' => 1])
            ->where('store_product.store_id NOT', $storeIdArr)
            ->where($where)
            ->order($order);

        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        $list = $list->fetchAll();
        foreach ($list as $key => &$value) {
            $value['use_name'] = '';

            if (LANG == 'zh-cn') {
                $value['use_name'] = $value['name'] ?? '';
                $value['use_price'] = $value['price'] ?? '';
            } else if (LANG == 'th') {
                $value['use_name'] = $value['th_name'] ?? '';
                $value['use_price'] = $value['price'] ?? '';
            }
        }

        return $list;
    }
}