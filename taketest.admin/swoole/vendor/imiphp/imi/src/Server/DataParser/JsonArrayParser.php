<?php

declare(strict_types=1);

namespace Imi\Server\DataParser;

class JsonArrayParser implements IParser
{
    /**
     * 由以下常量组成的二进制掩码：
     * JSON_HEX_QUOT
     * JSON_HEX_TAG
     * JSON_HEX_AMP
     * JSON_HEX_APOS
     * JSON_NUMERIC_CHECK
     * JSON_PRETTY_PRINT
     * JSON_UNESCAPED_SLASHES
     * JSON_FORCE_OBJECT
     * JSON_PRESERVE_ZERO_FRACTION
     * JSON_UNESCAPED_UNICODE
     * JSON_PARTIAL_OUTPUT_ON_ERROR。
     */
    protected int $options = \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE;

    /**
     * 设置最大深度。 必须大于0。
     */
    protected int $depth = 512;

    /**
     * {@inheritDoc}
     */
    public function encode($data): string
    {
        return json_encode($data, $this->options, $this->depth);
    }

    /**
     * {@inheritDoc}
     */
    public function decode(string $data)
    {
        return json_decode($data, true, $this->depth, $this->options);
    }
}
