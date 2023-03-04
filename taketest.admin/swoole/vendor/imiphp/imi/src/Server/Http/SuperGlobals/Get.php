<?php

declare(strict_types=1);

namespace Imi\Server\Http\SuperGlobals;

use Imi\Log\Log;
use Imi\RequestContext;

class Get implements \ArrayAccess, \JsonSerializable
{
    /**
     * @param int|string $offset
     * @param mixed      $value
     */
    public function offsetSet($offset, $value): void
    {
        Log::warning('imi does not support to assign values to $_GET');
    }

    /**
     * @param int|string $offset
     */
    public function offsetExists($offset): bool
    {
        /** @var \Imi\Server\Http\Message\Request $request */
        $request = RequestContext::get('request');

        return null !== $request->get($offset);
    }

    /**
     * @param int|string $offset
     */
    public function offsetUnset($offset): void
    {
        Log::warning('imi does not support to unset values from $_GET');
    }

    /**
     * @param int|string $offset
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        /** @var \Imi\Server\Http\Message\Request $request */
        $request = RequestContext::get('request');

        return $request->get($offset);
    }

    public function __debugInfo(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        /** @var \Imi\Server\Http\Message\Request $request */
        $request = RequestContext::get('request');

        return $request->get();
    }
}
