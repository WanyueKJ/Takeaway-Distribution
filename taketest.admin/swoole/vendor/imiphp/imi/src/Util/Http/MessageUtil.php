<?php

declare(strict_types=1);

namespace Imi\Util\Http;

class MessageUtil
{
    /**
     * 处理消息的 getHeaders() 方法返回值
     * 键值对应数组，值从数组变为字符串.
     */
    public static function headersToStringList(array $headers): array
    {
        $result = [];
        foreach ($headers as $name => $values)
        {
            $result[$name] = implode(', ', $values);
        }

        return $result;
    }
}
