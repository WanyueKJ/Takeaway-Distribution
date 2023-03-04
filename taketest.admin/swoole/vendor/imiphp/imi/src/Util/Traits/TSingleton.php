<?php

declare(strict_types=1);

namespace Imi\Util\Traits;

/**
 * 单例模式.
 */
trait TSingleton
{
    /**
     * 实例对象
     */
    protected static ?object $__instance = null;

    /**
     * 实例对象数组.
     */
    protected static array $__instances = [];

    private function __construct()
    {
    }

    /**
     * 获取单例对象
     *
     * @param array ...$args
     *
     * @return static
     */
    public static function getInstance(...$args): object
    {
        if (static::isChildClassSingleton())
        {
            $instances = &static::$__instances;
            if (isset($instances[static::class]))
            {
                return $instances[static::class];
            }
            else
            {
                return $instances[static::class] = new static(...$args);
            }
        }
        else
        {
            if (null === static::$__instance)
            {
                static::$__instance = new static(...$args);
            }

            // @phpstan-ignore-next-line
            return static::$__instance;
        }
    }

    /**
     * 是否子类作为单独实例.
     */
    protected static function isChildClassSingleton(): bool
    {
        return false;
    }
}
