<?php

namespace ImiApp\Models;


use Imi\Db\Db;
use Imi\Log\Log;
use Imi\Redis\Redis;
use Imi\RequestContext;
use ImiApp\Enum\KeyActions;
use ImiApp\Enum\MerchantAction;
use ImiApp\WebSocketServer\Enum\MessageActions;
use ImiApp\WebSocketServer\Model\Message;

class Orders
{

    public static function timeOuNotifice()
    {
        try {
            static::storeTimeOut();
            static::orderTimeOut();
        } catch (\Throwable $exception) {
        }
    }

    public static function getPayload($orderType, $noticeType, $storeOrderId, $runOrderId, $topTypeId)
    {
        $payload = [
            'order_type' => $orderType,//store(外卖订单)|run(跑腿订单)
            'notice_type' => $noticeType,//chat(聊天)|system(系统通知)|mer_take_order(商家接单)|rider_take_order(骑手接单)|order_complete(订单完成)|mer_new_order(商家新订单)|order_reminder(订单催单)|order_time_out(订单超时)
            'store_order_id' => $storeOrderId,//店铺订单id
            'run_order_id' => $runOrderId,//跑腿订单id
            'notice_id' => 0,//系统通知id
            'top_type_id' => $topTypeId,//店铺类型
        ];
        return $payload;
    }

    /**
     * 外卖超时提醒
     * @return void
     * @throws \Exception
     */
    public static function orderTimeOut()
    {
        $expirationTime = time();

        $storeOrder = Db::query()->table('orders')
            ->where('status', '=', 4)
            ->where('type', 'IN', [1, 2, 3, 4, 5])
            ->where('time_out', '<=', 0)
            ->where('servicetime', '>=', $expirationTime)
            ->select();

        foreach ($storeOrder as $value) {
            Db::query()->table('orders')
                ->where('id', '=', $value['id'])
                ->setFieldInc('time_out')
                ->update();

        }
    }

    /**
     * 店铺订单超时提醒
     * @return void
     * @throws \Exception
     */
    public static function storeTimeOut()
    {
        $expirationTime = time();

        $storeOrder = Db::query()->table('merchant_store_order')
            ->where('status', '=', 3)
            ->where('scheduled_time', '<>', 0)
            ->where('time_out', '<=', 0)
            ->where('scheduled_time', '<=', $expirationTime)
            ->select();
        $storeOrder = $storeOrder->getArray();

        foreach ($storeOrder as $value) {

            $store = Db::query()->table('merchant_store')
                ->field('top_type_id')
                ->where("id", "=", $value['store_id'])
                ->select()->get();
            if (!$store) continue;


            Db::query()->table('merchant_store_order')
                ->where('id', '=', $value['id'])
                ->setFieldInc('time_out')
                ->update();


        }

    }


    public static function getUser($riderUid)
    {
        return  [];
    }

    /* 私密配置 */
    public static function getConfigPri()
    {

        $config = Db::query()->table('option')
            ->field('option_value')
            ->where("option_name", "=", 'configpri')
            ->select()->get();
        $config = json_decode($config['option_value'], true) ?? [];

        return $config;
    }

    /**
     * 用户店铺订单过期检测
     * @return void
     */
    public static function storeCancel()
    {
        $expirationTime = time() - 30 * 60;
        Db::query()->table('merchant_store_order')
            ->where('status', '=', 0)
            ->where('add_time', '<=', $expirationTime)
            ->update([
                'status' => 7
            ]);
    }

    /**
     * 商家新订单通知
     * @return void
     */
    public static function newMerNotice()
    {
        $nowtime = time();
//        Log::info('newNotice');
        $key = MerchantAction::ORDER_NEW;
        $list = Redis::hGetAll($key);
        if (!$list) {
            return 0;
        }

        $data = [
            'action' => MessageActions::ORDER_NEW,
            'data' => '',
        ];

        foreach ($list as $k => $v) {
            $k = '' . $k;
            if ($v <= 0) {
                Redis::hDel($key, $k);
                continue;
            }

            $data['data'] = ['order_id' => $v];
            Redis::hDel($key, $k);
            Message::sendMer($k, $data);
        }


    }


    public static function cancel()
    {
        $nowtime = time();
//        Log::info('cancel');
        $addtime = $nowtime - 60 * 30;
        $key = KeyActions::ORDER_ADD;
        $add = $GLOBALS[$key] ?? 0;

        if ($add == 0) {
            $addList = Redis::zRange($key, 0, 1);
            if ($addList) {
                $add = $addList[0];
            }
        }

        if (!$add) {
            return 0;
        }

        if ($add > $addtime) {
            $GLOBALS[$key] = $add;
            return 0;
        }

        Redis::zRem($key, $add);
        $GLOBALS[$key] = 0;

        Db::query()->table('orders')
            ->where('status', '=', 1)
            ->where('addtime', '<=', $add)
            ->update([
                'status' => 10,
                'canceltime' => $nowtime,
            ]);

        return 1;
    }


    public static function newNotice()
    {

        //{}
        $nowtime = time();
//        Log::info('newNotice');
        $key = KeyActions::ORDER_NEW;
        $list = Redis::hGetAll($key);
//        log::info(json_encode($list));
        if (!$list) {
            return 0;
        }

        $data = [
            'action' => MessageActions::ORDER_NEW,
            'data' => '',
        ];

        foreach ($list as $k => $v) {
            $k = '' . $k;
            if ($v <= 0) {
                Redis::hDel($key, $k);
                continue;
            }
            if ($v > $nowtime) {
                continue;
            }

            Redis::hDel($key, $k);
            Message::sendCity($k, $data);
        }

        return 1;
    }

    public static function dispatchNotice()
    {
        $nowtime = time();
//        Log::info('dispatchNotice');

        $key = KeyActions::ORDER_DISPATCH;
        $list = Redis::hGetAll($key);
//        log::info(json_encode($list));
        if (!$list) {
            return 0;
        }

        $data = [
            'action' => MessageActions::ORDER_DISPATCH,
            'data' => '',
        ];

        foreach ($list as $k => $v) {
            $k = '' . $k;
            if ($v <= 0) {
                Redis::hDel($key, $k);
                continue;
            }
            if ($v > $nowtime) {
                continue;
            }

            Redis::hDel($key, $k);
            Message::sendRider($k, $data);

        }

        return 1;
    }

    public static function transNotice()
    {

//        Log::info('transNotice');

        $key = KeyActions::ORDER_TRANS;
        $list = Redis::hGetAll($key);
//        log::info(json_encode($list));
        if (!$list) {
            return 0;
        }

        foreach ($list as $k => $v) {
            $k = '' . $k;
            if ($v <= 0) {
                Redis::hDel($key, $k);
                continue;
            }

            Redis::hDel($key, $k);

            $data = [
                'action' => MessageActions::ORDER_TRANS,
                'type' => '' . $v,
                'data' => '',
            ];
            Message::sendRider($k, $data);

        }

        return 1;
    }

    public static function refundNotice()
    {

//        Log::info('refundNotice');

        $key = KeyActions::ORDER_REFUND;
        $list = Redis::hGetAll($key);
//        log::info(json_encode($list));
        if (!$list) {
            return 0;
        }

        foreach ($list as $k => $v) {
            $k = '' . $k;
            if ($v <= 0) {
                Redis::hDel($key, $k);
                continue;
            }

            Redis::hDel($key, $k);

            $data = [
                'action' => MessageActions::ORDER_REFUND,
                'data' => '',
            ];
            Message::sendRider($k, $data);
        }

        return 1;
    }

}
