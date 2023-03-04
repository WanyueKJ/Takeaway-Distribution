<?php

namespace ImiApp\Models;

use Imi\Db\Db;

class MerchantStoreProduct
{

    /**
     * 商品次日置满库存
     * @return void
     */
    public static function fillingTheInventory()
    {
        $list = Db::query()->table('merchant_store_product')
            ->field('id','max_repertory')
            ->where('day_repertory', '=', 1)
            ->select();

        foreach ($list as $value){
            Db::query()->table('merchant_store_product')
                ->where('id','=',$value['id'])
                ->update([
                    'repertory'=>$value['max_repertory'],
                ]);
        }
//        $action = "MerchantStoreProduct.fillingTheInventory";
//        $date = date("Y-m-d H:i:s");
//        file_put_contents('./log.txt', var_export(compact('action', 'date'), true) . PHP_EOL, FILE_APPEND);
    }
}