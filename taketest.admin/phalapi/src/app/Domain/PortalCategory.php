<?php

namespace App\Domain;

use App\Model\PortalCategory as PortalCategoryModel;

/**
 * 文章分类
 */
class PortalCategory
{


    /**
     * 分类列表
     * @param $where
     * @param $field
     * @param $p
     * @param $limit
     * @return mixed
     */
    public function getList($where, $field, $p = 1, $limit = 20)
    {
        $PortalCategoryModel = new PortalCategoryModel();
        $list = $PortalCategoryModel->selectList($where, $field, 'id ASC', $p, $limit);
        foreach ($list as $key => &$value) {
            $value['thumb'] = \App\get_upload_path(json_decode($value['more'], true)['thumbnail'] ?? '');
            unset($value['more']);
        }

        return $list;
    }


    /**
     * 分类下文章列表
     * @param $where
     * @param $field
     * @param $p
     * @param $limit
     * @return mixed
     */
    public function getPostList($categoryId, $where, $field, $p = 1, $limit = 20)
    {
        $PortalCategoryModel = new PortalCategoryModel();
        $list = $PortalCategoryModel->selectPostList($categoryId, $where, $field, 'p.id ASC', $p, $limit);
        foreach ($list as $key => &$value) {
            $value['thumbnail'] = \App\get_upload_path($value['thumbnail']);
            $value['update_time'] = date('Y-m-d',$value['update_time']);
            $value['href'] = \App\get_upload_path('/appapi/help/detail?id=' . $value['id']);
        }
        return $list;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {

        $MerchantStoreProductModel = new PortalCategoryModel();
        // TODO: Implement __call() method.
        return call_user_func_array([$MerchantStoreProductModel, $name], $arguments);
    }
}