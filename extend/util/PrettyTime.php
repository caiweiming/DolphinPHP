<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2024 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://www.dolphinphp.com
// +----------------------------------------------------------------------
// | 作者: 蔡伟明 <314013107@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace util;

/**
 * 友好时间格式
 */
class PrettyTime
{
    private const MINUTE = 60;
    private const HOUR = 3600;
    private const DAY = 86400;
    private const WEEK = 604800;
    private const MONTH = 2592000;
    private const YEAR = 31536000;

    /**
     * 获取友好的时间显示
     * @param int|string $timestamp
     * @return string
     */
    public static function format(int|string $timestamp): string
    {
        // 转换为时间戳
        if (is_string($timestamp)) {
            $timestamp = strtotime($timestamp);
        }

        if (empty($timestamp)) {
            return '';
        }

        // 获取当前时间
        $now = time();

        // 获取日期边界
        $todayStart              = strtotime('today');
        $tomorrowStart           = strtotime('tomorrow');
        $dayAfterTomorrowStart   = strtotime('+2 days midnight');
        $yesterdayStart          = strtotime('yesterday');
        $dayBeforeYesterdayStart = strtotime('-2 days midnight');

        // 计算时间差
        $diff     = $now - $timestamp;
        $isFuture = $diff < 0;
        $diff     = abs($diff);

        // 优先处理特殊日期
        if ($isFuture) {
            if ($timestamp >= $tomorrowStart && $timestamp < $dayAfterTomorrowStart) {
                return '明天';
            }
            if ($timestamp >= $dayAfterTomorrowStart && $timestamp < $dayAfterTomorrowStart + self::DAY) {
                return '后天';
            }
        } else {
            if ($timestamp >= $yesterdayStart && $timestamp < $todayStart) {
                return '昨天';
            }
            if ($timestamp >= $dayBeforeYesterdayStart && $timestamp < $yesterdayStart) {
                return '前天';
            }
        }

        // 处理其他时间范围
        return match (true) {
            // 1分钟内
            $diff < self::MINUTE => $isFuture ? '即将' : '刚刚',
            // 1小时内
            $diff < self::HOUR => self::formatMinutes($diff, $isFuture),
            // 1天内
            $diff < self::DAY => self::formatHours($diff, $isFuture),
            // 1周内
            $diff < self::WEEK => self::formatDays($diff, $isFuture),
            // 1个月内
            $diff < self::MONTH => self::formatWeeks($diff, $isFuture),
            // 1年内
            $diff < self::YEAR => self::formatMonths($diff, $isFuture),
            // 超过1年
            default => self::formatYears($diff, $isFuture)
        };
    }

    /**
     * 格式化分钟
     * @param int $diff
     * @param bool $isFuture
     * @return string
     */
    private static function formatMinutes(int $diff, bool $isFuture): string
    {
        $minutes = (int)floor($diff / self::MINUTE);

        return match ($minutes) {
            1 => $isFuture ? '1分钟后' : '1分钟前',
            default => "{$minutes}分钟" . ($isFuture ? '后' : '前')
        };
    }

    /**
     * 格式化小时
     * @param int $diff
     * @param bool $isFuture
     * @return string
     */
    private static function formatHours(int $diff, bool $isFuture): string
    {
        $hours = (int)floor($diff / self::HOUR);
        return match ($hours) {
            1 => $isFuture ? '1小时后' : '1小时前',
            default => "{$hours}小时" . ($isFuture ? '后' : '前')
        };
    }

    /**
     * 格式化天数
     * @param int $diff
     * @param bool $isFuture
     * @return string
     */
    private static function formatDays(int $diff, bool $isFuture): string
    {
        $days = (int)floor($diff / self::DAY);
        return "{$days}天" . ($isFuture ? '后' : '前');

    }

    /**
     * 格式化周数
     * @param int $diff
     * @param bool $isFuture
     * @return string
     */
    private static function formatWeeks(int $diff, bool $isFuture): string
    {
        $weeks = (int)floor($diff / self::WEEK);
        return match ($weeks) {
            1 => $isFuture ? '下周' : '上周',
            default => "{$weeks}周" . ($isFuture ? '后' : '前')
        };
    }

    /**
     * 格式化月数
     * @param int $diff
     * @param bool $isFuture
     * @return string
     */
    private static function formatMonths(int $diff, bool $isFuture): string
    {
        $months = (int)floor($diff / self::MONTH);
        return match ($months) {
            1 => $isFuture ? '下个月' : '上个月',
            default => "{$months}个月" . ($isFuture ? '后' : '前')
        };
    }

    /**
     * 格式化年数
     * @param int $diff
     * @param bool $isFuture
     * @return string
     */
    private static function formatYears(int $diff, bool $isFuture): string
    {
        $years = (int)floor($diff / self::YEAR);

        if ($years >= 10) {
            return date('Y-m-d', $isFuture ? time() + $diff : time() - $diff);
        }

        return match ($years) {
            1 => $isFuture ? '明年' : '去年',
            default => "{$years}年" . ($isFuture ? '后' : '前')
        };
    }
}
