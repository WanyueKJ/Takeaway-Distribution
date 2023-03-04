<?php

declare(strict_types=1);

namespace Imi\Pool;

use Imi\Pool\Interfaces\IPool;
use Imi\Pool\Interfaces\IPoolResource;
use Imi\Util\Traits\THashCode;

abstract class BasePoolResource implements IPoolResource
{
    use THashCode;

    /**
     * 池子实例.
     */
    private IPool $pool;

    public function __construct(IPool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * {@inheritDoc}
     */
    public function getPool(): IPool
    {
        return $this->pool;
    }
}
