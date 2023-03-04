<?php

declare(strict_types=1);

namespace Imi;

use Imi\Event\Event;
use Imi\IDEHelper\BuildIDEHelper;
use Imi\Main\BaseMain;
use Imi\Util\ImiPriority;

/**
 * 主类.
 */
class Main extends BaseMain
{
    public function __init(): void
    {
        Event::on('IMI.LOAD_RUNTIME_INFO', \Imi\Bean\Listener\LoadRuntimeListener::class, ImiPriority::IMI_MAX);
        Event::on('IMI.BUILD_RUNTIME', \Imi\Bean\Listener\BuildRuntimeListener::class, ImiPriority::IMI_MAX);

        Event::on('IMI.LOAD_RUNTIME_INFO', \Imi\Aop\Listener\LoadRuntimeListener::class, 19940300);
        Event::on('IMI.BUILD_RUNTIME', \Imi\Aop\Listener\BuildRuntimeListener::class, 19940300);

        Event::on('IMI.LOAD_RUNTIME_INFO', \Imi\Event\Listener\LoadRuntimeListener::class, 19940100);
        Event::on('IMI.BUILD_RUNTIME', \Imi\Event\Listener\BuildRuntimeListener::class, 19940100);

        Event::on('IMI.LOAD_RUNTIME_INFO', \Imi\Enum\Listener\LoadRuntimeListener::class, 19940000);
        Event::on('IMI.BUILD_RUNTIME', \Imi\Enum\Listener\BuildRuntimeListener::class, 19940000);

        Event::on('IMI.LOAD_RUNTIME_INFO', \Imi\Core\Component\Listener\LoadRuntimeListener::class, ImiPriority::IMI_MAX);
        Event::on('IMI.BUILD_RUNTIME', \Imi\Core\Component\Listener\BuildRuntimeListener::class, ImiPriority::IMI_MAX);

        if (Config::get('@app.imi.ideHelper') ?? App::isDebug())
        {
            Event::on('IMI.LOAD_RUNTIME', BuildIDEHelper::class, ImiPriority::MIN);
        }
    }

    /**
     * 获取配置.
     */
    public function getConfig(): array
    {
        if (null === $this->config)
        {
            return $this->config = Config::get('@imi');
        }

        return $this->config;
    }
}
