<?php

declare(strict_types=1);

namespace Imi\Event;

use Imi\Util\Traits\TSingleton;

class Event
{
    use TEvent
    {
        on as public __on;
        one as public __one;
        off as public __off;
        trigger as public __trigger;
    }

    use TSingleton;

    /**
     * 事件监听.
     *
     * @param string|string[] $name     事件名称
     * @param mixed           $callback 回调，支持回调函数、基于IEventListener的类名
     * @param int             $priority 优先级，越大越先执行
     */
    public static function on($name, $callback, int $priority = 0): void
    {
        static::getInstance()->__on($name, $callback, $priority);
    }

    /**
     * 监听事件，仅触发一次
     *
     * @param string|string[] $name     事件名称
     * @param mixed           $callback 回调，支持回调函数、基于IEventListener的类名
     * @param int             $priority 优先级，越大越先执行
     */
    public static function one($name, $callback, int $priority = 0): void
    {
        static::getInstance()->__one($name, $callback, $priority);
    }

    /**
     * 取消事件监听.
     *
     * @param string|string[] $name     事件名称
     * @param mixed|null      $callback 回调，支持回调函数、基于IEventListener的类名。为 null 则不限制
     */
    public static function off($name, $callback = null): void
    {
        static::getInstance()->__off($name, $callback);
    }

    /**
     * 触发事件.
     *
     * @param string      $name       事件名称
     * @param array       $data       数据
     * @param object|null $target     目标对象
     * @param string      $paramClass 参数类
     */
    public static function trigger(string $name, array $data = [], ?object $target = null, string $paramClass = EventParam::class): void
    {
        static::getInstance()->__trigger($name, $data, $target, $paramClass);
    }
}
