<?php
namespace Aren\Core;

class Cookie
{

    private static $config;

    /**
     * 获取COOKIE
     * @access public
     * @param string|null $name 名称 为空时返回所有
     * @return mixed|null
     */
    public static function get($name)
    {
        if ($_COOKIE[$name]) {
            $value = $_COOKIE[$name];
        } else {
            $value = '';
        }
        if (0 === strpos($value, 'aren:')) {
            $value = substr($value, 5);
            return array_map('urldecode', json_decode($value, true));
        } else {
            return $value;
        }
    }

    /**
     * 设置 Cookie
     * @access public
     * @param string $name
     * @param mixed $value
     * @param array $option
     */
    public static function set($name, $value = '', $option = [])
    {
        $expire = !empty(self::$config['expire']) ? time() + intval(self::$config['expire']) : 0;
        if (is_array($value))
            $value = 'aren:' . json_encode(array_map('urlencode', $value));
        self::edit($name, $value, $expire, $option);
        $_COOKIE[$name] = $value;
    }

    /**
     * 删除 Cookie
     * @access public
     * @param string $name
     * @param array $option
     */
    public static function rm($name, $option = [])
    {
        self::edit($name, '', time() - 3600, $option);
        unset($_COOKIE[$name]);
    }

    /**
     * 清除所有 Cookie
     * @access public
     * @access public
     * @param array $option
     * @return bool
     */
    public static function clear($option = [])
    {
        if (empty($_COOKIE))
            return null;
        foreach ($_COOKIE as $key => $val) {
            self::edit($key, '', time() - 3600, $option);
            unset($_COOKIE[$key]);
        }
        return null;
    }

    /**
     * 设置
     * @access private
     * @param $name
     * @param string $value
     * @param int $expire
     * @param array $option
     */
    private static function edit($name, $value = "", $expire = 0, $option = [])
    {
        self::init($option);
        setcookie($name, $value, $expire, self::$config['path'], self::$config['domain'], self::$config['secure'], self::$config['httponly']);
    }

    /**
     * 初始化配制
     * @access private
     * @param array $option
     */
    private static function init($option = [])
    {
        if (empty(self::$config)) {
            self::$config = [
                'expire' => $option['expire'] ? $option['expire'] : Config::get('COOKIE')['EXPIRE'],
                'path' => $option['path'] ? $option['path'] : Config::get('COOKIE')['PATH'],
                'domain' => $option['domain'] ? $option['domain'] : Config::get('COOKIE')['DOMAIN'],
                'secure' => $option['secure'] ? $option['secure'] : Config::get('COOKIE')['SECURE'],
                'httponly' => $option['httponly'] ? $option['httponly'] : Config::get('COOKIE')['HTTPONLY']
            ];
        }
    }

}
