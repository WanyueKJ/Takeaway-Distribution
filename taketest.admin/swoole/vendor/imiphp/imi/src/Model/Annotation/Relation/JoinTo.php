<?php

declare(strict_types=1);

namespace Imi\Model\Annotation\Relation;

use Imi\Bean\Annotation\Base;

/**
 * 关联右侧字段.
 *
 * @Annotation
 * @Target("PROPERTY")
 *
 * @property string|null $field 字段名
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class JoinTo extends Base
{
    /**
     * {@inheritDoc}
     */
    protected ?string $defaultFieldName = 'field';

    public function __construct(?array $__data = null, ?string $field = null)
    {
        parent::__construct(...\func_get_args());
    }
}
