<?php

namespace core\lib;


class Token
{

    const key = "L0629779230686459054449523202101";

    public static function en_token($sid)
    {
        $str = implode(array_reverse(str_split($sid, 1)));
        $str = $str ^ self::key;
        $str = base64_encode($str);
        $str = base64_encode($str);
        $str = str_replace("=", "!", $str);
        $str = "!HX!" . $str;
        return $str;
    }

    public static function de_token($token)
    {
        $str = str_replace("!HX!", "", $token);
        $str = str_replace("!", "=", $str);
        $str = base64_decode($str);
        $str = base64_decode($str);
        $str = $str ^ self::key;
        $str = implode(array_reverse(str_split($str, 1)));
        return $str;
    }


}