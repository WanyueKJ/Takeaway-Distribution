<?php

declare(strict_types=1);

namespace Imi\Server\TcpServer\Error;

use Imi\Server\TcpServer\IReceiveHandler;
use Imi\Server\TcpServer\Message\IReceiveData;

/**
 * 处理未找到 TCP 路由情况的接口.
 */
interface ITcpRouteNotFoundHandler
{
    /**
     * 处理方法.
     *
     * @return mixed
     */
    public function handle(IReceiveData $data, IReceiveHandler $handler);
}
