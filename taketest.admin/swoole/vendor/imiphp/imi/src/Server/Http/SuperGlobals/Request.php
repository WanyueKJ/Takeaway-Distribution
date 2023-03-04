<?php

declare(strict_types=1);

namespace Imi\Server\Http\SuperGlobals;

use Imi\Log\Log;
use Imi\RequestContext;

class Request implements \ArrayAccess, \JsonSerializable
{
    /**
     * @param int|string $offset
     * @param mixed      $value
     */
    public function offsetSet($offset, $value): void
    {
        Log::warning('imi does not support to assign values to $_REQUEST');
    }

    /**
     * @param int|string $offset
     */
    public function offsetExists($offset): bool
    {
        /** @var \Imi\Server\Http\Message\Request $request */
        $request = RequestContext::get('request');

        return null !== $request->request($offset);
    }

    /**
     * @param int|string $offset
     */
    public function offsetUnset($offset): void
    {
        Log::warning('imi does not support to unset values from $_REQUEST');
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

        return $request->request($offset);
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

        return $request->request();
    }
}
