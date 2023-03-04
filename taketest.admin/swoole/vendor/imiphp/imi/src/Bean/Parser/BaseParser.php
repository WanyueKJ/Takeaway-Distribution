<?php

declare(strict_types=1);

namespace Imi\Bean\Parser;

use Imi\Util\Traits\TSingleton;

abstract class BaseParser implements IParser
{
    use TSingleton;

    /**
     * 注解目标-类.
     */
    public const TARGET_CLASS = 'class';

    /**
     * 注解目标-属性.
     */
    public const TARGET_PROPERTY = 'property';

    /**
     * 注解目标-方法.
     */
    public const TARGET_METHOD = 'method';

    /**
     * 注解目标-常量.
     */
    public const TARGET_CONST = 'const';

    /**
     * 是否子类作为单独实例.
     */
    protected static function isChildClassSingleton(): bool
    {
        return true;
    }
}
