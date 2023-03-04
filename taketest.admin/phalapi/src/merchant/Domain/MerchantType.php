<?php

namespace Merchant\Domain;

use Merchant\Model\MerchantType as MerchantTypeModel;

/**
 * 店铺分类
 */
class MerchantType
{


    public function getSelectList(...$param)
    {

        [$uid, $id, $is_tree, $level] = $param;
        if (!$level) {
            $level = 1;
        }
        $where['level <= ?'] = $level;
        if ($id > 0) {
            $where['pid = ?'] = $id;
        }
    
        $res = $this->notInIdselectList('', $where, 'id,name,pid,level');

        if ($is_tree) {
            $res = \App\get_tree_children($res);
        }
        return $res;
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
     * @param $level
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