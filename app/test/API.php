<?php

namespace app\test;


use core\lib\BaseAPI;
use core\lib\Data;
use core\lib\Error;
use core\lib\Exception;
use core\lib\File;
use core\lib\Image;
use core\lib\MFA;
use core\lib\Response;
use core\lib\Session;
use core\lib\Web;
use core\lib\XML;
use core\sdk\amap\AMap;
use core\sdk\trans\BTrans;
use core\sdk\caiyun\Weather;
use core\sdk\kd100\Express;
use core\sdk\markdown\Parser;
use core\sdk\quark\Hots;
use core\sdk\sms\SMS;
use core\sdk\wxapp\WxApp;

class API extends BaseAPI
{
    public function sub()
    {

        Error::callback_error(300);
    }

    public function console()
    {
        $str = $this->getForm_opt('text');
        $this->callback([
            'input' => $str,
            'length' => strlen($str)
        ]);
    }

    public function mfa()
    {
        $mfa = new MFA();
        $res = [];
        $code = $this->getForm_opt("code");
        if ($code == null && Session::get('mfa_sercet') == null)
            Session::set("mfa_sercet", $mfa->get_secret(session_id()));
        if ($code == null) {
            $user =  "test";
            $res['qr'] = $mfa->get_qrimg($user, Session::get('mfa_sercet'));
            $res['link'] = $mfa->get_link($user, Session::get('mfa_sercet'));

            $res['key'] = strtoupper(join(" ", str_split(Session::get('mfa_sercet'), 4)));
        } else {
            $res['verify'] = $mfa->verify(Session::get('mfa_sercet'), $code);
        }

        $this->callback($res);
    }

    public function markdown()
    {
        $parse = new Parser();
        $res = $parse->parse($this->getForm_opt("text", "*Hello*, ***LITES!***"));
        $this->callback($res);
    }

    public function weather()
    {
        $x = $this->getForm_opt("x", 121.158219);
        $y = $this->getForm_opt("y", 38.798556);
        $cy = new Weather();
        $res = $cy->simple($x, $y);
        $this->callback($res);
    }

    public function sms()
    {
        $phone = $this->getForm("phone")['phone'];
        $sms = new SMS();
        $str = SMS::code_str(rand(1000, 9999));
        $res = $sms->send($phone, $str);
        $this->callback($res);
    }

    public function map()
    {
        $x = $this->getForm_opt("x", 121.158219);
        $y = $this->getForm_opt("y", 38.798556);
        $amap = new AMap();
        $res = $amap->regeo($x, $y);
        $this->callback($res);
    }

    public function request()
    {
        $this->callback($this->request);
    }

    public function xml()
    {
        $data = $this->getForm('array')['array'];
        $arr = json_decode($data, true);
        $str = XML::array2xml($arr);
        $this->callback($str);
    }

    public function trans()
    {
        $data = $this->getForm("str")['str'];
        $to = $this->getForm_opt("to", "zh");
        $tran = new BTrans();
        $res = $tran->translate($data, $to);
        $this->callback($res);
    }

    public function express()
    {
        $data = $this->getForm("num com");
        $ex = new Express();
        $res = $ex->query($data['num'], $data['com']);
        $res['at'] = time();
        $this->callback($res, Response::cache("6h"));
    }

    public function hots()
    {
        $hot = new Hots();
        $res = $hot->weibo();
        $this->callback($res);
    }

    public function save()
    {
        $u = $this->getForm("u")['u'];
        $res = File::save($u);
        $this->callback($res);
    }

    public function wxapp()
    {
        $wa = new WxApp();
        $res = $wa->get_wxacode("test=" . time(), "");
        if ($res['state']) {
            $this->callback(base64_encode($res['data']), Response::cache("1w"));
        } else {
            $this->callback_error($res['errMsg']);
        }
    }

    public function database()
    {
        $dao = new Data("`test`");
        $key = $this->getForm_opt('key');
        $value = $this->getForm_opt('value');
        $res = 0;
        if ($key != null && $value != null)
            $res = $dao->add(['`key`', '`value`'], [$key, $value]);
        $list = $dao->get(null, ['`key`', '`value`'], "0,10");
        $this->callback($list);
    }
}
