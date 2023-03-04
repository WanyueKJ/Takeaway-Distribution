<?php

declare(strict_types=1);

namespace Imi\Server\WebSocket;

use Imi\Bean\Annotation\Bean;
use Imi\RequestContext;
use Imi\Server\DataParser\DataParser;
use Imi\Server\WebSocket\Contract\IWebSocketServer;
use Imi\Server\WebSocket\Message\IFrame;

/**
 * @Bean("WebSocketDispatcher")
 */
class Dispatcher
{
    /**
     * 中间件数组.
     *
     * @var string[]
     */
    protected array $middlewares = [];

    /**
     * 最终使用的中间件列表.
     */
    private array $finalMiddlewares = [];

    public function dispatch(IFrame $frame): void
    {
        $requestHandler = new MessageHandler($this->getMiddlewares());
        $responseData = $requestHandler->handle($frame);
        if (null !== $responseData)
        {
            /** @var IWebSocketServer $server */
            $server = RequestContext::getServer();
            $server->push($frame->getClientId(), $server->getBean(DataParser::class)->encode($responseData));
        }
    }

    /**
     * 获取中间件列表.
     */
    protected function getMiddlewares(): array
    {
        if (!$this->finalMiddlewares)
        {
            $middlewares = $this->middlewares;
            $middlewares[] = \Imi\Server\WebSocket\Middleware\ActionWrapMiddleware::class;

            return $this->finalMiddlewares = $middlewares;
        }

        return $this->finalMiddlewares;
    }
}
