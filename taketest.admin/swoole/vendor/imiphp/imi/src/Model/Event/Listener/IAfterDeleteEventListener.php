<?php

declare(strict_types=1);

namespace Imi\Model\Event\Listener;

use Imi\Model\Event\Param\AfterDeleteEventParam;

/**
 * 模型 删除后 事件监听接口.
 */
interface IAfterDeleteEventListener
{
    public function handle(AfterDeleteEventParam $e): void;
}
