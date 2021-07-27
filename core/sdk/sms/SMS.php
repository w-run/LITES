<?php


namespace core\sdk\sms;

use core\lib\Date;
use core\lib\File;
use core\lib\Session;
use core\lib\Web;

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
        $res = Web::send('https://sms.yunpian.com/v2/sms/single_send.json','POST',[
            'text' => $text,
            'apikey' => $this->apikey,
            'mobile' => $phone
        ]);
        return json_decode($res, true);
    }


    public function check($phone, $code, $timeout = 30 * 60)
    {
        $sc = Session::get("sms_code");
        if ($sc != null && array_key_exists("time", $sc)) {
            $time_diff = Date::time_diff($sc['time']);
            if ($time_diff > $timeout)
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
