<?php

declare(strict_types=1);

namespace Imi\Server\UdpServer\Route\Annotation;

use Imi\Bean\Annotation\Base;
use Imi\Bean\Annotation\Parser;

/**
 * Udp 动作注解.
 *
 * @Annotation
 * @Target("METHOD")
 * @Parser("Imi\Server\UdpServer\Parser\UdpControllerParser")
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class UdpAction extends Base
{
}
