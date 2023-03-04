<?php

declare(strict_types=1);

namespace Imi\Cache\Handler;

use Imi\Bean\Annotation\Bean;
use Imi\Redis\Redis as ImiRedis;
use Imi\Util\DateTime;

/**
 * @Bean("RedisCache")
 */
class Redis extends Base
{
    /**
     * Redis连接池名称.
     */
    protected ?string $poolName = null;

    /**
     * 缓存键前缀
     */
    protected string $prefix = '';

    /**
     * 将 key 中的 "." 替换为 ":".
     */
    protected bool $replaceDot = false;

    /**
     * {@inheritDoc}
     */
    public function get($key, $default = null)
    {
        $this->checkKey($key);
        $result = ImiRedis::use(fn (\Imi\Redis\RedisHandler $redis) => $redis->get($this->parseKey($key)), $this->poolName, true);
        if (false === $result)
        {
            return $default;
        }
        else
        {
            return $this->decode($result);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function set($key, $value, $ttl = null)
    {
        $this->checkKey($key);
        // ttl 支持 \DateInterval 格式
        if ($ttl instanceof \DateInterval)
        {
            $ttl = DateTime::getSecondsByInterval($ttl);
        }

        return (bool) ImiRedis::use(fn (\Imi\Redis\RedisHandler $redis) => $redis->set($this->parseKey($key), $this->encode($value), $ttl), $this->poolName, true);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        $this->checkKey($key);

        return (bool) ImiRedis::use(fn (\Imi\Redis\RedisHandler $redis) => $redis->del($this->parseKey($key)) > 0, $this->poolName, true);
    }

    /**
     * {@inheritDoc}
     */
    public function clear()
    {
        return (bool) ImiRedis::use(fn (\Imi\Redis\RedisHandler $redis) => $redis->flushDB(), $this->poolName, true);
    }

    /**
     * {@inheritDoc}
     */
    public function getMultiple($keys, $default = null)
    {
        $this->checkArrayOrTraversable($keys);
        $mgetResult = ImiRedis::use(function (\Imi\Redis\RedisHandler $redis) use ($keys) {
            foreach ($keys as &$key)
            {
                $key = $this->parseKey($key);
            }

            return $redis->mget($keys);
        }, $this->poolName, true);
        $result = [];
        if ($mgetResult)
        {
            foreach ($mgetResult as $i => $v)
            {
                if (false === $v)
                {
                    $result[$keys[$i]] = $default;
                }
                else
                {
                    $result[$keys[$i]] = $this->decode($v);
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function setMultiple($values, $ttl = null)
    {
        $this->checkArrayOrTraversable($values);
        if ($values instanceof \Traversable)
        {
            $setValues = clone $values;
        }
        else
        {
            $setValues = $values;
        }
        foreach ($setValues as $k => $v)
        {
            $setValues[$this->parseKey($k)] = $this->encode($v);
        }
        // ttl 支持 \DateInterval 格式
        if ($ttl instanceof \DateInterval)
        {
            $ttl = DateTime::getSecondsByInterval($ttl);
        }
        $result = ImiRedis::use(static function (\Imi\Redis\RedisHandler $redis) use ($setValues, $ttl) {
            $result = $redis->mset($setValues);
            if (null !== $ttl)
            {
                foreach ($setValues as $k => $v)
                {
                    $result = $result && $redis->expire($k, $ttl);
                }
            }

            return $result;
        }, $this->poolName, true);

        return (bool) $result;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteMultiple($keys)
    {
        $this->checkArrayOrTraversable($keys);

        return (bool) ImiRedis::use(function (\Imi\Redis\RedisHandler $redis) use ($keys) {
            foreach ($keys as &$key)
            {
                $key = $this->parseKey($key);
            }

            return $redis->del($keys);
        }, $this->poolName, true);
    }

    /**
     * {@inheritDoc}
     */
    public function has($key)
    {
        $this->checkKey($key);

        return (bool) ImiRedis::use(fn (\Imi\Redis\RedisHandler $redis) => $redis->exists($this->parseKey($key)), $this->poolName, true);
    }

    /**
     * 处理键.
     */
    public function parseKey(string $key): string
    {
        if ($this->replaceDot)
        {
            $key = str_replace('.', ':', $key);
        }
        if ($this->prefix)
        {
            $key = $this->prefix . $key;
        }

        return $key;
    }
}
