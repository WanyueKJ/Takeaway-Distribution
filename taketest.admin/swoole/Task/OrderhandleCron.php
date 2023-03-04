<?php
namespace ImiApp\Task;

use Imi\Swoole\Task\TaskParam;
use Imi\Cron\Annotation\Cron;
use Imi\Swoole\Task\Annotation\Task;
use Imi\Cron\Util\CronUtil;
use Imi\Swoole\Task\Interfaces\ITaskHandler;
use ImiApp\Models\Orders;

/**
 * @Cron(id="OrderhandleCron", second="3n", data={"id":"OrderhandleCron"})
 * @Task("OrderhandleCron")
 */
class OrderhandleCron implements ITaskHandler
{

    /**
     * 任务处理方法.
     *
     * @return mixed
     */
    public function handle(TaskParam $param, \Swoole\Server $server, int $taskId, int $workerId)
    {

        Orders::cancel();

        Orders::storeCancel();

        Orders::newNotice();

        Orders::newMerNotice();

        Orders::dispatchNotice();

        Orders::transNotice();

        Orders::refundNotice();

        Orders::timeOuNotifice();

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