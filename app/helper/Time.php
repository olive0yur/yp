<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 刘志淳 <chun@engineer.com>
// +----------------------------------------------------------------------

namespace app\helper;

class Time
{

    /**
     * 获取当前时间
     *
     * @param bool $format 是否格式化
     * @return false|int|string
     */
    public static function getCurrentTime($format = false)
    {
        if ($format) {
            return date('Y-m-d H:i:s');
        } else {
            return time();
        }
    }

    /**
     * 返回今日开始和结束的时间
     *
     * @param bool $format 是否格式化
     * @return array
     */
    public static function today($format = false)
    {
        list($y, $m, $d) = explode('-', date('Y-m-d'));
        if ($format) {
            return [
                date('Y-m-d H:i:s', mktime(0, 0, 0, $m, $d, $y)),
                date('Y-m-d H:i:s', mktime(23, 59, 59, $m, $d, $y))
            ];
        } else {
            return [
                mktime(0, 0, 0, $m, $d, $y),
                mktime(23, 59, 59, $m, $d, $y)
            ];
        }
    }

    /**
     * 返回昨日开始和结束的时间
     *
     * @param bool $format 是否格式化
     * @return array
     */
    public static function yesterday($format = false)
    {
        $yesterday = date('d') - 1;
        if ($format) {
            return [
                date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), $yesterday, date('Y'))),
                date('Y-m-d H:i:s', mktime(23, 59, 59, date('m'), $yesterday, date('Y')))
            ];
        } else {
            return [
                mktime(0, 0, 0, date('m'), $yesterday, date('Y')),
                mktime(23, 59, 59, date('m'), $yesterday, date('Y'))
            ];
        }
    }

    /**
     * 返回本周开始和结束的时间
     *
     * @param bool $format 是否格式化
     * @return array
     */
    public static function week($format = false)
    {
        list($y, $m, $d, $w) = explode('-', date('Y-m-d-w'));
        if ($w == 0)
            $w = 7; //修正周日的问题
        if ($format) {
            return [
                date('Y-m-d H:i:s', mktime(0, 0, 0, $m, $d - $w + 1, $y)),
                date('Y-m-d H:i:s', mktime(23, 59, 59, $m, $d - $w + 7, $y))
            ];
        } else {
            return [
                mktime(0, 0, 0, $m, $d - $w + 1, $y),
                mktime(23, 59, 59, $m, $d - $w + 7, $y)
            ];
        }
    }

    /**
     * 返回上周开始和结束的时间
     *
     * @param bool $format 是否格式化
     * @return array
     */
    public static function lastWeek($format = false)
    {
        $timestamp = time();
        if ($format) {
            return [
                date('Y-m-d', strtotime("last week Monday", $timestamp)),
                date('Y-m-d', strtotime("last week Sunday", $timestamp) + 24 * 3600 - 1)
            ];
        } else {
            return [
                strtotime(date('Y-m-d', strtotime("last week Monday", $timestamp))),
                strtotime(date('Y-m-d', strtotime("last week Sunday", $timestamp))) + 24 * 3600 - 1
            ];
        }
    }

    /**
     * 返回本月开始和结束的时间
     *
     * @param bool $format 是否格式化
     * @return array
     */
    public static function month($format = false)
    {
        list($y, $m, $t) = explode('-', date('Y-m-t'));
        if ($format) {
            return [
                date('Y-m-d H:i:s', mktime(0, 0, 0, $m, 1, $y)),
                date('Y-m-d H:i:s', mktime(23, 59, 59, $m, $t, $y))
            ];
        } else {
            return [
                mktime(0, 0, 0, $m, 1, $y),
                mktime(23, 59, 59, $m, $t, $y)
            ];
        }
    }

    /**
     * 返回上个月开始和结束的时间
     *
     * @param bool $format 是否格式化
     * @return array
     */
    public static function lastMonth($format = false)
    {
        $y = date('Y');
        $m = date('m');
        $beginTime = mktime(0, 0, 0, $m - 1, 1, $y);
        $endTime = mktime(23, 59, 59, $m - 1, date('t', $beginTime), $y);
        if ($format) {
            $begin = date('Y-m-d H:i:s', $beginTime);
            $end = date('Y-m-d H:i:s', $endTime);
        } else {
            $begin = $beginTime;
            $end = $endTime;
        }
        return [$begin, $end];
    }

    /**
     * 返回今年开始和结束的时间
     *
     * @param bool $format 是否格式化
     * @return array
     */
    public static function year($format = false)
    {
        $y = date('Y');
        if ($format) {
            return [
                date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1, $y)),
                date('Y-m-d H:i:s', mktime(23, 59, 59, 12, 31, $y))
            ];
        } else {
            return [
                mktime(0, 0, 0, 1, 1, $y),
                mktime(23, 59, 59, 12, 31, $y)
            ];
        }
    }

    /**
     * 返回去年开始和结束的时间
     *
     * @param bool $format 是否格式化
     * @return array
     */
    public static function lastYear($format = false)
    {
        $year = date('Y') - 1;
        if ($format) {
            return [
                date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1, $year)),
                date('Y-m-d H:i:s', mktime(23, 59, 59, 12, 31, $year))
            ];
        } else {
            return [
                mktime(0, 0, 0, 1, 1, $year),
                mktime(23, 59, 59, 12, 31, $year)
            ];
        }
    }

    public static function dayOf()
    {

    }

    /**
     * 获取几天前零点到现在/昨日结束的时间戳
     *
     * @param int $day 天数
     * @param bool $now 返回现在或者昨天结束时间戳
     * @param bool $format 是否格式化
     * @return array
     */
    public static function dayToNow($day = 1, $now = true, $format = false)
    {
        $end = time();
        if (!$now) {
            list($foo, $end) = self::yesterday();
        }

        if ($format) {
            return [
                date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d') - $day, date('Y'))),
                date('Y-m-d H:i:s', $end)
            ];
        } else {
            return [
                mktime(0, 0, 0, date('m'), date('d') - $day, date('Y')),
                $end
            ];
        }
    }

    /**
     * 返回几天前的时间戳
     *
     * @param int $day
     * @return int
     */
    public static function daysAgo($day = 1)
    {
        $nowTime = time();
        return $nowTime - self::daysToSecond($day);
    }

    /**
     * 返回几天后的时间戳
     *
     * @param int $day
     * @return int
     */
    public static function daysAfter($day = 1)
    {
        $nowTime = time();
        return $nowTime + self::daysToSecond($day);
    }

    /**
     * 天数转换成秒数
     *
     * @param int $day
     * @return int
     */
    public static function daysToSecond($day = 1)
    {
        return $day * 86400;
    }

    /**
     * 周数转换成秒数
     *
     * @param int $week
     * @return int
     */
    public static function weekToSecond($week = 1)
    {
        return self::daysToSecond() * 7 * $week;
    }

    private static function startTimeToEndTime()
    {

    }

    /**
     * 日期时间友好显示
     * @param int|string $sTime 待显示的时间
     * @param string $type 类型. normal | mohu | full | ymd | other
     * @return bool|string
     */
    public static function friendDate($sTime, string $type = 'normal')
    {
        if (!is_numeric($sTime)) {
            $sTime = strtotime($sTime);
        }
        if (!$sTime) {
            return "";
        }
        //sTime=源时间，cTime=当前时间，dTime=时间差
        $cTime = time();
        $dTime = $cTime - $sTime;
        $dDay = intval(date("z", $cTime)) - intval(date("z", $sTime));
        //$dDay     =   intval($dTime/3600/24);
        $dYear = intval(date("Y", $cTime)) - intval(date("Y", $sTime));
        //normal：n秒前，n分钟前，n小时前，日期
        if ($type == 'normal') {
            if ($dTime < 60) {
                if ($dTime < 10) {
                    return '刚刚';    //by yangjs
                } else {
                    return intval(floor($dTime / 10) * 10) . "秒前";
                }
            } elseif ($dTime < 3600) {
                return intval($dTime / 60) . "分钟前";
                //今天的数据.年份相同.日期相同.
            } elseif ($dYear == 0 && $dDay == 0) {
                //return intval($dTime/3600)."小时前";
                return '今天' . date('H:i', $sTime);
            } elseif ($dYear == 0) {
                return date("m月d日 H:i", $sTime);
            } else {
                return date("Y-m-d H:i", $sTime);
            }
        } elseif ($type == 'mohu') {
            if ($dTime < 60) {
                return $dTime . "秒前";
            } elseif ($dTime < 3600) {
                return intval($dTime / 60) . "分钟前";
            } elseif ($dTime >= 3600 && $dDay == 0) {
                return intval($dTime / 3600) . "小时前";
            } elseif ($dDay > 0 && $dDay <= 7) {
                return intval($dDay) . "天前";
            } elseif ($dDay > 7 && $dDay <= 30) {
                return intval($dDay / 7) . '周前';
            } elseif ($dDay > 30) {
                return intval($dDay / 30) . '个月前';
            }
            //full: Y-m-d , H:i:s
        } elseif ($type == 'full') {
            return date("Y-m-d , H:i:s", $sTime);
        } elseif ($type == 'ymd') {
            return date("Y-m-d", $sTime);
        } else {
            if ($dTime < 60) {
                return $dTime . "秒前";
            } elseif ($dTime < 3600) {
                return intval($dTime / 60) . "分钟前";
            } elseif ($dTime >= 3600 && $dDay == 0) {
                return intval($dTime / 3600) . "小时前";
            } elseif ($dYear == 0) {
                return date("Y-m-d H:i:s", $sTime);
            } else {
                return date("Y-m-d H:i:s", $sTime);
            }
        }
    }

    /**
     * 计算两个时间的月份数
     *
     * @param $startTime
     * @param $endTime
     * @return false|float|int|string
     */
    public static function calcMonths($startTime, $endTime)
    {
        if (!is_numeric($startTime)) {
            $startTime = strtotime($startTime);
        }
        if (!is_numeric($endTime)) {
            $endTime = strtotime($endTime);
        }

        $year1 = date('Y', $startTime);
        $year2 = date('Y', $endTime);

        $month1 = date('m', $startTime);
        $month2 = date('m', $endTime);

        $diff = (($year2 - $year1) * 12) + ($month2 - $month1);

        return $diff;
    }

}
