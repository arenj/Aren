<?php
namespace Aren\Driver;
/**
 * Created by JetBrains PhpStorm.
 * User: Administrator
 * Date: 13-3-5
 * Time: 上午10:11
 * To change this template use File | Settings | File Templates.
 */
class FileCache
{
    protected $dir;

    public function __construct()
    {
        $this->dir = DATA_DIR . 'Cache'.DS;
    }

    public function __destruct()
    {

    }

    /**
     * 文件缓存-获取缓存
     * 获取缓存文件，分离出缓存开始时间和缓存时间
     * 返回分离后的缓存数据，解序列化
     * @param $key
     * @internal param string $filename 缓存名
     * @return array
     */
    public function get($key)
    {
        if (is_array($key)) {
            # 支持多取
            $data = array();
            foreach ($key as $k => $v) {
                $data[$k] = $this->get((string)$v);
            }
            return $data;
        }

        $filename = $this->get_filename_by_key($key, $this->dir);
        /* 缓存不存在的情况 */
        if (!file_exists($filename)) return false;
        $data = unserialize(file_get_contents($filename)); //获取缓存
        /* 缓存过期的情况 */
        if ($data['ttl'] > 0) {
            if (time() > $data['time'] + $data['ttl']) {
                @unlink($filename);
                return false;
            }
        }
        return $data['data'];
    }


    /**
     * 文件缓存-设置缓存
     * 设置缓存名称，数据，和缓存时间
     * @param $key
     * @param array $data 缓存数据
     * @param int| $ttl 缓存时间，单位：分钟，默认10分钟, -1表示永不过期
     * @return bool
     * @internal param string $filename 缓存名
     */
    public function set($key, $data, $ttl = 10)
    {
        $ttl = $ttl * 60;
        $contents = array(
            'time' => time(),
            'ttl' => $ttl,
            'data' => $data
        );
        $filename = $this->get_filename_by_key($key, $this->dir);
        @file_put_contents($filename, serialize($contents));
        clearstatcache();
        return true;
    }


    /**
     * 文件缓存-清除缓存
     * 删除缓存文件
     * @param $key
     * @internal param string $filename 缓存名
     * @return array
     */
    public function del($key)
    {
        if (is_array($key)) {
            # 支持多取
            $i = 0;
            foreach ($key as $k => $v) {
                if ($this->del((string)$v)) {
                    $i++;
                }
            }
            return $i == count($key) ? true : false;
        }
        $filename = $this->get_filename_by_key($key, $this->dir);
        if (!file_exists($filename)) return true;
        @unlink($filename);
        return true;
    }


    /**
     * 文件缓存-清除全部缓存
     * 删除整个缓存文件夹文件，一般情况下不建议使用
     * @internal param string $filename 缓存名
     * @return array
     */
    public function clear()
    {
        @set_time_limit(3600);
        $path = opendir($this->dir);
        while (false !== ($filename = readdir($path))) {
            if ($filename !== '.' && $filename !== '..') {
                @unlink($this->dir . $filename);
            }
        }
        closedir($path);
        return true;
    }


    /**
     * 根据KEY获取文件路径
     *
     * @param string $key
     * @param $basedir
     * @return string
     */
    protected function get_filename_by_key($key, $basedir)
    {
        return $basedir . 'cache_file_' . substr(preg_replace('#[^a-z0-9_\-]*#i', '', $key), 0, 100) . '_' . md5($key . '_&@c)ac%he_file');
    }
}