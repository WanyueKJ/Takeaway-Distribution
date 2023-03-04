<?php

declare(strict_types=1);

namespace Imi\Cron;

use Imi\App;
use Imi\Bean\Annotation\Bean;
use Imi\Cron\Consts\UniqueLevel;
use Imi\Util\Process\ProcessAppContexts;

/**
 * @Bean("CronLock")
 */
class CronLock
{
    /**
     * 锁列表.
     *
     * @var \Imi\Lock\Handler\ILockHandler[]
     */
    private array $locks = [];

    /**
     * 无需锁的列表.
     */
    private array $noLocks = [];

    /**
     * 加锁
     *
     * @param \Imi\Cron\CronTask $task
     */
    public function lock(CronTask $task): bool
    {
        $id = $task->getId();
        $locks = &$this->locks;
        if (isset($locks[$id]))
        {
            /** @var \Imi\Lock\Handler\ILockHandler $lock */
            $lock = $locks[$id];
        }
        else
        {
            switch ($task->getUnique())
            {
                case null:
                    return $this->noLocks[$id] = true;
                case UniqueLevel::ALL:
                    $keyPrefix = 'imi:cron:lock:unique:all:';
                    break;
                case UniqueLevel::CURRENT:
                    $keyPrefix = 'imi:cron:lock:unique:' . App::get(ProcessAppContexts::MASTER_PID) . ':';
                    break;
                default:
                    throw new \InvalidArgumentException(sprintf('Invalid unique type %s', $task->getUnique()));
            }
            /** @var \Imi\Lock\Handler\ILockHandler $lock */
            $lock = $locks[$id] = App::getBean('RedisLock', $id, [
                'poolName'      => $task->getRedisPool(),
                'waitTimeout'   => (int) ($task->getLockWaitTimeout() * 1000),
                'lockExpire'    => (int) ($task->getMaxExecutionTime() * 1000),
                'keyPrefix'     => $keyPrefix,
            ]);
        }

        return $lock->lock();
    }

    /**
     * 解锁
     *
     * @param \Imi\Cron\CronTask $task
     */
    public function unlock(CronTask $task): bool
    {
        $id = $task->getId();
        $locks = &$this->locks;
        if (!isset($locks[$id]))
        {
            return isset($this->noLocks[$id]);
        }

        return $locks[$id]->unlock();
    }
}
