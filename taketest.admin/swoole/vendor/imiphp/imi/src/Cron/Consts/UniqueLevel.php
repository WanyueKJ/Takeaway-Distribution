<?php

declare(strict_types=1);

namespace Imi\Cron\Consts;

use Imi\Enum\Annotation\EnumItem;
use Imi\Enum\BaseEnum;

/**
 * 任务唯一性等级.
 */
class UniqueLevel extends BaseEnum
{
    /**
     * @EnumItem("当前实例唯一")
     */
    public const CURRENT = 'current';

    /**
     * @EnumItem("所有实例唯一")
     */
    public const ALL = 'all';

    private function __construct()
    {
    }
}
