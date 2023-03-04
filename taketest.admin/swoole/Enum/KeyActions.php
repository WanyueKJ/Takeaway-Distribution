<?php
namespace ImiApp\Enum;

use Imi\Enum\BaseEnum;
use Imi\Enum\Annotation\EnumItem;

abstract class KeyActions extends BaseEnum
{
    /**
     * @EnumItem("订单提交时间")
     */
    const ORDER_ADD = 'orders_addtime';

    /**
     * @EnumItem("新订单")
     */
    const ORDER_NEW = 'orders_new';

    /**
     * @EnumItem("新派单")
     */
    const ORDER_DISPATCH = 'orders_dispatch';

    /**
     * @EnumItem("转单")
     */
    const ORDER_TRANS = 'orders_trans';

    /**
     * @EnumItem("退款取消")
     */
    const ORDER_REFUND = 'orders_refund';

    /**
     * @EnumItem("城市分组前缀")
     */
    const CITY_GROUP = 'city_group_';

    /**
     * @EnumItem("骑手对应client")
     */
    const RIDER_CLIENT = 'rider_client';


}
