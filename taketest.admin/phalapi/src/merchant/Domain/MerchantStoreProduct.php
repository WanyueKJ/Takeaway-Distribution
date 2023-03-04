<?php

namespace Merchant\Domain;

use App\ApiException;
use Merchant\Domain\MerchantStore as MerchantStoreDomain;
use Merchant\Model\MerchantStoreProduct as MerchantStoreProductModel;
use Merchant\Model\MerchantStoreProductAttr as MerchantStoreProductAttrModel;
use Merchant\Model\MerchantStoreType as MerchantStoreTypeModel;
use Merchant\Model\MerchantType as MerchantTypeModel;

/**
 * 店铺管理-商品管理
 */
class MerchantStoreProduct
{


    /**
     * 商品搜索
     * @param $uid
     * @param $keywords
     * @param $p
     * @return array
     * @throws ApiException
     */
    public function getSearchList($uid, $keywords, $p)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        $store = $this->checkStoreIdentity($uid);
        $storeId = $store['id'];
        $MerchantStoreProductModel = new MerchantStoreProductModel();
        $where  = ['store_id = ?' => $storeId];

        if ($keywords != '') {
            if(LANG == 'zh-cn'){
                $where['name LIKE ?'] =  "%{$keywords}%";
            }
        }

        $productList = $MerchantStoreProductModel
            ->selectList($where, 'id,is_show,name,price,recommend,type_id,store_type_id,image,repertory,max_repertory,sales,day_repertory', 'id DESC', $p, 20);

        foreach ($productList as &$value) {
            $image = json_decode($value['image'], true);
            array_walk($image, function (&$value, $key) {
                $value = \App\get_upload_path($value);
            });
            $value['image'] = $image[0] ?? '';
        }
        $rs['info'][] = $productList;

