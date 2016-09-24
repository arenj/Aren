<?php

/**
 * Created by PhpStorm.
 * User: Osacar
 * Date: 2016-08-19
 * Time: 16:05
 */
namespace Aren\Core;

use Exception;
use ReflectionMethod;

class Core
{
    public static $URI = '';
    public static $params = array();

    /**
     * 应用名称
     * @var string
     */
    private static $app;

    public function __construct()
    {
        self::init();
    }

    public static function init()
    {
        //定义常量
        define("DS", DIRECTORY_SEPARATOR);
        define('ROOT_PATH', realpath('./') . DS); //网站根目录
        define('DATA_DIR', ROOT_PATH . 'Data' . DS); //数据目录
        define('UPLOAD_DIR', ROOT_PATH . 'upload' . DS); //文件上传目录
        define('TEMP_PATH', ROOT_PATH.'Temp'.DS); //临时目录
        if (!defined('IS_CLI')) define('IS_CLI', (PHP_SAPI === 'cli'));

        //声明编码 UTF8
        header("Content-Type: text/html; charset=UTF-8");

        //崩溃处理函数
        register_shutdown_function(function () {
            $error = error_get_last();
            if (!empty($error) && $error['type'] <= 128 && $error['type'] != E_WARNING && $error['type'] != E_NOTICE) {
                self::error($error['message'], 'RUNTIME', 500, array($error));
            }

        });
        //异常处理函数
        set_exception_handler(function (Exception $exc) {
            self::error($exc->getMessage(), strtoupper(get_class($exc)), 500, $exc->getTrace());
        });


        //设置自动加载
        self::initAutoload();
        //加载配置文件
        Config::load(DATA_DIR . 'Config' . DS . 'config.inc.php');
        //定义静态文件路径
        define('WEB_URL', Config::get('WEB_URL')); //网站根目录
        define('ASSETS_URL', WEB_URL.'Assets/');

        //设置错误报告
        if (Config::get('APP_DEBUG') === true) {
            ini_set("display_errors", 1);
            error_reporting(E_ALL ^ E_NOTICE);//除了notice提示，其他类型的错误都报告
        } else {
            ini_set("display_errors", 0);
            error_reporting(0);//把错误报告，全部屏蔽
        }

        //设置时区
        date_default_timezone_set(Config::get('TIME_ZONE') ? Config::get('TIME_ZONE') : 'PRC');

        //解析URL
        self::$URI = self::getURI();

        //根据URL设置当前所在APP
        self::setApp(self::$URI);

        //Session初始化
        self::initSession();
        //设置请求项
        Request::init();
    }

    public static function getInstance()
    {
        static $instance = NULL;
        if ($instance === NULL) {
            $instance = new Core();
        }
        return $instance;
    }

