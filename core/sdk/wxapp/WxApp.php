<?php

namespace core\sdk\wxapp;

use core\lib\Error;
use core\lib\File;
use core\lib\Image;
use core\lib\Request;
use core\lib\Response;
use core\lib\Web;

class WxApp
{

    public $appid = "";
    private $secret = "";

    public function __construct()
    {
        $config = File::getJson(CONFIG_FILE);
        $this->appid = $config['wxapp']['appid'];
        $this->secret = $config['wxapp']['secret'];
    }

    public function get_openid($code)
    {
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $this->appid . "&secret=" . $this->secret . "&js_code=" . $code . "&grant_type=authorization_code";
        $res = Web::send($url, "GET");

        return json_decode($res, true);
    }

    public function get_token()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $this->appid . "&secret=" . $this->secret;
        $res = Web::send($url, "GET");
        $res = json_decode($res, true);
        if (!array_key_exists("errcode", $res))
            return $res['access_token'];
        else
            Response::print([], [
                "errCode" => ERR_SDK_ERROR,
                "errMsg" => $res['errmsg']
            ]);
    }

    public function get_wxacode($scene, $page = "pages/index/index", $width = 280)
    {
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $this->get_token();
        $data = [
            "scene" => $scene,
            "page" => $page,
            "width" => $width
        ];
        $res = Web::send($url, "POST", json_encode($data));
        if (strlen($res) > 200)
            return [
                "state" => true,
                "data" => $res
            ];
        else
            return [
                "state" => false,
                "errMsg" => json_decode($res, true)['errmsg']
            ];
    }

    public function urlscheme($query, $path = "pages/index/index", $is_expire = false, $expire_time = 0)
    {
        $url = "https://api.weixin.qq.com/wxa/generatescheme?access_token=" . $this->get_token();
        $data = [
            "jump_wxa" => [
                "path" => $path,
                "query" => $query
            ]
        ];
        if ($is_expire) {
            $data['is_expire'] = true;
            $data['expire_time'] = $expire_time;
        }
        $res = json_decode(Web::send($url, "POST", json_encode($data)), true);
        if ($res['errcode'] == 0)
            return [
                "state" => true,
                "data" => $res['openlink']
            ];
        else
            return [
                "state" => false,
                "errMsg" => $res['errmsg']
            ];
    }


    public function check_str($str)
    {
        $url = "https://api.weixin.qq.com/wxa/msg_sec_check?access_token=" . $this->get_token();

        $data = '{"content":"' . str_replace("\"", "\\\"", $str) . '"}';
        $res = Web::send($url, "POST", $data);
        return json_decode($res, true)['errcode'] != 87014;
    }

    public function check_img($file)
    {
        $url = "https://api.weixin.qq.com/wxa/img_sec_check?access_token=" . $this->get_token();
        $res = Web::send($url, "FILE", [
            "media" => new \CURLFile(Image::thumb_forWx($file))
        ]);
        return json_decode($res, true)['errcode'] != 87014;
    }

    public function uniform_send($openid, $template_id, $form_id, $data, $emphasis_keyword, $page = "pages/index/index")
    {
        $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/uniform_send?access_token=" . $this->get_token();
        $data = [
            'touser' => $openid,
            'weapp_template_msg' => [
                "template_id" => $template_id,
                "page" => $page,
                "form_id" => $form_id,
                "data" => $data,
                "emphasis_keyword" => $emphasis_keyword
            ]
        ];
        $res = Web::send($url, "POST", json_encode($data));
        $res = json_decode($res, true);
        if ($res['errcode'] == 0)
            return [
                "state" => true
            ];
        else
            return [
                "state" => false,
                "errMsg" => $res['errmsg']
            ];
    }

    public function get_tpl($key)
    {
        return File::getJson(CONFIG_FILE)['wxapp'][$key . "_tpl"];
    }

    public function decrypt($sessionKey, $encryptedData, $iv)
    {
        $data = null;
        $errCode = $this->decryptData($sessionKey, $encryptedData, $iv, $data);
        $retMsg = [
            -41001 => "encodingAesKey 非法",
            -41002 => "iv参数错误",
            -41003 => "aes 解密失败",
            -41004 => "解密后得到的buffer非法"
        ];
        if ($errCode == 0)
            return ["state" => true, "data" => json_decode($data, true)];
        else
            return ["state" => false, "errMsg" => $retMsg[$errCode]];
    }

    private function decryptData($sessionKey, $encryptedData, $iv, &$data)
    {
        if (strlen($sessionKey) != 24)
            return -41001;
        $aesKey = base64_decode($sessionKey);
        if (strlen($iv) != 24)
            return -41002;
        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);
        $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
        $dataObj = json_decode($result);
        if ($dataObj == NULL)
            return -41003;
        if ($dataObj->watermark->appid != $this->appid)
            return -41004;
        $data = $result;
        return 0;
    }
}
