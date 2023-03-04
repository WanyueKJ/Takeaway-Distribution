<?php

declare(strict_types=1);

namespace Imi\Model\Annotation;

use Imi\Bean\Annotation\Base;
use Imi\Model\Enum\RedisStorageMode;

/**
 * Redis模型注解.
 *
 * @Annotation
 * @Target("CLASS")
 *
 * @property string|null $poolName  redis 连接池名称
 * @property int|null    $db        第几个库，不传为null时使用连接池默认配置
 * @property string      $key       键，支持定义多个参数，格式：{key}
 * @property string      $member    redis hash 成员标识，支持定义多个参数，格式：{key}；仅 hash 存储模式有效
 * @property int|null    $ttl       数据默认的过期时间，null为永不过期；hash 存储模式不支持过期
 * @property string      $storage   Redis 实体类存储模式；支持 string、hash
 * @property string|null $formatter 格式
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class RedisEntity extends Base
{
    /**
     * {@inheritDoc}
     */
    protected ?string $defaultFieldName = 'poolName';

    public function __construct(?array $__data = null, ?string $poolName = null, ?int $db = null, string $key = '{key}', string $member = '{member}', ?int $ttl = null, string $storage = RedisStorageMode::STRING, ?string $formatter = null)
    {
        parent::__construct(...\func_get_args());
    }
}
