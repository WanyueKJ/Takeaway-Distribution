<?php

namespace Merchant\Domain;

use Merchant\Model\MerchantStoreType as MerchantStoreTypeModel;
use PhalApi\Model\NotORMModel as NotORM;

/**
 * 店铺分类
 */
class MerchantStoreType extends NotORM
{

    public function getList(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        [$uid, $id, $is_tree, $level] = $param;

        $loginInfo = \App\getcaches("merchant_token_{$uid}");
        $store_id = $loginInfo['store']['id'] ?? 0;
        if (!$store_id) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误');
            return $rs;
        }
        if (!$level) {
            $level = 1;
        }
        $where['level <= ?'] = $level;
        if ($id > 0) {
            $where['pid = ?'] = $id;
        }
        $where['store_id = ?'] = $store_id;
        $res = $this->selectList($where, 'id,name,pid,thumb');
        foreach ($res as $key => &$value) {
            $value['thumb'] = \App\get_upload_path($value['thumb'] ?? '');
        }
        if ($is_tree) {
            $res = \App\get_tree_children($res);
        }
        return $res;
    }


    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {

        $MerchantStoreTypeModel = new MerchantStoreTypeModel();
        // TODO: Implement __call() method.
        return call_user_func_array([$MerchantStoreTypeModel, $name], $arguments);
    }
}