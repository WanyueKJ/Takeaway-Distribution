<?php

namespace Merchant\Domain;

use App\ApiException;
use Merchant\Domain\MerchantStore as MerchantStoreDomain;
use Merchant\Model\MerchantStoreProduct as MerchantStoreProductModel;
use Merchant\Model\MerchantStoreProductAttr as MerchantStoreProductAttrModel;
use Merchant\Model\MerchantStoreType as MerchantStoreTypeModel;
use Merchant\Model\MerchantType as MerchantTypeModel;
use Merchant\Domain\MerchantStore as MerchantStore;

/**
 * 店铺管理-商品规格管理
 */
class MerchantStoreProductAttr
{

    /**
     * 修改商品规格信息
     * @param ...$param
     * @return array
     */
    public function updateProductAttr(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        [$uid, $product_id, $attr] = $param;

        $loginInfo = \App\getcaches("merchant_token_{$uid}");
        $store_id = $loginInfo['store']['id'] ?? 0;
        if (!$store_id) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误');
            return $rs;
        }

        $MerchantStoreProductModel = new MerchantStoreProductModel();
        $product = $MerchantStoreProductModel
            ->getOne(['id = ?' => $product_id], 'id,name,th_name,price,recommend,type_id,store_type_id,store_id,image,repertory,max_repertory,day_repertory');
        if (!$product || ($product['store_id'] != $store_id)) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误');
            return $rs;
        }
        $attrArr = json_decode($attr, true);
        if (!$attrArr) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('参数错误');
            return $rs;
        }
        $MerchantStoreProductAttrModel = new MerchantStoreProductAttrModel();
        $MerchantStoreDomain = new MerchantStoreDomain();
        $storeInfo = $MerchantStoreDomain->getOne(['id = ?' => $store_id], 'top_type_id');
        if (!$storeInfo) throw new ApiException(\PhalApi\T('店铺信息错误'));

        \PhalApi\DI()->notorm->beginTransaction('db_master');
        try {
            foreach ($attrArr as $key => $value) {
                if ($storeInfo['top_type_id'] == 1) {
                    //美食规格价格都为0
                    $value['price'] = 0;
                }

                $updateData = [
                    'attr_name' => $value['attr_name'],
                   
                    'price' => $value['price'],
                ];
                $attrId = $value['id'];

                $update = $MerchantStoreProductAttrModel->updateOne(['id = ?' => $attrId], $updateData);
                foreach ($value['children'] ?? [] as $v) {
                    if ($storeInfo['top_type_id'] == 1) {
                        //美食规格价格都为0
                        $v['price'] = 0;
                    }

                    if(isset($v['id'])){
                        $sonUpdateData = [
                            'attr_name' => $v['attr_name'],
                         
                            'price' => $v['price'],
                        ];
                        $attrId = $v['id'];
                        $update = $MerchantStoreProductAttrModel->updateOne(['id = ?' => $attrId], $sonUpdateData);
                    }else{
                        $sonAttr = $MerchantStoreProductAttrModel->saveOne([
                            'product_id' => $product['id'],
                            'attr_name' => $v['attr_name'],
                          
                            'price' => $v['price'] ?? 0,
                            'is_main' => $v['is_main'] ?? 0,
                            'pid' => $attrId,
                            'level' => 2,
                        ]);
                    }
                }
            }

        } catch (\Exception $exception) {
            \PhalApi\DI()->notorm->rollback('db_master');
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T($exception->getMessage());
            return $rs;
        }
        \PhalApi\DI()->notorm->commit('db_master');


        $rs['msg'] = \PhalApi\T('修改成功');

        return $rs;
    }

    /**
     * 获取商品信息
     * @param ...$param
     * @return array
     */
    public function getProductAttr(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        [$uid, $id] = $param;
        $loginInfo = \App\getcaches("merchant_token_{$uid}");
        $store_id = $loginInfo['store']['id'] ?? 0;
        if (!$store_id) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误');
            return $rs;
        }

        $MerchantStoreProductModel = new MerchantStoreProductModel();
        $product = $MerchantStoreProductModel
            ->getOne(['id = ?' => $id], 'id,name,th_name,price,recommend,type_id,store_type_id,store_id,image,repertory,max_repertory,day_repertory');

        if (!$product || ($product['store_id'] != $store_id)) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误');
            return $rs;
        }

        $MerchantStoreProductAttrModel = new MerchantStoreProductAttrModel();
        $storeAttr = $MerchantStoreProductAttrModel->getTreeAttr($product['id'], "id,product_id,attr_name,price,th_price,level,is_main");
        $rs['info'][] = $storeAttr;
        return $rs;
    }

    /**
     * 删除规格信息
     * @param ...$param
     * @return array
     */
    public function deleteAttr(...$param)
    {
        $rs = ['code' => 0, 'msg' => \PhalApi\T('删除成功'), 'info' => []];
        [$uid, $id] = $param;
        $loginInfo = \App\getcaches("merchant_token_{$uid}");
        $store_id = $loginInfo['store']['id'] ?? 0;
        if (!$store_id) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误');
            return $rs;
        }

        $MerchantStoreProductAttrModel = new MerchantStoreProductAttrModel();
        $storeAttr = $MerchantStoreProductAttrModel->updateOne(['id = ? ' => $id],['is_del'=>1]);
        $rs['info'][] = $storeAttr;
        return $rs;
    }


    /**
     * 获取规格信息
     * @param ...$param
     * @return array
     */
    public function getAttr(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        [$uid, $id] = $param;
        $loginInfo = \App\getcaches("merchant_token_{$uid}");
        $store_id = $loginInfo['store']['id'] ?? 0;
        if (!$store_id) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误1');
            return $rs;
        }

        $MerchantStoreProductAttrModel = new MerchantStoreProductAttrModel();
        $storeAttr = $MerchantStoreProductAttrModel->getOne(['id = ? ' => $id]);
        $rs['info'][] = $storeAttr;
        return $rs;
    }

    public function addProductAttr(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        [$uid, $productId, $attr] = $param;

        $loginInfo = \App\getcaches("merchant_token_{$uid}");
        $store_id = $loginInfo['store']['id'] ?? 0;
        if (!$store_id) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误');
            return $rs;
        }

        $MerchantStoreDomain = new MerchantStoreDomain();
        $storeInfo = $MerchantStoreDomain->getOne(['id = ?' => $store_id], 'top_type_id');
        if (!$storeInfo) throw new ApiException(\PhalApi\T('店铺信息错误'));

        $attrArr = json_decode($attr, true);
        if (!$attrArr) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('商品规格错误');
            return $rs;
        }

        $MerchantStoreProductAttrModel = new MerchantStoreProductAttrModel();

        \PhalApi\DI()->notorm->beginTransaction('db_master');
        try {
            foreach ($attrArr as $key => $value) {
                $children = $value['children'] ?? [];
                if ($storeInfo['top_type_id'] == 1) {
                    //美食规格价格都为0
                    $value['price'] = 0;
                }

                $parentAttr = $MerchantStoreProductAttrModel->saveOne([
                    'product_id' => $productId,
                    'attr_name' => $value['attr_name'],
                    'price' => $value['price'] ?? 0,
                    'is_main' => $value['is_main'] ?? 0,
                    'is_del' =>0,
                    'pid' => 0,
                    'level' => 1,
                ]);

                foreach ($children as $key2 => $value2) {
                    if ($storeInfo['top_type_id'] == 1) {
                        //美食规格价格都为0
                        $value2['price'] = 0;
                    }

                    if(isset($value2['id'])){
                        $sonAttr = $MerchantStoreProductAttrModel->updateOne(['id = ?'=>$value2['id']],[
                            'attr_name' => $value2['attr_name'],
                            'is_del' =>0,
                            'price' => $value2['price'] ?? 0,
                            'is_main' => $value2['is_main'] ?? 0,
                        ]);
                    }else{
                        $sonAttr = $MerchantStoreProductAttrModel->saveOne([
                            'product_id' => $productId,
                            'attr_name' => $value2['attr_name'],
                            'is_del' =>0,
                            'price' => $value2['price'] ?? 0,
                            'is_main' => $value2['is_main'] ?? 0,
                            'pid' => $parentAttr,
                            'level' => 2,
                        ]);
                    }

                }
            }
        } catch (\Exception $e) {
            \PhalApi\DI()->notorm->rollback('db_master');
            $rs['code'] = 995;
            $rs['msg'] = $e->getMessage();
            return $rs;
        }

        \PhalApi\DI()->notorm->commit('db_master');
        $rs['msg'] = \PhalApi\T('添加成功');
        return $rs;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {

        $MerchantStoreProductModel = new MerchantStoreProductModel();
        // TODO: Implement __call() method.
        return call_user_func_array([$MerchantStoreProductModel, $name], $arguments);
    }
}