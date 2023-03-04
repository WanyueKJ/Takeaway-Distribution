<?php

declare(strict_types=1);

namespace Imi\Model\Annotation\Relation;

use Imi\Bean\Annotation\Base;

/**
 * 多对多，中间表模型关联到右侧表.
 *
 * @Annotation
 * @Target("PROPERTY")
 *
 * @property string|null $field       字段名
 * @property string|null $middleField 中间表模型字段
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class JoinFromMiddle extends Base
{
    public function __construct(?array $__data = null, ?string $field = null, ?string $middleField = null)
    {
        parent::__construct(...\func_get_args());
    }
}
