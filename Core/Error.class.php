<?php
namespace Aren\Core;

use Exception;

class Error extends Exception
{

    /**
     * 类型
     * @var string
     */
    private $type;

    /**
     * 状态码
     * @var int
     */
    private $statusCode;

    /**
     * 错误跟踪
     * @var array
     */
    private $errorTrace;

    /**
     * 构造函数
     * @param string $message
     * @param string $type
     * @param int $statusCode
     * @param array $trace
     */
    public function __construct($message, $type, $statusCode = 500, $trace = array())
    {
        parent::__construct($message);
        $this->type = $type;
        $this->statusCode = $statusCode;
        $this->errorTrace = array_merge($this->getTrace(), $trace);
    }

    /**
     * 显示错误html
     * @return string
     */
    public function display()
    {
        $string = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>错误提示</title><style>*{font-family: Ubuntu,Consolas,"Microsoft YaHei",sans-serif}</style></head><body>';
        $string .= '<h1 style="color:#E32529;margin-top:.6em">[' . $this->type . ']&nbsp;&nbsp;' . $this->getMessage() . '</h1>';
        $string .= '<hr/><ul style="list-style:none;padding-left:0;line-height:2.2em">';
        foreach (array_reverse($this->errorTrace) as $k => $trace) {
            $string .= '<li>&nbsp;#' . ($k + 1) . '&nbsp;&nbsp;';
            if (!empty($trace['class'])) {
                $str = $trace['class'] . $trace['type'] . $trace['function'] . '()';
                $string .= $str;
            } elseif (!empty($trace['function'])) {
                $string .= $trace['function'] . '()';
            }
            if (!empty($trace['file'])) {
                $string .= '&nbsp;&nbsp;@&nbsp;&nbsp;' . $trace['file'] . ':' . $trace['line'];
            }
            $string .= '</li>';
        }
        $string .= '</ul><hr/>';
        $string .= '<p>Url:&nbsp;' . Request::$requestUrl . '&nbsp;&nbsp;;&nbsp;&nbsp;User-Agent:&nbsp;' . Request::$agent . '&nbsp;&nbsp;;&nbsp;&nbsp;IP:&nbsp;' . Request::$ip . '</p><hr/>';
        $bench = self::bench();
        $string .= '<p>Excuted:&nbsp;' . $bench['time'] . '&nbsp;ms&nbsp;;&nbsp;&nbsp;Memory:&nbsp;' .
            $bench['memory'] . '&nbsp;KB</p></body></html>';
        return $string;
    }

    /**
     * 获取状态码
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * 获取性能数据
     * @return array
     */
    public static function bench()
    {
        $time = microtime(true) - TIME_START;
        $memory = memory_get_usage() - MEMORY_START;
        return array(
            'time' => round($time * 1000, 2),
            'memory' => round($memory / 1024, 2)
        );
    }
}