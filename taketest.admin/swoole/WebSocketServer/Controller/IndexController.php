<?php

namespace ImiApp\WebSocketServer\Controller;

use Imi\App;
use Imi\ConnectionContext;
use Imi\Log\Log;
use Imi\Redis\Redis;
use Imi\Server\Server;
use Imi\Server\WebSocket\Controller\WebSocketController;
use Imi\Server\WebSocket\Route\Annotation\WSAction;
use Imi\Server\WebSocket\Route\Annotation\WSController;
use Imi\Server\WebSocket\Route\Annotation\WSRoute;
use ImiApp\Enum\MerchantAction;
use \ImiApp\WebSocketServer\Enum\MessageActions;
use \ImiApp\WebSocketServer\Enum\MerMessageActions;
use ImiApp\WebSocketServer\Model\Room;


/**
 * 数据收发.
 * @WSController
 */
class IndexController extends WebSocketController
{
    /**
     * 骑手加入房间.
     *
     * @WSAction
     * @WSRoute({"action"="join"})
     * @param
     * @return array
     */
    public function join($data)
    {
        $uid=$data->uid;
        $token=$data->token;

//        {"action":"join","uid":"6","token":"e73d82a4c047dd13f676b163e574f254"}
        $key='socket_rider_'.$token;
        $redis_rider=Redis::get($key);

        Log::info('join ');
        Log::info('uid : '.$uid);
        Log::info('key : '.$key);
        Log::info('token : '.$token);
        Log::info('redis_rider : '.$redis_rider);
        

        if(!$redis_rider){
            return [
                'action' => MessageActions::JOIN_ROOM_BACK,
                'success' => false,
            ];
        }

        $redis_rider=json_decode($redis_rider,true);
        if(!$redis_rider){
            return [
                'action' => MessageActions::JOIN_ROOM_BACK,
                'success' => false,
            ];
        }
        $riderid=$redis_rider['id'];
        if($riderid!=$uid){
            return [
                'action' => MessageActions::JOIN_ROOM_BACK,
                'success' => false,
            ];
        }
        $clientid=$this->frame->getClientId();
        Room::join($redis_rider,$clientid);

        return [
            'action' => MessageActions::JOIN_ROOM_BACK,
            'success' => true,
        ];
    }

    /**
     * 商家加入房间.
     *
     * @WSAction
     * @WSRoute({"action"="mer_join"})
     * @param
     * @return array
     */
    public function mer_join($data){
        $uid=$data->uid;
        $token=$data->token;

//        {"action":"mer_join","uid":"151","token":"813b69711c303887acdc2d97d79e90d9"}
        $key='merchant_token_'.$uid;
        $redis_mer=Redis::get($key);

        if(!$redis_mer){//商家未登录
            return [
                'action' => MerMessageActions::ORDER_NOLOGIN.'1',
                'success' => false,
            ];
        }

        $redis_mer=json_decode($redis_mer,true);

        if(!$redis_mer){
            return [
                'action' => MerMessageActions::UNPARSED_DATA,
                'success' => false,
            ];
        }

        $mer_token=$redis_mer['token'];
        if($mer_token!=$token){
            return [
                'action' => MerMessageActions::TOKEN_ERROR,
                'success' => false,
            ];
        }

        $clientid=$this->frame->getClientId();
        Room::mer_join($redis_mer,$clientid);

        return [
            'action' => MessageActions::JOIN_ROOM_BACK,
            'success' => true,
        ];

    }

}
