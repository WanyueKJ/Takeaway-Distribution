<?php

namespace App\Domain;
use App\Model\MerchantStoreProductAttr as MerchantStoreProductAttrModel;
/**
 * 商品规格
 */
class MerchantStoreProductAttr
{



    /**
     * 获取当前商品的所有规格下
     * @param $id
     * @return array|mixed
     */
    public function getTreeAttr($id, $field = "*")
    {
        $attr = $list = \PhalApi\DI()->notorm->merchant_store_product_attr
            ->select($field)
            ->where(['product_id = ?' => $id])
            ->where(['level = ?' => 1])
            ->where(['pid = ?' => 0])
            ->where(['is_del = ?'=>0])
            ->fetchAll();

        foreach ($attr as $key => &$value) {
            $value['use_attr_name'] = '';
            $value['use_price'] = '0.00';

            if (LANG == 'zh-cn') {
                $value['use_attr_name'] = $value['attr_name'];
                $value['use_price'] = $value['price'] ?? '';
            } else if (LANG == 'th') {
                $value['use_attr_name'] = $value['th_attr_name'];
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
            ->select('id,product_id,attr_name,th_attr_name,price,pid,level')
            ->where(['pid = ?' => $id])
            ->where(['level <= ?' => $level])
        
            ->fetchAll();

        foreach ($list as $key => &$value) {
            $value['use_attr_name'] = '';
            $value['use_price'] = '0.00';
            if (LANG == 'zh-cn') {
                $value['use_attr_name'] = $value['attr_name'];
                $value['use_price'] = $value['price'] ?? '';
            } else if (LANG == 'th') {
                $value['use_attr_name'] = $value['th_attr_name'];
                $value['use_price'] = $value['price'] ?? '';
            }

            $target[] = $value;
            $target = $this->getTree($value['id'], $level, $target);
        }
        return $target;
    }





    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {

        $MerchantStoreProductAttrModel = new MerchantStoreProductAttrModel();
        // TODO: Implement __call() method.
        return call_user_func_array([$MerchantStoreProductAttrModel, $name], $arguments);
    }
}