    public static function bootstrap()
    {
        static::getInstance();
        ob_start();
        $path = explode('/', trim(self::$URI, '/'));
        $controller = !empty($path[0]) ? $path[0] : 'index';
        $action = !empty($path[1]) ? $path[1] : 'index';
        //默认参数部分
        $paramStart = 2;
        //检查自定义短路由
        if (isset(Config::get('APP')[self::getApp()]['router']['controller'])) {
            $z = array_search($controller, Config::get('APP')[self::getApp()]['router']['controller']);
            if ($z) {
                $controller = $z;
                if (isset(Config::get('APP')[self::getApp()]['router']['action'][$z])) {
                    $b = array_search($action, Config::get('APP')[self::getApp()]['router']['action'][$z]);
                    if ($b)
                        $action = $b;
                }
            }

        }
        $className = $controller . 'Controller';
        $controllerName = $controller;
        $controller = '\Project\\' . self::getApp() . '\Controller\\' . ucfirst($controller) . 'Controller';
        if (!class_exists($controller)) {
            self::error('Project:[' . self::getApp() . ']中的' . $controller . '控制器不存在!', 'RUNTIME');
        }
        $methodName = $action . 'Action';

        define('CONTROLLER_NAME', $controllerName);
        define('ACTION_NAME', $action);

        $module = new $controller();
        if (!method_exists($module, $methodName) || !preg_match('/^[A-Za-z](\/|\w)*$/', $action)) {
            if (!method_exists($module, 'emptyAction')) {
                self::error('Project:[' . self::getApp() . "]中的{$controller}不存在[{$action}]操作方法", 'RUNTIME');
            }
            $methodName = 'emptyAction';
        }

        $defaultParams = array();
        $methodReflect = new reflectionMethod($controller, $methodName);
        if (!$methodReflect->isPublic() || $methodReflect->isStatic()) {
            self::error("只允许执行控制器中的公共方法", 'RUNTIME');
        }

        //获取默认参数
        foreach ($methodReflect->getParameters() as $param) {
            $name = $param->getName();
            $default = '';
            if (isset($paramDefaultValue[$className][$methodName][$name])) {
                $default = $paramDefaultValue[$className][$methodName][$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $default = $param->getDefaultValue();
            }
            $defaultParams[$name] = $default;
        }
        $params = array();
        $itemCount = count($path);
        for ($i = $paramStart; $i < $itemCount; $i++) {
            $key = key($defaultParams);     // Get key from the $defaultParams.
            $params[$key] = $path[$i];
            next($defaultParams);
        }
        //判断参数个数是否合法,以防没有默认参数时提示错误
        if (count($methodReflect->getParameters()) != count($params)) {
            self::error("参数传递错误", 'RUNTIME');
        }
        //加载基本函数方法
        self::import(ROOT_PATH.'Aren'.DS.'Function'.DS.'common.function.php');
        //设置语言包
        !defined('APP_LANG') && define('APP_LANG', Config::get('LANG_SWITCH') ? Lang::detect() : 'zh-cn');
        Lang::load(ROOT_PATH . 'Project' . DS . self::getApp() . DS . 'Lang' . DS . APP_LANG . '.php');
        //TODO 加载控制器定义方法

        //TODO 执行前置方法

        call_user_func_array(array(&$module, $methodName), $params);

        //TODO 执行后置方法

        //TODO 开启debug则显示

    }


    public static function getURI($prefixSlash = TRUE)
    {
        if (isset($_SERVER['PATH_INFO'])) {
            $uri = $_SERVER['PATH_INFO'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];

            if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0) {
                $uri = substr($uri, strlen($_SERVER['SCRIPT_NAME']));
            } elseif (strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0) {
                $uri = substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
            }

            if (strncmp($uri, '?/', 2) === 0) $uri = substr($uri, 2);

            $parts = preg_split('#\?#i', $uri, 2);
            $uri = $parts[0];

            if (isset($parts[1])) {
                $_SERVER['QUERY_STRING'] = $parts[1];
                parse_str($_SERVER['QUERY_STRING'], $_GET);
            } else {
                $_SERVER['QUERY_STRING'] = '';
                $_GET = array();
            }
            $uri = parse_url($uri, PHP_URL_PATH);
        } else {
            return FALSE;
        }

        $URIString = ($prefixSlash ? '/' : '') . str_replace(array('//', '../'), '/', trim($uri, '/'));
        $format = pathinfo($URIString, PATHINFO_EXTENSION);

        return str_replace('.' . $format, '', $URIString);
    }

    public static function setApp($uri)
    {
        $uri = strtolower(trim($uri, '/'));
        if ($uri == '') {
            //默认的访问路径
            self::$app = 'Frontend';
        } else {
            $path = explode('/', $uri);
            if (array_key_exists($path[0], Config::get('APP'))) {
                self::$app = ucfirst($path[0]);
                //去掉APP前缀
                array_shift($path);
                if (empty($path)) {
                    self::$URI = '/';
                } else {
                    self::$URI = '/' . implode('/', $path);
                }
            } else {
                self::$app = 'Frontend';
            }
        }
    }

    public static function getApp()
    {
        return self::$app;
    }

    public static function import($fileName, $return = false)
    {
        if (is_file($fileName)) {
            if ($return) {
                return include $fileName;
            }
            require_once $fileName;
            return true;
        }
        return false;
    }

    public static function error($message, $class, $code = 500, $trace = array())
    {
        $error = new Error($message, $class, $code, $trace);
        Response::setResBody($error->display());
        Response::setStatus($code);
        Response::sendResponse();
        exit();
    }


    private static function initAutoload()
    {
        spl_autoload_register(function ($class) {
            static $fileList = array();
            $prefixes = array(
                'Aren' => ROOT_PATH . 'Aren',
                'Project' => ROOT_PATH . 'Project',
                '*' => ROOT_PATH,
            );

            $class = ltrim($class, '\\');
            if (false !== ($pos = strrpos($class, '\\'))) {
                $namespace = substr($class, 0, $pos);
                $className = substr($class, $pos + 1);

                foreach ($prefixes as $prefix => $baseDir) {
                    if ('*' !== $prefix && 0 !== strpos($namespace, $prefix)) continue;
                    //file path case-insensitive
                    $fileDIR = $baseDir . str_replace('\\', DS, $namespace) . DS;
                    if (!isset($fileList[$fileDIR])) {
                        $fileList[$fileDIR] = array();
                        foreach (glob($fileDIR . '*.php') as $file) {
                            $fileList[$fileDIR][] = $file;
                        }
                    }

                    $fileBase = $baseDir . str_replace('\\', DS, $namespace) . DS . $className;
                    foreach ($fileList[$fileDIR] as $file) {
                        if (false !== stripos($file, $fileBase)) {
                            require $file;
                            return true;
                        }
                    }
                }
            }
            return false;
        });
    }

    private static function initSession() {
        //TODO 增加存储驱动
        session_start();
    }

}