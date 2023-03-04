<?php

namespace App\Domain;

use App\Model\MerchantType as MerchantTypeModel;

/**
 * 店铺分类
 */
class MerchantType
{


    public function getList($where, $field = '*')
    {
        $MerchantTypeModel = new MerchantTypeModel();
        $list = $MerchantTypeModel->selectList($where, $field);
        foreach ($list as $key => &$value) {
            $value['thumb'] = \App\get_upload_path($value['thumb']);
        }
        return $list;
    }


    /**
     * 如果有子级分类 则返回包括子级所有的分类
     * @return array
     */
    public function getSelfAndChildren($id)
    {
        $MerchantTypeModel = new MerchantTypeModel();
        $self = $MerchantTypeModel->getOne(['id = ?' => $id]);
        if (!$self) {
            return [];
        }
        $sonList = $MerchantTypeModel->getTree($id);

        array_unshift($sonList, $self);
        return $sonList;
    }

    /**
     * 返回子级分类
     * @param $id
     * @param $level 层级条件(全局)
     * @return array|mixed
     */
    public function getChildren($id, $level)
    {
        $MerchantTypeModel = new MerchantTypeModel();
        $self = $MerchantTypeModel->getOne(['id = ?' => $id]);
        if (!$self) {
            return [];
        }
        $sonList = $MerchantTypeModel->getTree($id, $level);

        foreach ($sonList as $key => &$value) {
            $value['thumb'] = \App\get_upload_path($value['thumb']);
            $value['background'] = \App\get_upload_path($value['background']);
            unset($value['list_order']);
            unset($value['pid']);

        }
        return $sonList;
    }

    /**
     * 返回子级分类
     * @param $id
     * @param $level 层级条件(表示查询自己分类下的几级)
     * @return array|mixed
     */
    public function getOwnChildren($id, $level)
    {
        $MerchantTypeModel = new MerchantTypeModel();
        $self = $MerchantTypeModel->getOne(['id = ?' => $id]);
        if (!$self) {
            return [];
        }
        $level = $self['level'] + $level;

        $sonList = $MerchantTypeModel->getTree($id, $level);

        foreach ($sonList as $key => &$value) {
            $value['thumb'] = \App\get_upload_path($value['thumb']);
            $value['background'] = \App\get_upload_path($value['background']);
            unset($value['list_order']);
            unset($value['pid']);

        }
        return $sonList;
    }


    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {

        $MerchantTypeModel = new MerchantTypeModel();
        // TODO: Implement __call() method.
        return call_user_func_array([$MerchantTypeModel, $name], $arguments);
    }
}