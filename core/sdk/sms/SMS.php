<?php


namespace core\sdk\sms;

use core\lib\Date;
use core\lib\File;
use core\lib\Session;

class SMS
{

    private $apikey = "";
    private $mark = "";

    public function __construct()
    {
        $config = File::getJson(CONFIG_FILE);
        $this->apikey = $config['sms']['api_key'];
        $this->mark = $config['sms']['sign'];
    }


    public static function code_str($CODE)
    {
        return "您的验证码是$CODE" . "（30分钟内有效）。如非本人操作，请忽略本短信。";
    }


    public function send($phone, $text)
    {
        $text = $this->mark . $text;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept:text/plain;charset=utf-8',
            'Content-Type:application/x-www-form-urlencoded', 'charset=utf-8'
        ));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, 'https://sms.yunpian.com/v2/sms/single_send.json');
        $data = array('text' => $text, 'apikey' => $this->apikey, 'mobile' => $phone);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $result = curl_exec($ch);
        return json_decode($result, true);
    }


    public function check($phone, $code)
    {
        $sc = Session::get("sms_code");
        if ($sc != null && array_key_exists("time", $sc)) {
            $time_diff = Date::time_diff($sc['time']);
            //判断验证码超时 60秒*30
            if ($time_diff > 60 * 30)
                return ["state" => false, "errMsg" => "code time out"];
            else if ($phone == $sc['phone'] && $code == $sc['code']) {
                Session::del("sms_code");
                return ["state" => true, "errMsg" => "ok"];
            } else
                return ["state" => false, "errMsg" => "code error"];
        } else
            return ["state" => false, "errMsg" => "code not send"];
    }

    public static function errMsg($res)
    {
        return array(
            "sub_code" => $res['code'],
            "sub_errMsg" => $res['msg'],
            "sub_detail" => $res['detail']
        );
    }
}
