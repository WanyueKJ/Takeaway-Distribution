<?php

declare(strict_types=1);

namespace Imi\Server\UdpServer\Error;

use Imi\Server\UdpServer\IPacketHandler;
use Imi\Server\UdpServer\Message\IPacketData;

/**
 * 处理未找到 UDP 路由情况的接口.
 */
interface IUdpRouteNotFoundHandler
{
    /**
     * 处理方法.
     *
     * @return mixed
     */
    public function handle(IPacketData $data, IPacketHandler $handler);
}
