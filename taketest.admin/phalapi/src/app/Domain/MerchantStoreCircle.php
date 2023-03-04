<?php

namespace App\Domain;

use App\Domain\MerchantType as MerchantTypeDomain;
use App\Model\MerchantType as MerchantTypeModel;
use App\Model\MerchantStore as MerchantStoreModel;
use App\Model\MerchantStoreCircle as MerchantStoreCircleModel;
use App\Domain\MerchantStoreOrder as MerchantStoreOrderDomain;

/**
 * 商圈
 */
class MerchantStoreCircle
{

    /**
     * 获取排行榜列表
     * @return array
     */
    public function getCircle(...$param)
    {
        [$uid, $type_id] = $param;
        $MerchantStoreCircleModel = new MerchantStoreCircleModel();
        $list = $MerchantStoreCircleModel->selectList([], 'id,name,th_name');
        return $list;
    }


    /**
     * 找店排行榜
     * @param ...$param
     * @return array
     */
    public function getlookShopRankingList(...$param){
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        [$uid, $type_id, $circle_id, $lng, $lat, $p] = $param;
        $MerchantStoreModel = new MerchantStoreModel();

        $MerchantTypeDomain = new MerchantTypeDomain();
        if($type_id > 0){
            $storeTypelist = $MerchantTypeDomain->getSelfAndChildren($type_id);
        }else{
            $storeTypelist = $MerchantTypeDomain->selectList([]);
        }
        $where = [];
        if ($circle_id) {
            $where['circle_id = ?'] = $circle_id;
        }
        $list = $MerchantStoreModel->inTypeSelectList(array_column($storeTypelist, 'id'), $where, "id,name,remark,stars,type_id,thumb,circle_id,per_capita,
            (
                6378.138 * 2 * ASIN(
                    SQRT(
                        POW( SIN( ( {$lat} * PI( ) / 180 - lat * PI( ) / 180 ) / 2 ), 2 ) + COS( {$lat} * PI( ) / 180 ) * COS( lat * PI( ) / 180 ) * POW( SIN( ( {$lng}* PI( ) / 180 - lng * PI( ) / 180 ) / 2 ), 2 ) 
                    ) 
                ) 
            ) AS distance", 'stars DESC', $p, 20);

        $MerchantStoreCircleModel = new MerchantStoreCircleModel();
        $MerchantTypeModel = new MerchantTypeModel();
        foreach ($list as $key => &$value) {
            $Circle = $MerchantStoreCircleModel->getOne(['id = ?' => $value['circle_id']], 'id,name,th_name');
            $typeInfo = $MerchantTypeModel->getOne(['id = ?' => $value['type_id']], 'id,name,th_name');
            $value['distance'] = round($value['distance'],2).'km';
            $value['thumb'] = \App\get_upload_path($value['thumb']);
            $value['circle_name'] = $Circle['use_name'] ?? '--';//商圈名
            $value['type_name'] = $typeInfo['use_name'] ?? '--';//店铺类型
        }
        $rs['info'][] = $list;
        return $rs;
    }

    /**
     * 查询美食排行榜下的店铺列表
     * @param ...$param
     * @return array
     */
    public function getCircleRankingList(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        [$uid, $type_id, $circle_id, $lng, $lat, $p] = $param;
        $MerchantStoreModel = new MerchantStoreModel();

        $MerchantTypeDomain = new MerchantTypeDomain();
        if($type_id > 0){
            $storeTypelist = $MerchantTypeDomain->getSelfAndChildren($type_id);
        }else{
            $storeTypelist = $MerchantTypeDomain->selectList([]);
        }
        $where = [];
        if ($circle_id) {
            $where['circle_id = ?'] = $circle_id;
        }
        $list = $MerchantStoreModel->inTypeSelectList(array_column($storeTypelist, 'id'), $where, "id,name,remark,stars,type_id,thumb,circle_id,
            (
                6378.138 * 2 * ASIN(
                    SQRT(
                        POW( SIN( ( {$lat} * PI( ) / 180 - lat * PI( ) / 180 ) / 2 ), 2 ) + COS( {$lat} * PI( ) / 180 ) * COS( lat * PI( ) / 180 ) * POW( SIN( ( {$lng}* PI( ) / 180 - lng * PI( ) / 180 ) / 2 ), 2 ) 
                    ) 
                ) 
            ) AS distance", 'stars DESC', $p, 20);

        $MerchantStoreCircleModel = new MerchantStoreCircleModel();
        $MerchantTypeModel = new MerchantTypeModel();
        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        foreach ($list as $key => &$value) {
            $Circle = $MerchantStoreCircleModel->getOne(['id = ?' => $value['circle_id']], 'id,name,th_name');
            $typeInfo = $MerchantTypeModel->getOne(['id = ?' => $value['type_id']], 'id,name,th_name');
            $value['per_capita'] = $MerchantStoreOrderDomain->getPerCost($value['id']);//人均消费
            $value['distance'] = round($value['distance'],2).'km';
            $value['thumb'] = \App\get_upload_path($value['thumb']);
            $value['circle_name'] = $Circle['use_name'] ?? '--';//商圈名
            $value['type_name'] = $typeInfo['use_name'] ?? '--';//店铺类型
        }
        $rs['info'][] = $list;
        return $rs;
    }
}