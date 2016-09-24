<?php

function HConvert($var)
{
    if (is_array($var)) {
        foreach ($var as $key => $value) {
            $var[$key] = HConvert($value);
        }
    } else {
        $var = trim($var);
        do {
            $clean = $var;
            $var = preg_replace('~&(?!(#[0-9]+|[a-z]+);)~is', '&amp;', $var);
            $var = preg_replace(array('~%0[0-8]~', '~%1[124-9]~', '~%2[0-9]~', '~%3[0-1]~', '~[\x00-\x08\x0b\x0c\x0e-\x1f]~'), '', $var);
        } while ($clean != $var);

        $var = str_replace(array('"', '\'', '<', '>', "\t", "\r"), array('&quot;', '&#39;', '&lt;', '&gt;', '', ''), $var);
    }
    return $var;
}

/**
 * 产生随机字串，可用来自动生成密码 默认长度6位 字母和数字混合
 * @param int|string $len 长度
 * @param string $type 字串类型
 * 0 字母 1 数字 其它 混合
 * @param string $addChars 额外字符
 * @return string
 */
function randString($len = 6, $type = '', $addChars = '')
{
    switch ($type) {
        case 0:
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
            break;
        case 1:
            $chars = str_repeat('0123456789', 3);
            break;
        case 2:
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
            break;
        case 3:
            $chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
            break;
        case 4:
            $chars = 'abcdefghijklmnopqrstuvwxyz0123456789' . $addChars;
            break;
        default :
            // 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数 23456789
            $chars = 'ABaCDv2EfFbGHcI3JKMNqeP4wQRSTUV5mWXYZ6dg7hijkn8prs9tuxyz' . $addChars;
            break;
    }
    if ($len > 10) { //位数过长重复字符串一定次数
        $chars = $type == 1 ? str_repeat($chars, $len) : str_repeat($chars, 5);
    }
    $chars = str_shuffle($chars);
    $str = substr($chars, 0, $len);
    return $str;
}

function ip2longx($ip)
{
    return bindec(decbin(ip2long($ip)));
}

function is_email($user_email)
{
    $chars = "/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,6}\$/i";
    if (strpos($user_email, '@') !== false && strpos($user_email, '.') !== false) {
        if (preg_match($chars, $user_email)) {
            return strtolower($user_email);
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function toInt($num)
{
    return (int)$num;
}

function toDec($num, $w = 2)
{
    $num = trim($num);
    if (is_numeric($num)) {
        return sprintf("%." . $w . "f", $num);
    } else {
        return 0.00;
    }
}

function toPhone($phone)
{
    if (!preg_match("/^[1-9]\d{2}-\d{3}-\d{4}$/", $phone)) {
        return '';
    }
    return $phone;
}

function html_out($xxx)
{
    return '';
}