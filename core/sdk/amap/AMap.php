<?php

namespace core\sdk\amap;


use core\lib\File;
use core\lib\Web;

class AMap
{
    private $api_key = "";

    public function __construct()
    {
        $this->api_key = File::getJson(CONFIG_FILE)['amap']['api_key'];
    }

    public function request($path, $param)
    {
        $param['key'] = $this->api_key;
        $param['output'] = "json";
        $param = http_build_query($param);
        $url = "https://restapi.amap.com/v3/$path?$param";
        $res = Web::send($url, "GET");
        return json_decode($res, true);
    }

    public function ip2address($ip)
    {
        $res = $this->request("ip", [
            "ip" => $ip
        ]);
        return $res;
    }

    public function geo($address, $city)
    {
        $res = $this->request("geocode/geo", [
            "address" => $address,
            "city" => $city
        ]);
        return $res;
    }

    public function regeo($x, $y)
    {
        $res = $this->request("geocode/regeo", [
            "location" => $x . "," . $y,
            "batch" => false
        ]);
        return $res;
    }

    public function tips($keywords, $x, $y, $city = "")
    {
        $res = $this->request("assistant/inputtips", [
            "city" => $city,
            "location" => $x . "," . $y,
            "keywords" => $keywords,
            "citylimit" => true
        ]);
        return $res;
    }

    public function traffic_status($start, $end)
    {
        $res = $this->request("traffic/status/rectangle", [
            "rectangle" => $start . "," . $end
        ]);
        return $res;
    }
}
