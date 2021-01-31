<?php

namespace core\lib;

class Date
{

    public static function time_diff($time)
    {
        return time() - $time;
    }

    public static function time_diff_str($time)
    {
        return time() - strtotime($time);
    }

    public static function time_format($time, $fmt = 'Y-m-d H:i:s')
    {
        return date($fmt, intval(strtotime($time)));
    }

    public static function time_h($time)
    {
        $t = $time;
        $hm = date('h:m', intval(strtotime($t)));
        $time = intval(time()) - intval(strtotime($time));
        if ($time < 60) {
            $str = '刚刚';
        } elseif ($time < 3600) {
            $min = floor($time / 60);
            $str = $min . '分钟前';
        } elseif ($time < 86400) {
            $h = floor($time / 3600);
            $str = $h . '小时前 ';
        } elseif ($time < 259200) {
            $d = floor($time / 86400);
            if ($d == 1) {
                $str = '昨天 ' . $hm;
            } else {
                $str = '前天 ' . $hm;
            }
        } else {
            $str = date('Y-m-d', intval(strtotime($t)));
        }
        return $str;
    }

    public static function cur_time($format = 'Y-m-d H:i:s')
    {
        return date($format);
    }

    public static function calc_time($str = "now", $format = 'Y-m-d H:i:s')
    {
        return date($format, strtotime($str));
    }

}