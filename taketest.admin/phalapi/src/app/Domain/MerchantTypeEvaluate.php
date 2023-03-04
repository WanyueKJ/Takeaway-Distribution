<?php

namespace App\Domain;

use App\ApiException;
use App\Domain\MerchantStore as MerchantStoreDomain;
use App\Model\MerchantTypeEvaluate as MerchantTypeEvaluateModel;

class MerchantTypeEvaluate
{
    public function getList($uid, $store_id)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        $MerchantStoreDomain = new MerchantStoreDomain();
        $storeInfo = $MerchantStoreDomain->getOne(['id = ?' => $store_id], 'type_id,top_type_id');
        if (!$storeInfo) throw new ApiException(\PhalApi\T('店铺不存在'));
        $list = $this->selectList(['type_id = ?' => $storeInfo['type_id']], '*');
        $rs['info'][] = $list;
        return $rs;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {

        $MerchantTypeEvaluateModel = new MerchantTypeEvaluateModel();
        // TODO: Implement __call() method.
        return call_user_func_array([$MerchantTypeEvaluateModel, $name], $arguments);
    }
}