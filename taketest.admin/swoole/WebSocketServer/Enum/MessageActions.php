<?php
namespace ImiApp\WebSocketServer\Enum;

use Imi\Enum\BaseEnum;
use Imi\Enum\Annotation\EnumItem;

abstract class MessageActions extends BaseEnum
{
    /**
     * @EnumItem("加入房间")
     */
    const JOIN_ROOM = 'joinRoom';

    /**
     * @EnumItem("加入房间回调")
     */
    const JOIN_ROOM_BACK = 'joinRoomBack';

    /**
     * @EnumItem("发送内容")
     */
    const SEND = 'send';

    /**
     * @EnumItem("发送内容回调")
     */
    const SEND_BACK = 'sendBack';

    /**
     * @EnumItem("接收内容")
     */
    const RECEIVE = 'receive';

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

}
