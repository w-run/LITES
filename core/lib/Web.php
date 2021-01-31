<?php

namespace core\lib;


class Web
{

    public static function rand_ip()
    {
        $ip_long = array(
            array('607649792', '608174079'), //36.56.0.0-36.63.255.255
            array('1038614528', '1039007743'), //61.232.0.0-61.237.255.255
            array('1783627776', '1784676351'), //106.80.0.0-106.95.255.255
            array('2035023872', '2035154943'), //121.76.0.0-121.77.255.255
            array('2078801920', '2079064063'), //123.232.0.0-123.235.255.255
            array('-1950089216', '-1948778497'), //139.196.0.0-139.215.255.255
            array('-1425539072', '-1425014785'), //171.8.0.0-171.15.255.255
            array('-1236271104', '-1235419137'), //182.80.0.0-182.92.255.255
            array('-770113536', '-768606209'), //210.25.0.0-210.47.255.255
            array('-569376768', '-564133889'), //222.16.0.0-222.95.255.255
        );
        $rand_key = mt_rand(0, 9);
        return long2ip(mt_rand($ip_long[$rand_key][0], $ip_long[$rand_key][1]));
    }

    public static function send($url, $method, $postfields = null, $headers = array(), $debug = false)
    {
        $method = strtoupper($method);
        $ci = curl_init();
        $headers = array_merge($headers, array('X-FORWARDED-FOR:' . self::rand_ip(), 'CLIENT-IP:' . self::rand_ip()));
        curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ci, CURLOPT_TIMEOUT, 10);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);

        switch ($method) {
            case "POST":
                curl_setopt($ci, CURLOPT_POST, true);
                if (!empty($postfields)) {
                    $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
                }
                break;
            case "FILE":
                curl_setopt($ci, CURLOPT_POST, true);
                curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
                break;
            default:
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method);
                break;
        }
        $ssl = preg_match('/^https:\/\//i', $url) ? TRUE : FALSE;
        curl_setopt($ci, CURLOPT_URL, $url);
        if ($ssl) {
            curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ci, CURLOPT_MAXREDIRS, 2);
        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ci, CURLINFO_HEADER_OUT, true);
        $response = curl_exec($ci);
        $requestinfo = curl_getinfo($ci);
        $errcode = curl_errno($ci);
        if ($debug) {
            echo "=====post data======\r\n";
            var_dump($postfields);
            echo "=====info===== \r\n";
            var_dump($requestinfo);
            echo "=====response=====\r\n";
            var_dump($response);
        }
        curl_close($ci);
        if ($response === false) {
            switch ($errcode) {
                case CURLE_OPERATION_TIMEDOUT:
                    Error::callback_error(ERR_GATEWAY_TIMEOUT);
                    break;
                default:
                    Error::callback_error(ERR_GATEWAY_FAIL);
            }
        }
        return $response;
    }

    public static function send_ssl($url, $form, $cert, $second = 30, $headers = array())
    {
        $ch = curl_init();
        $headers = array_merge($headers, array('X-FORWARDED-FOR:' . self::rand_ip(), 'CLIENT-IP:' . self::rand_ip()));

        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);


        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);


        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT, $cert['api_cert']);

        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY, $cert['api_key']);

        if (count($headers) >= 1) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $form);
        $data = curl_exec($ch);
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            Error::debug("call faild, errorCode:$error\n");
            curl_close($ch);
            return false;
        }
    }
}
