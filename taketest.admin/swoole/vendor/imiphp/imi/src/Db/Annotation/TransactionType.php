<?php

declare(strict_types=1);

namespace Imi\Db\Annotation;

class TransactionType
{
    /**
     * 事务嵌套.
     */
    public const NESTING = 'Nesting';

    /**
     * 该方法必须在事务中被调用.
     */
    public const REQUIREMENT = 'requirement';

    /**
     * 如果当前不在事务中则开启事务
     */
    public const AUTO = 'auto';

    private function __construct()
    {
    }
}
