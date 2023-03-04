<?php

namespace ImiApp\Models;

use Imi\Db\Db;

class MerchantStore
{


    /**
     * 重置所有店铺浏览量
     * @return void
     */
    public static function resetViews()
    {
        Db::query()->table('merchant_store')
            ->update([
                'views_day'=>0,
            ]);
//        $action = "Mercahnt.resetViews";
//        $date = date("Y-m-d H:i:s");
//        file_put_contents('./log.txt', var_export(compact('action','date'), true) . PHP_EOL, FILE_APPEND);

    }
}