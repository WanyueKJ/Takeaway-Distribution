<?php

declare(strict_types=1);

namespace Imi\Server\WebSocket;

use Imi\Server\WebSocket\Message\IFrame;

interface IMessageHandler
{
    /**
     * 返回值为响应内容，为null则无任何响应.
     *
     * @return mixed
     */
    public function handle(IFrame $frame);
}
