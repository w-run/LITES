<?php

namespace core\lib;


class Route
{
    public static function init($request)
    {
        $route_list = File::getJson('core/conf/route_list.json');

        $url = $request['url'];

        switch ($url[0]) {
            case "api":
                if (array_key_exists($url[1], $route_list['api'])
                    && array_key_exists(Request::url_cut($url, 2), $route_list['api'][$url[1]])) {
                    $mod = $url[1];
                    $func = $route_list['api'][$mod][Request::url_cut($url, 2)];

                    self::import("app/" . $mod . "/API.php");
                    $className = "\\app\\" . str_replace("/", "\\", $mod) . "\\API";
                    new $className($func, $request);

                } else
                    Error::notfound();
                break;
            case "res":
            case "temp":
                File::get($request['url_string'], [], "3M");
                break;
            default:
                if (array_key_exists($request['url_string'], $route_list) && !is_array($route_list[$request['url_string']])) {
                    if (substr($route_list[$request['url_string']], -3) == "php") {
                        Response::print_header();
                        self::import($route_list[$request['url_string']]);
                    } else
                        File::get($route_list[$request['url_string']]);
                } else {
                    Error::notfound();
                }
        }
    }


    public static function import($file)
    {
        if (file_exists($file)) {
            include_once $file;
        } else {
            Error::notfound();
        }
    }

}