<?php

namespace App\Domain;
use App\Model\MerchantSoreAccount as MerchantSoreAccountModel;

class MerchantSoreAccount
{
    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {

        $MerchantSoreAccountModel = new MerchantSoreAccountModel();
        // TODO: Implement __call() method.
        return call_user_func_array([$MerchantSoreAccountModel, $name], $arguments);
    }
}