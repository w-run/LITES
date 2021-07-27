<?php

namespace core\lib;


class AES
{
    const key = "LITES_KEY";


    static public function en($str)
    {
        $data = openssl_encrypt($str, 'AES-128-ECB', self::key, OPENSSL_RAW_DATA);
        $data = base64_encode($data);
        return $data;
    }


    static public function de($str)
    {
        return openssl_decrypt(base64_decode($str), 'AES-128-ECB', self::key, OPENSSL_RAW_DATA);
    }
}