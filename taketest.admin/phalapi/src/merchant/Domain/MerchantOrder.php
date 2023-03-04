<?php

namespace Merchant\Domain;

use Merchant\Model\MerchantStoreOrder as MerchantStoreOrderModel;

/**
 * 商家订单
 */
class MerchantOrder
{

    public function getList($uid, $type){
        $MerchantStoreOrderModel = new MerchantStoreOrderModel();

    }
    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {

        $MerchantStoreOrderModel = new MerchantStoreOrderModel();
        // TODO: Implement __call() method.
        return call_user_func_array([$MerchantStoreOrderModel, $name], $arguments);
    }
}