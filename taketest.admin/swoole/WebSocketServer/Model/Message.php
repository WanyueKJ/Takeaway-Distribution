<?php
namespace ImiApp\WebSocketServer\Model;


use Imi\Db\Db;
use Imi\Log\Log;
use Imi\Redis\Redis;
use Imi\Server\Server;
use ImiApp\Enum\KeyActions;
use ImiApp\Enum\MerchantAction;
use ImiApp\WebSocketServer\Enum\MessageActions;

class Message
{

    public static function sendMer($store_id,$data){
        $room=MerchantAction::MERCHSNT_GROUP.$store_id;


        Server::sendToGroup($room,$data,'main');
    }


    public static function sendCity($cityid,$data){
        Log::info('sendCity');
        $room=KeyActions::CITY_GROUP.$cityid;

        Log::info('sendCity-cityid:'.$cityid);

        Server::sendToGroup($room,$data,'main');
    }

    public static function sendRider($riderid,$data){
        Log::info('sendRider');
        Log::info('sendRider-riderid:'.$riderid);

        $key=KeyActions::RIDER_CLIENT;

        $clientid=Redis::hGet($key,$riderid);
        if(!$clientid){
            return 0;
        }

        Log::info('sendRider-clientid:'.$clientid);

        Server::send($data,$clientid,'main');

        return 1;
    }
}