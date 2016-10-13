<?php

namespace Aren\Core;

class Config
{
    private static $config = [];

    public static function get($key = true) {
        if ($key === true) {
            return self::$config;
        }
        if(strpos($key, '.') !== false){
            $keyArr = explode('.', $key);
            return self::$config[$keyArr[0]][$keyArr[1]];
        }
        return self::$config[$key];
    }

    public static function load($file) {
        if (!file_exists($file)) {
            Core::error('配置文件丢失：'.$file, 'CORE');
        }
        $config = Core::import($file, true) ;
        foreach ($config as $k => $v) {
            self::$config[$k] = $v;
        }
    }
}
