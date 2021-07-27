<?php


namespace core\sdk\trans;


use core\lib\File;
use core\lib\Web;

class BTrans
{

    private $appid = "";
    private $secret = "";

    public function __construct()
    {
        $config = File::getJson(CONFIG_FILE);
        $this->appid = $config['baidu_trans']['appid'];
        $this->secret = $config['baidu_trans']['secret'];
    }

    public function sign($q)
    {
        $salt = rand(10000, 99999) . "" . rand(10000, 99999);
        $data = $this->appid;
        $data .= $q;
        $data .= $salt;
        $data .= $this->secret;
        $sign = md5($data);
        return [
            "data" => $data,
            "sign" => $sign,
            "salt" => $salt
        ];
    }

    public function translate($str, $to = "", $from = "auto")
    {
        $url = "https://fanyi-api.baidu.com/api/trans/vip/translate";
        $signData = $this->sign($str);
        $data = [
            'q' => $str,
            'from' => $from,
            'to' => $to,
            'appid' => $this->appid,
            'sign' => $signData['sign'],
            'salt' => $signData['salt']
        ];
        $res = Web::send($url, "POST", $data);
        return $res;
    }
}