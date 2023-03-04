<?php

declare(strict_types=1);

namespace Imi\Model\Tree\Annotation;

use Imi\Bean\Annotation\Base;

/**
 * 提取字段中的属性到当前模型.
 *
 * @Annotation
 * @Target("CLASS")
 *
 * @property string|null $idField       主键字段名；默认为null，则自动获取
 * @property string      $parentField   父级ID字段名
 * @property string      $childrenField 子集字段名
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class TreeModel extends Base
{
    public function __construct(?array $__data = null, ?string $idField = null, string $parentField = 'parent_id', string $childrenField = 'children')
    {
        parent::__construct(...\func_get_args());
    }
}
