<?php

namespace core\lib;


class AES
{
    const key = "LITES_KEY";

    /**
     * 加密
     * @param string $str 要加密的数据
     * @return bool|string   加密后的数据
     */
    static public function en($str)
    {
        $data = openssl_encrypt($str, 'AES-128-ECB', self::key, OPENSSL_RAW_DATA);
        $data = base64_encode($data);
        return $data;
    }

    /**
     * 解密
     * @param string $str 要解密的数据
     * @return string        解密后的数据
     */
    static public function de($str)
    {
        return openssl_decrypt(base64_decode($str), 'AES-128-ECB', self::key, OPENSSL_RAW_DATA);
    }
}