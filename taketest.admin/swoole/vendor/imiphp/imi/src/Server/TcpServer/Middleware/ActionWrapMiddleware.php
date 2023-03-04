<?php

declare(strict_types=1);

namespace Imi\Server\TcpServer\Middleware;

use Imi\Bean\Annotation\Bean;
use Imi\RequestContext;
use Imi\Server\TcpServer\IReceiveHandler;
use Imi\Server\TcpServer\Message\IReceiveData;
use Imi\Server\TcpServer\ReceiveHandler;

/**
 * @Bean("TCPActionWrapMiddleware")
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
    public function process(IReceiveData $data, IReceiveHandler $handler)
    {
        // 获取路由结果
        $result = RequestContext::get('routeResult');
        if (null === $result)
        {
            return $handler->handle($data);
        }
        $middlewares = $result->routeItem->middlewares;
        if ($middlewares)
        {
            $middlewares[] = $this->actionMiddleware;
            $subHandler = new ReceiveHandler($middlewares);

            return $subHandler->handle($data);
        }
        else
        {
            /** @var \Imi\Server\TcpServer\Middleware\IMiddleware $requestHandler */
            $requestHandler = RequestContext::getServerBean($this->actionMiddleware);

            return $requestHandler->process($data, $handler);
        }
    }
}
