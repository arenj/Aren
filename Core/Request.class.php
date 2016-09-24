<?php
/**
 * Created by PhpStorm.
 * User: Osacar
 * Date: 2016-08-19
 * Time: 18:12
 */

namespace Aren\Core;


class Request
{

    public function __construct()
    {

    }

    /**
     * IP地址
     * @var string
     */
    public static $ip;

    /**
     * 客户端标识
     * @var string
     */
    public static $agent;

    /**
     * 请求方式
     * @var string
     */
    public static $method;

    /**
     * Restful请求方式
     * @var string
     */
    public static $restMethod;

    /**
     * 是否是Ajax请求
     * @var bool
     */
    public static $isAjax;

    /**
     * 顶级地址
     * @var string
     */
    public static $baseUrl;

    /**
     * 请求地址
     * @var string
     */
    public static $requestUrl;

    /**
     * 上一次访问地址
     * @var string
     */
    public static $refer;

    /**
     * 主机域名
     * @var string
     */
    public static $host;
    /**
     * 是否SSL访问
     * @var bool
     */
    public static $ssl;
    /**
     * 初始化请求数据
     */
    public static function init()
    {
        self::$method = strtoupper($_SERVER['REQUEST_METHOD']);
        self::$agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        self::$refer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        self::$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        self::$baseUrl = 'http://' . self::$host . '/';
        self::$requestUrl = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        self::$isAjax = strtoupper(isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? $_SERVER['HTTP_X_REQUESTED_WITH'] : '') == 'XMLHTTPREQUEST';
        self::$restMethod = isset($_SERVER['HTTP_REST_METHOD']) ? strtoupper($_SERVER['HTTP_REST_METHOD']) : self::$method;
        self::$ip = self::getIp();
        self::$ssl = (isset($_SERVER['HTTPS']) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower($_SERVER['HTTPS']))) || (isset($_SERVER['REQUEST_SCHEME']) && 'https' == $_SERVER['REQUEST_SCHEME']) || (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && 'https' == $_SERVER['HTTP_X_FORWARDED_PROTO']) ? true : false;

    }

    /**
     * 根据参数生成URL地址
     * @param $controller
     * @param string $action
     * @param array $param
     * @param string $ext
     * @return string
     */
    public static function url($controller, $action = 'index', $param = array(), $ext = '')
    {
        $url = '';
        $app = Config::get('APP');
        $now_app = strtolower(Core::getApp());
        if(isset($app[$now_app]['router']['controller'][$controller])){
            $controller = $app[$now_app]['router']['controller'][$controller].'/';
        }
        if(isset($app[$now_app]['router']['action'][$action])){
            $action = $app[$now_app]['router']['action'][$action];
        }
        $param = (array)$param;
        if($action == 'index' && empty($param)){
            $url .= $controller. '/';
        }else{
            $url .= $controller. '/'.$action .'/'.implode('/', $param);
        }
        if($now_app != 'frontend'){
            $url = $now_app.'/'.$url;
        }
        if($ext != '') $url .= '.'.$ext;
        return Config::get('WEB_URL').$url;
    }
    /**
     * 获取IP
     * @return string
     */
    private static function getIp()
    {
        if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
            $ip = getenv("HTTP_CLIENT_IP");
        else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
            $ip = getenv("REMOTE_ADDR");
        else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
            $ip = $_SERVER['REMOTE_ADDR'];
        else
            $ip = "unknown";
        return $ip;
    }

    /**
     * 获取HTTP头
     * @param string $name
     * @return mixed
     */
    public static function getHttp($name)
    {
        return $_SERVER[strtoupper('HTTP_' . $name)];
    }

    /**
     * 获取Server头
     * @param string $name
     * @return mixed
     */
    public static function getServer($name)
    {
        return $_SERVER[strtoupper($name)];
    }

    /**
     * 获取环境变量
     * @param string $name
     * @return string
     */
    public static function getEnv($name)
    {
        return getenv($name);
    }

    /**
     * 检测是否使用手机访问
     * @access public
     * @return bool
     */
    public function isMobile()
    {
        return (isset($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'], "wap")) || (strpos(strtoupper($_SERVER['HTTP_ACCEPT']), "VND.WAP.WML")) || (isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE'])) || (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $_SERVER['HTTP_USER_AGENT'])) ? true : false;
    }

    /**
     * 判断是否是通过微信访问
     *
     * @access public
     * @return boolean
     */
    public static function isWeixin()
    {
        return strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ? true : false;
    }

    /**
     * 判断是否POST请求
     * @return bool
     */
    public static function isPost(){
        return self::$method == 'POST' ? true : false;
    }
    /**
     * GET数据
     * @param bool $key
     * @param null $filter
     * @return array|string
     */
    public static function get($key = true, $filter = NULL)
    {
        if ($key === true) {
            return self::varFilter($_GET, $filter);
        }
        return self::varFilter($_GET[$key], $filter);
    }

    /**
     * POST数据
     * @param bool|string $key
     * @param null $filter
     * @return array|string
     */
    public static function post($key = true, $filter = NULL)
    {
        if ($key === true) {
            return self::varFilter($_POST, $filter);
        }
        return self::varFilter($_POST[$key], $filter);
    }

    /**
     * 获取POST
     *
     * @param string|NULL $key 名称 为空时返回所有
     * @param string $filter 安全过滤方法
     * @access public
     * @return mixed
     */
    public static function put($key = null, $filter = NULL)
    {
        static $_put = null;
        if (is_null($_put)) {
            parse_str(file_get_contents('php://input'), $_put);
        }
        return $_put;
    }

    /**
     * 获取POST
     *
     * @param string|NULL $key 名称 为空时返回所有
     * @param string $filter 安全过滤方法
     * @access public
     * @return mixed
     */
    public static function delete($key = null, $filter = NULL)
    {
        static $_delete = null;
        if (is_null($_delete)) {
            parse_str(file_get_contents('php://input'), $_delete);
            $_delete = array_merge($_delete, $_GET);
        }
        return $_delete;
    }

    /**
     * 所有请求数据
     * @return array
     */
    public static function all()
    {
        return $_REQUEST;
    }

    /**
     * 判断是否有某个数据
     * @param string $key
     * @return bool
     */
    public static function has($key)
    {
        return isset($_REQUEST[$key]);
    }

    /**
     * 参数过滤方法
     *
     * @param string|array $content 过滤内容
     * @param string $filter 过滤方法
     * @access public
     * @return mixed
     */
    public static function varFilter($content, $filter = NULL)
    {
        $filter = $filter ? $filter : Config::get('FILTER');
        if (is_array($content)) {
            return array_map(function ($content) {
                return self::varFilter($content);
            }, $content);
        } else {
            return $filter($content);
        }
    }


}