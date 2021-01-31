<?php

namespace core\lib;


class RSA
{

    /**
     * 获取私钥
     * @return bool|resource
     */
    private static function getPrivateKey() {
        $abs_path = '/core/conf/rsa_key/rsa_private_key.pem';
        $content = file_get_contents($abs_path);
        return openssl_pkey_get_private($content);
    }

    /**
     * 获取公钥
     * @return bool|resource
     */
    private static function getPublicKey() {
        $abs_path = '/core/conf/rsa_key/rsa_public_key.crt';
        $content = file_get_contents($abs_path);
        return openssl_pkey_get_public($content);
    }

    /**
     * 私钥加密
     * @param string $data
     * @return null|string
     */
    public static function pi_en($data = '') {
        if (!is_string($data)) {
            return null;
        }
        return openssl_private_encrypt($data,$encrypted,self::getPrivateKey()) ? base64_encode($encrypted) : null;
    }

    /**
     * 公钥加密
     * @param string $data
     * @return null|string
     */
    public static function pu_en($data = '') {
        if (!is_string($data)) {
            return null;
        }
        return openssl_public_encrypt($data,$encrypted,self::getPublicKey()) ? base64_encode($encrypted) : null;
    }

    /**
     * 私钥解密
     * @param string $encrypted
     * @return null
     */
    public static function pi_de($encrypted = '') {
        if (!is_string($encrypted)) {
            return null;
        }
        return (openssl_private_decrypt(base64_decode($encrypted), $decrypted, self::getPrivateKey())) ? $decrypted : null;
    }

    /**
     * 公钥解密
     * @param string $encrypted
     * @return null
     */
    public static function pu_de($encrypted = '') {
        if (!is_string($encrypted)) {
            return null;
        }
        return (openssl_public_decrypt(base64_decode($encrypted), $decrypted, self::getPublicKey())) ? $decrypted : null;
    }
}