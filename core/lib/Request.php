<?php

namespace core\lib;

class Request
{

    public static function init()
    {
        $body = [];
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_')
                $headers[str_replace(' ', '-', strtolower(str_replace('_', ' ', substr($name, 5))))] = $value;
        }
        $url = $_REQUEST['url'];

        if (substr($url, -1) == "/")
            $url = substr($url, 0, strlen($url) - 1);

        if (substr($url, 0, 1) == "/")
            $url = substr($url, 1, strlen($url) - 1);
        $urlArr = explode("/", $url);
        $data = array_merge($_GET, $_POST);
        unset($data['url']);
        $headers['http'] = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https' : 'http';

        $body['header'] = $headers;
        $body['url'] = $urlArr;
        $body['url_string'] = $url;
        $body['form'] = $data;
        $body['isAjax'] = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
        $body['isPost'] = isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'], 'POST');
        $body['ip'] = self::get_ip();
        $body['isDebug'] = (array_key_exists('debug', $data) && $data['debug'] == 'on');
        $urlArr2 = array_splice($urlArr, 1, 2);
        ksort($body);
        return $body;
    }

    public static function url_cut($url, $index = 0)
    {
        $urlArr = $url;
        $urlArr = array_splice($urlArr, $index, $index + 1);
        return implode("/", $urlArr);
    }


    public static function getParam($paramName)
    {
        if (array_key_exists($paramName, $_REQUEST))
            return $_REQUEST[$paramName];
        else
            return "";
    }


    public static function get_ip()
    {
        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else if (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $ip = getenv('HTTP_X_FORWARDED_FOR');
            } else if (getenv('HTTP_CLIENT_IP')) {
                $ip = getenv('HTTP_CLIENT_IP');
            } else {
                $ip = getenv('REMOTE_ADDR');
            }
        }
        return $ip;
    }
}