<?php

namespace App\Domain;

use App\ApiException;
use App\Domain\MerchantStoreType as MerchantStoreTypeDomain;
use App\Domain\MerchantType as MerchantTypeDomain;
use App\Domain\MerchantStore as MerchantStoreDomain;
use App\Model\MerchantStore as MerchantStoreModel;
use App\Model\MerchantStoreProduct as MerchantStoreProductModel;
use App\Domain\MerchantStoreProduct as MerchantStoreProductDomain;
use App\Domain\MerchantStoreProductReply as MerchantStoreProductReplyDomain;
use App\Model\MerchantStoreCart as MerchantStoreCartModel;
use App\Domain\MerchantStoreProductAttr as MerchantStoreProductAttrDomain;
use App\Domain\MerchantStoreOrder as MerchantStoreOrderDomain;
use App\Model\MerchantType as MerchantTypeModel;

/**
 * Class MerchantStoreProduct
 * @package App\Domain\MerchantStoreProduct
 * @method test(array $where) 测试
 */
class  MerchantStoreProduct
{


    /**
     * 重新计算商品的点评数,评价
     * @param $product_id
     * @return void
     */
    public function updateSales($product_id)
    {
        $field = 'id,sales,starts,good_starts';
        $detail = $this->getOne(['id = ?' => $product_id], $field);
        if ($detail) {
            $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
            $MerchantStoreProductReply = new MerchantStoreProductReply();
            //销量(订单状态已完成的商品数量)
            $sales = $MerchantStoreOrderDomain->getCountOne(['product_id = ?' => $product_id, 'status = ?' => 4],'sum(total_num) as total_num')['total_num'] ?? 0;
            //总评价
            $starts = $MerchantStoreProductReply->getCount(['product_id = ?' => $product_id]);
            //总好评价
            $good_starts = $MerchantStoreProductReply->getCount(['product_id = ?' => $product_id, 'overall_star = ?' => 5]);

            $update = [
                'sales' => $sales,
                'starts' => $starts,
                'good_starts' => $good_starts,
            ];
            $this->updateOne(['id = ?' => $product_id], $update);

        }
    }

    /**
     * 订单生成后超市商品减库存
     * @param $product_id
     * @param $repertory
     * @return void
     */
    public function inventoryReduction($product_id, $repertory)
    {
        $product = $this->getOne(['id = ?' => $product_id], 'id,store_id,repertory,type_id');
        if (!$product) {
            throw new ApiException('商品不存在');
        }
        $MerchantTypeDomain = new MerchantTypeDomain();
        $top_type_id = $MerchantTypeDomain->getTopTree($product['type_id'])['id'] ?? 0;
        if(!$top_type_id){
            throw new ApiException(\PhalApi\T('商品类型错误!'));
        }
        if (in_array($top_type_id, [5,6,7])) {
            $this->updateOne(['id = ?' => $product['id']], ['repertory' => ($product['repertory'] - $repertory)]);
        }
    }

    /**
     * 服务商品(无分页)
     * @param ...$param
     * @return array
     */
    public function getAllListByServe(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        [$uid, $store_id, $store_type_id] = $param;
        $MerchantStoreDomain = new MerchantStoreDomain();
        $store = $MerchantStoreDomain->getOne($store_id, 'id');
        if (!$store) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误');
            return $rs;
        }
        $where['store_id = ?'] = $store_id;

        if ($store_type_id == 0) {//推荐
            $where['recommend = ?'] = 1;
            $list = $this->selectList($where, 'id,name,price,image,des', 'list_order ASC');
        } else {
            $where['store_type_id = ?'] = $store_type_id;
            $list = $this->selectList($where, 'id,name,price,image,des', 'list_order ASC');
        }

