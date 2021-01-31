<?php

namespace core\lib;


class Socket
{

    private $sp = "\r\n";

    private $protocol = 'HTTP/1.1';

    private $requestLine = "";

    private $requestHeader = "";

    private $requestBody = "";

    private $requestInfo = "";

    private $fp = null;

    private $urlinfo = null;

    private $header = array();

    private $body = "";

    private $responseInfo = "";

    private static $http = null;

    private $timeout = 0;


    private function __construct()
    {
    }


    public static function create()
    {
        if (self::$http === null) {
            self::$http = new Socket();
        }
        return self::$http;
    }

    public function init($url)
    {
        $this->parseurl($url);
        $this->header['Host'] = $this->urlinfo['host'];
        return $this;
    }


    public function get($header = array())
    {
        $this->header = array_merge($this->header, $header);
        return $this->request('GET');
    }


    public function post($header = array(), $body = array())
    {
        $this->header = array_merge($this->header, $header);
        if (!empty($body)) {
            $this->body = http_build_query($body);
            $this->header['Content-Type'] = 'application/x-www-form-urlencoded';
            $this->header['Content-Length'] = strlen($this->body);
        }
        return $this->request('POST');
    }


    private function request($method)
    {
        $header = "";
        $this->requestLine = $method . ' ' . $this->urlinfo['path'] . '?' . $this->urlinfo['query'] . ' ' . $this->protocol;
        foreach ($this->header as $key => $value) {
            $header .= $header == "" ? $key . ':' . $value : $this->sp . $key . ':' . $value;
        }
        $this->requestHeader = $header . $this->sp . $this->sp;
        $this->requestInfo = $this->requestLine . $this->sp . $this->requestHeader;
        if ($this->body != "") {
            $this->requestInfo .= $this->body;
        }

        $scheme = strtolower($this->urlinfo['scheme']);
        $port = isset($this->urlinfo['port']) ? (int)$this->urlinfo['port'] : ('https' == $scheme ? 443 : 80);
        $this->fp = fsockopen($this->urlinfo['host'], $port, $errno, $errstr);
        if (!$this->fp) {
            echo $errstr . '(' . $errno . ')';
            return false;
        }
        if (fwrite($this->fp, $this->requestInfo)) {
            $str = "";
            if ($this->timeout) {
                stream_set_blocking($this->fp, TRUE);
                stream_set_timeout($this->fp, $this->timeout);
                $info = stream_get_meta_data($this->fp);
            }
            while ((!feof($this->fp)) && ($this->timeout ? !$info['timed_out'] : TRUE)) {
                $str .= fread($this->fp, 1024);
                if ($this->timeout) {
                    $info = stream_get_meta_data($this->fp);
                    ob_flush;
                    flush();
                }
            }
            $this->responseInfo = $str;
        }
        fclose($this->fp);
        return $this->responseInfo;
    }

    public function bin2str($bin)
    {
        $hex = unpack("H*", $bin);
        $str = '';
        for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
            $str .= chr(hexdec($hex[$i] . $hex[$i + 1]));
        }
        return $str;
    }


    private function parseurl($url)
    {
        $this->urlinfo = parse_url($url);
    }

}