<?php

declare(strict_types=1);

namespace Imi\Util;

/**
 * 日期时间工具类.
 */
class DateTime
{
    private function __construct()
    {
    }

    /**
     * 将一个 \DateInterval，与当前时间进行计算，获取秒数.
     */
    public static function getSecondsByInterval(\DateInterval $dateInterval): int
    {
        $dateTime = new \DateTime();
        $dateTime->add($dateInterval);

        return $dateTime->getTimestamp() - time();
    }

    /**
     * 获取昨天的时间.
     *
     * 可传入 $format 格式化，不传则返回时间戳
     * 可传入 $timestamp 指定时间戳，不传则取当前时间
     *
     * @return string|int
     */
    public static function getYesterday(?string $format = null, ?int $timestamp = null)
    {
        if (null === $timestamp)
        {
            $timestamp = time();
        }
        $timestamp -= 86400;
        if (null === $format)
        {
            return $timestamp;
        }

        return date($format, $timestamp);
    }

    /**
     * 获取明天的时间.
     *
     * 可传入 $format 格式化，不传则返回时间戳
     * 可传入 $timestamp 指定时间戳，不传则取当前时间
     *
     * @return string|int
     */
    public static function getTomorrow(?string $format = null, ?int $timestamp = null)
    {
        if (null === $timestamp)
        {
            $timestamp = time();
        }
        $timestamp += 86400;
        if (null === $format)
        {
            return $timestamp;
        }

        return date($format, $timestamp);
    }

    /**
     * 获取下周的时间.
     *
     * 可传入 $weekNo 指定周几，周一到周日为1-7，不传则取时间戳对应周几
     * 可传入 $format 格式化，不传则返回时间戳
     * 可传入 $timestamp 指定时间戳，不传则取当前时间
     *
     * @return string|int
     */
    public static function getNextWeek(?int $weekNo = null, ?string $format = null, ?int $timestamp = null)
    {
        if (null === $timestamp)
        {
            $timestamp = time();
        }
        $currentWeek = date('N', $timestamp);
        $timestamp += ((7 - $currentWeek + ($weekNo ?? $currentWeek)) * 86400);
        if (null === $format)
        {
            return $timestamp;
        }

        return date($format, $timestamp);
    }

    /**
     * 获取上周的时间.
     *
     * 可传入 $weekNo 指定周几，周一到周日为1-7，不传则取时间戳对应周几
     * 可传入 $format 格式化，不传则返回时间戳
     * 可传入 $timestamp 指定时间戳，不传则取当前时间
     *
     * @return string|int
     */
    public static function getLastWeek(?int $weekNo = null, ?string $format = null, ?int $timestamp = null)
    {
        if (null === $timestamp)
        {
            $timestamp = time();
        }
        $currentWeek = date('N', $timestamp);
        $timestamp -= ((7 - $currentWeek + ($weekNo ?? $currentWeek)) * 86400);
        if (null === $format)
        {
            return $timestamp;
        }

        return date($format, $timestamp);
    }

    /**
     * 获取一个月中的第几周.
     *
     * 可传入 $weekNo 指定周几，周一到周日为1-7，不传则取时间戳对应周几
     * 可传入 $format 格式化，不传则返回时间戳
     * 可传入 $timestamp 指定时间戳，不传则取当前时间
     */
    public static function getWeekOfMonth(?int $timestamp = null): int
    {
        if (null === $timestamp)
        {
            $timestamp = time();
        }
        $y = date('Y', $timestamp);
        $m = date('m', $timestamp);
        $firstDay = strtotime($y . '-' . $m . '-1');
        $week = date('N', $firstDay);
        $days = (int) (($timestamp - $firstDay) / 86400) + 1;
        if (1 == $week)
        {
            $weeks = 0;
        }
        else
        {
            $weeks = 1;
            $days -= (8 - $week);
        }
        $weeks += (int) ($days / 7);
        if ($days % 7 > 0)
        {
            ++$weeks;
        }

        return $weeks;
    }

    /**
     * 获取该月份一共有多少周.
     *
     * 返回值可能是 4 或 5
     */
    public static function getMonthWeekCount(int $year, int $month): int
    {
        $firstDay = strtotime($year . '-' . $month . '-1');
        $week = date('N', $firstDay);
        $days = date('t', $firstDay);
        if (1 == $week)
        {
            $weeks = 0;
        }
        else
        {
            $weeks = 1;
            $days -= (8 - $week);
        }
        $weeks += (int) ($days / 7) + 1;

        return $weeks;
    }
}
