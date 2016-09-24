<?php
/**
 * Created by PhpStorm.
 * User: Osacar
 * Date: 2016-08-20
 * Time: 1:21
 */

namespace Aren\Core;


class Security
{
    /**
     * 加密
     *
     * @param string $string 字符
     * @param string $key
     * @param int|string $expiry
     * @return string
     */
    public static function encode($string, $key = '', $expiry = 0) {
        return self::authcode($string, 'ENCODE', $key, $expiry);
    }

    /**
     * 解密
     *
     * @param string $string 字符
     * @param string $key
     * @param int|string $expiry
     * @return string
     */
    public static function decode($string, $key = '', $expiry = 0) {
        return self::authcode($string, 'DECODE', $key, $expiry);
    }

    /**
     * 密码和解密
     *
     * @param string $string 字符
     * @param string $operation 加密(ENCODE)或解密(DECODE),
     * @param string $key
     * @param int|string $expiry
     * @return string
     */
    public static function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
        $ckey_length = 4;
        $key = md5($key ? : Config::get('AUTH_KEY'));
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';
        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);
        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
        $string_length = strlen($string);
        $result = '';
        $box = range(0, 255);
        $rndkey = [];
        for ($i = 0; $i <= 255; $i++)
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result.=chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        if ($operation == 'DECODE') {
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16))
                return substr($result, 26);
            else
                return '';
        } else
            return $keyc . str_replace('=', '', base64_encode($result));
    }
}