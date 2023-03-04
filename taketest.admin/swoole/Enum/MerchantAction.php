<?php

namespace ImiApp\Enum;

use Imi\Enum\BaseEnum;
use Imi\Enum\Annotation\EnumItem;


/**
 * 商家
 */
abstract class MerchantAction extends BaseEnum
{

    /**
     * @EnumItem("新订单")
     */
    const ORDER_NEW = 'orders_mer_new';


    /**
     * @EnumItem("商户分组前缀")
     */
    const MERCHSNT_GROUP = 'mer_group_';

    /**
     * @EnumItem("商家对应client")
     */
    const RIDER_CLIENT = 'merchant_client';
}