<?php
/**
 * Created by PhpStorm.
 * User: Osacar
 * Date: 2016-08-20
 * Time: 14:32
 */

namespace Aren\Core;

class File
{

    /**
     * 检测文件是否存在
     * @access public
     * @param string $file
     * @return boolean
     */
    public static function has($file)
    {
        return is_file($file) ? true : false;
    }

    /**
     * 创建目录
     * @access public
     * @param string $path 路径
     * @param int|string $mode 权限
     * @return string 如果已经存在则返回YES，否则为flase
     */
    public static function mkDir($path, $mode = 0777)
    {
        if (is_dir($path))
            return true;
        else {
            $_path = dirname($path);
            if ($_path !== $path)
                self::mkDir($_path, $mode);
            return @mkdir($path, $mode);
        }
    }

    /**
     * 向一个文件写入数据
     * @access private
     * @param string $file 写入的文件
     * @param string $data 写入的数据
     * @param int $flags
     * @return int
     */
    private static function _write($file, $data, $flags = LOCK_EX)
    {
        return file_put_contents($file, $data, $flags);
        //@chmod($file, 0777);
    }

    /**
     * 向一个文件写入数据 在目录/文件不存在的时候，自动创建
     * @access public
     * @param string $file 写入的文件
     * @param string $data 写入的数据
     * @param bool|int $flags FILE_APPEND 追加数据而不是覆盖  LOCK_EX 在写入时获得一个独占锁
     * @return int
     */
    public static function write($file, $data, $flags = LOCK_EX)
    {
        !is_dir(dirname($file)) && self::mkDir(dirname($file));
        return self::_write($file, $data, $flags);
    }

    /**
     * 向一个文件写入数据 在目录/文件不存在的时候，自动创建
     * @access public
     * @param string $file 写入的文件
     * @param string $data 写入的数据
     * @param int|string $flags FILE_APPEND 追加数据而不是覆盖  LOCK_EX 在写入时获得一个独占锁
     */
    public static function set($file, $data, $flags = LOCK_EX)
    {
        self::write($file, $data, $flags);
    }

    /**
     * 读取文件内容
     * @access public
     * @param string $file 文件地址，绝对路径
     * @param int|number $offset 起始位置
     * @param number $len 读取长度，默认读取所有
     * @return string
     */
    public static function read($file, $offset = 0, $len = null)
    {
        return self::has($file) ? ($len ? file_get_contents($file, true, null, $offset, $len) : file_get_contents($file, true, null, $offset)) : null;
    }

    /**
     * 读取文件内容
     * @access public
     * @param string $file 文件地址，绝对路径
     * @param int|number $offset 起始位置
     * @param number $len 读取长度，默认读取所有
     * @return string
     */
    public static function get($file, $offset = 0, $len = null)
    {
        return self::read($file, $offset, $len);
    }

    /**
     * 删除一个文件
     * @access public
     * @param string $file 文件名，绝对地址
     */
    public static function delete($file)
    {
        self::remove($file);
    }

    /**
     * 删除一个文件
     * @access public
     * @param string $file 文件名，绝对地址
     */
    public static function remove($file)
    {
        strpos($file, '..') === FALSE && self::has($file) && unlink($file);
    }

    public static function move($source, $dest)
    {
        if (@copy($source, $dest) || self::writeFile($dest, self::readFile($source), 'wb')) {
            self::remove($source);
            return true;
        }
        return false;
    }

    public static function writeFile($filename, $content, $mode = 'ab', $chmod = 1)
    {
        strpos($filename, '..') !== FALSE && exit('Access Denied!');

        $fp = @fopen($filename, $mode);
        if ($fp) {
            flock($fp, LOCK_EX);
            fwrite($fp, $content);
            fclose($fp);
            $chmod && @chmod($filename, 0666);
            return TRUE;
        }
        return FALSE;
    }

    public static function readFile($filename, $mode = 'rb')
    {
        strpos($filename, '..') !== FALSE && exit('Access Denied!');
        if ($fp = @ fopen($filename, $mode)) {
            flock($fp, LOCK_SH);
            $filedata = @ fread($fp, filesize($filename));
            fclose($fp);
            return $filedata;
        }
        return null;
    }

    /**
     * 清空目录
     * @access public
     * @param string $dirName
     */
    public static function emptyDir($dirName)
    {
        if (is_dir($dirName)) {
            $handle = opendir($dirName);
            if ($handle) {
                while (false !== ($item = readdir($handle))) {
                    if ($item != "." && $item != "..")
                        is_dir($dirName . DS . $item) ? self::emptyDir($dirName . DS . $item) : self::delete($dirName . DS . $item);
                }
                closedir($handle);
                rmdir($dirName);
            }
        }
    }

    /**
     * 强制下载文件
     * @access public
     * @param string $filename
     */
    public function download($filename)
    {
        if (file_exists($filename)) {
            header("Content-length:" . filesize($filename));
            header('Content-Type:application/octet-stream');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            readfile($filename);
        }
    }

}
