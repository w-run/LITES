<?php

namespace core\sdk\pay;

use core\lib\Config;
use core\lib\Error;
use core\lib\File;
use core\lib\Request;
use core\lib\Web;
use core\lib\XML;

class WePay
{

    private $config = array(
        'appid' => '',
        'mch_id' => '',
        'pay_apikey' => '',
        'api_cert' => '',
        'api_key' => '',
        'notify_url' => ''
    );

    public function __construct($config = array())
    {
        $this->config = File::getJson(CONFIG_FILE)['wepay'];
        foreach ($this->config as $k => $v) {
            $this->config[$k] = str_replace("BASEDIR", __DIR__, $v);
        }
        $this->config = array_merge($this->config, $config);
    }


    public function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    public function makeSign($data)
    {
        //获取微信支付秘钥
        $key = $this->config['pay_apikey'];

        $data = array_filter($data);
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = http_build_query($data);
        $string_a = urldecode($string_a);
        //签名步骤二：在string后加入KEY
        $string_sign_temp = $string_a . "&key=" . $key;
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);

        return strtoupper($sign);
    }


    public function request($openid, $order)
    {
        $config = $this->config;
        //统一下单参数构造
        $unifiedorder = array(
            'appid' => $config['appid'],
            'mch_id' => $config['mch_id'],
            'nonce_str' => self::getNonceStr(),
            'body' => $order['type'],
            'out_trade_no' => $order['order_id'],
            'total_fee' => $order['price_total'] * 100,
            'spbill_create_ip' => Request::get_ip(),
            'notify_url' => $config['notify_url'],
            'trade_type' => 'APP',
            'openid' => $openid
        );
        $unifiedorder['sign'] = self::makeSign($unifiedorder);
        //请求数据,统一下单
        $xmldata = XML::array2xml($unifiedorder);
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $res = Web::send_ssl($url, $xmldata, $config);
        if (!$res) {
            return array('status' => 0, 'msg' => "Can't connect the server");
        }
        $content = XML::xml2array($res);
        if (strval($content['result_code']) == 'FAIL') {
            return array('status' => 0, 'msg' => strval($content['err_code']) . ':' . strval($content['err_code_des']));
        }
        if (strval($content['return_code']) == 'FAIL') {
            return array('status' => 0, 'msg' => strval($content['return_msg']));
        }
        $time = time();
        settype($time, "string");        //jsapi支付界面,时间戳必须为字符串格式
        $resdata = array(
            'appid' => strval($content['appid']),
            'prepayid' => strval($content['prepay_id']),
            'partnerid' => $config['mch_id'],
            'noncestr' => strval($content['nonce_str']),
            'package' => "Sign=WXPay",
            'timestamp' => $time
        );
        $resdata['sign'] = self::makeSign($resdata);
        $resdata['uo'] = $content;
        return $resdata;
    }


    public function refund($order)
    {
        $config = $this->config;
        //退款参数
        $refundorder = array(
            'appid' => $config['appid'],
            'mch_id' => $config['mch_id'],
            'nonce_str' => self::getNonceStr(),
            'transaction_id' => $order['wxpay_no'],
            'out_refund_no' => $order['order_id'],
            'total_fee' => $order['price_total'] * 100,
            'refund_fee' => $order['price_total'] * 100
        );
        $refundorder['sign'] = self::makeSign($refundorder);
        //请求数据,进行退款
        $xmldata = XML::array2xml($refundorder);
        $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
        $res = Web::send_ssl($url, $xmldata, $config);
        if (!$res) {
            return array('status' => 0, 'msg' => "Can't connect the server");
        }
        $content = XML::xml2array($res);
        if (strval($content['result_code']) == 'FAIL') {
            return array('status' => 0, 'msg' => strval($content['err_code']) . ':' . strval($content['err_code_des']));
        }
        if (strval($content['return_code']) == 'FAIL') {
            return array('status' => 0, 'msg' => strval($content['return_msg']));
        }
        return $content;
    }


    public function transfers($openid, $price, $desc, $no)
    {
        $config = $this->config;
        //退款参数
        $tansorder = array(
            'amount' => $price * 100,
            'check_name' => 'NO_CHECK',
            'desc' => $desc,
            'mch_appid' => $config['appid'],
            'mch_id' => $config['mch_id'],
            'nonce_str' => self::getNonceStr(),
            'openid' => $openid,
            'partner_trade_no' => $no
        );
        $tansorder['sign'] = self::makeSign($tansorder);

        //请求数据,进行退款
        $xmldata = XML::array2xml($tansorder);
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
        $res = Web::send_ssl($url, $xmldata, $config);
        if (!$res) {
            return array('status' => 0, 'msg' => "Can't connect the server");
        }
        $content = XML::xml2array($res);
        if (strval($content['result_code']) == 'FAIL') {
            return array('status' => 0, 'msg' => strval($content['err_code']) . ':' . strval($content['err_code_des']));
        }
        if (strval($content['return_code']) == 'FAIL') {
            return array('status' => 0, 'msg' => strval($content['return_msg']));
        }
        return $content;
    }


    public function query($order)
    {
        $config = $this->config;
        //退款参数
        $tansorder = array(
            'appid' => $config['appid'],
            'mch_id' => $config['mch_id'],
            'nonce_str' => self::getNonceStr(),
            'out_trade_no' => $order['order_id'],
            'transaction_id' => $order['wxpay_no']
        );
        $tansorder['sign'] = self::makeSign($tansorder);
        $xmldata = XML::array2xml($tansorder);
        $url = 'https://api.mch.weixin.qq.com/pay/orderquery';
        $res = Web::send_ssl($url, $xmldata, $config);
        if (!$res) {
            return array('status' => 0, 'msg' => "Can't connect the server");
        }
        $content = XML::xml2array($res);
        if (strval($content['result_code']) == 'FAIL') {
            return array('status' => 0, 'msg' => strval($content['err_code']) . ':' . strval($content['err_code_des']));
        }
        if (strval($content['return_code']) == 'FAIL') {
            return array('status' => 0, 'msg' => strval($content['return_msg']));
        }

        return $content;
    }


    public function notify()
    {
        $xml = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : file_get_contents("php://input");        //获取微信支付服务器返回的数据
        //将服务器返回的XML数据转化为数组
        $data = XML::xml2array($xml);

        $data_sign = $data['sign'];

        unset($data['sign']);
        $sign = self::makeSign($data);


        if (($sign === $data_sign) && ($data['return_code'] == 'SUCCESS') && ($data['result_code'] == 'SUCCESS')) {
            $result = $data;
            //在此更新数据库
        } else {
            $result = false;
        }

        if ($result) {
            $str = '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        } else {
            $str = '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>';
        }
        return $str;
    }
}