        return $rs;
    }

    public function getList($uid, $store_type_id, $p)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        $store = $this->checkStoreIdentity($uid);
        $storeId = $store['id'];
        $MerchantStoreProductModel = new MerchantStoreProductModel();

        if ($store_type_id > 0) {
            $productList = $MerchantStoreProductModel
                ->selectList(['store_type_id = ?' => $store_type_id, 'store_id = ?' => $storeId,'is_del = ?'=>0], 'id,is_show,name,price,recommend,type_id,store_type_id,image,repertory,max_repertory,sales,day_repertory', 'id DESC', $p, 20);

        } else {
            $productList = $MerchantStoreProductModel
                ->selectList(['recommend = ?' => 1, 'store_id = ?' => $storeId,'is_del = ?'=>0], 'id,name,price,is_show,recommend,type_id,store_type_id,image,repertory,max_repertory,sales,day_repertory', 'id DESC', $p, 20);
        }

        foreach ($productList as &$value) {
            $image = json_decode($value['image'], true);
            array_walk($image, function (&$value, $key) {
                $value = \App\get_upload_path($value);
            });
            $value['image'] = $image[0] ?? '';

        }
        $rs['info'][] = $productList;

        return $rs;
    }


    /**
     * 检测用户身份
     * @param $uid
     * @return array
     * @throws ApiException
     */
    public function checkStoreIdentity($uid)
    {
        $loginInfo = \App\getcaches("merchant_token_{$uid}");
        $store_id = $loginInfo['store']['id'] ?? 0;
        if (!$store_id) {
            throw new ApiException(\PhalApi\T('店铺信息错误'), 995);
        }
        return $loginInfo['store'] ?? [];
    }

    /**
     * 修改商品信息
     * @param ...$param
     * @return array
     */
    public function updateProduct(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        [$uid, $id, $image, $name, $price, $type_id, $store_type_id, $recommend, $repertory, $des,$max_repertory, $day_repertory,$has_attr] = $param;
     
        $loginInfo = \App\getcaches("merchant_token_{$uid}");
        $store_id = $loginInfo['store']['id'] ?? 0;
        if (!$store_id) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误');
            return $rs;
        }
      
        $MerchantStoreTypeModel = new MerchantStoreTypeModel();
        $storeType = $MerchantStoreTypeModel->getOne(['id = ?' => $store_type_id]);

        if (!$storeType || (($storeType['store_id'] ?? 0) != $store_id)) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺分类参数错误');
            return $rs;
        }

        $MerchantTypeModel = new MerchantTypeModel();
        $merchantType = $MerchantTypeModel->getOne(['id = ?' => $type_id]);
        if (!$merchantType) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺类型参数错误');
            return $rs;
        }

        $MerchantStoreProductModel = new MerchantStoreProductModel();
        $product = $MerchantStoreProductModel
            ->getOne(['id = ?' => $id], 'id,name,price,recommend,type_id,store_type_id,store_id,image,repertory,max_repertory,day_repertory');
        if (!$product || ($product['store_id'] != $store_id)) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误');
            return $rs;
        }
        $image = json_decode($image, true) ?: [];
        if (!$image) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('商品图片格式错误');
            return $rs;
        }

        if (!$name ) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('商品名称不能为空');
            return $rs;
        }

        if (!$price) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('商品金额不能为空');
            return $rs;
        }

        if (!$type_id) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('请选择平台分类');
            return $rs;
        }

        if (!$store_type_id) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('请选择店铺分类');
            return $rs;
        }
        if (!in_array((int)$recommend, [0, 1], true)) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('参数错误');
            return $rs;
        }
        if (!in_array((int)$day_repertory, [0, 1], true)) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('参数错误');
            return $rs;
        }
        $updateData = [
            'image' => json_encode($image),
            'name' => $name,
            
            'type_id' => $type_id,
            'store_id' => $store_id,
            'store_type_id' => $store_type_id,
            'recommend' => (int)$recommend,
            'des' => $des,
            'day_repertory' => (int)$day_repertory,
        ];
        if ($price) {
            $updateData['price'] = $price;
        }

        if ($repertory) {
            $updateData['repertory'] = $repertory;
        }
        if ($max_repertory) {
            $updateData['max_repertory'] = $max_repertory;
        }
        if ($max_repertory) {
            $updateData['max_repertory'] = $max_repertory;
        }

        $MerchantStoreProductModel = new MerchantStoreProductModel();
        $update = $MerchantStoreProductModel->updateOne(['id = ?' => $id], $updateData);
        $MerchantStoreDomain = new MerchantStoreDomain();
        $MerchantStoreDomain->updatePutaway($store_id);
        if ($has_attr == 1) {
            //无规格商品 清除商品规格
            $MerchantStoreProductAttrModel = new MerchantStoreProductAttrModel();
            $MerchantStoreProductAttrModel->updateOne(['product_id = ?' => $id], ['is_del' => 1]);
        }
        $rs['msg'] = \PhalApi\T('修改成功');
        return $rs;
    }

    /**
     * 获取商品信息
     * @param ...$param
     * @return array
     */
    public function getProduct(...$param)
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
            ->getOne(['id = ?' => $id], 'id,name,price,recommend,type_id,store_type_id,store_id,image,repertory,max_repertory,day_repertory,des');

        if (!$product || ($product['store_id'] != $store_id)) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误');
            return $rs;
        }

        $MerchantStoreProductAttrModel = new MerchantStoreProductAttrModel();
        $MerchantStoreTypeModell = new MerchantStoreTypeModel();
        $MerchantTypeModel = new MerchantTypeModel();

        $storeAttr = $MerchantStoreProductAttrModel->getTreeAttr($product['id']);
       
        $product['attr'] = $storeAttr;
        $product['top_store_type_name'] = $MerchantStoreTypeModell->getTopName($product['store_type_id']);
        $product['top_type_name'] = $MerchantTypeModel->getTopName($product['type_id']);
        $imageArr = json_decode($product['image'], true);
        array_walk($imageArr, function ($value, $index) use (&$imageArr) {
            $imageArr[$index] = \App\get_upload_path($value);
        });
        $product['image'] = $imageArr;
        $rs['info'][] = $product;
        return $rs;
    }

    public function addProduct(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        [$uid, $image, $name,  $price, $type_id, $store_type_id, $recommend, $repertory, $max_repertory, $day_repertory, $attr, $des] = $param;

        $loginInfo = \App\getcaches("merchant_token_{$uid}");
        $store_id = $loginInfo['store']['id'] ?? 0;
        if (!$store_id) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误');
            return $rs;
        }
        if (!json_decode($image, true)) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('商品图片格式错误');
            return $rs;
        }
        if (!$name ) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('商品名称不能为空');
            return $rs;
        }
        if (!$price) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('商品金额不能为空');
            return $rs;
        }
        if (!$type_id) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('请选择平台分类');
            return $rs;
        }
        if (!$store_type_id) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('请选择店铺分类');
            return $rs;
        }
        if ($recommend != 1) {
            $recommend = 0;
        }
        if ($day_repertory != 1) {
            $day_repertory = 0;
        }
        $attrArr = json_decode($attr, true) ?: [];

        $MerchantStoreDomain = new \App\Domain\MerchantStore();
        $storeInfo = $MerchantStoreDomain->getOne(['id = ?' => $store_id], 'top_type_id');
        if (!$storeInfo) throw new ApiException(\PhalApi\T('店铺不存在'));
        if($storeInfo['top_type_id'] == 3){
            if(!$attrArr) throw new ApiException(\PhalApi\T('最少添加一个规格'));
            foreach ($attrArr as $v){
                if(count($v['children'] ?: []) <= 0){
                    throw new ApiException(\PhalApi\T('必须添加子规格'));
                }
            }
        }

        $MerchantStoreTypeModel = new MerchantStoreTypeModel();
        $storeType = $MerchantStoreTypeModel->getOne(['id = ?' => $store_type_id]);
       
        if (!$storeType || (($storeType['store_id'] ?? 0) != $store_id)) {
            throw new ApiException(\PhalApi\T('店铺分类参数错误'));
        }

        $MerchantTypeModel = new MerchantTypeModel();
        $merchantType = $MerchantTypeModel->getOne(['id = ?' => $type_id]);
        if (!$merchantType) throw new ApiException(\PhalApi\T('店铺类型参数错误'));

        $installData = [
            'image' => $image,
            'name' => $name,
       
            'price' => $price,
            'type_id' => $type_id,
            'store_id' => $store_id,
            'store_type_id' => $store_type_id,
            'recommend' => $recommend,
            'repertory' => $repertory,
            'max_repertory' => $max_repertory,
            'day_repertory' => $day_repertory,
            'des' => $des,
            'add_time' => time(),
        ];
        $MerchantStoreProductModel = new MerchantStoreProductModel();
        $MerchantStoreProductAttrModel = new MerchantStoreProductAttrModel();
        $MerchantStoreDomain = new MerchantStoreDomain();

        \PhalApi\DI()->notorm->beginTransaction('db_master');

        try {
            $productId = $MerchantStoreProductModel->saveOne($installData);
            foreach ($attrArr as $key => $value) {
                $children = $value['children'] ?? [];
                $parentAttr = $MerchantStoreProductAttrModel->saveOne([
                    'product_id' => $productId,
                    'attr_name' => $value['attr_name'],
                    'th_attr_name' => $value['th_attr_name'],
                    'price' => $value['price'],
                    'pid' => 0,
                    'level' => 1,
                ]);

                foreach ($children as $key2 => $value2) {
                    $sonAttr = $MerchantStoreProductAttrModel->saveOne([
                        'product_id' => $productId,
                        'attr_name' => $value2['attr_name'],
                        'th_attr_name' => $value2['th_attr_name'],
                        'price' => $value2['price'],
                        'pid' => $parentAttr,
                        'level' => 2,
                    ]);
                }
            }

        } catch (\Exception $e) {
            \PhalApi\DI()->notorm->rollback('db_master');
            $rs['code'] = 995;
            $rs['msg'] = $e->getMessage();
            return $rs;
        }
        \PhalApi\DI()->notorm->commit('db_master');

        $MerchantStoreDomain->updatePutaway($store_id);
        $rs['msg'] = \PhalApi\T('添加成功');
        return $rs;
    }

    /**
     * 修改状态
     * @param ...$param
     * @return void
     */
    public function changeStatus(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        [$uid, $id, $is_del, $is_show] = $param;

        $loginInfo = \App\getcaches("merchant_token_{$uid}");
        $store_id = $loginInfo['store']['id'] ?? 0;
        if (!$store_id) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误');
            return $rs;
        }

        $MerchantStoreProductModel = new MerchantStoreProductModel();
        $product = $MerchantStoreProductModel
            ->getOne(['id = ?' => $id], 'id,name,price,recommend,type_id,store_type_id,store_id,image,repertory,max_repertory,day_repertory');

        if (!$product || ($product['store_id'] != $store_id)) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('操作成功');
            return $rs;
        }

        if (!in_array((int)$is_del, [0, 1], true)) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('参数错误');
            return $rs;
        }
        if (!in_array((int)$is_show, [0, 1], true)) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('参数错误');
            return $rs;
        }

        $updateData = [];
        if ($is_del != '') {
            $updateData['is_del'] = (int)$is_del;
        }
        if ($is_show != '') {
            $updateData['is_show'] = (int)$is_show;
        }
        $MerchantStoreProductModel = new MerchantStoreProductModel();
        $update = $MerchantStoreProductModel->updateOne(['id = ?' => $id], $updateData);
        $MerchantStoreDomain = new MerchantStoreDomain();
        $MerchantStoreDomain->updatePutaway($store_id);

        if ($is_del != '') {
            $MerchantStoreProductAttrModel = new MerchantStoreProductAttrModel();
            $MerchantStoreProductAttrModel->updateOne(['product_id = ?' => $id], ['is_del' => $is_del]);
        }

        $rs['msg'] = \PhalApi\T('操作成功');
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