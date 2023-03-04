<?php

declare(strict_types=1);

namespace Imi\Facade;

use Imi\App;
use Imi\Bean\Annotation\AnnotationManager;
use Imi\Facade\Annotation\Facade;
use Imi\RequestContext;

/**
 * 门面基类.
 */
abstract class BaseFacade
{
    /**
     * 门面缓存.
     *
     * @var \Imi\Facade\Annotation\Facade[]
     */
    protected static array $cache = [];

    private function __construct()
    {
    }

    /**
     * 获取实例.
     *
     * @return mixed
     */
    public static function __getFacadeInstance()
    {
        $cache = &self::$cache;
        if (isset($cache[static::class]))
        {
            $cacheItem = $cache[static::class];
        }
        else
        {
            $annotations = AnnotationManager::getClassAnnotations(static::class, Facade::class);
            if (!$annotations)
            {
                throw new \RuntimeException(sprintf('Class %s not found @Facade Annotation', static::class));
            }
            $cache[static::class] = $cacheItem = $annotations[0];
        }
        if ($cacheItem->request)
        {
            return RequestContext::getBean($cacheItem->class, ...$cacheItem->args);
        }
        else
        {
            return App::getBean($cacheItem->class, ...$cacheItem->args);
        }
    }

    /**
     * 绑定门面.
     *
     * @param mixed ...$args
     */
    public static function __bindFacade(string $facadeClass, ?string $bindClass = null, ...$args): void
    {
        $cache = &self::$cache;
        if (isset($cache[$facadeClass]))
        {
            throw new \RuntimeException(sprintf('Facade %s already exists', $facadeClass));
        }
        $cache[$facadeClass] = new Facade([
            'class' => $bindClass,
            'args'  => $args,
        ]);
    }

    /**
     * 清除门面缓存.
     */
    public static function __clearCache(): void
    {
        self::$cache = [];
    }

    /**
     * @return mixed
     */
    public static function __callStatic(string $method, array $arguments)
    {
        return static::__getFacadeInstance()->$method(...$arguments);
    }
}
