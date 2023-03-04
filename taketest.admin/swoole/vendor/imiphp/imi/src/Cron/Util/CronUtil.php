<?php

declare(strict_types=1);

namespace Imi\Cron\Util;

use Imi\App;
use Imi\Cron\Annotation\Cron;
use Imi\Cron\Client;
use Imi\Cron\Message\AddCron;
use Imi\Cron\Message\Clear;
use Imi\Cron\Message\RemoveCron;
use Imi\Cron\Message\Result;
use Imi\Log\Log;

class CronUtil
{
    private function __construct()
    {
    }

    /**
     * 上报定时任务结果.
     */
    public static function reportCronResult(string $id, bool $success, string $message): void
    {
        $client = new Client([
            // @phpstan-ignore-next-line
            'socketFile'    => App::getBean('CronManager')->getSocketFile(),
        ]);
        if ($client->connect())
        {
            $result = new Result('cronTask', $id, $success, $message);
            $client->send($result);
            $client->close();
        }
        else
        {
            Log::error('Cannot connect to CronProcess');
        }
    }

    /**
     * 增加 Cron 任务
     *
     * @param callable|string $task
     */
    public static function addCron(Cron $cron, $task): void
    {
        $client = new Client([
            // @phpstan-ignore-next-line
            'socketFile'    => App::getBean('CronManager')->getSocketFile(),
        ]);
        if ($client->connect())
        {
            $result = new AddCron();
            $result->cronAnnotation = $cron;
            $result->task = $task;
            $client->send($result);
            $client->close();
        }
        else
        {
            Log::error('Cannot connect to CronProcess');
        }
    }

    /**
     * 移除定时任务
     */
    public static function removeCron(string $id): void
    {
        $client = new Client([
            // @phpstan-ignore-next-line
            'socketFile'    => App::getBean('CronManager')->getSocketFile(),
        ]);
        if ($client->connect())
        {
            $result = new RemoveCron();
            $result->id = $id;
            $client->send($result);
            $client->close();
        }
        else
        {
            Log::error('Cannot connect to CronProcess');
        }
    }

    /**
     * 清空定时任务
     */
    public static function clear(): void
    {
        $client = new Client([
            // @phpstan-ignore-next-line
            'socketFile'    => App::getBean('CronManager')->getSocketFile(),
        ]);
        if ($client->connect())
        {
            $result = new Clear();
            $client->send($result);
            $client->close();
        }
        else
        {
            Log::error('Cannot connect to CronProcess');
        }
    }
}
