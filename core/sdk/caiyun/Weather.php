<?php

namespace core\sdk\caiyun;


use core\lib\File;
use core\lib\Web;

class Weather
{
    private $api_key = "";
    private $weather_code = [
        "CLEAR_DAY" => "晴",
        "CLEAR_NIGHT" => "晴",
        "PARTLY_CLOUDY_DAY" => "多云",
        "PARTLY_CLOUDY_NIGHT" => "多云",
        "CLOUDY" => "阴",
        "LIGHT_HAZE" => "轻度雾霾",
        "MODERATE_HAZE" => "中度雾霾",
        "HEAVY_HAZE" => "重度雾霾",
        "LIGHT_RAIN" => "小雨",
        "MODERATE_RAIN" => "中雨",
        "HEAVY_RAIN" => "大雨",
        "STORM_RAIN" => "暴雨",
        "FOG" => "雾",
        "LIGHT_SNOW" => "小雪",
        "MODERATE_SNOW" => "中雪",
        "HEAVY_SNOW" => "大雪",
        "STORM_SNOW" => "暴雪",
        "DUST" => "浮尘",
        "SAND" => "浮尘",
        "WIND" => "大风",
    ];

    public function __construct()
    {
        $this->api_key = File::getJson(CONFIG_FILE)['caiyun']['api_key'];
    }

    public function weather($x, $y)
    {
        $url = "https://api.caiyunapp.com/v2.5/" . $this->api_key . "/$x,$y/weather.json";
        $res = Web::send($url, "GET");
        $res = json_decode($res, true);
        $time = $res['server_time'];
        $res = $res['result'];
        $res['timestamp'] = $time;
        return $res;
    }

    public function simple($x, $y)
    {
        $data = $this->weather($x, $y);
        $realtime = $data['realtime'];
        $daily = $data['daily'];
        $res = [
            "humidity_now" => ($realtime['humidity'] * 100),
            "wind" => [
                "max" => self::wind_calc($daily['wind'][0]['max']),
                "min" => self::wind_calc($daily['wind'][0]['min']),
                "now" => self::wind_calc($realtime['wind'])
            ],
            "air_quality_now" => [
                "description" => $realtime['air_quality']['description']['chn'],
                "aqi" => $realtime['air_quality']['aqi']['chn']
            ],
            "life_index" => [
                "feeling_now" => $realtime['life_index']['comfort']['desc'],
                'carWashing' => $daily['life_index']['carWashing'][0]['desc'],
                'coldRisk' => $daily['life_index']['coldRisk'][0]['desc'],
                'comfort' => $daily['life_index']['comfort'][0]['desc'],
                'ultraviolet' => $daily['life_index']['ultraviolet'][0]['desc'],
                'dressing' => $daily['life_index']['dressing'][0]['desc'],
            ],
            "astro" => [
                "sumrise" => $daily['astro'][0]['sunrise']['time'],
                "sunset" => $daily['astro'][0]['sunset']['time']
            ],
            "status_now" => $this->weather_code[$realtime['skycon']],
            "status_today" => $this->weather_code[$daily['skycon'][0]['value']],
            "status_now_code" => str_replace(['_day', "_night"], "", strtolower($realtime['skycon'])),
            "status_today_code" => str_replace(['_day', "_night"], "", strtolower($daily['skycon'][0]['value'])),
            "temperature" => [
                "now" => $realtime['temperature'] . "",
                "max" => number_format($daily['temperature'][0]['max'], 0),
                "min" => number_format($daily['temperature'][0]['min'], 0)
            ],
            "tips" => $data['minutely']['description'],
            "alert" => null
        ];
        if (array_key_exists("alert", $data))
            $res['alert'] = $data['alert'];
        return $res;
    }

    public static function wind_calc($wind)
    {
        $d = $wind['direction'];
        $s = $wind['speed'];

        $fx = ['北', "东北", "东北", "东", "东", "东南", "东南", "南", "南", "西南", "西南", "西", "西", "西北", "西北", "北"][intval($d / 22.5)];
        $fs_filter = [118, 103, 89, 75, 62, 50, 39, 29, 20, 12, 6, 1, 0];
        $fs = 0;
        foreach ($fs_filter as $i => $v)
            if ($s >= $v) {
                $fs = 12 - $i;
                break;
            }

        return [
            "direction" => $fx,
            "speed" => $fs
        ];
    }
}
