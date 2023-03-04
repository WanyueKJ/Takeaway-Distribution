<?php

declare(strict_types=1);

namespace Imi\Cron;

use Imi\Util\Traits\TDataToProperty;

/**
 * 定时规则.
 */
class CronRule
{
    use TDataToProperty;

    /**
     * 年.
     *
     * 指定任务执行年份，默认为 `*`。
     * `*` - 不限制
     * `2019` - 指定年
     * `2019-2022` - 指定年份区间
     * `2019,2021,2022` - 指定多个年份
     * `2n` - 每 2 年，其它以此类推
     */
    private string $year = '*';

    /**
     * 月.
     *
     * 指定任务执行月份，默认为 `*`。
     * `*` - 不限制
     * `1` (1 月), `-1` (12 月) - 指定月份，支持负数为倒数的月
     * `1-6` (1-6 月), `-3--1` (10-12 月) - 指定月份区间，支持负数为倒数的月
     * `1,3,5,-1` (1、3、5、12 月) - 指定多个月份，支持负数为倒数的月
     * `2n` - 每 2 个月，其它以此类推
     */
    private string $month = '*';

    /**
     * 日.
     *
     * 指定任务执行日期，默认为 `*`。
     * `*` - 不限制
     * `1` (1 日), `-1` (每月最后一天) - 指定日期，支持负数为倒数的日期
     * `1-6` (1-6 日), `-3--1` (每月倒数 3 天) - 指定日期区间，支持负数为倒数的日期
     * `1,3,5,-1` (每月 1、3、5、最后一天) - 指定多个日期，支持负数为倒数的日期
     * `2n` - 每 2 天，其它以此类推
     * `year 1` (一年中的第 1 日), `year -1` (每年最后一天) - 指定一年中的日期，支持负数为倒数的日期
     * `year 1-6` (一年中的第 1-6 日), `year -3--1` (每年倒数 3 天) - 指定一年中的日期区间，支持负数为倒数的日期
     * `year 1,3,5,-1` (每年 1、3、5、最后一天) - 指定一年中的多个日期，支持负数为倒数的日期
     */
    private string $day = '*';

    /**
     * 周几.
     *
     * 指定周几执行任务，默认为 `*`。
     * `*` - 不限制
     * `1` (周一), `-1` (周日) - 指定周几（1-7），支持负数为倒数的周
     * `1-6` (周一到周六), `-3--1` (周五到周日) - 指定周几，支持负数为倒数的周
     * `1,3,5,-1` (周一、三、五、日) - 指定多个日期，支持负数为倒数的周
     */
    private string $week = '*';

    /**
     * 小时.
     *
     * 指定任务执行小时，默认为 `*`。
     * `*` - 不限制
     * `0` (0 点), `-1` (23 点) - 指定小时，支持负数为倒数的小时
     * `1-6` (1-6 店), `-3--1` (21-23 点) - 指定小时区间，支持负数为倒数的小时
     * `1,3,5,-1` (1、3、5、23 点) - 指定多个小时，支持负数为倒数的小时
     * `2n` - 每 2 小时，其它以此类推
     */
    private string $hour = '*';

    /**
     * 分钟
     *
     * 指定任务执行分钟，默认为 `*`。
     * `*` - 不限制
     * `0` (0 分), `-1` (23 分) - 指定分钟，支持负数为倒数的分钟
     * `1-6` (1-6 分), `-3--1` (57-59 分) - 指定分钟区间，支持负数为倒数的分钟
     * `1,3,5,-1` (1、3、5、59 分) - 指定多个分钟，支持负数为倒数的分钟
     * `2n` - 每 2 分钟，其它以此类推
     */
    private string $minute = '*';

    /**
     * 秒.
     *
     * 指定任务执行秒，默认为 `*`。
     * `*` - 不限制
     * `0` (0 秒), `-1` (23 秒) - 指定秒，支持负数为倒数的秒
     * `1-6` (1-6 秒), `-3--1` (57-59 秒) - 指定秒区间，支持负数为倒数的秒
     * `1,3,5,-1` (1、3、5、59 秒) - 指定多个秒，支持负数为倒数的秒
     * `2n` - 每 2 秒，其它以此类推
     */
    private string $second = '*';

    /**
     * 最小延迟执行秒数.
     */
    private int $delayMin = 0;

    /**
     * 最大延迟执行秒数.
     */
    private int $delayMax = 0;

    /**
     * 年.
     */
    public function getYear(): string
    {
        return $this->year;
    }

    /**
     * 月.
     */
    public function getMonth(): string
    {
        return $this->month;
    }

    /**
     * 日.
     */
    public function getDay(): string
    {
        return $this->day;
    }

    /**
     * 周.
     */
    public function getWeek(): string
    {
        return $this->week;
    }

    /**
     * 小时.
     */
    public function getHour(): string
    {
        return $this->hour;
    }

    /**
     * 分钟
     */
    public function getMinute(): string
    {
        return $this->minute;
    }

    /**
     * 秒.
     */
    public function getSecond(): string
    {
        return $this->second;
    }

    /**
     * 最小延迟执行秒数.
     */
    public function getDelayMin(): int
    {
        return $this->delayMin;
    }

    /**
     * 最大延迟执行秒数.
     */
    public function getDelayMax(): int
    {
        return $this->delayMax;
    }
}
