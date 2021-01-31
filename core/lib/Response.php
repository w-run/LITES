<?php

namespace core\lib;


class Response
{
    public static function print($data, $header = [], $cache = "0")
    {
        $system = Config::get('system');
        $callback_type = gettype($data);
        $headers = [
            "Content-type" => "text/json;charset=utf-8",
            "timestamp" => strtotime('now'),
            "X-Powered-By" => "LITES Framework",
            "Server" => $system['name'],
            "errCode" => 0,
            "errMsg" => "ok",
            "Set-Cookie" => ""
        ];

        if ($callback_type == "array") {
            $data = Response::sort_data($data);
            $data = json_encode($data);
        }
        if (L_DEBUG != "on")
            $header['Content-Length'] = strlen($data);
        $cache_control = self::cache($cache);
        foreach ($cache_control as $k => $v)
            $headers[$k] = $v;
        foreach ($header as $k => $v)
            $headers[$k] = $v;
        foreach ($headers as $k => $v)
            header(ucfirst($k) . ":$v");


        if (in_array($headers['Content-type'], [
            'text/json;charset=utf-8',
            'text/html;charset=utf-8'
        ])) {
            header("Access-Control-Allow-Origin: " . (array_key_exists('HTTP_ORIGIN', $_SERVER) ? $_SERVER['HTTP_ORIGIN'] : Config::system('allow_host')));
            header("Access-Control-Allow-Credentials: true");
            header("Access-Control-Expose-Headers: " . implode(",", array_keys($headers)));
        }

        if (L_DEBUG == "on" && $callback_type == "array") {
            $data = json_decode($data, true);
            $data = array_merge(['header' => $headers], $data);
            $data = json_encode($data);
        }
        exit($data);
    }

    public static function print_header($header = [], $cache = "0")
    {
        $headers = [
            "Content-type" => "text/html;charset=utf-8",
            "timestamp" => strtotime('now'),
            "X-Powered-By" => "LITES Framework",
            "Server" => "LITES",
            "errCode" => 0,
            "errMsg" => "ok",
            "Set-Cookie" => "",
            "debug" => L_DEBUG
        ];
        foreach ($header as $k => $v)
            $headers[$k] = $v;
        $cache_control = self::cache($cache);
        foreach ($cache_control as $k => $v)
            $headers[$k] = $v;
        foreach ($headers as $k => $v)
            header(ucfirst($k) . ":$v");

    }

    public static function sort_data($data)
    {
        ksort($data);
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::sort_data($value);
            }
        }
        return $data;
    }

    public static function cache($t)
    {
        $factor = [
            "y" => 365 * 24 * 60 * 60,
            "M" => 30 * 24 * 60 * 60,
            "w" => 7 * 24 * 60 * 60,
            "d" => 24 * 60 * 60,
            "h" => 60 * 60,
            "m" => 60,
            "s" => 1
        ];
        foreach ($factor as $k => $v)
            if (strstr($t, $k)) {
                $t = str_replace($k, "", $t);
                $t *= $v;
            }
        if ($t == 0)
            return [
                "Last-Modified" => gmdate("D, d M Y H:i:s") . " GMT",
                "Pragma" => "no-cache",
                "Cache-Control" => "no-store, no-cache, must-revalidate"
            ];
        $ts = gmdate("D, d M Y H:i:s", time() + $t) . " GMT";
        return [
            "Expires" => $ts,
            "Pragma" => "cache",
            "Cache-Control" => $t
        ];
    }
}