<?php
namespace ImiApp\WebSocketServer\Listener;


use Imi\ConnectionContext;
use Imi\Event\EventParam;
use Imi\Event\IEventListener;
use Imi\Bean\Annotation\Listener;
use Imi\Bean\Annotation\ClassEventListener;
use Imi\Log\Log;
use ImiApp\WebSocketServer\Model\Room;

/**
 * websocket 客户端断线监听
 * @ClassEventListener(className="Imi\Swoole\Server\WebSocket\Server", eventName="disconnect")
 */
class OnDisconnect implements IEventListener
{

    /**
     * 事件处理方法
     * @param EventParam $e
     * @return void
     */
    public function handle(EventParam $e):void
    {
        //$data = $e->getData();
        Log::info('22clint disconnect');
        Log::info(json_encode($e));


    }

}
