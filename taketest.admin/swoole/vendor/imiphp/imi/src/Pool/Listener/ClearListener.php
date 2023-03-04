<?php

declare(strict_types=1);

namespace Imi\Pool\Listener;

use Imi\Bean\Annotation\Listener;
use Imi\Event\EventParam;
use Imi\Event\IEventListener;
use Imi\Pool\PoolManager;

/**
 * @Listener(eventName="IMI.COMMAND.AFTER")
 */
class ClearListener implements IEventListener
{
    /**
     * {@inheritDoc}
     */
    public function handle(EventParam $e): void
    {
        PoolManager::clearPools();
    }
}
