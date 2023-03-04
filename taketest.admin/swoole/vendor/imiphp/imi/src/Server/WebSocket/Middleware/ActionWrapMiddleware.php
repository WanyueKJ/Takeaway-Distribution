<?php

declare(strict_types=1);

namespace Imi\Server\WebSocket\Middleware;

use Imi\Bean\Annotation\Bean;
use Imi\RequestContext;
use Imi\Server\WebSocket\IMessageHandler;
use Imi\Server\WebSocket\Message\IFrame;
use Imi\Server\WebSocket\MessageHandler;

/**
 * @Bean("WebSocketActionWrapMiddleware")
 */
class ActionWrapMiddleware implements IMiddleware
{
    /**
     * 动作中间件.
     */
    protected string $actionMiddleware = ActionMiddleware::class;

    /**
     * {@inheritDoc}
     */
    public function process(IFrame $frame, IMessageHandler $handler)
    {
        // 获取路由结果
        $result = RequestContext::get('routeResult');
        if (null === $result)
        {
            return $handler->handle($frame);
        }
        $middlewares = $result->routeItem->middlewares;
        if ($middlewares)
        {
            $middlewares[] = $this->actionMiddleware;
            $subHandler = new MessageHandler($middlewares);

            return $subHandler->handle($frame);
        }
        else
        {
            /** @var \Imi\Server\WebSocket\Middleware\IMiddleware $requestHandler */
            $requestHandler = RequestContext::getServerBean($this->actionMiddleware);

            return $requestHandler->process($frame, $handler);
        }
    }
}
