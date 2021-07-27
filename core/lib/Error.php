<?php

namespace core\lib;


class Error
{

    public static function debug($data)
    {
        if (L_DEBUG == "on")
            print_r($data);
    }


    public static function notfound()
    {
        self::callback_error(ERR_NOT_FOUND);
    }


    public static function callback_error($code, $data = [])
    {
        header("Content-type:text/json;charset=utf-8");
        $all_retCode = array_keys(get_defined_constants(), $code);
        $errMsg = "unknow";
        foreach ($all_retCode as $item) {
            if (!strstr("ERR", $item))
                $errMsg = str_replace("err ", "", str_replace("_", " ", strtolower($item)));
        }
        $res = array(
            "errCode" => $code,
            "errMsg" => $errMsg
        );
        Error::debug($res);
        if ($code == 104) header("Status: 404 Not Found");
        if ($code == 300) header("Status: 502 Bad Gateway");

        Response::print($data, $res);
    }


    public static function register_retCode()
    {
        define("ERR_OK", 0);
        define("ERR_UNKNOW", -1);
        define("ERR_WAIT", 1);
        define("ERR_BAD_REQUEST", 101);
        define("ERR_NEED_AUTH", 102);
        define("ERR_FORBIDDEN", 103);
        define("ERR_NOT_FOUND", 104);
        define("ERR_METHOD_DENIED", 105);
        define("ERR_NOT_ACCEPT", 106);
        define("ERR_MISS_PARAM", 107);
        define("ERR_TIMEOUT", 108);
        define("ERR_REQUEST_LONG", 109);
        define("ERR_URL_LONG", 110);
        define("ERR_FILE_TYPE", 111);
        define("ERR_CREATED", 201);
        define("ERR_RECEIVED", 202);
        define("ERR_AUTH_FAILED", 203);
        define("ERR_NO_CONTENT", 204);
        define("ERR_RESET_CONTENT", 205);
        define("ERR_PART_CONTENT", 206);
        define("ERR_HANDLE_ERROR", 207);
        define("ERR_SERVER", 300);
        define("ERR_NOT_IMPLEMENTED", 301);
        define("ERR_GATEWAY_FAIL", 302);
        define("ERR_GATEWAY_TIMEOUT", 303);
        define("ERR_VERSION_UNSUPPORT", 304);
        define("ERR_DATABASE_ERROR", 305);
        define("ERR_API_ERROR", 306);
        define("ERR_SDK_ERROR", 307);
        define("ERR_PLUGIN_ERROR", 308);
        define("ERR_FILE_ERROR", 400);
        define("ERR_FILE_SIZE", 401);
        define("ERR_FILE_INCOMPLETE", 402);
        define("ERR_FILE_NULL", 403);
        define("ERR_FILE_DENIED", 403);
        define("ERR_FILE_VIOLATE", 404);
    }


}
