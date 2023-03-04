<?php

declare(strict_types=1);

namespace Imi\Server\Http\Middleware;

use Imi\Bean\Annotation\Bean;
use Imi\RequestContext;
use Imi\Server\Http\Message\Contract\IHttpResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 解决：使用 application/json 请求时，浏览器会先发送一个 OPTIONS 请求
 *
 * @Bean("OptionsMiddleware")
 */
class OptionsMiddleware implements MiddlewareInterface
{
    /**
     * 设置允许的 Origin
     * 为 null 时允许所有
     * 为数组时允许多个.
     *
     * @var string|string[]|null
     */
    protected $allowOrigin = null;

    /**
     * 允许的请求头.
     */
    protected string $allowHeaders = 'Authorization, Content-Type, Accept, Origin, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-Requested-With, X-Id, X-Token, Cookie';

    /**
     * 允许的跨域请求头.
     */
    protected string $exposeHeaders = 'Authorization, Content-Type, Accept, Origin, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-Requested-With, X-Id, X-Token, Cookie';

    /**
     * 允许的请求方法.
     */
    protected string $allowMethods = 'GET, POST, PATCH, PUT, DELETE';

    /**
     * 是否允许跨域 Cookie.
     */
    protected string $allowCredentials = 'true';

    /**
     * 当请求为 OPTIONS 时，是否中止后续中间件和路由逻辑.
     *
     * 一般建议设为 true
     */
    protected bool $optionsBreak = false;

    /**
     * {@inheritDoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestContext = RequestContext::getContext();
        /** @var IHttpResponse $response */
        $response = $requestContext['response'] ?? null;
        if ($isOptions = ('OPTIONS' === $request->getMethod()))
        {
            if (null !== $this->allowHeaders)
            {
                $response->setHeader('Access-Control-Allow-Headers', $this->allowHeaders);
            }
            if (null !== $this->exposeHeaders)
            {
                $response->setHeader('Access-Control-Expose-Headers', $this->exposeHeaders);
            }
            if (null !== $this->allowMethods)
            {
                $response->setHeader('Access-Control-Allow-Methods', $this->allowMethods);
            }
        }
        if (null === $this->allowOrigin || (\is_array($this->allowOrigin) && \in_array($request->getHeaderLine('Origin'), $this->allowOrigin)))
        {
            $response->setHeader('Access-Control-Allow-Origin', $request->getHeaderLine('Origin'));
        }
        elseif (!\is_array($this->allowOrigin))
        {
            $response->setHeader('Access-Control-Allow-Origin', $this->allowOrigin);
        }
        if (null !== $this->allowCredentials)
        {
            $response->setHeader('Access-Control-Allow-Credentials', $this->allowCredentials);
        }
        $requestContext['response'] = $response;
        if ($isOptions && $this->optionsBreak)
        {
            return $response;
        }
        else
        {
            return $handler->handle($request);
        }
    }
}
