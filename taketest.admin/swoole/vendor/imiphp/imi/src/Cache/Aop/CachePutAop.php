<?php

declare(strict_types=1);

namespace Imi\Cache\Aop;

use Imi\Aop\Annotation\Around;
use Imi\Aop\Annotation\Aspect;
use Imi\Aop\Annotation\PointCut;
use Imi\Aop\AroundJoinPoint;
use Imi\Aop\PointCutType;
use Imi\Bean\Annotation\AnnotationManager;
use Imi\Cache\Annotation\CachePut;
use Imi\Cache\CacheManager;
use Imi\Config;
use Imi\Util\ClassObject;

/**
 * @Aspect
 */
class CachePutAop
{
    use TCacheAopHelper;

    /**
     * 处理 CachePut 注解.
     *
     * @PointCut(
     *         type=PointCutType::ANNOTATION,
     *         allow={
     *             \Imi\Cache\Annotation\CachePut::class,
     *         }
     * )
     * @Around
     *
     * @return mixed
     */
    public function parseCachePut(AroundJoinPoint $joinPoint)
    {
        $methodReturn = $joinPoint->proceed();
        $method = $joinPoint->getMethod();

        $class = get_parent_class($joinPoint->getTarget());

        // CachePut 注解列表
        /** @var CachePut[] $cachePuts */
        $cachePuts = AnnotationManager::getMethodAnnotations($class, $method, CachePut::class);

        // 方法参数
        $args = ClassObject::convertArgsToKV($class, $method, $joinPoint->getArgs());
        $cacheDefault = null;

        foreach ($cachePuts as $cachePut)
        {
            // 缓存名
            $name = $cachePut->name;
            if (null === $name)
            {
                $name = ($cacheDefault ??= Config::get('@currentServer.cache.default'));
                if (null === $name)
                {
                    throw new \RuntimeException('Config "@currentServer.cache.default" not found');
                }
            }

            // 键
            $key = $this->getKey($joinPoint, $args, $cachePut);
            $cacheInstance = CacheManager::getInstance($name);

            $cacheInstance->set($key, $this->getValue($cachePut, $methodReturn), $cachePut->ttl);
        }

        return $methodReturn;
    }
}
