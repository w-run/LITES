<?php

namespace core\lib;


class Session
{


    public static function set($key, $value = "")
    {
        $_SESSION[$key] = $value;
    }


    public static function get($key)
    {
        if ($_SESSION != null && array_key_exists($key, $_SESSION) && $_SESSION[$key] != null)
            return $_SESSION[$key];
        else
            return null;
    }


    public static function del($key)
    {
        if (array_key_exists($key, $_SESSION) && $_SESSION[$key] != null) {
            unset($_SESSION[$key]);
            return true;
        } else
            return false;
    }


    public static function denied()
    {
        setcookie("PHPSESSID", null, time() - 1, "/");
    }

}