        foreach ($list as $key => &$value) {
            $image = json_decode($value['image'], true);
            array_walk($image, function (&$value, $key) {
                $value = \App\get_upload_path($value);
            });
            $value['image'] = $image[0] ?? '';
        }
        $rs['info'][] = $list;
        return $rs;
    }

    /**
     * 服务商品(分页)
     * @param ...$param
     * @return array
     */
    public function getListByServe(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        [$uid, $store_id, $store_type_id, $p] = $param;
        $MerchantStoreDomain = new MerchantStoreDomain();
        $store = $MerchantStoreDomain->getOne($store_id, 'id');
        if (!$store) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误');
            return $rs;
        }
        $where['store_id = ?'] = $store_id;

        if ($store_type_id == 0) {//推荐
            $where['recommend = ?'] = 1;
            $list = $this->selectList($where, 'id,name,price,image,des', 'list_order ASC', $p, 20);
        } else {
            $where['store_type_id = ?'] = $store_type_id;
            $list = $this->selectList($where, 'id,name,price,image,des', 'list_order ASC', $p, 20);
        }

        foreach ($list as $key => &$value) {
            $image = json_decode($value['image'], true);
            array_walk($image, function (&$value, $key) {
                $value = \App\get_upload_path($value);
            });
            $value['image'] = $image[0] ?? '';
        }
        $rs['info'][] = $list;
        return $rs;
    }


    public function getList($type_id, $where, $field, $p = 0, $limit = 20)
    {
        $MerchantStoreProductModel = new MerchantStoreProductModel();

        $MerchantTypeDomain = new MerchantTypeDomain();
        $typeList = $MerchantTypeDomain->getSelfAndChildren($type_id);

        $list = $MerchantStoreProductModel->inTypeIdselectList(array_column($typeList, 'id'), $where, $field, 'id DESC', $p, $limit);
        foreach ($list as $key => &$value) {
            $image = json_decode($value['image'], true);
            array_walk($image, function (&$value2, $key2) {
                $value2 = \App\get_upload_path($value2);
            });
            $value['image'] = $image[0] ?? '';
        }
        return $list;
    }

    public function addProduct(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        [$uid, $image, $name, $store_id] = $param;
        $MerchantStoreDomain = new MerchantStoreDomain();
        $store = $MerchantStoreDomain->getOne(['id = ?' => $store_id], 'id,type_id');

        if (!$store) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误');
            return $rs;
        }

        $MerchantTypeDomain = new MerchantTypeDomain();
        $typeList = $MerchantTypeDomain->getSelfAndChildren(4);

        if (!in_array($store['type_id'], array_column($typeList, 'id'))) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺类型错误');
            return $rs;
        }
        $image = json_decode($image, true);
        if (!is_array($image)) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('商品图片格式错误');
            return $rs;
        }
        if (!$name) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('商品名称不能为空');
            return $rs;
        }

        $installData = [
            'image' => json_encode($image),
            'name' => $name,
           
            'store_id' => $store_id,
            'is_show' => 0,
            'type_id' => $store['type_id'],
            'store_type_id' => $store['type_id'],
        ];
        $MerchantStoreProductModel = new MerchantStoreProductModel();
        \PhalApi\DI()->notorm->beginTransaction('db_master');

        try {
            $productId = $MerchantStoreProductModel->saveOne($installData);
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
     * 获取商品加入到购物车中的数量
     * @param $uid
     * @param $store_id
     * @param $product_id
     * @param $product_attr_id
     * @return int
     */
    public function getCountInCart($uid, $store_id, $product_id, $product_attr_id = 0)
    {
        $MerchantStoreCartModel = new MerchantStoreCartModel();
        $where = [
            'product_id = ?' => $product_id,
            'store_id = ?' => $store_id,
            'uid = ?' => $uid
        ];
        if ($product_attr_id > 0) {
            $where['product_attr_id = ?'] = $product_attr_id;
        }

        $info = $MerchantStoreCartModel->getOne($where, 'id,sum(cart_num) as cart_num');
        if ($info) {
            return $info['cart_num'] ?? 0;
        } else {
            return 0;
        }
    }

    /**
     * 美食下的所有分类及商品
     * @param ...$param
     * @return array
     */
    public function getClassOfFood(...$param)
    {
        [$uid, $id] = $param;
       
        $MerchantStoreTypeDomain = new MerchantStoreTypeDomain();
        $storeTypeList = $MerchantStoreTypeDomain->getStoreTypeList2($uid, $id, ['level = ?' => 2]); 
        $MerchantStoreProductAttrDomain = new MerchantStoreProductAttrDomain();
      
        foreach ($storeTypeList as $key => &$value) {

            $productList = $this->getAllListByStore($uid, $id, $value['id']);
            if(count($productList)<=0){
                unset($storeTypeList[$key]);
                continue;
            }
            foreach ($productList as $key2 => &$value2) {
                $value2['cart_num'] = $this->getCountInCart($uid, $id, $value2['id']);
                $attr = $MerchantStoreProductAttrDomain->getTreeAttr($value2['id']);

                foreach ($attr as $key3 => &$value4) {
                    $value4['cart_num'] = $this->getCountInCart($uid, $id, $value2['id'], $value4['id']);
                    $value4['use_price'] = bcadd($value4['use_price'] ?? 0, $value2['use_price'] ?? 0, 2);
                }
                $value2['attr'] = $attr ?? [];
            }
            $value['list'] = $productList;
        }
        return array_values($storeTypeList);
    }


    /**
     * 商品月销售数量
     * @param $productId
     * @return int
     */
    public function getMonthlySales($productId)
    {
        $daysAgo = strtotime('-30day');

        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $count = $MerchantStoreOrderDomain->getCountOne(['product_id = ?' => $productId, 'status = ?' => 4, 'add_time >= ?' => $daysAgo], 'sum(total_num) as total_num')['total_num'] ?? 0;
        return $count;
    }



    /**
     * 商品详情(美食店铺)
     * @param ...$param
     * @return array
     */
    public function getProductDetail(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        [$uid, $id] = $param;
        $MerchantStoreProductModel = new MerchantStoreProductModel();
        $where = ['id = ?' => $id];
        $info = $MerchantStoreProductModel->getOne($where, 'id,name,image,price,store_id,des,repertory');
        if (!$info) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('商品不存在!');
            return $rs;
        }
        if ((int)$info['store_id'] <= 0) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误!');
            return $rs;
        }

        $image = json_decode($info['image'], true);
        array_walk($image, function (&$value, $key) {
            $value = \App\get_upload_path($value);
        });
        $info['image'] = $image[0] ?? '';

        $MerchantStoreDomain = new MerchantStoreDomain();

        $store = $MerchantStoreDomain->getOne(['id = ?' => $info['store_id']], 'id,name,operating_state,type_id,lng,lat,top_type_id,open_date,open_time');
        if (!$store) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误!');
            return $rs;
        }
        if (in_array($store['top_type_id'], [1, 2, 3, 4, 8])) {
            $info['repertory'] = 1000000;
        }
        if($store['operating_state'] == 1){
            $store['operating_state'] = $MerchantStoreDomain->isOpen($store['open_date'],$store['open_time']);
        }
        $info['store'] = $store;
        $MerchantStoreProductAttrDomain = new MerchantStoreProductAttrDomain();
        $attr = $MerchantStoreProductAttrDomain->getTreeAttr($info['id']);

        foreach ($attr as $key => &$value) {
            $value['use_price'] = bcadd($value['use_price'], $info['use_price'], 2);
            foreach ($value['children'] as $key2 => &$value2) {
                $value2['use_price'] = bcadd($value['use_price'], $value2['use_price'], 2);
            }
            if (in_array($store['top_type_id'], [1, 2, 3, 4, 8])) {
                $value2['repertory'] = 100000;
            }
        }
        $info['attr'] = $attr ?? [];
        $cart_num = $this->getCountInCart($uid, $info['store_id'], $info['id']);
        $info['cart_num'] = $cart_num;
        $info['monthly_sales'] = $this->getMonthlySales($id);//月销售


        $rs['info'][] = $info;
        return $rs;
    }

    /**
     * 店铺内美食搜索(分页)
     * @param $param
     * @return array
     */
    public function getListByStore(...$param)
    {
        [$uid, $id, $store_type_id, $p, $limit,$next] = $param;

        $MerchantStoreProductModel = new MerchantStoreProductModel();
        $MerchantStoreTypeDomain = new MerchantStoreTypeDomain();
        $MerchantStoreProductReplyDomain = new MerchantStoreProductReplyDomain();
        $where = [];
        $where['store_id = ?'] = $id;
        if ($store_type_id > 0) {

            if($next){
                $store_type_list =  $MerchantStoreTypeDomain->getSelfAndChildren($store_type_id);
                $where['store_type_id'] = array_column($store_type_list,'id');
            }else{
                $where['store_type_id = ?'] = $store_type_id;
            }

        } else {
            $where['recommend = ?'] = 1;
        }

        $list = $MerchantStoreProductModel->selectList($where, 'id,name,image,good_starts,starts,price,repertory,type_id', 'list_order ASC', $p, $limit);
        foreach ($list as $key => &$value) {
            $image = json_decode($value['image'], true);
            array_walk($image, function (&$value, $key) {
                $value = \App\get_upload_path($value);
            });
            $value['image'] = $image[0] ?? '';
            $value['month_sales'] = $this->getMonthlySales($value['id']);//月销量
            $value['starts_rate'] = $value['starts'] > 0 ? (round($value['good_starts'] / $value['starts'], 2) * 100) . '%' : '0%';//好评率
            $value['evaluate'] = $MerchantStoreProductReplyDomain->getThumbUpMost(['product_id = ?' => $value['id']]);//商品评价
            $value['cart_num'] = $this->getCountInCart($uid, $id, $value['id']);

            unset($value['price']);
    
            unset($value['name']);
        }

        return $list;
    }


    /**
     * 店铺内搜索商品
     * @param ...$param
     * @return array
     */
    public function getProductList(...$param)
    {
        [$uid, $store_id, $store_type_id, $overall, $price, $evaluate, $keywords, $page] = $param;
        $MerchantStoreProductModel = new MerchantStoreProductModel();
        $MerchantStoreProductDomain = new MerchantStoreProductDomain();

        $where = [];
        $where['store_id = ?'] = $store_id;
        if ($store_type_id > 0) {
            $where['store_type_id = ?'] = $store_type_id;
        } else {
            $where['recommend = ?'] = 1;
        }

        if ($keywords != '') {
            $where['name LIKE ?'] = "%$keywords%";
        }
        if ($overall) {//商品销量
            $order = "sales {$overall}";
        } else if ($price) {//商品价格排序
            $order = "price {$price}";
        } else if ($evaluate) {//店铺评分排序
            $order = "good_starts {$evaluate}";
        } else {
            $order = "sales {$overall}";
        }

        $MerchantStoreModel = new MerchantStoreModel();

        $list = $MerchantStoreProductModel->selectList($where, 'id,name,image,starts,good_starts,price,repertory', $order, $page, 20);
        foreach ($list as $key => &$value) {
            $image = json_decode($value['image'], true);
            array_walk($image, function (&$value, $key) {
                $value = \App\get_upload_path($value);
            });
            $value['image'] = $image[0] ?? '';
            $value['monthly_sales'] = $this->getMonthlySales($value['id']);//月销售
            $value['cart_num'] = $MerchantStoreProductDomain->getCountInCart($uid, $store_id, $value['id']);

            $storeinfo = $MerchantStoreModel->getOne(['id = ?' => $store_id], 'top_type_id');

            if(in_array($storeinfo['top_type_id'],[1,2,3,4,8])){
                $value['repertory'] = 100000;
            }

        }
        return $list;
    }


    /**
     * 店铺内商品搜索(无分页)
     * @param $param
     * @return array
     */
    public function getAllListByStore(...$param)
    {
        [$uid, $id, $store_type_id,$limit] = $param;
        if($limit) $limit = 0;

        $MerchantStoreProductModel = new MerchantStoreProductModel();
        $MerchantStoreProductReplyDomain = new MerchantStoreProductReplyDomain();
        $where = [];
        $where['store_id = ?'] = $id;
        if ($store_type_id > 0) {
            $where['store_type_id = ?'] = $store_type_id;
        } else {
            $where['recommend = ?'] = 1;
        }
        $list = $MerchantStoreProductModel->selectList($where, 'id,name,image,good_starts,starts,price,repertory', 'list_order ASC',0,$limit);
        foreach ($list as $key => &$value) {
            $image = json_decode($value['image'], true);
            array_walk($image, function (&$value, $key) {
                $value = \App\get_upload_path($value);
            });
            $value['image'] = $image[0] ?? '';
            $value['month_sales'] = $this->getMonthlySales($value['id']);//月销量
            $value['starts_rate'] = $value['starts'] > 0 ? (round($value['good_starts'] / $value['starts'], 2) * 100) . '%' : '0%';//好评率
            $value['evaluate'] = $MerchantStoreProductReplyDomain->getThumbUpMost(['product_id = ?' => $value['id']]);//商品评价
            unset($value['price']);
      
            unset($value['name']);
        }

        return $list;
    }


    /**
     * 首页商品搜索
     * @param ...$param
     * @return array
     */
    public function getHomeSearch(...$param)
    {
        /**
         * $type_id 平台店铺分类
         * $overall 综合排序
         * $price 价格排序
         * $distanc 距离排序
         * $evaluate 评分排序
         */
        [$lng, $lat, $overall, $price, $distanc, $evaluate, $keywords, $page] = $param;

        $MerchantStoreDomain = new MerchantStoreDomain();
        $MerchantTypeModel = new MerchantTypeModel();
        $storeList = $MerchantStoreDomain->getTypeOfStore(4);

        $MerchantStoreProductModel = new MerchantStoreProductModel();
        $list = $MerchantStoreProductModel->notInStoreIdSelectList(array_column($storeList, 'id'), $lng, $lat, $overall, $price, $distanc, $evaluate, $keywords, $page, 20);
        foreach ($list as $key => &$value) {
            $value['monthly_sales'] = $this->getMonthlySales($value['id']);//月销售
            $value['distance'] = round($value['distance'], 2);//距离(千米)

            $image = json_decode($value['image'], true);
            array_walk($image, function (&$value2, $key2) {
                $value2 = \App\get_upload_path($value2);
            });
            $value['image'] = $image[0] ?? '';
            $store = $MerchantStoreDomain->getOne(['id = ?' => $value['store_id']], 'id,name,thumb,stars,up_to_send');
            $time = $MerchantStoreDomain->getPresetTime($value['lng'], $value['lat'], $lng, $lat);
            $store['time'] = \App\handellength(round($time * 60));//预计送达时间
            $store['distance'] = $value['distance'] . 'km';
            $store['thumb'] = \App\get_upload_path($store['thumb']);
            $store['top_type_id'] = $MerchantTypeModel->getTopInfo($value['type_id'])['id'] ?? 0;

            unset($value['lng']);
            unset($value['lat']);
            unset($value['distance']);

            $value['store'] = $store;
        }
        return $list;
        return [];
    }


    /**
     * 美食搜索(平台分类)
     * @param ...$param
     * @return array
     */
    public function getCateSearchList(...$param)
    {
        /**
         * $type_id 平台店铺分类
         * $overall 综合排序
         * $price 价格排序
         * $distanc 距离排序
         * $evaluate 评分排序
         */
        [$lng, $lat, $type_id, $overall, $price, $distanc, $evaluate, $keywords, $page] = $param;
        $MerchantTypeDomain = new MerchantTypeDomain();
        $storeTypelist = $MerchantTypeDomain->getSelfAndChildren($type_id);
        $type_id_arr = array_column($storeTypelist, 'id');
        $MerchantStoreProductModel = new MerchantStoreProductModel();
        $list = $MerchantStoreProductModel->cateSelectList($lng, $lat, $type_id_arr, $overall, $price, $distanc, $evaluate, $keywords, $page, 20);
        $MerchantStoreDomain = new MerchantStoreDomain();
        foreach ($list as $key => &$value) {
            $value['monthly_sales'] = $this->getMonthlySales($value['id']);//月销售
            $value['distance'] = round($value['distance'], 2);//距离(千米)

            $image = json_decode($value['image'], true);
            array_walk($image, function (&$value2, $key2) {
                $value2 = \App\get_upload_path($value2);
            });
            $value['image'] = $image[0] ?? '';
            $store = $MerchantStoreDomain->getOne(['id = ?' => $value['store_id']], 'id,name,thumb,stars,up_to_send');
            $time = $MerchantStoreDomain->getPresetTime($value['lng'], $value['lat'], $lng, $lat);
            $store['time'] = \App\handellength2(round($time * 60));//预计送达时间
            $store['distance'] = $value['distance'] . 'km';
            $store['thumb'] = \App\get_upload_path($store['thumb']);

            unset($value['lng']);
            unset($value['lat']);
            unset($value['distance']);

            $value['store'] = $store;
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
        $MerchantStoreProductModel = new MerchantStoreProductModel();
        // TODO: Implement __call() method.
        return call_user_func_array([$MerchantStoreProductModel, $name], $arguments);
    }
}