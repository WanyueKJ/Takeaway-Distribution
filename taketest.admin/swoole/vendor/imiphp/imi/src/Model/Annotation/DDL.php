<?php

declare(strict_types=1);

namespace Imi\Model\Annotation;

use Imi\Bean\Annotation\Base;

/**
 * 创建表语句注解.
 *
 * @Annotation
 * @Target("CLASS")
 *
 * @property string $sql    表结构 SQL；CREATE TABLE 语句
 * @property string $decode SQL 解码函数
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class DDL extends Base
{
    /**
     * {@inheritDoc}
     */
    protected ?string $defaultFieldName = 'sql';

    public function __construct(?array $__data = null, string $sql = '', string $decode = '')
    {
        parent::__construct(...\func_get_args());
    }

    /**
     * 获取真实 SQL，如果需要解码会自动解码
     */
    public function getRawSql(): string
    {
        if ('' === $this->decode)
        {
            return $this->sql;
        }
        else
        {
            return ($this->decode)($this->sql);
        }
    }
}
