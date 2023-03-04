<?php
namespace ImiApp\WebSocketServer\Model;


use Imi\ConnectionContext;
use Imi\Log\Log;
use Imi\Redis\Redis;
use Imi\RequestContext;
use ImiApp\Enum\KeyActions;
use ImiApp\Enum\MerchantAction;

class Room
{

    public static function mer_join($redis_mer,$clientid){

        $merid=$redis_mer['user_id'];

        ConnectionContext::set('merid',$redis_mer['user_id']);

        $room=MerchantAction::MERCHSNT_GROUP.$redis_mer['store']['id'];
        RequestContext::getServer()->joinGroup($room, $clientid);

        $key2=MerchantAction::RIDER_CLIENT;
        Redis::hSet($key2,$merid,$clientid);

    }

    public static function join($redis_rider,$clientid){

        Log::info('room join ');

        $cityid=$redis_rider['cityid'];
        $riderid=$redis_rider['id'];

        ConnectionContext::set('riderid',$redis_rider['id']);
        ConnectionContext::set('ridername',$redis_rider['user_nickname']);
        ConnectionContext::set('rideravatar',$redis_rider['avatar']);
        ConnectionContext::set('rideravatar2',$redis_rider['avatar_thumb']);
        ConnectionContext::set('cityid',$redis_rider['cityid']);

        $room=KeyActions::CITY_GROUP.$cityid;
        RequestContext::getServer()->joinGroup($room, $clientid);

        /*$nums=RequestContext::getServer()->getGroup($room)->count();
        Log::info('nums='.$nums);*/

        $key2=KeyActions::RIDER_CLIENT;
        Redis::hSet($key2,$riderid,$clientid);
        Log::info('join-riderid='.$riderid);
        Log::info('join-clientid='.$clientid);
        Log::info('join-cityid='.$cityid);

    }

    public static function leave($clientid){
        Log::info('room leave');
        $cityid= ConnectionContext::get('cityid');
        $riderid= ConnectionContext::get('riderid');
        Log::info('leave-cityid='.$cityid);
        Log::info('leave-riderid='.$riderid);
        Log::info('leave-clientid='.$clientid);


        $room=KeyActions::CITY_GROUP.$cityid;

        /*$nums=RequestContext::getServer()->getGroup($room)->count();
        Log::info('nums='.$nums);
        $res=RequestContext::getServer()->getGroup($room)->isInGroup($clientid);
        Log::info('res='.json_encode($res));*/

        RequestContext::getServer()->leaveGroup($room, $clientid);

        /*$nums2=RequestContext::getServer()->getGroup($room)->count();
        Log::info('nums2='.$nums2);*/

        $key2=KeyActions::RIDER_CLIENT;
        Redis::hDel($key2,$riderid);

        return 1;
    }
}