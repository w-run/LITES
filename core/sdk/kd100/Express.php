<?php


namespace core\sdk\kd100;


use core\lib\File;
use core\lib\Web;

class Express
{

    private $key = "";
    private $customer = "";
    private $secret = "";
    private $userid = "";

    public function __construct()
    {
        $config = File::getJson(CONFIG_FILE);
        $this->key = $config['kd100']['key'];
        $this->customer = $config['kd100']['customer'];
        $this->secret = $config['kd100']['secret'];
        $this->userid = $config['kd100']['userid'];
    }

    public function query_com($data)
    {
        $url = "http://www.kuaidi100.com/autonumber/auto";
        $data = [
            "num" => $data,
            "key" => $this->key
        ];
        $res = Web::send($url, "POST", $data);
        return $res;
    }

    public function query($data, $com)
    {
        $url = "https://poll.kuaidi100.com/poll/query.do";
        $param = [
            "num" => $data,
            "com" => $com
        ];
        $data = [
            "customer" => $this->customer,
            "sign" => strtoupper(md5(json_encode($param) . $this->key . $this->customer)),
            "param" => json_encode($param)
        ];
        $res = Web::send($url, "POST", $data);
        return json_decode($res, true);
    }
}