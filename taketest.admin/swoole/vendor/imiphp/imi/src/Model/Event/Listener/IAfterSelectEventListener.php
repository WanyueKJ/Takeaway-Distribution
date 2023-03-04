<?php

declare(strict_types=1);

namespace Imi\Model\Event\Listener;

use Imi\Model\Event\Param\AfterSelectEventParam;

/**
 * 模型 查询后 事件监听接口.
 */
interface IAfterSelectEventListener
{
    public function handle(AfterSelectEventParam $e): void;
}
