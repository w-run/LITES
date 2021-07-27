<?php

namespace core\lib;

class L
{
    public static function init()
    {
        include "core/lib/Loader.php";
        spl_autoload_register("core\\lib\\Loader::autoload");
        Error::register_retCode();
        define("CONFIG_FILE", "core/conf/config.json");
        $request = Request::init();
        Auth::request();
        define("L_DEBUG", $request['isDebug'] ? "on" : "off");
        if (L_DEBUG != "on") {
            error_reporting(0);
        } else
            error_reporting(E_ALL);
        Route::init($request);
    }

    public static function multi_array_key_exists($needle, $haystack)
    {
        foreach ($haystack as $key => $value) {
            if ($needle == $key) return true;
            if (is_array($value)) {
                if (self::multi_array_key_exists($needle, $value) == true)
                    return true;
                else
                    continue;
            }
        }
        return false;
    }

    public static function basePath()
    {
        return str_replace("\\", "/", dirname(dirname(dirname(__FILE__))));
    }

}