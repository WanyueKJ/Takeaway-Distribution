<?php
namespace ImiApp\WebSocketServer\Enum;

use Imi\Enum\BaseEnum;
use Imi\Enum\Annotation\EnumItem;

abstract class MerMessageActions extends BaseEnum
{
    /**
     * @EnumItem("加入房间")
     */
    const JOIN_ROOM = 'joinRoom';

    /**
     * @EnumItem("未登录")
     */
    const ORDER_NOLOGIN = 'mer_nologin';

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
     * @EnumItem("数据格式错误")
     */
    const UNPARSED_DATA = 'unparsed_data';

    /**
     * @EnumItem("token错误")
     */
    const TOKEN_ERROR = 'token_error';

    /**
     * @EnumItem("退款取消")
     */
    const ORDER_REFUND = 'orders_refund';

}
