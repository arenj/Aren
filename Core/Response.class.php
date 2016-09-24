<?php
namespace Aren\Core;

class Response
{

    public function __construct()
    {

    }

    /**
     * 状态码
     * @var int
     */
    private static $statusCode = 200;

    /**
     * 设置状态码
     * @param int $code
     */
    public static function setStatus($code)
    {
        self::$statusCode = (int)$code;
    }

    /**
     * 获得状态码
     * @return int
     */
    public static function getStatus()
    {
        return self::$statusCode;
    }

    /**
     * 获取状态码描述
     * @param int $status
     * @return string
     */
    public static function getStatusText($status)
    {
        return 'HTTP/1.1 ' . $status . ' ' . ucwords(self::$statusTexts[$status]);
    }

    /**
     * 头信息
     * @var array
     */
    private static $header = array();

    /**
     * 设置头信息
     */
    public static function setHeader()
    {
        #数组方式添加
        if (func_num_args() === 1) {
            if (is_array(func_get_arg(0))) {
                self::$header += func_get_arg(0);
            }
            return;
        }
        #key/value方式添加
        if (func_num_args() === 2) {
            self::$header[func_get_arg(0)] = func_get_arg(1);
            return;
        }
    }

    /**
     * 清空头信息
     */
    public static function clearHeader()
    {
        self::$header = array();
    }

    /**
     * 返回文本
     * @var string
     */
    private static $body;

    /**
     * 设置返回文本
     * @param string $body
     */
    public static function setResBody($body)
    {
        self::$body = $body;
    }

    /**
     * 获取返回文本
     * @return string
     */
    public static function getResBody()
    {
        return self::$body;
    }

    /**
     * 设置缓存头信息
     * @param int $expire
     */
    public static function setCache($expire = 0)
    {
        if ($expire <= 0) {
            self::$header['Cache-Control'] = 'no-cache, no-store, max-age=0, must-revalidate';
            self::$header['Expires'] = 'Mon, 26 Jul 1997 05:00:00 GMT';
            self::$header['Pragma'] = 'no-cache';
        } else {
            self::$header['Last-Modified'] = gmdate('r', time());
            self::$header['Expires'] = gmdate('r', time() + $expire);
            self::$header['Cache-Control'] = 'max-age=' . $expire;
            unset(self::$header['Pragma']);
        }
    }

    /**
     * 状态码说明
     * @var array
     */
    protected static $statusTexts = array(
        '100' => 'Continue',
        '101' => 'Switching Protocols',
        '200' => 'OK',
        '201' => 'Created',
        '202' => 'Accepted',
        '203' => 'Non-Authoritative Information',
        '204' => 'No Content',
        '205' => 'Reset Content',
        '206' => 'Partial Content',
        '300' => 'Multiple Choices',
        '301' => 'Moved Permanently',
        '302' => 'Found',
        '303' => 'See Other',
        '304' => 'Not Modified',
        '305' => 'Use Proxy',
        '306' => '(Unused)',
        '307' => 'Temporary Redirect',
        '400' => 'Bad Request',
        '401' => 'Unauthorized',
        '402' => 'Payment Required',
        '403' => 'Forbidden',
        '404' => 'Not Found',
        '405' => 'Method Not Allowed',
        '406' => 'Not Acceptable',
        '407' => 'Proxy Authentication Required',
        '408' => 'Request Timeout',
        '409' => 'Conflict',
        '410' => 'Gone',
        '411' => 'Length Required',
        '412' => 'Precondition Failed',
        '413' => 'Request Entity Too Large',
        '414' => 'Request-URI Too Long',
        '415' => 'Unsupported Media Type',
        '416' => 'Requested Range Not Satisfiable',
        '417' => 'Expectation Failed',
        '500' => 'Internal Server Error',
        '501' => 'Not Implemented',
        '502' => 'Bad Gateway',
        '503' => 'Service Unavailable',
        '504' => 'Gateway Timeout',
        '505' => 'HTTP Version Not Supported',
    );

    //------------------------

    /**
     * 是否已经发送
     * @var bool
     */
    private static $isSend = false;

    /**
     * 发送头信息
     * @return bool
     */
    public static function sendResponse()
    {
        if (self::$isSend) {
            return false;
        }
        if (isset(self::$header['Content-Type']) && !self::$header['Content-Type']) {
            self::$header['Content-Type'] = 'text/html;charset=UTF-8';
        }
        if (isset(self::$header['Cache-Control']) && !self::$header['Cache-Control']) {
            self::setCache(0);
        }
        self::$header['X-Powered-By'] = 'ARENMVC';
        header('HTTP/1.1 ' . self::$statusCode . ' ' . ucwords(self::$statusTexts[self::$statusCode]));
        header('Status: ' . self::$statusCode . ' ' . ucwords(self::$statusTexts[self::$statusCode]));
        #头信息
        foreach (self::$header as $key => $value) {
            header($key . ': ' . $value);
        }
        #输出内容
        if (self::$body) {
            echo self::$body;
        }
        self::$isSend = true;
        return true;
    }

    /**
     * 重定向
     * @param string $url
     * @param int $code
     */
    public static function sendRedirect($url, $code = 302)
    {
        self::$header['Location'] = $url;
        self::$statusCode = $code;
        self::sendResponse();
        exit;
    }

    /**
     * 返回错误
     * @param int $code
     * @param string $message
     */
    public static function sendError($code, $message = '')
    {
        self::$statusCode = $code;
        if (!$message) {
            $message = 'HTTP/1.1 ' . self::$statusCode . ' ' . ucwords(self::$statusTexts[self::$statusCode]);
        }
        self::$body = $message;
        self::sendResponse();
    }

}