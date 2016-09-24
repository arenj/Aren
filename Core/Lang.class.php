<?php
/**
 * Created by PhpStorm.
 * User: Osacar
 * Date: 2016-08-13
 * Time: 3:18
 */
namespace Aren\Core;
/**
 * 语言包
 * @author
 */
class Lang
{

    // 语言数据

    private static $lang = array();


    /**
     * 获取语言定义(不区分大小写)
     * @access public
     * @param string|null $name 语言变量
     * @param array $vars 变量替换
     * @return mixed
     */
    public static function get($name, $vars = array())
    {
        $key = strtolower($name);
        $value = self::$lang[$key] ? self::$lang[$key] : $name;
        if (!empty($vars) && is_array($vars)) {
            $value = str_replace(array_keys($vars), $vars, $value);
        }
        return $value;
    }

    /**
     * 返回所有语言
     * @access public
     * @return array
     */
    public static function getAll()
    {
        return self::$lang;
    }

    /**
     * 设置语言定义(不区分大小写)
     * @access public
     * @param string|array $name 语言变量
     * @param string $value 语言值
     * @return mixed
     * @internal param string $range 语言作用域
     */
    public static function set($name, $value = null)
    {
        if (is_array($name)) {
            self::$lang = array_merge(self::$lang, array_change_key_case($name, CASE_LOWER));
        } else {
            return self::$lang[strtolower($name)] = $value;
        }
    }

    /**
     * 获取语言定义(不区分大小写)
     * @access public
     * @param string|null $name 语言变量
     * @return mixed
     * @internal param array $vars 变量替换
     */
    public static function has($name)
    {
        return isset(self::$lang[strtolower($name)]);
    }

    /**
     * 加载语言定义(不区分大小写)
     * @access public
     * @param string $file 语言文件
     * @return mixed
     */
    public static function load($file)
    {
        $lang = array();
        foreach ((array)$file as $_file) {
            $_lang = Core::import($_file, true) ?: [];
            if (!empty($_lang) && is_array($_lang)) {
                self::$lang = array_merge(self::$lang, array_change_key_case($_lang, CASE_LOWER));
            }
        }
        if (!empty($lang)) {
            self::$lang = $lang + self::$lang;
        }
        return self::$lang;
    }

    /**
     * 自动侦测设置获取语言选择
     * @access public
     * @return string
     */
    public static function detect()
    {
        static $lang = '';
        if ($lang)
            return $lang;

        if (isset($_GET[Config::get('LANG')])) {
            $lang = strtolower($_GET[Config::get('LANG')]);
            Cookie::set(Config::get('LANG'), $lang, 3600);
        } elseif (Cookie::get(Config::get('LANG'))) {
            $lang = strtolower(Cookie::get(Config::get('LANG')));
        } elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            preg_match('/^([a-z\d\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
            $lang = strtolower($matches[1]);
            Cookie::set(Config::get('LANG'), $lang, 3600);
        }
        $cLang = Config::get('LANGS');
        if (!empty($cLang) && !in_array($lang, explode(',', Config::get('LANGS')))) {
            $lang = 'zh-cn';
        }
        return $lang;
    }

}
