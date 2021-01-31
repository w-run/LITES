<?php

namespace app\wxapp;


use app\order\Order;
use core\lib\BaseAPI;
use core\sdk\pay\WePay;
use core\sdk\wxapp\WxApp;

class API extends BaseAPI
{

    public function getIp()
    {
        $this->callback($this->request['ip']);
    }

    public function login()
    {
        $code = $this->getForm("code")['code'];
        $wa = new WxApp();
        $res = $wa->get_openid($code);
        $this->callback($res);
    }

    public function crypt()
    {
        $form = $this->getForm("session data iv");
        $wa = new WxApp();
        $res = $wa->decrypt($form['session'], $form['data'], $form['iv']);
        if ($res['state']) {
            $this->callback($res['data']);
        } else
            $this->callback_error($res['errMsg']);
    }
}