<?php

namespace App\Domain;

use App\Domain\MerchantStore as MerchantStoreDomain;
use App\Domain\MerchantType as MerchantTypeDomain;
use App\Model\MerchantStoreCard as MerchantStoreCardModel;

/**
 * 商店打卡
 */
class MerchantStoreCard
{

    /**
     * 新增打卡记录
     * @param ...$param
     * @return false|int|mixed
     */
    public function addPunchCard(...$param)
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        [$uid, $lng, $lat, $store_id] = $param;

        $MerchantTypeDomain = new MerchantTypeDomain();
        $typeList = $MerchantTypeDomain->getSelfAndChildren(4);
        $MerchantStoreDomain = new MerchantStoreDomain();
        $store = $MerchantStoreDomain->getOne(['id = ?' => $store_id], 'id,type_id,lng,lat');
        if (!$store || !$store['lng'] || !$store['lat']) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误');
            return $rs;
        }
        if (!in_array($store['type_id'], array_column($typeList, 'id'))) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺类型错误');
            return $rs;
        }
        $lineDistance = \App\getDistance($store['lng'], $store['lat'], $lng, $lat);
        $roundLineDistance = round($lineDistance, 2);
        $getConfigPub = \App\getConfigPri();
        $punch_card = $getConfigPub['punch_card'];
        if ($punch_card < $roundLineDistance) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('在商户附近才能打卡成功哦');
            return $rs;
        }

        $MerchantStoreCardModel = new MerchantStoreCardModel();
        $data = [
            'uid' => $uid,
            'lng' => $lng,
            'lat' => $lat,
            'store_id' => $store_id,
            'addtime' => time(),
        ];
        $save = $MerchantStoreCardModel->saveOne($data);
        $rs['msg'] = \PhalApi\T('打卡成功');

        return $rs;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {

        $MerchantStoreCardModel = new MerchantStoreCardModel();
        // TODO: Implement __call() method.
        return call_user_func_array([$MerchantStoreCardModel, $name], $arguments);
    }
}
