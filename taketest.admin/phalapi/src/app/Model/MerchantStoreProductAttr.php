<?php

namespace App\Model;

class MerchantStoreProductAttr
{
    public function getOne(array $where, $field = '*')
    {
        $info = \PhalApi\DI()->notorm->merchant_store_product_attr
            ->select($field)
            ->where($where)
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


    public function idInSelectList($idArr, array $where = [], $field = '*')
    {
        $list = \PhalApi\DI()->notorm->merchant_store_product_attr
            ->select($field)
            ->where($where)
            ->where('id', $idArr)
            ->where(['is_del = ?'=>0])
            ->fetchAll();
        foreach ($list as $key =>&$value){
            if (LANG == 'zh-cn') {
                $value['use_attr_name'] = $value['attr_name'] ?? '';
                $value['use_price'] = $value['price'] ?? '';
            } else if (LANG == 'th') {
                $value['use_attr_name'] = $value['th_attr_name'] ?? '';
                $value['use_price'] = $value['price'] ?? '';
            }
        }
        return $list;
    }

    public function selectList(array $where = [], $field = '*')
    {
        $list = \PhalApi\DI()->notorm->merchant_store_product_attr
            ->select($field)
            ->where($where)
            ->where(['is_del = ?'=>0])
            ->fetchAll();
        foreach ($list as $key =>&$value){
            if (LANG == 'zh-cn') {
                $value['use_attr_name'] = $value['attr_name'] ?? '';
                $value['use_price'] = $value['price'] ?? '';
            } else if (LANG == 'th') {
                $value['use_attr_name'] = $value['th_attr_name'] ?? '';
                $value['use_price'] = $value['price'] ?? '';
            }
        }
        return $list;
    }
}