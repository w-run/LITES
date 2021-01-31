<?php


namespace core\lib;


class Loader
{

    public static function autoload($className = "")
    {
        $className = str_replace("\\", "/", $className);
        $file = $className . ".php";
        if (is_file($file)) {
            include_once $file;
            return;
        } else {
            Error::callback_error(ERR_SERVER);
        }
    }

}