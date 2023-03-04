<?php

namespace Merchant\Model;

use PhalApi\Model\NotORMModel as NotORM;

/**
 * 店铺管理-商品管理
 */
class MerchantStoreProductAttr extends NotORM
{
    public function saveOne($data)
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_product_attr
            ->insert($data);
        return $rs;
    }

    /**
     * 获取当前商品的所有规格下
     * @param $id
     * @param $level
     * @param $target
     * @return array|mixed
     */
    public function getTreeAttr($id,$field ="*")
    {
        $attr = $list = \PhalApi\DI()->notorm->merchant_store_product_attr
            ->select($field)
            ->where(['product_id = ?' => $id])
            ->where(['level = ?' => 1])
            ->order("is_main DESC")
            ->where(['is_del = ?'=>0])
            ->fetchAll();

        foreach ($attr as $key => &$value) {
            $value['use_attr_name'] = '';
            $value['use_price'] = '0.00';

            if (LANG == 'zh-cn') {
                $value['use_attr_name'] = $value['attr_name'] ?? '';
                $value['use_price'] = $value['price'] ?? '';
            } else if (LANG == 'th') {
                $value['use_attr_name'] = $value['th_attr_name'] ?? '';
                $value['use_price'] = $value['price'] ?? '';
            }

            $value['children'] = $this->getTree($value['id']);


        }
        return $attr;
    }

    public function selectList(array $where, $field = '*', $order = 'id ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_store_product_attr
            ->select($field)
            ->order($order)
            ->where(['is_del = ?'=>0])
            ->where($where);
        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        return $list->fetchAll();
    }

    /**
     * 更新数据
     * @param $where
     * @param $field
     * @return mixed
     */
    public function updateOne($where, $update = [])
    {
        return \PhalApi\DI()->notorm->merchant_store_product_attr
            ->where($where)
            ->update($update);
    }

    /**
     * 获取当前ID下的所有子级
     * @param $id
     * @param $level
     * @param $target
     * @return array|mixed
     */
    public function getTree($id, $level = 3, $target = [])
    {
        $list = \PhalApi\DI()->notorm->merchant_store_product_attr
            ->select('*')
            ->where(['pid = ?' => $id])
            ->where(['level <= ?' => $level])
            ->where(['is_del = ?'=>0])
            ->order("is_main DESC")
            ->fetchAll();

        foreach ($list as $key => &$value) {
            $value['use_price'] = '0.00';

            if (LANG == 'zh-cn') {
                $value['use_attr_name'] = $value['attr_name'] ?? '';
                $value['use_price'] = $value['price'] ?? '';
            } else if (LANG == 'th') {
                $value['use_attr_name'] = $value['th_attr_name'] ?? '';
                $value['use_price'] = $value['price'] ?? '';
            }

            $target[] = $value;
            $target = $this->getTree($value['id'], $level, $target);
        }
        return $target;
    }

    public function getOne(array $where, $field = '*')
    {
        $info = \PhalApi\DI()->notorm->merchant_store_product_attr
            ->select($field)
            ->where($where)
            ->where(['is_del = ?'=>1])
            ->fetchOne();
        if ($info) {
            if (LANG == 'zh-cn') {
                $info['use_attr_name'] = $info['attr_name'] ?? '';
                $info['use_price'] = $info['price'] ?? '';
            } else if (LANG == 'th') {
                $info['use_attr_name'] = $info['th_attr_name'] ?? '';
                $info['use_price'] = $info['price'] ?? '';
            }
        }
        return $info;
    }

    public function deleteOne(array $where)
    {
        $info = \PhalApi\DI()->notorm->merchant_store_product_attr
            ->where($where)
            ->delete();

        return $info;
    }
}