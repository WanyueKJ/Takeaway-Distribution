<?php

namespace ImiApp\Task;
use Imi\Swoole\Task\TaskParam;
use Imi\Cron\Annotation\Cron;
use Imi\Swoole\Task\Annotation\Task;
use Imi\Cron\Util\CronUtil;
use Imi\Swoole\Task\Interfaces\ITaskHandler;
use ImiApp\Models\MerchantStoreProduct;
use ImiApp\Models\MerchantStore;

/**
 * 每天定时任务
 * @Cron(id="EverydayCron", day="1n", data={"id":"EverydayCron"})
 * @Task("EverydayCron")
 */
class EverydayCron implements ITaskHandler
{
    /**
     * 任务处理方法.
     *
     * @return mixed
     */
    public function handle(TaskParam $param, \Swoole\Server $server, int $taskId, int $workerId)
    {

        MerchantStore::resetViews();
        MerchantStoreProduct::fillingTheInventory();
        // 上报任务完成
        CronUtil::reportCronResult($param->getData()['id'], true, '');
        return date('Y-m-d H:i:s');
    }

    /**
     * 任务结束时触发.
     *
     * @param mixed $data
     */
    public function finish(\Swoole\Server $server, int $taskId, $data): void
    {
    }
}