<?php
/**
 * Created by PhpStorm.
 * User: Osacar
 * Date: 2016-08-19
 * Time: 18:41
 */

namespace Aren\Core;


use Exception;

class Controller
{

    /**
     * 视图文件夹地址
     * @var string
     */
    protected $path;

    /**
     * 当前访问的控制器名
     * @var string
     */
    protected $className;
    /**
     * 视图数据
     * @var array
     */
    protected $data;

    /**
     * 模板的输出内容
     * @var
     */
    protected $output;

    /**
     * 数据调用模型
     * @var mixed
     */
    protected $model;

    /**
     * 缓存类
     * @var Cache
     */
    protected $cache;

    /**
     * 当前登录用户
     * @var
     */
    protected $loginUser;


    /**
     * 构造方法
     * @param array $data
     * @internal param string $path
     * @internal param int $compile
     */
    public function __construct($data = array())
    {
        $this->path = ROOT_PATH . 'Project' . DS . Core::getApp() . DS . 'View' . DS;
        $this->data = $data;
        $class = get_class($this);
        //exit($class);
        if (false !== ($pos = strrpos($class, '\\'))) {
            $this->className = substr(substr($class, $pos + 1), 0, -10);
            $this->model = $this->model($this->className);
        } else {
            $this->className = 'Index';
        }
        $this->cache = new Cache();
    }

    /**
     * @param null $name
     * @return mixed
     * @throws exception
     */
    protected function model($name = null)
    {
        static $loadedClass = [];
        if (null === $name) {
            return $this->model;
        }
        $class = 'Project\\' . Core::getApp() . '\\Model\\' . ucfirst($name) . 'Model';
        if (class_exists($class)) {
            if (isset($loadedClass[$class])) {
                return $loadedClass[$class];
            }
            $loadedClass[$class] = new $class();
            return new $loadedClass[$class];
        }
        return false;
        //throw new exception("Can't load model '$class'");
    }

    /**
     * 添加数据
     * @param string|array $name
     * @param mixed $value
     * @return $this
     */
    protected function assign($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $k => $v) {
                $this->data[$k] = $v;
            }
            return $this;
        }
        $this->data[$name] = $value;
        return $this;
    }


    /**
     * 渲染文件
     * @param string $template
     * @param string $dir
     * @return string
     */
    protected function render($template = '', $dir = '')
    {
        if ($template == '') {
            $template = strtolower($this->className);
        }
        if ($dir == '') {
            $file = $this->path . $template . '.tpl.php';
        } else {
            $file = $this->path . $dir . DS . $template . '.tpl.php';
        }
        if (!file_exists($file)) {
            Core::error('模板文件不存，无法渲染文件"' . $dir . '/' . $template . '.tpl.php"', 'VIEW');
        }
        $currentPWD = getcwd();
        chdir(dirname($file));
        ob_start();
        extract($this->data);
        include $file;
        $this->output .= ob_get_contents();
        ob_get_clean();
        chdir($currentPWD);
        return $this->output;
    }

    /**
     * @param string $template
     * @param string $dir
     */
    protected function display($template = '', $dir = '')
    {
        echo $this->render($template, $dir);
        //exit();
    }

    /**
     * @param $msg
     */
    protected function displayMsg($msg)
    {
        // TODO 设置默认模板
        echo $msg;
        exit;
    }

    protected function redirect($url)
    {
        header("location: $url");
        exit;
    }

    /**
     * @param $controller
     * @param string $action
     * @param array $param
     * @param string $ext
     * @return string
     */
    protected function url($controller, $action = 'index', $param = array(), $ext = '')
    {
        return Request::url($controller, $action, $param, $ext);
    }

    /**
     * @param null $name
     * @param array $vars
     * @return mixed
     */
    protected function lang($name = null, $vars = array())
    {
        return Lang::get($name, $vars);
    }

    /**
     * @param $msg
     * @param int $status
     * @param array $data
     */
    protected function ajaxReturn($msg, $status = 0, $data = array())
    {
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode(array(
            'status' => $status,
            'info' => $msg,
            'data' => $data
        )));
    }

    /**
     * @param $data
     * @param string $fun
     */
    protected function jsonp($data, $fun = '')
    {
        header('Content-Type:application/json; charset=utf-8');
        if (empty($fun)) {
            $fun = Request::get('jsoncallback');
        }
        die($fun . '(' . json_encode($data) . ');');
    }

    /**
     * @param $message
     * @param array $data
     */
    protected function error($message, $data = array())
    {
        $this->ajaxReturn($message, 0, $data);
    }

    /**
     * @param $message
     * @param array $data
     */
    protected function success($message, $data = array())
    {
        $this->ajaxReturn($message, 1, $data);
    }

    protected function buildToken($uid = '')
    {
        $key = md5(randString(16, 4, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ').time());
        $token = time() . '|' . Request::$ip . '|' . Request::$agent.'|'.$uid;
        $_SESSION['__token__' . $key] = base64_encode($token);
        return $key;
    }

    protected function checkToken($key, $uid = '', $clear = false)
    {
        if (!$key) return false;
        if (isset($_SESSION['__token__' . $key])) {
            //验证数据
            list($time, $ip, $agent, $userid) = explode('|', base64_decode($_SESSION['__token__' . $key]));
            if ((time() - $time) > Config::get('TOKEN_LIFE_TIME') || Request::$ip != $ip || Request::$agent != $agent || $userid != $uid) return false;
            // 验证完成立即销毁session
            if ($clear) {
                self::clearToken($key);
            }
            return true;
        } else {
            return false;
        }
    }

    protected function clearToken($key)
    {
        if (isset($_SESSION['__token__' . $key])) {
            unset($_SESSION['__token__' . $key]);
        }
    }

    protected function html_out($type, $init = '')
    {
        $html = '';
        static $fields = null;
        if (is_null($fields)) {
            $fields = $this->cache->get('fields');
        }
        if (empty($fields[$type])) return $html;
        $data = json_decode($fields[$type]->dval, true);
        switch ($type) {
            case 'input':
                break;
            case 'select':
                foreach ($data as $key => $val) {
                    $selected = $key == $init ? 'selected="selected"' : '';
                    $html .= '<option value="' . $key . '" ' . $selected . '>' . $val . '</option>';
                }
                break;
            case 'radio':
                foreach ($data as $key => $val) {
                    $checked = $key == $init ? 'checked="checked"' : '';
                    $html .= '<label class="form-label"><input name="' . $fields[$type]->domid . '" class="checkbox-radio" value="' . $key . '" type="radio" ' . $checked . '> ' . $val . '</label>';
                }
                break;
            case 'checkbox':
                foreach ($data as $key => $val) {
                    $checked = $key == $init ? 'checked="checked"' : '';
                    $html .= '<label class="form-label"><input name="' . $fields[$type]->domid . '" class="checkbox-radio" value="' . $key . '" type="checkbox" ' . $checked . '> ' . $val . '</label>';
                }
                break;
            case 'textarea':
                break;
        }
        return $html;
    }

    protected function checkCaptcha($code)
    {
        if ( isset($_SESSION['securimage_code_value']) && !empty($_SESSION['securimage_code_value']) ) {
            if ( $_SESSION['securimage_code_value'] == strtolower(trim($code)) ) {
                $_SESSION['securimage_code_value'] = '';
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

}