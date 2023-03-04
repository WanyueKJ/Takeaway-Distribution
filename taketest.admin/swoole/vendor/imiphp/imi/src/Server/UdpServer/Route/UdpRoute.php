<?php

declare(strict_types=1);

namespace Imi\Server\UdpServer\Route;

use Imi\Bean\Annotation\Bean;
use Imi\Bean\BeanFactory;
use Imi\Log\Log;
use Imi\Server\UdpServer\Route\Annotation\UdpRoute as UdpRouteAnnotation;
use Imi\Util\DelayServerBeanCallable;
use Imi\Util\ObjectArrayHelper;

/**
 * @Bean("UdpRoute")
 */
class UdpRoute implements IRoute
{
    /**
     * 路由规则.
     *
     * @var \Imi\Server\UdpServer\Route\RouteItem[]
     */
    protected array $rules = [];

    /**
     * {@inheritDoc}
     */
    public function parse($data): ?RouteResult
    {
        foreach ($this->rules as $item)
        {
            if ($this->checkCondition($data, $item->annotation))
            {
                return new RouteResult($item);
            }
        }

        return null;
    }

    /**
     * 增加路由规则，直接使用注解方式.
     *
     * @param mixed $callable
     */
    public function addRuleAnnotation(UdpRouteAnnotation $annotation, $callable, array $options = []): void
    {
        $routeItem = new RouteItem($annotation, $callable, $options);
        if (isset($options['middlewares']))
        {
            $routeItem->middlewares = $options['middlewares'];
        }
        $this->rules[spl_object_id($annotation)] = $routeItem;
    }

    /**
     * 清空路由规则.
     */
    public function clearRules(): void
    {
        $this->rules = [];
    }

    /**
     * 路由规则是否存在.
     */
    public function existsRule(UdpRouteAnnotation $rule): bool
    {
        return isset($this->rules[spl_object_id($rule)]);
    }

    /**
     * 获取路由规则.
     *
     * @return \Imi\Server\UdpServer\Route\RouteItem[]
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * 检查条件是否匹配.
     *
     * @param array|object $data
     */
    private function checkCondition($data, UdpRouteAnnotation $annotation): bool
    {
        if ([] === $annotation->condition)
        {
            return false;
        }
        foreach ($annotation->condition as $name => $value)
        {
            if (ObjectArrayHelper::get($data, $name) !== $value)
            {
                return false;
            }
        }

        return true;
    }

    /**
     * 检查重复路由.
     */
    public function checkDuplicateRoutes(): void
    {
        $first = true;
        $map = [];
        foreach ($this->rules as $routeItem)
        {
            $string = (string) $routeItem->annotation;
            if (isset($map[$string]))
            {
                if ($first)
                {
                    $first = false;
                    $this->logDuplicated($map[$string]);
                }
                $this->logDuplicated($routeItem);
            }
            else
            {
                $map[$string] = $routeItem;
            }
        }
    }

    private function logDuplicated(RouteItem $routeItem): void
    {
        $callable = $routeItem->callable;
        $route = 'condition=' . json_encode($routeItem->annotation->condition, \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
        if ($callable instanceof DelayServerBeanCallable)
        {
            $logString = sprintf('UDP Route %s duplicated (%s::%s)', $route, $callable->getBeanName(), $callable->getMethodName());
        }
        elseif (\is_array($callable))
        {
            $class = BeanFactory::getObjectClass($callable[0]);
            $method = $callable[1];
            $logString = sprintf('UDP Route "%s" duplicated (%s::%s)', $route, $class, $method);
        }
        else
        {
            $logString = sprintf('UDP Route "%s" duplicated', $route);
        }
        Log::warning($logString);
    }
}
