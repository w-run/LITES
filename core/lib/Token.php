<?php

namespace core\lib;


class Token
{

    const key = "LITES_FANCTION_TEST_KEY_629_TEST";

    public static function en_token($sid)
    {
        $str = implode(array_reverse(str_split($sid, 1)));
        $str = $str ^ self::key;
        $str = base64_encode($str);
        $str = base64_encode($str);
        $str = str_replace("=", "_", $str);
        $str = "_LITES_" . $str;
        return $str;
    }

    public static function de_token($token)
    {
        $str = str_replace("_LITES_", "", $token);
        $str = str_replace("_", "=", $str);
        $str = base64_decode($str);
        $str = base64_decode($str);
        $str = $str ^ self::key;
        $str = implode(array_reverse(str_split($str, 1)));
        return $str;
    }


}