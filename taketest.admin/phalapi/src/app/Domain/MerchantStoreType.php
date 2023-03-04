<?php

namespace App\Domain;

use App\Domain\MerchantStoreProduct as MerchantStoreProductDomain;
use App\Model\MerchantStore as MerchantStoreModel;
use App\Model\MerchantStoreType as MerchantStoreTypeModel;

/**
 * 店铺分类
 */
class MerchantStoreType
{


    /**
     * 获取服务店铺所有分类几商品
     * @return void
     */
    public function getAllServetList(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        [$uid, $store_id] = $param;

        $MerchantStoreModel = new MerchantStoreModel();
        $field = 'id,name,operating_state,lng,lat,stars,remark,open_date,open_time,address';
        $detail = $MerchantStoreModel->getOne(['id = ?' => $store_id], $field);
        if (!$detail) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误!');
            return $rs;
        }

//        $where['pid = ?'] = 0;
        $where['store_id = ?'] = $store_id;

        $res = $this->selectList($where, 'id,name,store_id');
        array_unshift($res, ['id' => 0, 'name' => \PhalApi\T('热门'), 'use_name' => \PhalApi\T('热门')]);
        $MerchantStoreProductDomain = new MerchantStoreProductDomain();

        foreach ($res as $key => &$value) {
            $product = $MerchantStoreProductDomain->getAllListByServe($uid, $store_id, $value['id']);
            if(count($product['info'][0] ?? []) <= 0){
                unset($res[$key]);
                continue;
            }
            $value['product'] = $product['info'][0] ?? [];
        }
        return array_values($res);
    }

    /**
     * 获取服务店铺分类
     * @return void
     */
    public function getServetList(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        [$uid, $store_id] = $param;

        $MerchantStoreModel = new MerchantStoreModel();
        $field = 'id,name,operating_state,lng,lat,stars,remark,open_date,open_time,address';
        $detail = $MerchantStoreModel->getOne(['id = ?' => $store_id], $field);
        if (!$detail) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误!');
            return $rs;
        }

//        $where['pid = ?'] = 0;
        $where['store_id = ?'] = $store_id;

        $res = $this->selectList($where, 'id,name');
        array_unshift($res, ['id' => 0, 'name' => \PhalApi\T('推荐'), 'use_name' => \PhalApi\T('推荐'), ]);
        return $res;
    }

    /**
     * 获取店铺分类
     * @param ...$param
     * @return array
     */
    public function getList(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        [$uid, $id, $store_id, $is_tree, $level] = $param;

        if (!$level) {
            $level = 1;
        }
        $where['level <= ?'] = $level;
        if ($id > 0) {
            $where['pid = ?'] = $id;
        }
        $where['store_id = ?'] = $store_id;
        $res = $this->selectList($where, 'id,name,pid,thumb,level');
        foreach ($res as $key => &$value) {
            $value['thumb'] = \App\get_upload_path($value['thumb']);
        }
        if ($is_tree) {
            $res = \App\get_tree_children($res);
        }
        $rs['info'][0] = $res;
        return $rs;
    }

    /**
     * 获取美食店铺商品分类
     * @param ...$param
     * @return array
     */
    public function getStoreTypeList(...$param)
    {
        [$uid, $id] = $param;
        $MerchantStoreTypeModel = new MerchantStoreTypeModel();

        $list = $MerchantStoreTypeModel->selectList(['store_id = ?' => $id], 'id,name');
        array_walk($list, function (&$value, $index) {
            unset($value['name']);
          
        });
        array_unshift($list, ['id' => '0', 'use_name' => \PhalApi\T('人气推荐')]);
        return $list;
    }

    /**
     * 获取美食店铺商品分类
     * @param ...$param
     * @return array
     */
    public function getStoreTypeList2(...$param)
    {
        [$uid, $id, $where] = $param;
        $MerchantStoreTypeModel = new MerchantStoreTypeModel();
        
        $list = $MerchantStoreTypeModel->selectList(array_merge(['store_id = ?' => $id],$where), 'id,name');
        array_walk($list, function (&$value, $index) {
            unset($value['name']);
          
        });
        array_unshift($list, ['id' => '0', 'use_name' => \PhalApi\T('人气推荐')]);
        return $list;
    }


    /**
     * 如果有子级分类 则返回包括子级所有的分类
     * @return array
     */
    public function getSelfAndChildren($id)
    {
        $MerchantStoreTypeModel = new MerchantStoreTypeModel();
        $self = $MerchantStoreTypeModel->getOne(['id = ?' => $id]);
        if (!$self) {
            return [];
        }
        $sonList = $MerchantStoreTypeModel->getTree($id);

        array_unshift($sonList, $self);
        return $sonList;
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