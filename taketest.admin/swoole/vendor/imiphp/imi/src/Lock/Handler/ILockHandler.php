<?php

declare(strict_types=1);

namespace Imi\Lock\Handler;

interface ILockHandler
{
    public function __construct(string $id, array $options = []);

    /**
     * 加锁，会挂起协程.
     *
     * @param callable|null $taskCallable      加锁后执行的任务，可为空；如果不为空，则执行完后自动解锁
     * @param callable|null $afterLockCallable 当获得锁后执行的回调，只有当 $taskCallable 不为 null 时有效。该回调返回 true 则不执行 $taskCallable
     */
    public function lock(?callable $taskCallable = null, ?callable $afterLockCallable = null): bool;

    /**
     * 尝试获取锁
     *
     * @param callable|null $taskCallable 加锁后执行的任务，可为空；如果不为空，则执行完后自动解锁
     */
    public function tryLock(?callable $taskCallable = null): bool;

    /**
     * 解锁
     */
    public function unlock(): bool;

    /**
     * 获取当前是否已获得锁状态
     */
    public function isLocked(): bool;

    /**
     * 获取锁的唯一ID.
     */
    public function getId(): string;

    /**
     * 解锁并释放所有资源.
     */
    public function close(): void;

    /**
     * Get 等待锁超时时间，单位：毫秒，0为不限制.
     */
    public function getWaitTimeout(): int;

    /**
     * Get 锁超时时间，单位：毫秒.
     */
    public function getLockExpire(): int;

    /**
     * 获取获得锁的标志.
     */
    public function getLockFlag(): string;
}
