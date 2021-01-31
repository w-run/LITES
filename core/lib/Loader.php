<?php
/*  
 *  @file Loader.php
 *  @project LITES_Example
 *  @author W/Run
 *  @version 2021-01-18
 */

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