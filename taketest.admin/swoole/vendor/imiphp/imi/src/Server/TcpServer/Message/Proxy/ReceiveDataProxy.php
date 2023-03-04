<?php

declare(strict_types=1);

namespace Imi\Server\TcpServer\Message\Proxy;

use Imi\Bean\Annotation\Bean;
use Imi\RequestContextProxy\Annotation\RequestContextProxy;
use Imi\RequestContextProxy\BaseRequestContextProxy;
use Imi\Util\Socket\IPEndPoint;

/**
 * @Bean(name="TcpReceiveDataProxy", recursion=false)
 * @RequestContextProxy(class="Imi\Server\TcpServer\Message\IReceiveData", name="receiveData")
 *
 * @method int|string                  getClientId()
 * @method static                      int|string getClientId()
 * @method string                      getData()
 * @method static                      string getData()
 * @method mixed                       getFormatData()
 * @method static                      mixed getFormatData()
 * @method \Imi\Util\Socket\IPEndPoint getFormatData()
 * @method static                      \Imi\Util\Socket\IPEndPoint getFormatData()
 */
class ReceiveDataProxy extends BaseRequestContextProxy implements \Imi\Server\TcpServer\Message\IReceiveData
{
    /**
     * {@inheritDoc}
     */
    public function getClientId()
    {
        return self::__getProxyInstance()->getClientId(...\func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function getData(): string
    {
        return self::__getProxyInstance()->getData(...\func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function getFormatData()
    {
        return self::__getProxyInstance()->getFormatData(...\func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    public function getClientAddress(): IPEndPoint
    {
        return self::__getProxyInstance()->getClientAddress(...\func_get_args());
    }
}
